<?php

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Layout;
use App\Support\Seo;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class ProductCatalogue extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'cat')]
    public string $categorySlug = '';

    #[Url(as: 'min')]
    public ?int $priceMin = null;

    #[Url(as: 'max')]
    public ?int $priceMax = null;

    #[Url(as: 'stock')]
    public bool $inStockOnly = false;

    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedCategorySlug(): void { $this->resetPage(); }
    public function updatedPriceMin(): void     { $this->resetPage(); }
    public function updatedPriceMax(): void     { $this->resetPage(); }
    public function updatedInStockOnly(): void  { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset('search', 'categorySlug', 'priceMin', 'priceMax', 'inStockOnly');
        $this->resetPage();
    }

    public function render()
    {
        app(Seo::class)->description('Shop Merza farm produce and fruit snacks. Compare current sizes and prices, then check delivery options for your area at checkout.');
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();

        $products = Product::where('is_active', true)
            ->with(['category', 'activeVariants', 'media'])
            ->withCount('activeVariants')
            ->when($this->search, fn($q) =>
                $q->where(fn ($search) => $search
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('short_description', 'like', "%{$this->search}%")))
            ->when($this->categorySlug, fn($q) =>
                $q->whereHas('category', fn($c) => $c->where('slug', $this->categorySlug)))
            ->when($this->priceMin, fn($q) =>
                $q->whereHas('activeVariants', fn($v) => $v->where('price', '>=', $this->priceMin)))
            ->when($this->priceMax, fn($q) =>
                $q->whereHas('activeVariants', fn($v) => $v->where('price', '<=', $this->priceMax)))
            ->when($this->inStockOnly, fn($q) =>
                $q->whereHas('activeVariants', fn($v) => $v->where('stock_qty', '>', 0)))
            ->orderBy('sort_order')
            ->paginate(12);

        return view('livewire.storefront.product-catalogue', [
            'categories' => $categories,
            'products'   => $products,
        ])->title('Shop Farm Produce & Fruit Snacks');
    }
}
