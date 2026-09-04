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

        return $next($request);
    }
}
