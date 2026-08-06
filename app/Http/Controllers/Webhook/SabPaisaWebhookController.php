<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SabPaisaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SabPaisaWebhookController extends Controller
{
    /**
     * POST /webhook/sabpaisa — real-time payment.success / payment.failed /
     * payment.expired / payment.timeout notifications. Runs independently of
     * (and is the reliable backup to) PaymentReturnController's redirect
     * handling, so must verify its own signature and be safe to retry.
     */
    public function handle(Request $request, SabPaisaService $sabPaisa): Response
    {
        $rawBody   = $request->getContent();
        $signature = $request->header('X-SabPaisa-Signature');

        if (! $sabPaisa->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('SabPaisa webhook: signature verification failed');
            return response('Invalid signature', 400);
        }

        $payload       = $request->json()->all();
        $merchantTxnId = $payload['merchant_txn_id'] ?? null;
        $status        = $payload['status'] ?? null;

        if (empty($merchantTxnId)) {
            return response('Missing merchant_txn_id', 400);
        }

        $order = Order::where('order_number', $merchantTxnId)->first();

        if (! $order) {
            Log::warning('SabPaisa webhook: no matching order', ['merchant_txn_id' => $merchantTxnId]);
            return response('Order not found', 404);
        }

        // Idempotent by design — a redirect return may have already marked
        // this paid, and SabPaisa retries webhooks up to 10 times.
        if ($status === 'SUCCESS' && $order->payment_status !== 'paid') {
            $order->update([
                'payment_status'    => 'paid',
                'payment_reference' => $payload['txn_id'] ?? $order->payment_reference,
            ]);
        }

        return response('OK', 200);
    }
}
