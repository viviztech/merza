<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPricingTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(): Product
    {
        $category = Category::create([
            'name' => 'Freeze-Dried Fruits',
            'slug' => 'freeze-dried-fruits',
            'is_active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Merza Premium Freeze-Dried Red Banana Chips',
            'slug' => 'freeze-dried-red-banana-chips',
            'base_price' => 397.50,
            'is_active' => true,
        ]);
    }

    public function test_small_packaged_snack_shows_actual_pack_price_not_extrapolated_per_kg(): void
    {
        $product = $this->makeProduct();

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => '40 g pouch',
            'sku' => 'RB-40G',
            'price' => 397.50,
            'weight_value' => 40,
            'weight_unit' => 'g',
            'stock_qty' => 20,
            'is_active' => true,
        ]);

        // A 40g pack must not be extrapolated into a misleading "₹9,937.50/kg".
        $this->assertNull($product->fresh()->min_price_per_kg);
    }

    public function test_multi_kg_bulk_box_still_shows_comparable_per_kg_price(): void
    {
        $product = $this->makeProduct();

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => '5 kg box',
            'sku' => 'MG-5KG',
            'price' => 500,
            'weight_value' => 5,
            'weight_unit' => 'kg',
            'stock_qty' => 20,
            'is_active' => true,
        ]);

        $this->assertSame(100.0, $product->fresh()->min_price_per_kg);
    }
}
