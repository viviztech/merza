<?php

namespace App\Jobs;

use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\Webhook;
use App\Services\WebhookDeliveryService;
use App\Services\WebhookPayloadFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendOrderWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $orderId,
        public readonly string $event,
    ) {}

    public function handle(): void
    {
        // Once a topic has been configured on the dedicated Webhooks page,
        // those records are authoritative. The legacy Integration Settings
        // fields below remain as a safe compatibility path for existing
        // installations that have not created a Webhooks record yet.
        if (Webhook::query()->where('topic', $this->event)->exists()) {
            $order = Order::with('items')->find($this->orderId);

            if (! $order) {
                Log::warning('Order webhook skipped because the order no longer exists.', [
                    'order_id' => $this->orderId,
                    'event' => $this->event,
                ]);

                return;
            }

            $failure = null;
            $data = app(WebhookPayloadFactory::class)->order($order);

            foreach (Webhook::query()->active()->where('topic', $this->event)->get() as $webhook) {
                try {
                    app(WebhookDeliveryService::class)->deliver($webhook, $this->event, $data);
                } catch (\Throwable $e) {
                    // Try every configured destination before allowing the
                    // queue to retry the failed delivery.
                    $failure = $e;
                }
            }

            if ($failure) {
                throw $failure;
            }

            return;
        }

        $settings = IntegrationSetting::current();

        if (blank($settings->order_webhook_url)) {
            return;
        }

        if (blank($settings->order_webhook_secret)) {
            throw new RuntimeException('Order webhook is configured without a signing secret.');
        }

        if (! str_starts_with($settings->order_webhook_url, 'https://')) {
            throw new RuntimeException('Order webhook URL must use HTTPS.');
        }

        $order = Order::with('items')->find($this->orderId);

        if (! $order) {
            Log::warning('Order webhook skipped because the order no longer exists.', [
                'order_id' => $this->orderId,
                'event' => $this->event,
            ]);

            return;
        }

        $payload = [
            'event' => $this->event,
            'created_at' => now()->toIso8601String(),
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'channel' => $order->channel,
                    'customer' => [
                        'name' => $order->customer_name,
                        'phone' => $order->customer_phone,
                        'email' => $order->customer_email,
                    ],
                    'delivery' => [
                        'address' => $order->delivery_address,
                        'city' => $order->city,
                        'state' => $order->state,
                        'postcode' => $order->postcode,
                        'landmark' => $order->landmark,
                    ],
                    'subtotal' => $order->subtotal,
                    'gst_total' => $order->gst_total,
                    'delivery_fee' => $order->delivery_fee,
                    'packaging_fee' => $order->packaging_fee,
                    'total' => $order->total,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'tracking_number' => $order->tracking_number,
                    'courier_name' => $order->courier_name,
                    'tracking_url' => $order->tracking_url,
                    'created_at' => $order->created_at?->toIso8601String(),
                    'updated_at' => $order->updated_at?->toIso8601String(),
                ],
                'items' => $order->items->map(fn ($item): array => [
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ])->all(),
            ],
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, $settings->order_webhook_secret);

        $response = Http::timeout(10)
            ->withHeaders([
                'X-Merza-Event' => $this->event,
                'X-Merza-Timestamp' => $timestamp,
                'X-Merza-Signature' => 'sha256=' . $signature,
            ])
            ->withBody($body, 'application/json')
            ->post($settings->order_webhook_url);

        $response->throw();

        Log::info('Order webhook delivered.', [
            'order_id' => $order->id,
            'event' => $this->event,
            'status' => $response->status(),
        ]);
    }
}
