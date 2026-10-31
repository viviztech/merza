<?php

namespace App\Http\Middleware;

use App\Services\OrderAttributionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureOrderAttribution
{
    public function handle(Request $request, Closure $next): Response
    {
        // Don't track static assets, webhooks, or filament admin polling
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $service = app(OrderAttributionService::class);
        $result = $service->capture($request);

        $response = $next($request);

        foreach ($result['cookies'] as $cookie) {
            $response->headers->setCookie($cookie);
        }

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $request->path();

        return str_starts_with($path, 'webhook/')
            || str_starts_with($path, 'admin/')
            || str_starts_with($path, 'livewire/message')
            || str_starts_with($path, 'livewire/upload')
            || str_ends_with($path, '.png')
            || str_ends_with($path, '.jpg')
            || str_ends_with($path, '.svg')
            || str_ends_with($path, '.css')
            || str_ends_with($path, '.js');
    }
}

