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

        if ($order->payment_status === 'paid') {
            session()->forget('pending_razorpay_order_id');
            app(\App\Services\CartService::class)->clear();
            \App\Livewire\Storefront\CheckoutForm::sendOrderConfirmationWhatsApp($order);
            \App\Jobs\SendOrderWebhookJob::dispatch($order->id, 'order.created')->afterCommit();
            session()->put("ga4_purchase_events.{$order->id}", \App\Support\EcommerceData::purchase($order));
        }

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
}
