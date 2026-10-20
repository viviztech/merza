<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendOrderWebhookJob;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function orders(Request $request): JsonResponse
    {
        if (! $this->hasAbility($request, 'orders:read')) {
            return response()->json(['message' => 'This API key does not have orders:read permission.'], 403);
        }

        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);

        $query = Order::query()->with('items');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->query('payment_status'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->query('channel'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $orders = $query->latest('id')->paginate($perPage);

        return response()->json($orders);
    }

    public function showOrder(Request $request, string|int $order): JsonResponse
    {
        if (! $this->hasAbility($request, 'orders:read')) {
            return response()->json(['message' => 'This API key does not have orders:read permission.'], 403);
        }

        $record = Order::query()
            ->with(['items', 'contact'])
            ->where('id', $order)
            ->orWhere('order_number', $order)
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json([
            'data' => $record,
        ]);
    }

    public function createOrder(Request $request): JsonResponse
    {
        if (! $this->hasAbility($request, 'orders:write')) {
            return response()->json(['message' => 'This API key does not have orders:write permission.'], 403);
        }

        $validated = $request->validate([
            'customer_name'    => ['required', 'string', 'max:255'],
            'customer_phone'   => ['required', 'string', 'max:30'],
            'customer_email'   => ['nullable', 'email', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'city'             => ['nullable', 'string', 'max:100'],
            'state'            => ['nullable', 'string', 'max:100'],
            'postcode'         => ['nullable', 'string', 'max:20'],
            'landmark'         => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'payment_method'   => ['nullable', 'string', 'in:cod,upi,bank_transfer,whatsapp'],
            'payment_status'   => ['nullable', 'string', 'in:unpaid,paid,refunded'],
            'delivery_fee'     => ['nullable', 'numeric', 'min:0'],
            'packaging_fee'    => ['nullable', 'numeric', 'min:0'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.sku'                => ['nullable', 'string', 'max:100'],
            'items.*.quantity'           => ['required', 'integer', 'min:1'],
            'items.*.unit_price'         => ['nullable', 'numeric', 'min:0'],
            'items.*.product_name'       => ['nullable', 'string', 'max:255'],
            'items.*.variant_name'       => ['nullable', 'string', 'max:255'],
        ]);

        $order = DB::transaction(function () use ($validated) {
            $customerData = [
                'customer_name'    => $validated['customer_name'],
                'customer_phone'   => $validated['customer_phone'],
                'customer_email'   => $validated['customer_email'] ?? null,
                'delivery_address' => $validated['delivery_address'],
                'city'             => $validated['city'] ?? null,
                'state'            => $validated['state'] ?? null,
                'postcode'         => $validated['postcode'] ?? null,
                'landmark'         => $validated['landmark'] ?? null,
            ];

            $orderData = [
                'channel'          => 'manual',
                'status'           => 'pending',
                'payment_method'   => $validated['payment_method'] ?? 'cod',
                'payment_status'   => $validated['payment_status'] ?? 'unpaid',
                'delivery_fee'     => (float) ($validated['delivery_fee'] ?? 0),
                'packaging_fee'    => (float) ($validated['packaging_fee'] ?? 0),
                'notes'            => $validated['notes'] ?? null,
                'subtotal'         => 0,
                'total'            => (float) ($validated['delivery_fee'] ?? 0) + (float) ($validated['packaging_fee'] ?? 0),
            ];

            $order = Order::create(array_merge($customerData, $orderData));

            $variantIds = collect($validated['items'])->pluck('product_variant_id')->filter()->all();
            $skus = collect($validated['items'])->pluck('sku')->filter()->all();

            $variants = ProductVariant::with('product')
                ->where(function ($query) use ($variantIds, $skus) {
                    if (! empty($variantIds)) {
                        $query->whereIn('id', $variantIds);
                    }
                    if (! empty($skus)) {
                        $query->orWhereIn('sku', $skus);
                    }
                })
                ->get();

            foreach ($validated['items'] as $itemData) {
                $variant = null;
                if (! empty($itemData['product_variant_id'])) {
                    $variant = $variants->firstWhere('id', (int) $itemData['product_variant_id']);
                } elseif (! empty($itemData['sku'])) {
                    $variant = $variants->firstWhere('sku', $itemData['sku']);
                }

                $qty = max(1, (int) $itemData['quantity']);
                $unitPrice = isset($itemData['unit_price'])
                    ? (float) $itemData['unit_price']
                    : ($variant ? (float) $variant->price : 0.0);

                $productName = $itemData['product_name'] ?? ($variant?->product?->name ?? 'Item');
                $variantName = $itemData['variant_name'] ?? ($variant?->name ?? 'Standard');
                $sku = $itemData['sku'] ?? ($variant?->sku ?? null);
                $gstRate = $variant ? (float) ($variant->product->gst_rate ?? 0) : 0.0;
                $lineSubtotal = $unitPrice * $qty;
                $gstAmount = OrderItem::gstIncludedIn($lineSubtotal, $gstRate);

                OrderItem::create([
                    'order_id'            => $order->id,
                    'product_variant_id'  => $variant?->id,
                    'product_name'        => $productName,
                    'variant_name'        => $variantName,
                    'free_gift_label'     => $variant?->free_gift_label,
                    'free_gift_weight_kg' => $variant?->free_gift_weight_kg,
                    'sku'                 => $sku,
                    'quantity'            => $qty,
                    'unit_price'          => $unitPrice,
                    'subtotal'            => $lineSubtotal,
                    'gst_rate'            => $gstRate,
                    'gst_amount'          => $gstAmount,
                ]);
            }

            $order->recalculateTotals();

            return $order;
        });

        SendOrderWebhookJob::dispatch($order->id, 'order.created')->afterCommit();

        return response()->json([
            'message' => 'Order created successfully.',
            'data'    => $order->fresh()->load('items'),
        ], 201);
    }

    public function updateOrder(Request $request, string|int $order): JsonResponse
    {
        if (! $this->hasAbility($request, 'orders:write')) {
            return response()->json(['message' => 'This API key does not have orders:write permission.'], 403);
        }

        $orderRecord = Order::query()
            ->where('id', $order)
            ->orWhere('order_number', $order)
            ->first();

        if (! $orderRecord) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $validated = $request->validate([
            'status'            => ['nullable', 'string', 'in:pending,confirmed,preparing,delivering,delivered,cancelled'],
            'payment_status'    => ['nullable', 'string', 'in:unpaid,paid,refunded'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'tracking_number'   => ['nullable', 'string', 'max:255'],
            'courier_name'      => ['nullable', 'string', 'max:255'],
            'tracking_url'      => ['nullable', 'string', 'max:500'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'admin_notes'       => ['nullable', 'string', 'max:2000'],
        ]);

        if (isset($validated['status']) && $validated['status'] !== $orderRecord->status) {
            if ($validated['status'] === 'confirmed' && is_null($orderRecord->confirmed_at)) {
                $validated['confirmed_at'] = now();
            }
            if ($validated['status'] === 'delivering' && is_null($orderRecord->dispatched_at)) {
                $validated['dispatched_at'] = now();
            }
            if ($validated['status'] === 'delivered' && is_null($orderRecord->delivered_at)) {
                $validated['delivered_at'] = now();
            }
        }

        $orderRecord->update($validated);

        return response()->json([
            'message' => 'Order updated successfully.',
            'data'    => $orderRecord->fresh()->load('items'),
        ]);
    }

    private function hasAbility(Request $request, string $ability): bool
    {
        return $request->attributes->get('api_key')?->allows($ability) ?? false;
    }
}
