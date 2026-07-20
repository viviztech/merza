<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
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

    /**
     * Customer-facing invoice download. No login required — access is
     * controlled by the route's signed-URL middleware instead, since
     * checkout allows guest orders with no account to authenticate against.
     */
    public function customerInvoice(Order $order): Response
    {
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

    public function deliveryChallans(Request $request): Response
    {
        abort_if(! auth()->user()?->hasAnyRole(['Admin', 'Sales', 'Operations', 'Delivery']), 403);

        $status   = $request->query('status', 'confirmed');
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $query = Order::query()->orderBy('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            return response('No orders match the selected filters.', 200);
        }

        $pdf = Pdf::loadView('pdf.delivery-slip-bulk', compact('orders'))
            ->setPaper('a5', 'portrait');

        $label = $status === 'all' ? 'All' : ucfirst($status);

        return $pdf->download("DeliveryChallans-{$label}-" . now()->format('Y-m-d') . '.pdf');
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
