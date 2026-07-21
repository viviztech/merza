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
        $cart->update($variantId, $qty);
        $this->dispatch('cart-updated', count: $cart->count());
    }

    public function remove(int $variantId): void
    {
        $cart = app(CartService::class);
        $cart->remove($variantId);
        $this->dispatch('cart-updated', count: $cart->count());
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
