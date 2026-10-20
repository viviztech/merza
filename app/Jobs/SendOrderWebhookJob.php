<?php

namespace App\Jobs;

use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\Webhook;
use App\Services\WebhookDeliveryService;
use App\Services\WebhookDispatcher;
use App\Services\WebhookPayloadFactory;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendOrderWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public readonly string $eventId;

    public readonly string $occurredAt;

    public function __construct(
        public readonly int $orderId,
        public readonly string $event,
        ?string $eventId = null,
        ?string $occurredAt = null,
    ) {
        $this->eventId = $eventId ?: 'evt_'.(string) Str::uuid();
        $this->occurredAt = $occurredAt ?: now()->toIso8601String();
    }

    public function handle(
        ?WebhookDispatcher $dispatcher = null,
        ?WebhookPayloadFactory $payloadFactory = null,
        ?WebhookDeliveryService $deliveryService = null,
    ): void {
        $this->initializeEventMetadata();

        $dispatcher ??= app(WebhookDispatcher::class);
        $payloadFactory ??= app(WebhookPayloadFactory::class);
        $deliveryService ??= app(WebhookDeliveryService::class);

        $order = Order::with('items')->find($this->orderId);

        if (! $order) {
            Log::warning('Order webhook skipped because the order no longer exists.', [
                'order_id' => $this->orderId,
                'event' => $this->event,
            ]);

            return;
        }

        // The Webhooks settings page is the primary integration path. It uses
        // the same queued, idempotent delivery job as every other event.
        if (Webhook::query()->where('topic', $this->event)->exists()) {
            $dispatcher->dispatchOrder($this->event, $order, $this->eventId, $this->occurredAt);

            return;
        }

        // Existing installations may still have the old Integration Settings
        // order endpoint. Keep it working until a Webhooks record is created,
        // while sharing the exact same signing and request implementation.
        $settings = IntegrationSetting::current();

        if (blank($settings->order_webhook_url)) {
            return;
        }

        $deliveryService->deliverLegacy(
            $settings->order_webhook_url,
            $settings->order_webhook_secret,
            $this->event,
            $payloadFactory->order($order),
            $this->eventId,
            $this->occurredAt,
        );
    }

    private function initializeEventMetadata(): void
    {
        // Use the original queue envelope for jobs produced by the previous
        // release, rather than assigning a different event ID on each retry.
        $payload = $this->job?->payload() ?? [];

        if (! isset($this->eventId)) {
            $identity = $payload['uuid'] ?? substr(hash('sha256', serialize([
                $this->orderId, $this->event,
            ])), 0, 40);
            $this->eventId = 'evt_'.$identity;
        }

        if (! isset($this->occurredAt)) {
            $this->occurredAt = CarbonImmutable::createFromTimestampUTC(
                $payload['createdAt'] ?? now()->timestamp,
            )->toIso8601String();
        }
    }
}
