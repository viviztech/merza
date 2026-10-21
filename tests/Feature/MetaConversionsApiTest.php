<?php

namespace Tests\Feature;

use App\Jobs\SendMetaPurchaseEvent;
use App\Models\Order;
use App\Services\MetaConversionsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MetaConversionsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'services.meta.tracking_enabled' => true,
            'services.meta.dataset_id' => '2128624118065839',
            'services.meta.require_consent' => false,
            'services.meta.conversions_api.enabled' => true,
            'services.meta.conversions_api.access_token' => 'test-capi-token',
            'services.meta.conversions_api.graph_api_version' => 'v23.0',
            'services.meta.conversions_api.test_event_code' => null,
        ]);
    }

    public function test_it_hashes_customer_identifiers_and_sends_the_supplied_deduplication_id(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);

        $sent = app(MetaConversionsService::class)->trackFromRequest(
            'Lead',
            customerData: [
                'email' => ' Customer@Example.COM ',
                'phone' => '+91 98765 43210',
                'external_id' => 'lead-123',
            ],
            customData: ['content_name' => 'Contact enquiry'],
            eventId: 'merza-lead-123',
            eventSourceUrl: 'https://merza.test/contact',
        );

        $this->assertTrue($sent);

        Http::assertSent(function (ClientRequest $request): bool {
            $event = $request->data()['data'][0];

            return $request->url() === 'https://graph.facebook.com/v23.0/2128624118065839/events'
                && $event['event_name'] === 'Lead'
                && $event['event_id'] === 'merza-lead-123'
                && $event['user_data']['em'][0] === hash('sha256', 'customer@example.com')
                && $event['user_data']['ph'][0] === hash('sha256', '919876543210')
                && ! str_contains(json_encode($event, JSON_THROW_ON_ERROR), 'Customer@Example.COM');
        });
    }

    public function test_invalid_event_data_is_not_sent_to_meta(): void
    {
        Http::fake();

        $sent = app(MetaConversionsService::class)->trackFromRequest(
            'Purchase',
            customerData: ['email' => 'buyer@example.com'],
            customData: ['currency' => 'INR', 'value' => -1],
            eventId: 'merza-invalid-purchase',
        );

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_a_purchase_is_queued_only_when_the_server_marks_an_order_paid(): void
    {
        Queue::fake();

        $order = Order::create([
            'customer_name' => 'Verified Buyer',
            'customer_phone' => '9876543210',
            'delivery_address' => '1 Test Street',
            'subtotal' => 500,
            'total' => 500,
            'payment_method' => 'upi',
            'meta_tracking_consent' => true,
        ]);

        Queue::assertNothingPushed();

        $order->update(['payment_status' => 'paid']);

        Queue::assertPushed(SendMetaPurchaseEvent::class, fn (SendMetaPurchaseEvent $job) => $job->orderId === $order->id);
    }

    public function test_verified_purchase_uses_the_same_deterministic_event_id_as_the_browser(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);

        $order = Order::create([
            'customer_name' => 'Verified Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '9876543210',
            'delivery_address' => '1 Test Street',
            'city' => 'Theni',
            'state' => 'Tamil Nadu',
            'subtotal' => 500,
            'total' => 500,
            'payment_method' => 'upi',
            'payment_status' => 'paid',
            'meta_tracking_consent' => true,
            'meta_event_context' => [
                'fbp' => 'fb.1.123.456',
                'fbc' => 'fb.1.123.789',
                'client_ip_address' => '203.0.113.9',
                'client_user_agent' => 'Merza Test Browser',
            ],
        ]);

        $meta = app(MetaConversionsService::class);
        $this->assertTrue($meta->trackPurchase($order));

        $browserEvent = $meta->purchaseBrowserEvent($order);
        $this->assertSame(MetaConversionsService::purchaseEventId($order), $browserEvent['eventId']);

        Http::assertSent(function (ClientRequest $request) use ($browserEvent): bool {
            $event = $request->data()['data'][0];

            return $event['event_name'] === 'Purchase'
                && $event['event_id'] === $browserEvent['eventId']
                && $event['custom_data']['value'] === 500.0
                && $event['custom_data']['currency'] === 'INR';
        });
    }

    public function test_purchase_is_not_sent_without_the_checkout_consent_record(): void
    {
        config()->set('services.meta.require_consent', true);
        Http::fake();

        $order = Order::create([
            'customer_name' => 'No Consent Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '9876543210',
            'delivery_address' => '1 Test Street',
            'subtotal' => 500,
            'total' => 500,
            'payment_method' => 'upi',
            'payment_status' => 'paid',
            'meta_tracking_consent' => false,
        ]);

        $this->assertFalse(app(MetaConversionsService::class)->trackPurchase($order));
        Http::assertNothingSent();
    }

    public function test_pixel_layout_never_exposes_the_conversions_api_token(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('2128624118065839')
            ->assertDontSee('test-capi-token');
    }
}
