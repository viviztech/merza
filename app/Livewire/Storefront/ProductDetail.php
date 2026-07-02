<?php

namespace App\Livewire\Storefront;

use App\Models\Product;
use App\Services\CartService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class ProductDetail extends Component
{
    public Product $product;
    public int $selectedVariantId = 0;
    public int $qty = 1;
    public string $addedMessage = '';

    public function mount(string $slug): void
    {
        $this->product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'activeVariants'])
            ->firstOrFail();

        $first = $this->product->activeVariants->first();
        if ($first) {
            $this->selectedVariantId = $first->id;
        }
    }

    public function getTitle(): string
    {
        return "{$this->product->name} — Merza";
    }

    public function addToCart(): void
    {
        $this->validate([
            'selectedVariantId' => 'required|exists:product_variants,id',
            'qty'               => 'required|integer|min:1|max:100',
        ]);

        app(CartService::class)->add($this->selectedVariantId, $this->qty);

        $this->addedMessage = 'Added to cart!';
        $this->dispatch('cart-updated');

        // Auto-clear the message after 3s via JS
        $this->dispatch('flash-added');
    }

    public function render()
    {
        $selectedVariant = $this->product->activeVariants
            ->firstWhere('id', $this->selectedVariantId);

        return view('livewire.storefront.product-detail', [
            'selectedVariant' => $selectedVariant,
        ]);
    }
}
