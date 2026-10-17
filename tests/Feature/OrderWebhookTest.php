<?php

namespace Tests\Feature;

use App\Filament\Pages\IntegrationSettingsPage;
use App\Jobs\SendOrderWebhookJob;
use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_webhook_sends_order_and_items_with_an_hmac_signature(): void
    {
        Http::fake();

        IntegrationSetting::current()->update([
            'order_webhook_url' => 'https://platform.example.com/hooks/orders',
            'order_webhook_secret' => 'test-webhook-secret',
        ]);

        $order = Order::create([
            'customer_name' => 'Test Customer',
            'customer_phone' => '919876543210',
            'customer_email' => 'customer@example.com',
            'delivery_address' => '12 Test Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postcode' => '400001',
            'subtotal' => 598,
            'total' => 598,
            'payment_method' => 'cod',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Mango',
            'variant_name' => '1 kg',
            'sku' => 'MANGO-1KG',
            'quantity' => 2,
            'unit_price' => 299,
            'subtotal' => 598,
        ]);

        (new SendOrderWebhookJob($order->id, 'order.created'))->handle();

        Http::assertSent(function (ClientRequest $request): bool {
            $body = $request->body();
            $payload = json_decode($body, true);
            $timestamp = $request->header('X-Merza-Timestamp')[0] ?? '';

            return $request->url() === 'https://platform.example.com/hooks/orders'
                && ($request->header('X-Merza-Event')[0] ?? null) === 'order.created'
                && ($request->header('X-Merza-Signature')[0] ?? null) === 'sha256='
                    .hash_hmac('sha256', $timestamp.'.'.$body, 'test-webhook-secret')
                && ($payload['data']['order']['customer']['name'] ?? null) === 'Test Customer'
                && ($payload['data']['items'][0]['sku'] ?? null) === 'MANGO-1KG';
        });
    }

    public function test_webhook_settings_require_a_secret_and_store_it_encrypted(): void
    {
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin);

        $page = Livewire::test(IntegrationSettingsPage::class)
            ->set('data.order_webhook_url', 'https://platform.example.com/hooks/orders')
            ->call('save')
            ->assertHasErrors(['data.order_webhook_secret']);

        $page->set('data.order_webhook_secret', 'new-webhook-secret')
            ->call('save')
            ->assertHasNoErrors();

        $settings = IntegrationSetting::current();
        $this->assertSame('https://platform.example.com/hooks/orders', $settings->order_webhook_url);
        $this->assertSame('new-webhook-secret', $settings->order_webhook_secret);
        $this->assertStringStartsNotWith('new-webhook-secret', $settings->getRawOriginal('order_webhook_secret'));

        Livewire::test(IntegrationSettingsPage::class)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('new-webhook-secret', IntegrationSetting::current()->order_webhook_secret);
    }

    public function test_order_updates_queue_a_webhook_delivery(): void
    {
        Queue::fake();

        $order = Order::create([
            'customer_name' => 'Test Customer',
            'customer_phone' => '919876543210',
            'delivery_address' => '12 Test Street',
            'payment_method' => 'cod',
        ]);

        $order->update(['status' => 'confirmed']);

        Queue::assertPushed(
            SendOrderWebhookJob::class,
            fn (SendOrderWebhookJob $job): bool => $job->orderId === $order->id
                && $job->event === 'order.updated',
        );
    }

    public function test_configured_order_webhook_contains_e164_customer_products_currency_and_total(): void
    {
        Http::fake([
            'https://gowhats.example.com/hooks/orders' => Http::response(['ok' => true], 200),
        ]);

        $webhook = Webhook::create([
            'name' => 'GoWhats order sync',
            'status' => 'active',
            'topic' => 'order.created',
            'delivery_url' => 'https://gowhats.example.com/hooks/orders',
            'secret' => 'gowhats-shared-secret',
        ]);
        $order = Order::create([
            'customer_name' => 'GoWhats Customer',
            'customer_phone' => '9876543210',
            'customer_email' => 'gowhats@example.com',
            'delivery_address' => '12 Test Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postcode' => '400001',
            'subtotal' => 598,
            'total' => 648,
            'delivery_fee' => 50,
            'payment_method' => 'upi',
            'payment_status' => 'paid',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Mango',
            'variant_name' => '1 kg',
            'sku' => 'MANGO-1KG',
            'quantity' => 2,
            'unit_price' => 299,
            'subtotal' => 598,
        ]);
        $eventId = 'evt_32bc64bc-1cfb-48ea-85f8-2c6dbbaf446c';

        (new SendOrderWebhookJob($order->id, 'order.created', $eventId, '2026-10-17T10:00:00+05:30'))->handle();

        Http::assertSent(function (ClientRequest $request) use ($eventId): bool {
            $body = $request->body();
            $payload = json_decode($body, true);
            $timestamp = $request->header('X-Merza-Timestamp')[0] ?? '';

            return $request->url() === 'https://gowhats.example.com/hooks/orders'
                && ($payload['event_id'] ?? null) === $eventId
                && ($request->header('Idempotency-Key')[0] ?? null) === $eventId
                && ($request->header('X-Merza-Signature')[0] ?? null) === 'sha256='
                    .hash_hmac('sha256', $timestamp.'.'.$body, 'gowhats-shared-secret')
                && ($payload['data']['order']['customer']['phone'] ?? null) === '+919876543210'
                && ($payload['data']['order']['currency'] ?? null) === 'INR'
                && ($payload['data']['order']['total'] ?? null) === 648.0
                && ($payload['data']['order']['payment_status'] ?? null) === 'paid'
                && ($payload['data']['items'][0]['product_name'] ?? null) === 'Mango'
                && ($payload['data']['items'][0]['quantity'] ?? null) === 2
                && ($payload['data']['items'][0]['unit_price'] ?? null) === 299.0;
        });

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'event_id' => $eventId,
            'status' => 'success',
            'response_status' => 200,
        ]);
        $this->assertSame(1, WebhookDelivery::where('event_id', $eventId)->value('attempts'));
    }
}
