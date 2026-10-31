<?php

namespace App\Http\Controllers\Webhook;

use App\Exceptions\RazorpayPaymentException;
use App\Http\Controllers\Controller;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    /**
     * Razorpay retries this endpoint independently of the customer's browser.
     * Its raw body must be used for HMAC verification; re-encoding JSON would
     * alter the signed bytes.
     */
    public function handle(Request $request, RazorpayService $razorpay): Response
    {
        $rawBody = $request->getContent();

        if (! $razorpay->verifyWebhookSignature($rawBody, $request->header('X-Razorpay-Signature'))) {
            Log::warning('Razorpay webhook: signature verification failed');

            return response('Invalid signature', 400);
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return response('Invalid JSON payload', 400);
        }

        $event = $payload['event'] ?? null;

        if (! in_array($event, ['payment.authorized', 'payment.captured', 'payment.failed', 'order.paid'], true)) {
            // Acknowledge unrelated verified events so Razorpay does not
            // repeatedly deliver events this storefront does not consume.
            return response('Ignored', 200);
        }

        $payment = data_get($payload, 'payload.payment.entity');

        if (! is_array($payment)) {
            return response('Missing payment payload', 400);
        }

        $expectedStatus = match ($event) {
            'payment.authorized' => 'authorized',
            'payment.captured', 'order.paid' => 'captured',
            'payment.failed' => 'failed',
        };

        if (($payment['status'] ?? null) !== $expectedStatus) {
            Log::warning('Razorpay webhook: event and payment status differ', ['event' => $event]);

            return response('Payment status does not match event', 422);
        }

        try {
            $order = $razorpay->recordWebhookPayment($payment);
        } catch (RazorpayPaymentException $exception) {
            Log::warning('Razorpay webhook: payment did not match order', [
                'event' => $event,
                'message' => $exception->getMessage(),
            ]);

            return response('Payment details do not match order', 422);
        }

        if (! $order) {
            Log::warning('Razorpay webhook: no matching local order', [
                'razorpay_order_id' => $payment['order_id'] ?? null,
            ]);

            return response('Order not found', 404);
        }

        return response('OK', 200);
    }
}
