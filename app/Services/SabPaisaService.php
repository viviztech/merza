<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SabPaisa PG 3.0 REST integration (POST /api/v2/payments — see
 * https://devdocs.sabpaisa.in/api-reference). Flow:
 *
 *  1. createPaymentSession() — server-to-server call that returns a
 *     checkoutUrl + clientSecret; redirect the customer there.
 *  2. Customer pays on SabPaisa's hosted page, then returns to our
 *     returnUrl with 8 signed query params — verifyReturnSignature().
 *  3. SabPaisa also calls our webhook independently for the same event —
 *     verifyWebhookSignature().
 *  4. Either path should treat enquiry() as the source of truth before
 *     marking an order paid — the redirect can be replayed/bookmarked.
 */
class SabPaisaService
{
    public function isConfigured(): bool
    {
        return filled(config('services.sabpaisa.api_key'))
            && filled(config('services.sabpaisa.secret_key'))
            && filled(config('services.sabpaisa.merchant_id'));
    }

    /**
     * Create a hosted payment session for an order. Returns null (and logs)
     * on any failure — the order itself is left untouched so staff can
     * still follow up manually, same as a failed manual-UPI checkout.
     */
    public function createPaymentSession(Order $order, string $returnUrl): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $merchantId    = config('services.sabpaisa.merchant_id');
        $merchantTxnId = $order->order_number;
        $amountPaise   = (int) round(((float) $order->total) * 100);
        $currency      = 'INR';
        $timestamp     = now()->timestamp;

        $payload = [
            'merchantId'    => $merchantId,
            'merchantTxnId' => $merchantTxnId,
            'amount'        => $amountPaise,
            'currency'      => $currency,
            'customerName'  => $order->customer_name,
            // Email remains optional for the shopper. Use the merchant inbox
            // when the hosted gateway requires a receipt address.
            'customerEmail' => $order->customer_email
                ?: (config('services.sabpaisa.fallback_email') ?: config('mail.from.address')),
            'customerPhone' => $this->normalisePhone($order->customer_phone),
            'returnUrl'     => $returnUrl,
            'timestamp'     => $timestamp,
            'checksum'      => $this->generateChecksum($merchantId, $merchantTxnId, $amountPaise, $currency, $timestamp),
            'description'   => "Merza order {$order->order_number}",
            'metadata'      => ['orderId' => (string) $order->id],
        ];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Api-Key'    => config('services.sabpaisa.api_key'),
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl() . '/api/v2/payments', $payload);
        } catch (\Throwable $e) {
            Log::error('SabPaisaService: createPaymentSession request failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        if ($response->failed() || ! $response->json('success')) {
            Log::error('SabPaisaService: createPaymentSession failed', [
                'order_id' => $order->id,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            return null;
        }

        return [
            'checkoutUrl'  => $response->json('checkoutUrl'),
            'clientSecret' => $response->json('clientSecret'),
            'paymentId'    => $response->json('paymentId'),
            'expiresAt'    => $response->json('expiresAt'),
        ];
    }

    /**
     * Authoritative status check — call this before trusting a return-URL
     * redirect or as a periodic reconciliation for orders stuck unpaid.
     */
    public function enquiry(string $merchantTxnId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Api-Key'     => config('services.sabpaisa.api_key'),
                    'X-Merchant-Id' => config('services.sabpaisa.merchant_id'),
                    'Content-Type'  => 'application/json',
                ])
                ->post($this->baseUrl() . '/api/v2/payments/enquiry', [
                    'clientCode'    => config('services.sabpaisa.merchant_id'),
                    'merchantTxnId' => $merchantTxnId,
                ]);
        } catch (\Throwable $e) {
            Log::error('SabPaisaService: enquiry request failed', [
                'merchant_txn_id' => $merchantTxnId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        if ($response->failed() || ! $response->json('success')) {
            Log::error('SabPaisaService: enquiry failed', [
                'merchant_txn_id' => $merchantTxnId,
                'status'          => $response->status(),
                'body'            => $response->body(),
            ]);
            return null;
        }

        return $response->json();
    }

    /**
     * message = merchantId|merchantTxnId|amount|currency|timestamp,
     * HMAC-SHA256 with the secret key, lowercase hex.
     */
    public function generateChecksum(string $merchantId, string $merchantTxnId, int $amountPaise, string $currency, int $timestamp): string
    {
        $message = "{$merchantId}|{$merchantTxnId}|{$amountPaise}|{$currency}|{$timestamp}";

        return hash_hmac('sha256', $message, (string) config('services.sabpaisa.secret_key'));
    }

    /**
     * Verify the 7-param + signature return-URL redirect. Sort the
     * non-signature params alphabetically by key, join as key=value with
     * "|", HMAC-SHA256 with the secret key, compare hex digests.
     */
    public function verifyReturnSignature(array $params): bool
    {
        $signature = $params['signature'] ?? null;

        if (empty($signature)) {
            return false;
        }

        $sorted = $params;
        unset($sorted['signature']);
        ksort($sorted);

        $dataString = collect($sorted)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode('|');

        $expected = hash_hmac('sha256', $dataString, (string) config('services.sabpaisa.secret_key'));

        return hash_equals($expected, (string) $signature);
    }

    /**
     * Verify the X-SabPaisa-Signature webhook header: "timestamp.base64sig",
     * signed payload is "timestamp.rawBody" (raw JSON, not re-serialized).
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        if (blank(config('services.sabpaisa.webhook_secret')) || empty($signatureHeader) || ! str_contains($signatureHeader, '.')) {
            return false;
        }

        [$timestamp, $signature] = explode('.', $signatureHeader, 2);

        if (! ctype_digit($timestamp) || abs((int) round(microtime(true) * 1000) - (int) $timestamp) > 300000) {
            return false;
        }

        $signedPayload = "{$timestamp}.{$rawBody}";
        $expected      = base64_encode(hash_hmac('sha256', $signedPayload, (string) config('services.sabpaisa.webhook_secret'), true));

        return hash_equals($expected, $signature);
    }

    public function enquiryConfirmsOrder(Order $order, ?array $result): bool
    {
        return $result !== null
            && ($result['success'] ?? false) === true
            && ($result['status'] ?? null) === 'SUCCESS'
            && ($result['merchantTxnId'] ?? null) === $order->order_number
            && ($result['currency'] ?? null) === 'INR'
            && is_numeric($result['amountPaise'] ?? null)
            && is_numeric($result['paidAmount'] ?? null)
            && (int) ($result['amountPaise'] ?? -1) === (int) round((float) $order->total * 100)
            && (int) round((float) ($result['paidAmount'] ?? -1) * 100) === (int) round((float) $order->total * 100);
    }

    public function webhookConfirmsOrder(Order $order, array $payload): bool
    {
        $expectedPaise = (int) round((float) $order->total * 100);

        return ($payload['event'] ?? null) === 'payment.success'
            && ($payload['status'] ?? null) === 'SUCCESS'
            && ($payload['merchant_txn_id'] ?? null) === $order->order_number
            && ($payload['currency'] ?? null) === 'INR'
            && is_numeric($payload['request_amount'] ?? null)
            && is_numeric($payload['paid_amount'] ?? null)
            && (int) round((float) ($payload['request_amount'] ?? -1) * 100) === $expectedPaise
            && (int) round((float) ($payload['paid_amount'] ?? -1) * 100) === $expectedPaise;
    }

    /**
     * SabPaisa expects a bare 10-digit Indian mobile number starting 6-9 —
     * strip everything else and drop any country code prefix.
     */
    private function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return substr($digits, -10);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.sabpaisa.base_url'), '/');
    }
}
