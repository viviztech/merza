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
use Illuminate\Support\Facades\Http;
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
            ->assertSet('hasPreviousAddress', true)
            ->assertSet('delivery_address', '77 Storefront Lane')
            ->assertSet('city', 'Theni')
            ->assertSet('state', 'Tamil Nadu')
            ->assertSet('postcode', '625513')
            ->assertSet('landmark', 'Near Bus Stand')
            ->assertSet('previousAddressApplied', true);

        $test->assertSee('Deliver to')
            ->assertSee('Change')
            ->assertSee('Continue');
    }

    public function test_unknown_phone_number_shows_no_welcome_banner(): void
    {
        app(CartService::class)->add($this->variant->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set('customer_phone', '9999999999')
            ->assertSet('returningCustomerName', null)
            ->assertSet('hasPreviousAddress', false);
    }

    public function test_formatted_six_digit_pincode_is_normalized_and_auto_filled(): void
    {
        Http::fake([
            'api.postalpincode.in/*' => Http::response([[
                'Status' => 'Success',
                'PostOffice' => [['District' => 'Theni', 'State' => 'Tamil Nadu']],
            ]]),
        ]);

        app(CartService::class)->add($this->variant->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set('postcode', '625 513')
            ->assertSet('postcode', '625513')
            ->assertSet('city', 'Theni')
            ->assertSet('state', 'Tamil Nadu')
            ->assertSet('pincodeAutoFilled', true)
            ->assertHasNoErrors('postcode');
    }

    public function test_current_location_fills_delivery_address_and_pincode(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'display_name' => '12, Market Road, Bodinayakanur, Tamil Nadu, 625513',
                'address' => [
                    'house_number' => '12',
                    'road' => 'Market Road',
                    'town' => 'Bodinayakanur',
                    'state' => 'Tamil Nadu',
                    'postcode' => '625513',
                ],
            ]),
        ]);

        app(CartService::class)->add($this->variant->id, 1);

        Livewire::test(CheckoutForm::class)
            ->call('autofillFromLocation', 10.0117, 77.3498)
            ->assertSet('delivery_address', '12, Market Road')
            ->assertSet('postcode', '625513')
            ->assertSet('city', 'Bodinayakanur')
            ->assertSet('state', 'Tamil Nadu')
            ->assertSet('pincodeAutoFilled', true)
            ->assertSet('locationLookupFailed', false);
    }

    public function test_email_is_optional_even_when_hosted_gateway_is_active(): void
    {
        config()->set('payments.gateway', 'sabpaisa');
        config()->set('services.sabpaisa.api_key', 'test-key');
        config()->set('services.sabpaisa.secret_key', 'test-secret');
        config()->set('services.sabpaisa.merchant_id', 'test-merchant');

        app(CartService::class)->add($this->variant->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set('customer_name', 'No Email Customer')
            ->set('customer_phone', '9333300002')
            ->set('delivery_address', '12 Market Road')
            ->set('postcode', '625513')
            ->set('city', 'Theni')
            ->set('state', 'Tamil Nadu')
            ->call('placeOrder')
            ->assertHasNoErrors('customer_email');
    }
}
