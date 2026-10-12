<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WebhookDeliveryService
{
    public function deliver(Webhook $webhook, string $topic, array $data): void
    {
        if (blank($webhook->secret)) {
            throw new RuntimeException("Webhook [{$webhook->id}] does not have a signing secret.");
        }

        if (! str_starts_with($webhook->delivery_url, 'https://')) {
            throw new RuntimeException("Webhook [{$webhook->id}] delivery URL must use HTTPS.");
        }

        $payload = [
            'event' => $topic,
            'created_at' => now()->toIso8601String(),
            'data' => $data,
        ];
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, $webhook->secret);

        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'topic' => $topic,
            'status' => 'pending',
            'attempts' => 1,
            'payload_hash' => hash('sha256', $body),
        ]);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Merza-Webhooks/1.0',
                    'X-Merza-Event' => $topic,
                    'X-Merza-API-Version' => $webhook->api_version ?: 'v1',
                    'X-Merza-Timestamp' => $timestamp,
                    'X-Merza-Signature' => 'sha256=' . $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($webhook->delivery_url);

            $response->throw();

            $deliveredAt = now();
            $delivery->update([
                'status' => 'success',
                'response_status' => $response->status(),
                'delivered_at' => $deliveredAt,
            ]);
            $webhook->update([
                'last_delivered_at' => $deliveredAt,
                'last_response_status' => $response->status(),
                'last_error' => null,
            ]);
        } catch (\Throwable $e) {
            $delivery->update([
                'status' => 'failed',
                'response_status' => isset($response) ? $response->status() : null,
                'error' => $e->getMessage(),
            ]);
            $webhook->update([
                'last_response_status' => isset($response) ? $response->status() : null,
                'last_error' => $e->getMessage(),
            ]);

            Log::warning('Webhook delivery failed.', [
                'webhook_id' => $webhook->id,
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
