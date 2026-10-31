<?php

namespace App\Services;

use App\Exceptions\RazorpayPaymentException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Throwable;

class RazorpayService
{
    /**
     * Test and live keys use the same integration. The selected Razorpay key
     * controls the mode; no secret is ever returned from this service.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.razorpay.key_id'))
            && filled(config('services.razorpay.key_secret'));
    }

    public function webhookSecretIsConfigured(): bool
    {
        return filled(config('services.razorpay.webhook_secret'));
    }

    /**
     * Create (once) the Razorpay order backing a persisted Merza order and
     * return only the public Standard Checkout options.
     */
    public function checkoutOptions(Order $order): ?array
    {
        if (! $this->isConfigured() || $order->payment_status === 'paid') {
            return null;
        }

        $order->refresh();

        if (blank($order->razorpay_order_id)) {
            $amount = $this->amountInPaise($order);

            if ($amount < 100) {
                Log::warning('Razorpay order was not created for an invalid amount', [
                    'order_id' => $order->id,
                    'amount_paise' => $amount,
                ]);

                return null;
            }

            try {
                $gatewayOrder = $this->api()->order->create([
                    'amount' => $amount,
                    'currency' => 'INR',
                    // Razorpay limits receipts to 40 characters. Merza order
                    // numbers are short, but truncate defensively.
                    'receipt' => mb_substr($order->order_number, 0, 40),
                    'payment_capture' => 1,
                    'notes' => [
                        'merza_order_id' => (string) $order->id,
                        'merza_order_number' => $order->order_number,
                    ],
                ])->toArray();
            } catch (Throwable $exception) {
                Log::error('Razorpay order creation failed', [
                    'order_id' => $order->id,
                    'error' => $exception->getMessage(),
                ]);

                return null;
            }

            if (blank($gatewayOrder['id'] ?? null)
                || (int) ($gatewayOrder['amount'] ?? -1) !== $amount
                || ($gatewayOrder['currency'] ?? null) !== 'INR') {
                Log::error('Razorpay returned an invalid order payload', [
                    'order_id' => $order->id,
                ]);

                return null;
            }

            $gatewayOrderId = (string) $gatewayOrder['id'];

            DB::transaction(function () use ($order, $gatewayOrderId): void {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

                // A retry or a double-click may have won the race while the
                // gateway call was in flight. Keep the first local gateway
                // order; its amount came from the same persisted Merza order.
                if (filled($lockedOrder->razorpay_order_id)) {
                    return;
                }

                $lockedOrder->forceFill([
                    'razorpay_order_id' => $gatewayOrderId,
                    'razorpay_payment_status' => 'created',
                ])->save();
            });

            $order->refresh();
        }

        return $this->publicCheckoutOptions($order);
    }

    /**
     * Verify Checkout's signed response, fetch the payment directly from
     * Razorpay, and only then update the local payment state.
     *
     * @param  array{razorpay_order_id:string,razorpay_payment_id:string,razorpay_signature:string}  $attributes
     */
    public function verifyCheckoutPayment(array $attributes): Order
    {
        if (! $this->isConfigured()) {
            throw new RazorpayPaymentException('Online payment is not configured.');
        }

        $order = Order::query()
            ->where('razorpay_order_id', $attributes['razorpay_order_id'])
            ->first();

        if (! $order) {
            throw new RazorpayPaymentException('This payment does not match an order.');
        }

        try {
            $this->api()->utility->verifyPaymentSignature($attributes);
        } catch (Throwable $exception) {
            Log::warning('Razorpay checkout signature verification failed', [
                'order_id' => $order->id,
            ]);

            throw new RazorpayPaymentException('We could not verify this payment.');
        }

        if ($order->payment_status === 'paid') {
            if ($order->razorpay_payment_id === $attributes['razorpay_payment_id']) {
                return $order;
            }

            throw new RazorpayPaymentException('This order has already been paid.');
        }

        $payment = $this->fetchPayment($attributes['razorpay_payment_id'], $order->id);

        if (! $this->paymentMatchesOrder($order, $payment)) {
            Log::warning('Razorpay payment did not match the local order', [
                'order_id' => $order->id,
                'razorpay_payment_id' => $attributes['razorpay_payment_id'],
            ]);

            throw new RazorpayPaymentException('The payment amount or order details could not be confirmed.');
        }

        return $this->recordPayment($order, $payment);
    }

    /**
     * Re-check an already known payment when rendering the confirmation page.
     * Webhook processing remains the durable fallback if this network call is
     * unavailable.
     */
    public function refreshPaymentStatus(Order $order): Order
    {
        if (! $this->isConfigured()
            || $order->payment_status === 'paid'
            || blank($order->razorpay_payment_id)) {
            return $order;
        }

        try {
            $payment = $this->fetchPayment($order->razorpay_payment_id, $order->id);
        } catch (RazorpayPaymentException) {
            return $order;
        }

        if (! $this->paymentMatchesOrder($order, $payment)) {
            Log::warning('Razorpay payment refresh did not match the local order', ['order_id' => $order->id]);

            return $order;
        }

        return $this->recordPayment($order, $payment);
    }

    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        if (! $this->webhookSecretIsConfigured() || blank($signature)) {
            return false;
        }

