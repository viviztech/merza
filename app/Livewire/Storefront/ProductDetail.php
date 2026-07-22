<?php

namespace App\Livewire\Storefront;

use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\AnalyticsTracker;
use App\Services\CartService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.storefront')]
class ProductDetail extends Component
{
    use WithFileUploads;

    public Product $product;
    public int $selectedVariantId = 0;
    public int $qty = 1;
    public string $addedMessage = '';
    public int $addedCount = 0;

    public string $reviewName    = '';
    public int    $reviewRating  = 5;
    public string $reviewComment = '';
    public $reviewPhoto          = null;
    public bool   $reviewSubmitted = false;

    public function mount(string $slug): void
    {
        $this->product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'activeVariants', 'approvedReviews'])
            ->firstOrFail();

        $first = $this->product->activeVariants->first();
        if ($first) {
            $this->selectedVariantId = $first->id;
        }

        app(AnalyticsTracker::class)->track('product_view', $this->product->id);
    }

    #[Computed]
    public function viewedTodayCount(): int
    {
        return AnalyticsEvent::query()
            ->where('event_type', 'product_view')
            ->where('product_id', $this->product->id)
            ->whereDate('created_at', today())
            ->distinct('session_id')
            ->count('session_id');
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

        $cart = app(CartService::class);
        $cart->add($this->selectedVariantId, $this->qty);

        app(AnalyticsTracker::class)->track('add_to_cart', $this->product->id);

        $this->addedMessage = 'Added to cart!';
        $this->addedCount++;
        $this->dispatch('cart-updated', count: $cart->count());

        // Auto-clear the message after 3s via JS
        $this->dispatch('flash-added');
    }

    public function submitReview(): void
    {
        $this->validate([
            'reviewName'    => 'required|string|max:100',
            'reviewRating'  => 'required|integer|min:1|max:5',
            'reviewComment' => 'nullable|string|max:1000',
            'reviewPhoto'   => 'nullable|image|max:5120',
        ]);

        $photoPath = null;
        if ($this->reviewPhoto) {
            $disk      = config('media-library.disk_name', 'r2');
            $photoPath = $this->reviewPhoto->store('product-review-photos', $disk);
        }

        ProductReview::create([
            'product_id'    => $this->product->id,
            'customer_name' => $this->reviewName,
            'rating'        => $this->reviewRating,
            'comment'       => $this->reviewComment ?: null,
            'photo_path'    => $photoPath,
            'is_approved'   => false,
        ]);

        $this->reset('reviewName', 'reviewRating', 'reviewComment', 'reviewPhoto');
        $this->reviewRating     = 5;
        $this->reviewSubmitted  = true;
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
