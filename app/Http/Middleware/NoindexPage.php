<?php

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoindexPage
{
    public function handle(Request $request, Closure $next): Response
    {
        app(Seo::class)->noindex();

        $response = $next($request);

        // A cached checkout contains a CSRF token tied to an older session.
        // Always fetch a fresh form when a customer opens or revisits checkout.
        if ($request->routeIs('checkout.index')) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}