        try {
            $this->api()->utility->verifyWebhookSignature(
                $payload,
                $signature,
                (string) config('services.razorpay.webhook_secret'),
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Persist an authenticated webhook payment. A null return means the
     * Razorpay order is not known locally; a mismatch raises an exception.
     */
    public function recordWebhookPayment(array $payment): ?Order
    {
        $gatewayOrderId = (string) ($payment['order_id'] ?? '');

        if ($gatewayOrderId === '') {
            throw new RazorpayPaymentException('Webhook has no Razorpay order ID.');
        }

        $order = Order::query()->where('razorpay_order_id', $gatewayOrderId)->first();

        if (! $order) {
            return null;
        }

        if (! $this->paymentMatchesOrder($order, $payment)) {
            throw new RazorpayPaymentException('Webhook payment does not match the local order.');
        }

        return $this->recordPayment($order, $payment);
    }

    /**
     * The only source of the amount is the persisted order. Values supplied
     * by Checkout or a webhook are compared against it, never copied into it.
     */
    public function paymentMatchesOrder(Order $order, array $payment): bool
    {
        return filled($payment['id'] ?? null)
            && ($payment['order_id'] ?? null) === $order->razorpay_order_id
            && ($payment['currency'] ?? null) === 'INR'
            && is_numeric($payment['amount'] ?? null)
            && (int) $payment['amount'] === $this->amountInPaise($order);
    }

    private function publicCheckoutOptions(Order $order): ?array
    {
        if (blank($order->razorpay_order_id)) {
            return null;
        }

        $prefill = array_filter([
            'name' => $order->customer_name,
            'email' => $order->customer_email,
            'contact' => $this->razorpayPhone($order->customer_phone),
        ]);

        return [
            'key' => (string) config('services.razorpay.key_id'),
            'order_id' => $order->razorpay_order_id,
            'name' => (string) config('app.name', 'Merza'),
            // Razorpay Checkout renders this public URL beside the merchant
            // name. Keep the image in public/images so the hosted popup can
            // retrieve it without exposing any private media.
            'image' => asset('images/logo.png'),
            'description' => "Merza order {$order->order_number}",
            'prefill' => $prefill,
            'notes' => ['merza_order_number' => $order->order_number],
            'theme' => ['color' => '#D97706'],
        ];
    }

    private function recordPayment(Order $order, array $payment): Order
    {
        $paymentId = (string) $payment['id'];
        $gatewayStatus = (string) ($payment['status'] ?? 'created');

        return DB::transaction(function () use ($order, $paymentId, $gatewayStatus): Order {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->payment_status === 'paid') {
                if ($lockedOrder->razorpay_payment_id === $paymentId) {
                    return $lockedOrder;
                }

                throw new RazorpayPaymentException('This order has already been paid.');
            }

            $paymentAlreadyUsed = Order::query()
                ->where('razorpay_payment_id', $paymentId)
                ->whereKeyNot($lockedOrder->id)
                ->lockForUpdate()
                ->exists();

            if ($paymentAlreadyUsed) {
                throw new RazorpayPaymentException('This payment has already been used for another order.');
            }

            $attributes = [
                'razorpay_payment_id' => $paymentId,
                'razorpay_payment_status' => $gatewayStatus,
            ];

            // Razorpay's fetched payment status is authoritative. A valid
            // checkout signature alone is not treated as payment completion.
            if ($gatewayStatus === 'captured') {
                $attributes += [
                    'payment_status' => 'paid',
                    'payment_reference' => $paymentId,
                    'payment_verified_at' => now(),
                ];
            }

            $lockedOrder->forceFill($attributes)->save();

            return $lockedOrder->fresh();
        });
    }

    private function fetchPayment(string $paymentId, int $orderId): array
    {
        try {
            return $this->api()->payment->fetch($paymentId)->toArray();
        } catch (Throwable $exception) {
            Log::error('Razorpay payment fetch failed', [
                'order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'error' => $exception->getMessage(),
            ]);

            throw new RazorpayPaymentException('We could not confirm this payment yet.');
        }
    }

    private function amountInPaise(Order $order): int
    {
        return (int) round(((float) $order->total) * 100);
    }

    private function razorpayPhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';
        $lastTenDigits = substr($digits, -10);

        return preg_match('/^[6-9]\d{9}$/', $lastTenDigits) === 1
            ? '91'.$lastTenDigits
            : null;
    }

    private function api(): Api
    {
        return new Api(
            (string) config('services.razorpay.key_id'),
            (string) config('services.razorpay.key_secret'),
        );
    }
}
