<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\Contact;
use App\Models\Webhook;
use Illuminate\Support\Facades\Log;

class WebhookDispatcher
{
    public function dispatch(string $topic, array $data): void
    {
        try {
            Webhook::query()
                ->active()
                ->where('topic', $topic)
                ->pluck('id')
                ->each(fn (int $webhookId) => SendWebhookJob::dispatch($webhookId, $topic, $data));
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
            ...app(WebhookPayloadFactory::class)->cart($cart),
            'source' => 'website',
        ]);
    }

    public function dispatchCustomer(string $topic, Contact $contact): void
    {
        $this->dispatch($topic, app(WebhookPayloadFactory::class)->contact($contact));
    }
}
