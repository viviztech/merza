<?php

namespace Tests\Feature;

use App\Jobs\SendOrderWebhookJob;
use App\Jobs\SendWebhookJob;
use App\Models\Order;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\WebhookDeliveryService;
use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\Jobs\FakeJob;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

class WebhookQueueCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private const QUEUE_UUID = 'ce032f0f-71a3-4663-a25a-cf5c2e5fb346';

    private const QUEUED_AT = 1792211400;

    public function test_legacy_webhook_jobs_keep_the_queue_event_identity_and_request_body_across_retries(): void
    {
        Http::fakeSequence()->push('Try again', 503)->push(['accepted' => true], 200);
        $webhook = $this->webhook();
        $serialized = $this->legacyJob(SendWebhookJob::class, [
            'webhookId' => $webhook->id,
            'topic' => 'cart.added',
            'data' => ['cart' => ['item_count' => 1]],
        ]);

        $first = unserialize($serialized);
        $first->setJob($this->queueJob());

        try {
            $first->handle(app(WebhookDeliveryService::class));
            $this->fail('The first delivery must be retried.');
        } catch (RuntimeException) {
            // A queue retry restores the original command, not this instance.
        }

        $this->travel(5)->minutes();
        $retry = unserialize($serialized);
        $retry->setJob($this->queueJob(2));
        $retry->handle(app(WebhookDeliveryService::class));

        $requests = Http::recorded()->map(fn (array $record): Request => $record[0]);
        $this->assertCount(2, $requests);
        $this->assertSame($requests[0]->body(), $requests[1]->body());
        $this->assertSame('evt_'.self::QUEUE_UUID, $first->eventId);
        $this->assertSame($first->eventId, $retry->eventId);
        $this->assertSame($first->occurredAt, $retry->occurredAt);
        $this->assertDatabaseHas('webhook_deliveries', [
            'event_id' => $first->eventId,
            'status' => 'success',
            'attempts' => 2,
        ]);
    }

    public function test_legacy_order_jobs_deliver_the_same_event_after_being_restored_again(): void
    {
        Http::fake();
        $webhook = $this->webhook('order.created');
        $order = Order::withoutEvents(fn () => Order::create([
            'order_number' => 'LEGACY-QUEUED-001',
            'customer_name' => 'Legacy queued order',
            'customer_phone' => '9876543210',
            'delivery_address' => '12 Test Street',
            'payment_method' => 'cod',
        ]));
        $serialized = $this->legacyJob(SendOrderWebhookJob::class, [
            'orderId' => $order->id,
            'event' => 'order.created',
        ]);

        foreach ([1, 2] as $attempt) {
            $restored = unserialize($serialized);
            $restored->setJob($this->queueJob($attempt));
            $restored->handle();
            $this->travel(5)->minutes();
        }

        Http::assertSentCount(1);
        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'event_id' => 'evt_'.self::QUEUE_UUID,
            'status' => 'success',
        ]);
    }

    public function test_restored_new_jobs_release_the_original_unique_lock(): void
    {
        $job = new SendWebhookJob(123, 'cart.added', []);
        $lock = new UniqueLock(app(Repository::class));
        $this->assertTrue($lock->acquire($job));

        app(CallQueuedHandler::class)->call($this->queueJob(), ['command' => serialize($job)]);

        $this->assertTrue($lock->acquire($job));
        $lock->release($job);
    }

    public function test_legacy_jobs_can_release_unique_locks_even_when_the_webhook_is_missing(): void
    {
        $serialized = $this->legacyJob(SendWebhookJob::class, [
            'webhookId' => 123,
            'topic' => 'cart.added',
            'data' => [],
        ]);
        $queueJob = $this->queueJob();

        app(CallQueuedHandler::class)->call($queueJob, ['command' => $serialized]);

        $this->assertTrue($queueJob->isDeleted());
    }

    public function test_failed_legacy_jobs_mark_the_original_event_failed(): void
    {
        $webhook = $this->webhook();
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_id' => 'evt_'.self::QUEUE_UUID,
            'topic' => 'cart.added',
            'status' => 'retrying',
        ]);
        $serialized = $this->legacyJob(SendWebhookJob::class, [
            'webhookId' => $webhook->id,
            'topic' => 'cart.added',
            'data' => [],
        ]);

        app(CallQueuedHandler::class)->failed(
            ['command' => $serialized], new RuntimeException('Retry limit reached'),
            self::QUEUE_UUID, $this->queueJob(4),
        );

        $this->assertSame('failed', $delivery->fresh()->status);
        $this->assertSame('Retry limit reached', $delivery->fresh()->error);
    }

    public function test_a_live_delivery_lease_retries_and_an_expired_lease_can_be_reclaimed(): void
    {
        Http::fake();
        $webhook = $this->webhook();
        $job = new SendWebhookJob($webhook->id, 'cart.added', ['cart' => []]);
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_id' => $job->eventId,
            'topic' => 'cart.added',
            'status' => 'sending',
            'attempts' => 1,
            // The queue retries at 90 seconds, before the 120-second lease.
            'locked_at' => now()->subSeconds(90),
        ]);

        try {
            $job->handle(app(WebhookDeliveryService::class));
            $this->fail('A live lease must leave the job available for retry.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('locked by another worker', $exception->getMessage());
        }

        Http::assertNothingSent();
        $this->assertSame('sending', $delivery->fresh()->status);
        $this->travel(31)->seconds();
        $job->handle(app(WebhookDeliveryService::class));

        Http::assertSentCount(1);
        $this->assertSame('success', $delivery->fresh()->status);
        $this->assertSame(2, $delivery->fresh()->attempts);
        $this->assertNull($delivery->fresh()->locked_at);
    }

    private function webhook(string $topic = 'cart.added'): Webhook
    {
        return Webhook::create([
            'name' => 'Queue compatibility destination',
            'status' => 'active',
            'topic' => $topic,
            'delivery_url' => 'https://platform.example.com/hooks/compatibility',
            'secret' => 'compatibility-secret',
        ]);
    }

    private function legacyJob(string $class, array $properties): string
    {
        $job = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        $job->__unserialize($properties);

        return serialize($job);
    }

    private function queueJob(int $attempt = 1): FakeJob
    {
        $job = new class(self::QUEUE_UUID, self::QUEUED_AT) extends FakeJob
        {
            public function __construct(private string $queueUuid, private int $queuedAt) {}

            public function payload(): array
            {
                return ['uuid' => $this->queueUuid, 'createdAt' => $this->queuedAt];
            }
        };
        $job->attempts = $attempt;

        return $job;
    }
}
