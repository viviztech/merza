<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featured = Product::with(['activeVariants', 'media'])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->limit(4)
            ->get();

        return view('storefront.home', compact('featured'));
    }
}
