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
        $this->validateSelection();

        $cart = app(CartService::class);
        $cart->add($this->selectedVariantId, $this->qty);

        app(AnalyticsTracker::class)->track('add_to_cart', $this->product->id);

        $this->addedMessage = $this->product->is_preorder ? 'Pre-booking added!' : 'Added to cart!';
        $this->addedCount++;
        $this->dispatch('cart-updated', count: $cart->count());
        $this->dispatch('flash-added');
    }

    public function buyNow(): void
    {
        $this->validateSelection();

        $cart = app(CartService::class);
        // Buy Now is an express purchase and should not carry unrelated items
        // from an earlier browsing session into checkout.
        $cart->clear();
        $cart->add($this->selectedVariantId, $this->qty);
        app(AnalyticsTracker::class)->track('add_to_cart', $this->product->id);
        $this->dispatch('cart-updated', count: $cart->count());
        $this->redirectRoute('checkout.index', navigate: true);
    }

    private function validateSelection(): void
    {
        $this->validate([
            'selectedVariantId' => 'required|exists:product_variants,id,product_id,' . $this->product->id,
            'qty'               => 'required|integer|min:1|max:100',
        ]);
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
