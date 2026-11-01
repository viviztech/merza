<?php

namespace App\Http\Controllers\Storefront;

use App\Exceptions\RazorpayPaymentException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MetaConversionsService;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class RazorpayPaymentController extends Controller
{
    /**
     * Standard Checkout posts only its signed gateway identifiers here. The
     * amount is intentionally absent: this action looks up the local order
     * and compares Razorpay's fetched payment against its server-side total.
     */
    public function verify(Request $request, RazorpayService $razorpay): JsonResponse
    {
        $attributes = $request->validate([
            'razorpay_order_id' => ['required', 'string', 'max:255'],
            'razorpay_payment_id' => ['required', 'string', 'max:255'],
            'razorpay_signature' => ['required', 'string', 'max:512'],
        ]);

        $pendingOrderId = session()->get('pending_razorpay_order_id');
        $completedOrderId = session()->get('completed_razorpay_order_id');
        $sessionOrderIds = array_filter([$pendingOrderId, $completedOrderId]);

        // Gateway identifiers alone do not grant access to another customer's
        // order, its signed confirmation URL, or the current session's cart.
        $belongsToSession = Order::query()
            ->whereIn('id', $sessionOrderIds)
            ->where('razorpay_order_id', $attributes['razorpay_order_id'])
            ->exists();
        if (! $belongsToSession) {
            return response()->json(['message' => 'This payment does not match your checkout session.'], 403);
        }

        try {
            $order = $razorpay->verifyCheckoutPayment($attributes);
        } catch (RazorpayPaymentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            Log::error('Razorpay checkout verification failed unexpectedly', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'We could not confirm the payment yet. Please wait a moment and try again.',
            ], 503);
        }

        $this->completeCheckoutSession($order);

        return response()->json([
            'status' => $order->payment_status === 'paid' ? 'SUCCESS' : 'PENDING',
            'redirect_url' => URL::temporarySignedRoute(
                'payment.razorpay.confirmation',
                now()->addMinutes(30),
                ['order' => $order->id],
            ),
        ]);
    }

    /**
     * A signed, guest-accessible landing page. It makes one additional status
     * lookup when a payment was only authorised; webhooks remain the reliable
     * path if the shopper closes this page or the lookup is temporarily down.
     */
    public function confirmation(
        Order $order,
        RazorpayService $razorpay,
        MetaConversionsService $meta,
    ): View {
        $order = $razorpay->refreshPaymentStatus($order)->fresh();
        $this->completeCheckoutSession($order);

        $status = $order->payment_status === 'paid'
            ? 'SUCCESS'
            : (($order->razorpay_payment_status === 'failed') ? 'FAILED' : 'PENDING');
        $metaPurchase = $order->payment_status === 'paid'
            && $order->meta_tracking_consent
            && $meta->consentGranted()
            ? $meta->purchaseBrowserEvent($order)
            : null;
        $ga4Purchase = $order->payment_status === 'paid'
            ? session()->pull("ga4_purchase_events.{$order->id}")
            : null;

        return view('storefront.checkout.confirmation', compact('order', 'status', 'metaPurchase', 'ga4Purchase'));
    }

    private function completeCheckoutSession(Order $order): void
    {
        if ($order->payment_status !== 'paid'
            || (string) session()->get('pending_razorpay_order_id') !== (string) $order->id) {
            return;
        }

        session()->forget('pending_razorpay_order_id');
        // Retain ownership for harmless browser retries after a lost response.
        session()->put('completed_razorpay_order_id', $order->id);
        app(\App\Services\CartService::class)->clear();
        session()->put("ga4_purchase_events.{$order->id}", \App\Support\EcommerceData::purchase($order));
    }
}
