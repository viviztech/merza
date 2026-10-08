<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Models\Category;
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
        $categories = Category::query()->where('is_active', true)
            ->whereHas('products', fn ($query) => $query->where('is_active', true))
            ->with(['products' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->with('media')])
            ->orderBy('sort_order')->get();

        return view('storefront.home', compact('featured', 'todaysArrivals', 'testimonials', 'deliveryZones', 'categories'));
    }
}
