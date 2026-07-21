<?php

namespace Tests\Feature;

use App\Livewire\Storefront\CheckoutForm;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutRepeatCustomerTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Fresh Fruits', 'slug' => 'fresh-fruits', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name'        => 'Test Mango',
            'slug'        => 'test-mango',
            'base_price'  => 100,
            'unit'        => 'kg',
            'is_active'   => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id'   => $product->id,
            'name'         => '5 kg',
            'sku'          => 'TM-5KG',
            'price'        => 500,
            'weight_value' => 5,
            'weight_unit'  => 'kg',
            'stock_qty'    => 10,
            'is_active'    => true,
        ]);
    }

    public function test_typing_a_known_phone_number_shows_welcome_back_and_previous_address(): void
    {
        $contact = Contact::create(['name' => 'Repeat Storefront Customer', 'phone' => '9333300001', 'source' => 'website']);
        Order::create([
            'channel' => 'website', 'contact_id' => $contact->id,
            'customer_name' => 'Repeat Storefront Customer', 'customer_phone' => '9333300001',
            'delivery_address' => '77 Storefront Lane', 'city' => 'Theni', 'state' => 'Tamil Nadu',
            'postcode' => '625513', 'landmark' => 'Near Bus Stand',
            'subtotal' => 100, 'delivery_fee' => 10, 'total' => 110, 'payment_method' => 'upi',
        ]);

        app(CartService::class)->add($this->variant->id, 1);

        $test = Livewire::test(CheckoutForm::class)
            ->set('customer_phone', '9333300001');

        $test->assertSet('returningCustomerName', 'Repeat Storefront Customer')
            ->assertSet('hasPreviousAddress', true);

        $test->call('useSameAddress')
            ->assertSet('delivery_address', '77 Storefront Lane')
            ->assertSet('city', 'Theni')
            ->assertSet('state', 'Tamil Nadu')
            ->assertSet('postcode', '625513')
            ->assertSet('landmark', 'Near Bus Stand')
            ->assertSet('previousAddressApplied', true);
    }

    public function test_unknown_phone_number_shows_no_welcome_banner(): void
    {
        app(CartService::class)->add($this->variant->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set('customer_phone', '9999999999')
            ->assertSet('returningCustomerName', null)
            ->assertSet('hasPreviousAddress', false);
    }
}
