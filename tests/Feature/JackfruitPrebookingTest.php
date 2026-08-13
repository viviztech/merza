<?php

namespace Tests\Feature;

use App\Livewire\Storefront\ProductDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JackfruitPrebookingTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Fresh Fruits',
            'slug' => 'fresh-fruits',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Vietnam Gold Jackfruit',
            'slug' => 'vietnam-gold-jackfruit',
            'base_price' => 499,
            'is_active' => true,
            'is_preorder' => true,
            'available_from' => '2026-08-20',
            'preorder_note' => 'Fresh harvest dispatches next week.',
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => '5 kg box',
            'sku' => 'JF-5KG',
            'price' => 499,
            'weight_value' => 5,
            'weight_unit' => 'kg',
            'stock_qty' => 20,
            'is_active' => true,
        ]);
    }

    public function test_prebooking_metadata_is_kept_in_the_cart(): void
    {
        app(CartService::class)->add($this->variant->id, 2);

        $item = app(CartService::class)->items()->first();

        $this->assertTrue($item->is_preorder);
        $this->assertSame('2026-08-20', $item->available_from);
        $this->assertSame('Fresh harvest dispatches next week.', $item->preorder_note);
        $this->assertSame(2, $item->qty);
    }

    public function test_buy_now_adds_the_prebooking_and_opens_checkout(): void
    {
        Livewire::test(ProductDetail::class, ['slug' => $this->product->slug])
            ->set('qty', 3)
            ->call('buyNow')
            ->assertRedirect(route('checkout.index'));

        $item = app(CartService::class)->items()->first();
        $this->assertTrue($item->is_preorder);
        $this->assertSame(3, $item->qty);
    }

    public function test_out_of_stock_product_cannot_be_added(): void
    {
        $this->variant->update(['stock_qty' => 0]);

        app(CartService::class)->add($this->variant->id);

        $this->assertSame(0, app(CartService::class)->count());
    }
}
