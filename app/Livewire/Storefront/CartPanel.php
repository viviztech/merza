<?php

namespace App\Livewire\Storefront;

use App\Models\Product;
use App\Services\CartService;
use App\Services\MetaConversionsService;
use App\Services\WebhookDispatcher;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
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

        if ($difference > 0) {
            $eventId = MetaConversionsService::eventId('AddToCart');
            $customData = [
                'content_name' => $previous['product_name'],
                'content_type' => 'product',
                'content_ids' => [(string) $previous['variant_id']],
                'currency' => 'INR',
                'value' => round((float) $previous['price'] * $quantity, 2),
                'contents' => [[
                    'id' => (string) $previous['variant_id'],
                    'quantity' => $quantity,
                    'item_price' => (float) $previous['price'],
                ]],
            ];
            app(MetaConversionsService::class)->trackFromRequest(
                'AddToCart',
                customData: $customData,
                eventId: $eventId,
                eventSourceUrl: route('cart.index'),
            );
            $this->dispatch('meta-event', eventName: 'AddToCart', eventId: $eventId, customData: $customData);
            app(WebhookDispatcher::class)->dispatchCartAdded();
        }
    }

    public function render()
    {
        $cart = app(CartService::class);
        $items = $cart->items();
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
