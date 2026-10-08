<?php

namespace App\Livewire\Storefront;

use App\Models\Product;
use App\Services\CartService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
#[Title('Cart — Merza')]
class CartPanel extends Component
{
    #[On('cart-updated')]
    public function refresh(): void {}

    public function updateQty(int $variantId, int $qty): void
    {
        $cart = app(CartService::class);
        $previous = $cart->all()[$variantId] ?? null;
        $cart->update($variantId, $qty);
        $this->trackChange($previous, $qty);
        $this->dispatch('cart-updated', count: $cart->count());
    }

    public function remove(int $variantId): void
    {
        $cart = app(CartService::class);
        $previous = $cart->all()[$variantId] ?? null;
        $cart->remove($variantId);
        $this->trackChange($previous, 0);
        $this->dispatch('cart-updated', count: $cart->count());
    }

    private function trackChange(?array $previous, int $newQuantity): void
    {
        if (! $previous || $newQuantity === (int) $previous['qty']) {
            return;
        }

        $difference = $newQuantity - (int) $previous['qty'];
        $quantity = abs($difference);
        $this->dispatch('gtm-ecommerce',
            eventName: $difference > 0 ? 'add_to_cart' : 'remove_from_cart',
            ecommerce: [
                'currency' => 'INR',
                'value' => round((float) $previous['price'] * $quantity, 2),
                'items' => [[
                    'item_id' => (string) ($previous['sku'] ?: $previous['variant_id']),
                    'item_name' => $previous['product_name'],
                    'item_variant' => $previous['variant_name'],
                    'price' => (float) $previous['price'],
                    'quantity' => $quantity,
                ]],
            ],
        );
    }

    public function render()
    {
        $cart     = app(CartService::class);
        $items    = $cart->items();
        $subtotal = $cart->subtotal();

        $suggestedProducts = $items->isEmpty()
            ? Product::with('activeVariants')
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->limit(4)
                ->get()
            : collect();

        return view('livewire.storefront.cart-panel', compact('items', 'subtotal', 'suggestedProducts'));
    }
}
