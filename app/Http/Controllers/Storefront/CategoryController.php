<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\Seo;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(string $slug, Seo $seo): View
    {
        $category = Category::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();

        $products = Product::query()->where('category_id', $category->id)
            ->where('is_active', true)
            ->with(['activeVariants', 'media'])
            ->orderBy('sort_order')->paginate(24);

        abort_if($products->total() === 0, 404);

        [$categoryIntro, $tamilName, $categoryQuestion, $categoryAnswer] = match (true) {
            $category->slug === 'fresh-fruits' => [
                'Explore the fresh fruits currently listed by Merza. Compare each fruit’s available sizes and current price before ordering.',
                'புதிய பழங்கள்',
                'Where can I find origin and handling details?',
                'Open each fruit’s product page for the origin and care details we have verified. Contact us if you need more information before ordering.',
            ],
            str_contains($category->slug, 'freeze') => [
                'Browse Merza’s freeze-dried fruit snacks. Open a product to see its ingredients, pack sizes and current price.',
                'பழச் சிற்றுண்டிகள்',
                'How should I store these snacks?',
                'Follow the storage instructions on the product page or pack. Contact us if you need product-specific guidance.',
            ],
            str_contains($category->slug, 'jaggery') || str_contains($category->slug, 'sweetener') => [
                'Compare the jaggery and natural sweeteners currently available at Merza, including their sizes and prices.',
                'வெல்லம் மற்றும் இயற்கை இனிப்புகள்',
                'Where can I check ingredients and nutrition?',
                'Open a product page for the ingredient and nutrition details we have verified, or ask us before ordering.',
            ],
            default => [
                "Explore {$category->name} at Merza. Compare current products, sizes and prices before ordering.",
                null,
                'Where can I find product details?',
                'Open a product page for its available sizes and any verified product-specific details.',
            ],
        };

        $categoryIntro = $category->description ?: $categoryIntro;
        $seo->description(Str::limit(strip_tags($categoryIntro).' Delivery options are shown at checkout.', 160, ''));
        $seo->schema([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Products', 'item' => route('products.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $category->name, 'item' => route('categories.show', $category->slug)],
            ],
        ]);

        return view('storefront.pages.category', compact('category', 'products', 'categoryIntro', 'tamilName', 'categoryQuestion', 'categoryAnswer'));
    }
}
