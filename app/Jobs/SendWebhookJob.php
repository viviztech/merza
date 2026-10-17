<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Services\WebhookDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SendWebhookJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public int $timeout = 30;

    public int $uniqueFor = 3600;

    public readonly string $eventId;

    public readonly string $occurredAt;

    /** @param array<string, mixed> $data */
    public function __construct(
        public readonly int $webhookId,
        public readonly string $topic,
        public readonly array $data,
        ?string $eventId = null,
        ?string $occurredAt = null,
    ) {
        $this->eventId = $eventId ?: 'evt_'.(string) Str::uuid();
        $this->occurredAt = $occurredAt ?: now()->toIso8601String();
    }

    public function uniqueId(): string
    {
        return $this->webhookId.':'.$this->eventId;
    }

    public function handle(WebhookDeliveryService $deliveryService): void
    {
        $webhook = Webhook::query()->find($this->webhookId);

        if (! $webhook || ! $webhook->isActive()) {
            return;
        }

        $deliveryService->deliver(
            $webhook,
            $this->topic,
            $this->data,
            $this->eventId,
            $this->occurredAt,
            max(1, $this->attempts()),
            $this->tries,
        );
    }

    public function failed(\Throwable $exception): void
    {
        app(WebhookDeliveryService::class)->markFailed($this->webhookId, $this->eventId, $exception);
    }
}
