<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class CartService
{
    private const SESSION_KEY = 'merza_cart';

    public function all(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public function add(int $variantId, int $qty = 1): void
    {
        $variant = ProductVariant::with('product')->findOrFail($variantId);
        $cart    = $this->all();

        if (isset($cart[$variantId])) {
            $cart[$variantId]['qty'] = min($cart[$variantId]['qty'] + $qty, $variant->stock_qty);
        } else {
            $weightKg = $variant->weight_unit === 'g'
                ? ($variant->weight_value / 1000)
                : (float) $variant->weight_value;

            $cart[$variantId] = [
                'variant_id'      => $variantId,
                'product_id'      => $variant->product_id,
                'product_name'    => $variant->product->name,
                'variant_name'    => $variant->name,
                'free_gift_label' => $variant->free_gift_label,
                'sku'             => $variant->sku,
                'price'           => (float) $variant->price,
                'qty'             => $qty,
                'thumbnail_url'   => $variant->product->thumbnail_url,
                'weight_kg'       => $weightKg,
            ];
        }

        session([self::SESSION_KEY => $cart]);
        $this->syncCount();
    }

    public function update(int $variantId, int $qty): void
    {
        $cart = $this->all();

        if (!isset($cart[$variantId])) return;

        if ($qty <= 0) {
            $this->remove($variantId);
            return;
        }

        $cart[$variantId]['qty'] = $qty;
        session([self::SESSION_KEY => $cart]);
        $this->syncCount();
    }

    public function remove(int $variantId): void
    {
        $cart = $this->all();
        unset($cart[$variantId]);
        session([self::SESSION_KEY => $cart]);
        $this->syncCount();
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
        session(['cart_count' => 0]);
    }

    public function count(): int
    {
        return array_sum(array_column($this->all(), 'qty'));
    }

    public function subtotal(): float
    {
        return array_sum(array_map(
            fn($item) => $item['price'] * $item['qty'],
            $this->all()
        ));
    }

    public function totalWeightKg(): float
    {
        return array_sum(array_map(
            fn($item) => ($item['weight_kg'] ?? 0) * $item['qty'],
            $this->all()
        ));
    }

    public function items(): Collection
    {
        return collect($this->all())->map(fn($item) => (object) array_merge(
            $item,
            ['line_total' => $item['price'] * $item['qty']]
        ));
    }

    private function syncCount(): void
    {
        session(['cart_count' => $this->count()]);
    }
}
