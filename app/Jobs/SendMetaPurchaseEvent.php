<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\MetaConversionsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMetaPurchaseEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $orderId) {}

    public function handle(MetaConversionsService $meta): void
    {
        $order = Order::with('items')->find($this->orderId);

        // The job may run after a refund or a later correction. A purchase is
        // sent only for an order that remains server-verified as paid.
        if (! $order || $order->payment_status !== 'paid') {
            return;
        }

        // Disabled tracking and missing consent are expected no-ops, not
        // delivery failures. A configured, consented event that fails must
        // fail the job so the queue can retry transient Meta outages.
        if (! $meta->isConfigured() || ! $meta->consentGranted($order->meta_tracking_consent)) {
            return;
        }

        if (! $meta->trackPurchase($order)) {
            throw new \RuntimeException('Meta purchase event delivery failed.');
        }
    }
}
