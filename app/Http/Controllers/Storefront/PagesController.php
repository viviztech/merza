<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PagesController extends Controller
{
    public function about(): View
    {
        return view('storefront.pages.about');
    }

    public function blog(): View
    {
        return view('storefront.pages.blog');
    }

    public function wholesale(): View
    {
        return view('storefront.pages.wholesale');
    }

    public function careers(): View
    {
        return view('storefront.pages.careers');
    }

    public function privacy(): View
    {
        return view('storefront.pages.privacy');
    }

    public function terms(): View
    {
        return view('storefront.pages.terms');
    }

    public function faq(): View
    {
        return view('storefront.pages.faq');
    }
}
