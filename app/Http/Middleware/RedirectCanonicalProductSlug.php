<?php

namespace App\Http\Middleware;

use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RedirectCanonicalProductSlug
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('slug');
        $canonical = Str::lower($slug);

        if ($slug !== $canonical && Product::query()->where('slug', $canonical)->where('is_active', true)->exists()) {
            $url = route('products.show', ['slug' => $canonical]);
            if ($request->getQueryString()) {
                $url .= '?'.$request->getQueryString();
            }

            return redirect()->to($url, 301);
        }

        return $next($request);
    }
}
