<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->bearerToken()
            ?: $request->header('X-API-Key')
            ?: $request->header('x-api-key')
            ?: $request->query('api_key')
            ?: $request->query('key');

        if (! is_string($secret) || trim($secret) === '') {
            return response()->json([
                'message' => 'An API key is required. Pass via Authorization: Bearer <key>, X-API-Key header, or ?api_key=<key> query parameter.',
                'endpoints' => [
                    'GET /api/v1' => 'API Status & Key Information',
                    'GET /api/v1/products' => 'Product Catalog & Categories',
                    'GET /api/v1/categories' => 'Category List',
                ],
            ], 401);
        }

        $apiKey = ApiKey::query()
            ->where('key_hash', ApiKey::hashSecret(trim($secret)))
            ->whereNull('revoked_at')
            ->first();

        if (! $apiKey || ($apiKey->expires_at && $apiKey->expires_at->isPast())) {
            return response()->json(['message' => 'The API key is invalid, expired, or revoked.'], 401);
        }

        $apiKey->forceFill(['last_used_at' => now()])->saveQuietly();
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
