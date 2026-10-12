<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Services\WebhookDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $webhookId,
        public readonly string $topic,
        public readonly array $data,
    ) {}

    public function handle(WebhookDeliveryService $deliveryService): void
    {
        $webhook = Webhook::query()->find($this->webhookId);

        if (! $webhook || ! $webhook->isActive()) {
            return;
        }

        $deliveryService->deliver($webhook, $this->topic, $this->data);
    }
}
