<?php

namespace Tests\Feature;

use App\Livewire\Storefront\ProductDetail;
use App\Livewire\Storefront\CheckoutForm;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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
            'harvest_date' => '2026-08-18',
            'farm_location' => 'Bodinayakanur, Tamil Nadu',
            'sweetness_level' => 'Honey sweet',
            'delivery_time' => 'Delivered within 48 hours',
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
        // Simulate an item left in the cart from an earlier browsing session.
        app(CartService::class)->add($this->variant->id, 1);

        Livewire::test(ProductDetail::class, ['slug' => $this->product->slug])
            ->set('qty', 3)
            ->call('buyNow')
            ->assertRedirect(route('checkout.index'));

        $item = app(CartService::class)->items()->first();
        $this->assertTrue($item->is_preorder);
        $this->assertSame(3, $item->qty);
    }

    public function test_prebooking_page_prioritizes_usp_points_and_hides_price_controls(): void
    {
        Livewire::test(ProductDetail::class, ['slug' => $this->product->slug])
            ->assertSee('Why pre-book this harvest')
            ->assertSee('18 Aug 2026')
            ->assertSee('Bodinayakanur, Tamil Nadu')
            ->assertSee('Honey sweet')
            ->assertSee('Delivered within 48 hours')
            ->assertSee('Pre-book now')
            ->assertDontSee('Choose Size / Weight')
            ->assertDontSee('₹499.00');
    }

    public function test_prebooking_checkout_only_requires_contact_details_and_has_no_payment_step(): void
    {
        Queue::fake();
        app(CartService::class)->add($this->variant->id, 1);

        $test = Livewire::test(CheckoutForm::class)
            ->assertSeeHtml('id="merza-checkout-form"')
            ->assertSeeHtml('data-form-id="merza-checkout-form"')
            ->assertSee('Reservation Contact')
            ->assertSee('That is all we need to reserve your harvest.')
            ->assertDontSee('Delivery Details')
            ->assertDontSee('Payment Method')
            ->assertDontSee('Amount to pay')
            ->assertDontSee('₹499.00')
            ->set('customer_name', 'Prebook Customer')
            ->set('customer_phone', '9876543210')
            ->call('placeOrder')
            ->assertHasNoErrors()
            ->assertSet('orderPlaced', true)
            ->assertSet('orderIsPreorderOnly', true)
            ->assertSee('Pre-booking Confirmed!')
            ->assertSeeHtml('id="checkout-thank-you"')
            ->assertSeeHtml('data-form-id="merza-checkout-form"')
            ->assertDontSee('Download Invoice');

        $test->assertDispatched('meta-purchase');

        $order = Order::latest('id')->firstOrFail();

        $this->assertSame('whatsapp', $order->payment_method);
        $this->assertSame('0.00', $order->delivery_fee);
        $this->assertNull($order->city);
        $this->assertNull($order->postcode);
        $this->assertSame('Pre-order — address to be confirmed', $order->delivery_address);

        $pixelPayload = session("meta_purchase_events.{$order->id}");
        $this->assertSame($order->order_number, $pixelPayload['orderId']);
        $this->assertSame('INR', $pixelPayload['currency']);
        $this->assertSame([(string) $this->variant->id], $pixelPayload['contentIds']);
        $this->assertSame(1, $pixelPayload['numItems']);
        $this->assertSame(499.0, $pixelPayload['value']);
    }

    public function test_out_of_stock_product_cannot_be_added(): void
    {
        $this->variant->update(['stock_qty' => 0]);

        app(CartService::class)->add($this->variant->id);

        $this->assertSame(0, app(CartService::class)->count());
    }
}
