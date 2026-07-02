<?php

namespace App\Livewire\Storefront;

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
        app(CartService::class)->update($variantId, $qty);
        $this->dispatch('cart-updated');
    }

    public function remove(int $variantId): void
    {
        app(CartService::class)->remove($variantId);
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        $cart     = app(CartService::class);
        $items    = $cart->items();
        $subtotal = $cart->subtotal();

        $deliveryFee = $subtotal >= 150 ? 0 : 10;
        $total       = $subtotal + $deliveryFee;

        return view('livewire.storefront.cart-panel', compact('items', 'subtotal', 'deliveryFee', 'total'));
    }
}
