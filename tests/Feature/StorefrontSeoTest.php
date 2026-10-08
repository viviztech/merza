<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StorefrontSeoTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Fresh Fruits', 'slug' => 'fresh-fruits', 'is_active' => true]);
        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Farm Fresh Amla',
            'slug' => 'farm-fresh-amla',
            'short_description' => 'Fresh amla from Bodinayakanur.',
            'base_price' => 129,
            'is_active' => true,
            'is_featured' => true,
            'is_available_today' => true,
        ]);
        ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => '1 kg',
            'sku' => 'AMLA-1KG',
            'price' => 149,
            'weight_value' => 1,
            'weight_unit' => 'kg',
            'stock_qty' => 20,
            'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Unavailable sample',
            'sku' => 'AMLA-SAMPLE',
            'price' => 129,
            'stock_qty' => 0,
            'is_active' => true,
        ]);
    }

    public function test_product_metadata_price_and_review_schema_use_product_data(): void
    {
        ProductReview::create([
            'product_id' => $this->product->id,
            'customer_name' => 'Test Customer',
            'rating' => 5,
            'comment' => 'Fresh fruit.',
            'is_approved' => true,
        ]);

        $this->get(route('products.show', $this->product->slug))
            ->assertOk()
            ->assertSee('<title>Buy Farm Fresh Amla Online | Merza</title>', false)
            ->assertSee('Fresh amla from Bodinayakanur. Shop online at Merza.')
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"@type":"Review"', false)
            ->assertSee('"price":149', false)
            ->assertSee('₹149.00');
    }

    public function test_home_and_category_use_current_catalog_without_stale_promises(): void
    {
        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('<title>Shop Farm Produce &amp; Fruit Snacks | Merza</title>', false)
            ->assertSee('Shop Merza farm produce and fruit snacks.');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('categories.show', 'fresh-fruits'))
            ->assertSee('From ₹149.00')
            ->assertDontSee('Mango Season is Here')
            ->assertDontSee('Delivered Today')
            ->assertDontSee('Only 20 left');

        $this->get(route('categories.show', 'fresh-fruits'))
            ->assertOk()
            ->assertSee('<title>Fresh Fruits — Shop Online | Merza</title>', false)
            ->assertSee('Farm Fresh Amla');
    }

    public function test_uppercase_product_url_redirects_to_lowercase_and_track_is_noindex(): void
    {
        $this->get('/products/FARM-FRESH-AMLA')
            ->assertStatus(301)
            ->assertRedirect(route('products.show', 'farm-fresh-amla'));

        $this->get(route('track.index'))
            ->assertOk()
            ->assertSee('noindex, nofollow');
    }

    public function test_recipe_has_its_own_url_and_sitemap_entry(): void
    {
        Cache::forget('seo:sitemap-xml');

        $this->get(route('blog.recipe', 'amla-ginger-chutney'))
            ->assertOk()
            ->assertSee('Fresh Amla and Ginger Chutney')
            ->assertSee(route('products.show', $this->product->slug));

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('blog.recipe', 'amla-ginger-chutney'))
            ->assertSee(route('categories.show', 'fresh-fruits'))
            ->assertDontSee(route('track.index'));
    }

    public function test_about_wholesale_faq_and_ai_summary_match_the_current_catalog(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('போடிநாயக்கனூரில்')
            ->assertSee(route('categories.show', 'fresh-fruits'));

        $this->get(route('wholesale'))
            ->assertOk()
            ->assertSee('Farm Fresh Amla')
            ->assertDontSee('Mango puree');

        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('"@type":"FAQPage"', false);

        $this->get(route('llms-txt'))
            ->assertOk()
            ->assertSee('Farm Fresh Amla')
            ->assertDontSee('Imam Pasand');
    }
}
