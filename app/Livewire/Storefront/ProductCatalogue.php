<?php

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
#[Title('Products — Merza')]
class ProductCatalogue extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'cat')]
    public string $categorySlug = '';

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedCategorySlug(): void { $this->resetPage(); }

    public function render()
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();

        $products = Product::where('is_active', true)
            ->with(['category', 'activeVariants'])
            ->withCount('activeVariants')
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('short_description', 'like', "%{$this->search}%"))
            ->when($this->categorySlug, fn($q) =>
                $q->whereHas('category', fn($c) => $c->where('slug', $this->categorySlug)))
            ->orderBy('sort_order')
            ->paginate(12);

        return view('livewire.storefront.product-catalogue', [
            'categories' => $categories,
            'products'   => $products,
        ]);
    }
}
