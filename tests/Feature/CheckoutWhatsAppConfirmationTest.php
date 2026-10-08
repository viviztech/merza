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
use App\Support\EcommerceData;
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
        config(['payments.gateway' => 'manual']);

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

    public function test_placing_an_order_without_recent_whatsapp_inbound_does_not_queue_freeform_confirmation(): void
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

        $test->assertDispatched('meta-purchase');
        $test->assertDispatched('gtm-purchase');

        Queue::assertNotPushed(SendWhatsAppMessageJob::class);

        $contact = Contact::where('phone', '9123456780')->first();
        $this->assertNotNull($contact);

        $this->assertFalse(Conversation::where('contact_id', $contact->id)->where('direction', 'outbound')->exists());

        $order = \App\Models\Order::where('order_number', $test->get('orderNumber'))->firstOrFail();
        $pixelPayload = session("meta_purchase_events.{$order->id}");
        $this->assertSame($order->order_number, $pixelPayload['orderId']);
        $this->assertSame('INR', $pixelPayload['currency']);
        $this->assertSame([(string) $this->variant->id], $pixelPayload['contentIds']);
        $this->assertSame(1, $pixelPayload['numItems']);

        $ecommerce = EcommerceData::purchase($order);
        $this->assertSame($order->order_number, $ecommerce['transaction_id']);
        $this->assertSame('INR', $ecommerce['currency']);
        $this->assertSame(500.0, $ecommerce['value']);
        $this->assertSame('TM-5KG', $ecommerce['items'][0]['item_id']);
        $this->assertSame(1, $ecommerce['items'][0]['quantity']);
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

    public function test_recent_whatsapp_customer_can_receive_freeform_order_confirmation(): void
    {
        Queue::fake();
        $contact = Contact::create(['name' => 'Recent Customer', 'phone' => '9123456782', 'source' => 'whatsapp']);
        Conversation::create([
            'contact_id' => $contact->id,
            'channel' => 'whatsapp',
            'direction' => 'inbound',
            'message' => 'I want to order',
            'status' => 'read',
            'sent_at' => now(),
        ]);
        app(CartService::class)->add($this->variant->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set('customer_name', 'Recent Customer')
            ->set('customer_phone', '9123456782')
            ->set('delivery_address', '789 Test Street')
            ->set('postcode', '625513')
            ->set('city', 'Theni')
            ->set('state', 'Tamil Nadu')
            ->call('placeOrder');

        Queue::assertPushed(SendWhatsAppMessageJob::class);
    }
}
