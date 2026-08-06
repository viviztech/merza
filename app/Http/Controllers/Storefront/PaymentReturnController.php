<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SabPaisaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class PaymentReturnController extends Controller
{
    /**
     * GET /payment/return — SabPaisa redirects the customer's browser here
     * after checkout (success, failure, timeout, or cancellation), with a
     * signed query string. The redirect itself is never trusted for marking
     * an order paid — only the Transaction Enquiry lookup below is.
     */
    public function handleReturn(Request $request, SabPaisaService $sabPaisa): RedirectResponse
    {
        $params = $request->only([
            'transaction_id', 'merchant_txn_id', 'status', 'amount',
            'paid_amount', 'payment_mode', 'timestamp', 'signature',
        ]);

        if (empty($params['merchant_txn_id']) || ! $sabPaisa->verifyReturnSignature($params)) {
            Log::warning('SabPaisa return: signature verification failed', $params);

            return redirect()->route('checkout.index')->with(
                'error',
                "We couldn't verify that payment redirect. If money was deducted, please contact us on WhatsApp with your order number."
            );
        }

        $order = Order::where('order_number', $params['merchant_txn_id'])->first();

        if (! $order) {
            abort(404);
        }

        $this->confirmIfPaid($order, $sabPaisa);

        $confirmationUrl = URL::temporarySignedRoute(
            'checkout.confirmation',
            now()->addMinutes(30),
            ['order' => $order->id, 'status' => $order->fresh()->payment_status === 'paid' ? 'SUCCESS' : ($params['status'] ?? 'UNKNOWN')]
        );

        return redirect($confirmationUrl);
    }

    /**
     * GET /checkout/confirmation/{order} — signed landing page shown after
     * the SabPaisa redirect (or reachable again later from the link SabPaisa
     * emails/SMS's the customer). No login required, same guest-access
     * pattern as the signed customer invoice route.
     */
    public function confirmation(Request $request, Order $order): View
    {
        $status = $request->query('status', $order->payment_status === 'paid' ? 'SUCCESS' : 'UNKNOWN');

        return view('storefront.checkout.confirmation', compact('order', 'status'));
    }

    /**
     * Authoritative status check via Transaction Enquiry. Idempotent —
     * a webhook delivery may have already marked the order paid.
     */
    private function confirmIfPaid(Order $order, SabPaisaService $sabPaisa): void
    {
        if ($order->payment_status === 'paid') {
            return;
        }

        $result = $sabPaisa->enquiry($order->order_number);

        if (($result['status'] ?? null) === 'SUCCESS') {
            $order->update([
                'payment_status'    => 'paid',
                'payment_reference' => $result['txnId'] ?? $order->payment_reference,
            ]);
        }
    }
}
