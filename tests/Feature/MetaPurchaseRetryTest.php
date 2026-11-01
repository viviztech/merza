<?php

namespace Tests\Feature;

use App\Jobs\SendMetaPurchaseEvent;
use App\Models\Order;
use App\Services\MetaConversionsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MetaPurchaseRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_failure_fails_the_job_for_queue_retry(): void
    {
        Queue::fake();
        config()->set([
            'services.meta.tracking_enabled' => true,
            'services.meta.dataset_id' => '123456',
            'services.meta.require_consent' => true,
            'services.meta.conversions_api.enabled' => true,
            'services.meta.conversions_api.access_token' => 'test-token',
            'services.meta.conversions_api.graph_api_version' => 'v23.0',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response([], 503)]);
        $order = $this->paidOrder(true);

        try {
            (new SendMetaPurchaseEvent($order->id))->handle(app(MetaConversionsService::class));
            $this->fail('A failed delivery should fail the queue job.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Meta purchase event delivery failed.', $exception->getMessage());
        }
        Http::assertSentCount(1);
    }

    public function test_missing_consent_and_disabled_tracking_do_not_retry(): void
    {
        Queue::fake();
        config()->set([
            'services.meta.tracking_enabled' => true,
            'services.meta.dataset_id' => '123456',
            'services.meta.require_consent' => true,
            'services.meta.conversions_api.enabled' => true,
            'services.meta.conversions_api.access_token' => 'test-token',
        ]);
        Http::fake();
        $order = $this->paidOrder(false);

        (new SendMetaPurchaseEvent($order->id))->handle(app(MetaConversionsService::class));
        config()->set('services.meta.tracking_enabled', false);
        (new SendMetaPurchaseEvent($order->id))->handle(app(MetaConversionsService::class));

        Http::assertNothingSent();
    }

    private function paidOrder(bool $consent): Order
    {
        return Order::create([
            'customer_name' => 'Buyer',
            'customer_phone' => '9876543210',
            'delivery_address' => '1 Test Street',
            'subtotal' => 500,
            'total' => 500,
            'payment_method' => 'upi',
            'payment_status' => 'paid',
            'meta_tracking_consent' => $consent,
        ]);
    }
}
