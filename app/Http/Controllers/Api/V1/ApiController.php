<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function health(Request $request): JsonResponse
    {
        $apiKey = $request->attributes->get('api_key');

        return response()->json([
            'status' => 'ok',
            'application' => config('app.name'),
            'api_key' => [
                'name' => $apiKey->name,
                'prefix' => $apiKey->key_prefix,
            ],
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        if (! $this->hasAbility($request, 'catalog:read')) {
            return response()->json(['message' => 'This API key does not have catalog:read permission.'], 403);
        }

        $perPage = min(max((int) $request->integer('per_page', 50), 1), 100);

        $products = Product::query()
            ->with([
                'category:id,name,slug',
                'activeVariants:id,product_id,name,sku,price,weight_value,weight_unit,stock_qty,is_active,sort_order',
            ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json($products);
    }

    public function categories(Request $request): JsonResponse
    {
        if (! $this->hasAbility($request, 'catalog:read')) {
            return response()->json(['message' => 'This API key does not have catalog:read permission.'], 403);
        }

        return response()->json([
            'data' => Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'slug', 'description', 'icon']),
        ]);
    }

    private function hasAbility(Request $request, string $ability): bool
    {
        return $request->attributes->get('api_key')?->allows($ability) ?? false;
    }
}
