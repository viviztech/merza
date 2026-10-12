<?php

namespace Tests\Feature;

use App\Filament\Resources\WebhookResource\Pages\ListWebhooks;
use App\Jobs\SendWebhookJob;
use App\Models\Contact;
use App\Models\User;
use App\Models\Webhook;
use App\Services\WebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_resource_can_be_rendered(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(ListWebhooks::class)
            ->assertSuccessful();
    }

    public function test_create_webhook_action_keeps_the_generated_secret_in_a_popup(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ListWebhooks::class)
            ->callAction('createWebhook', [
                'name' => 'Popup webhook',
                'status' => 'active',
                'topic' => 'order.created',
                'delivery_url' => 'https://platform.example.com/hooks/orders',
                'api_version' => 'v1',
            ])
            ->assertSet('generatedSecret', fn (string $secret): bool => str_starts_with($secret, 'whsec_'))
            ->assertSet('mountedActions.0.data.secret_key_display', fn (string $secret): bool => str_starts_with($secret, 'whsec_'));

        $this->assertDatabaseHas('webhooks', [
            'name' => 'Popup webhook',
            'topic' => 'order.created',
            'status' => 'active',
        ]);
    }

    public function test_webhook_dispatch_sends_signed_event_data_and_records_delivery(): void
    {
        Http::fake();

        $webhook = Webhook::create([
            'name' => 'CRM cart sync',
            'status' => 'active',
            'topic' => 'cart.added',
            'delivery_url' => 'https://platform.example.com/hooks/cart',
            'secret' => 'cart-webhook-secret',
            'api_version' => 'v1',
        ]);

        $data = ['cart' => ['item_count' => 1, 'subtotal' => 299]];

        (new SendWebhookJob($webhook->id, 'cart.added', $data))
            ->handle(app(\App\Services\WebhookDeliveryService::class));

        Http::assertSent(function (ClientRequest $request) use ($data): bool {
            $body = $request->body();
            $timestamp = $request->header('X-Merza-Timestamp')[0] ?? '';
            $payload = json_decode($body, true);

            return $request->url() === 'https://platform.example.com/hooks/cart'
                && ($request->header('X-Merza-Event')[0] ?? null) === 'cart.added'
                && ($request->header('X-Merza-Signature')[0] ?? null) === 'sha256='
                    . hash_hmac('sha256', $timestamp . '.' . $body, 'cart-webhook-secret')
                && ($payload['data'] ?? null) === $data;
        });

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'topic' => 'cart.added',
            'status' => 'success',
            'response_status' => 200,
        ]);

        $this->assertSame('cart-webhook-secret', $webhook->fresh()->secret);
        $this->assertNotSame('cart-webhook-secret', $webhook->fresh()->getRawOriginal('secret'));
    }

    public function test_dispatcher_queues_only_active_webhooks_for_a_topic(): void
    {
        Queue::fake();

        $active = Webhook::create([
            'name' => 'Active checkout sync',
            'status' => 'active',
            'topic' => 'checkout.started',
            'delivery_url' => 'https://platform.example.com/hooks/checkout',
            'secret' => 'checkout-webhook-secret',
        ]);

        Webhook::create([
            'name' => 'Disabled checkout sync',
            'status' => 'disabled',
            'topic' => 'checkout.started',
            'delivery_url' => 'https://platform.example.com/hooks/checkout-disabled',
            'secret' => 'disabled-webhook-secret',
        ]);

        app(WebhookDispatcher::class)->dispatch('checkout.started', ['cart' => []]);

        Queue::assertPushed(SendWebhookJob::class, fn (SendWebhookJob $job): bool =>
            $job->webhookId === $active->id
            && $job->topic === 'checkout.started'
        );
        Queue::assertPushed(SendWebhookJob::class, 1);
    }

    public function test_customer_creation_queues_the_customer_payload(): void
    {
        Queue::fake();

        $webhook = Webhook::create([
            'name' => 'CRM customer sync',
            'status' => 'active',
            'topic' => 'customer.created',
            'delivery_url' => 'https://platform.example.com/hooks/customers',
            'secret' => 'customer-webhook-secret',
        ]);

        Contact::create([
            'name' => 'Webhook Customer',
            'phone' => '919876543210',
            'email' => 'customer@example.com',
            'source' => 'website',
        ]);

        Queue::assertPushed(SendWebhookJob::class, fn (SendWebhookJob $job): bool =>
            $job->webhookId === $webhook->id
            && $job->topic === 'customer.created'
            && ($job->data['customer']['email'] ?? null) === 'customer@example.com'
        );
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        return $admin;
    }
}
