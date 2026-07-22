<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Testimonial;
use App\Services\AnalyticsTracker;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(AnalyticsTracker $tracker): View
    {
        $tracker->track('page_view');

        $featured = Product::with(['activeVariants', 'media'])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->limit(4)
            ->get();

        $todaysArrivals = Product::with(['activeVariants', 'media'])
            ->where('is_active', true)
            ->where('is_available_today', true)
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $testimonials = Testimonial::active()->limit(6)->get();
        $deliveryZones = DeliveryZone::active()->get();
        $ordersToday   = Order::whereDate('created_at', today())->count();

        return view('storefront.home', compact('featured', 'todaysArrivals', 'testimonials', 'deliveryZones', 'ordersToday'));
    }
}
