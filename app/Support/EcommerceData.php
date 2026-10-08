<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

class EcommerceData
{
    public static function product(Product $product, ProductVariant $variant, int $quantity = 1): array
    {
        return [
            'item_id' => (string) ($variant->sku ?: $variant->id),
            'item_name' => $product->name,
            'item_variant' => $variant->name,
            'item_category' => $product->category?->name,
            'price' => (float) $variant->price,
            'quantity' => $quantity,
        ];
    }

    public static function cart(CartService $cart): array
    {
        return [
            'currency' => 'INR',
            'value' => round($cart->subtotal(), 2),
            'items' => $cart->items()->map(fn ($item) => [
                'item_id' => (string) ($item->sku ?: $item->variant_id),
                'item_name' => $item->product_name,
                'item_variant' => $item->variant_name,
                'price' => (float) $item->price,
                'quantity' => (int) $item->qty,
            ])->values()->all(),
        ];
    }

    public static function purchase(Order $order): array
    {
        $order->loadMissing('items');

        return [
            'transaction_id' => $order->order_number,
            'currency' => 'INR',
            // GA4 value is the sum of item prices; shipping is sent separately.
            'value' => (float) $order->subtotal,
            'shipping' => (float) $order->delivery_fee + (float) $order->packaging_fee,
            'tax' => (float) $order->gst_total,
            'items' => $order->items->map(fn ($item) => [
                'item_id' => (string) ($item->sku ?: $item->product_variant_id),
                'item_name' => $item->product_name,
                'item_variant' => $item->variant_name,
                'price' => (float) $item->unit_price,
                'quantity' => (int) $item->quantity,
            ])->values()->all(),
        ];
    }
}
