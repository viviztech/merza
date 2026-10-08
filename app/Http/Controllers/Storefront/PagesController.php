<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\FaqData;
use App\Support\RecipeData;
use App\Support\Seo;
use App\Models\Product;
use App\Models\Category;
use Illuminate\View\View;

class PagesController extends Controller
{
    public function about(): View
    {
        $categories = Category::query()->where('is_active', true)
            ->whereHas('products', fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')->get();

        return view('storefront.pages.about', compact('categories'));
    }

    public function blog(): View
    {
        $recipes = $this->availableRecipes();

        return view('storefront.pages.blog', compact('recipes'));
    }

    public function recipe(string $slug, Seo $seo): View
    {
        $recipe = $this->availableRecipes()[$slug] ?? null;
        abort_unless($recipe, 404);

        $seo->description($recipe['summary']);
        $seo->schema([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Recipes', 'item' => route('blog')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $recipe['title'], 'item' => route('blog.recipe', $slug)],
            ],
        ]);

        return view('storefront.pages.recipe', compact('recipe', 'slug'));
    }

    private function availableRecipes(): array
    {
        $recipes = RecipeData::all();

        foreach ($recipes as $slug => &$recipe) {
            $product = Product::query()->where('is_active', true)
                ->whereRaw('LOWER(name) LIKE ?', ['%'.$recipe['product_search'].'%'])
                ->first();

            if (! $product) {
                unset($recipes[$slug]);
                continue;
            }

            $recipe['product'] = $product;
            $recipe['slug'] = $slug;
        }
        unset($recipe);

        return $recipes;
    }

    public function wholesale(): View
    {
        $products = Product::query()->where('is_active', true)->orderBy('sort_order')->get(['name', 'slug']);

        return view('storefront.pages.wholesale', compact('products'));
    }

    public function careers(): View
    {
        return view('storefront.pages.careers');
    }

    public function privacy(): View
    {
        return view('storefront.pages.privacy');
    }

    public function terms(): View
    {
        return view('storefront.pages.terms');
    }

    public function faq(Seo $seo): View
    {
        $seo->schema(FaqData::schema());

        return view('storefront.pages.faq');
    }

    public function contact(): View
    {
        return view('storefront.pages.contact');
    }
}
