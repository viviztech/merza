<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Webhook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookDispatcher
{
    public function dispatch(
        string $topic,
        array $data,
        ?string $eventId = null,
        ?string $occurredAt = null,
    ): void {
        if (! array_key_exists($topic, Webhook::TOPICS)) {
            Log::warning('Webhook dispatch was skipped for an unsupported topic.', ['topic' => $topic]);

            return;
        }

        $eventId ??= 'evt_'.(string) Str::uuid();
        $occurredAt ??= now()->toIso8601String();

        try {
            Webhook::query()
                ->active()
                ->where('topic', $topic)
                ->pluck('id')
                ->each(fn (int $webhookId) => SendWebhookJob::dispatch(
                    $webhookId,
                    $topic,
                    $data,
                    $eventId,
                    $occurredAt,
                )->afterCommit());
        } catch (\Throwable $e) {
            // A webhook must never break checkout, cart, or CRM writes.
            Log::warning('Webhook dispatch could not be queued.', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function dispatchCartAdded(): void
    {
        $cart = app(CartService::class);

        $this->dispatch('cart.added', [
            ...app(WebhookPayloadFactory::class)->cart($cart),
            'source' => 'website',
        ]);
    }

    public function dispatchCheckoutStarted(): void
    {
        $cart = app(CartService::class);

        if ($cart->count() === 0) {
            return;
        }

        $this->dispatch('checkout.started', [
            'checkout' => [
                'id' => session()->getId(),
                'currency' => 'INR',
                'source' => 'website',
            ],
            ...app(WebhookPayloadFactory::class)->cart($cart),
            'source' => 'website',
        ]);
    }

    public function dispatchCustomer(string $topic, Contact $contact): void
    {
        $this->dispatch($topic, app(WebhookPayloadFactory::class)->contact($contact));
    }

    public function dispatchOrder(
        string $topic,
        Order $order,
        ?string $eventId = null,
        ?string $occurredAt = null,
    ): void {
        $this->dispatch(
            $topic,
            app(WebhookPayloadFactory::class)->order($order),
            $eventId,
            $occurredAt,
        );
    }
}
