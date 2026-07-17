<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class OrderPdfController extends Controller
{
    public function invoice(Order $order): Response
    {
        abort_if(! auth()->user()?->hasAnyRole(['Admin', 'Sales', 'Operations', 'Delivery']), 403);

        $order->load('items');

        $pdf = Pdf::loadView('pdf.invoice', compact('order'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Invoice-{$order->order_number}.pdf");
    }

    public function deliverySlip(Order $order): Response
    {
        abort_if(! auth()->user()?->hasAnyRole(['Admin', 'Sales', 'Operations', 'Delivery']), 403);

        $pdf = Pdf::loadView('pdf.delivery-slip', compact('order'))
            ->setPaper('a5', 'portrait');

        return $pdf->download("DeliveryChallan-{$order->order_number}.pdf");
    }

    public function confirmedDeliveryChallans(): Response
    {
        abort_if(! auth()->user()?->hasAnyRole(['Admin', 'Sales', 'Operations', 'Delivery']), 403);

        $orders = Order::where('status', 'confirmed')
            ->orderBy('created_at')
            ->get();

        if ($orders->isEmpty()) {
            return response('No confirmed orders to include.', 200);
        }

        $pdf = Pdf::loadView('pdf.delivery-slip-bulk', compact('orders'))
            ->setPaper('a5', 'portrait');

        return $pdf->download('DeliveryChallans-Confirmed-' . now()->format('Y-m-d') . '.pdf');
    }

    public function dailyReport(): Response
    {
        abort_if(! auth()->user()?->hasAnyRole(['Admin', 'Sales', 'Operations', 'Delivery']), 403);

        $date   = request('date', today()->toDateString());
        $orders = Order::with('items')
            ->whereDate('created_at', $date)
            ->orderBy('created_at')
            ->get();

        $pdf = Pdf::loadView('pdf.daily-report', compact('orders', 'date'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Orders-{$date}.pdf");
    }
}
