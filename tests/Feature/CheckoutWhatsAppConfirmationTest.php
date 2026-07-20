<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessageJob;
use App\Livewire\Storefront\CheckoutForm;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutWhatsAppConfirmationTest extends TestCase
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
            'product_id' => $product->id,
            'name'       => '5 kg',
            'sku'        => 'TM-5KG',
            'price'      => 500,
            'weight_value' => 5,
            'weight_unit'  => 'kg',
            'stock_qty'    => 10,
            'is_active'    => true,
        ]);

        DeliveryZone::create([
            'name'         => 'Tamil Nadu',
            'match_type'   => 'state',
            'match_values' => ['Tamil Nadu'],
            'rate_per_kg'  => 20,
            'eta_days'     => 2,
            'is_active'    => true,
        ]);
    }

    public function test_placing_an_order_queues_a_whatsapp_confirmation_and_creates_a_contact(): void
    {
        Queue::fake();

        app(CartService::class)->add($this->variant->id, 1);

        $test = Livewire::test(CheckoutForm::class)
            ->set('customer_name', 'WA Test User')
            ->set('customer_phone', '9123456780')
            ->set('delivery_address', '789 Test Street')
            ->set('postcode', '625513')
            ->set('city', 'Theni')
            ->set('state', 'Tamil Nadu')
            ->call('placeOrder');

        $test->assertSet('orderPlaced', true);

        Queue::assertPushed(SendWhatsAppMessageJob::class);

        $contact = Contact::where('phone', '9123456780')->first();
        $this->assertNotNull($contact);

        $conversation = Conversation::where('contact_id', $contact->id)->latest()->first();
        $this->assertNotNull($conversation);
        $this->assertStringContainsString($test->get('orderNumber'), $conversation->message);
    }

    public function test_opted_out_contact_does_not_get_a_confirmation_queued(): void
    {
        Queue::fake();

        Contact::create([
            'name'         => 'Opted Out User',
            'phone'        => '9123456781',
            'wa_opted_out' => true,
        ]);

        app(CartService::class)->add($this->variant->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set('customer_name', 'Opted Out User')
            ->set('customer_phone', '9123456781')
            ->set('delivery_address', '789 Test Street')
            ->set('postcode', '625513')
            ->set('city', 'Theni')
            ->set('state', 'Tamil Nadu')
            ->call('placeOrder');

        Queue::assertNotPushed(SendWhatsAppMessageJob::class);
    }
}
