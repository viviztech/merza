<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class WebhookDeliveryService
{
    private const LOCK_TIMEOUT_SECONDS = 120;

    /**
     * Deliver a configured webhook exactly once for an event ID. Queue retries
     * reuse the persisted request body, so the HMAC always covers the same
     * bytes and receivers can safely deduplicate on X-Merza-Event-ID.
     *
     * @param  array<string, mixed>  $data
     */
    public function deliver(
        Webhook $webhook,
        string $topic,
        array $data,
        string $eventId,
        string $occurredAt,
        int $attempt = 1,
        int $maxAttempts = 3,
    ): void {
        $body = $this->encodePayload($topic, $eventId, $occurredAt, $webhook->api_version ?: 'v1', $data);
        $delivery = $this->findOrCreateDelivery($webhook, $topic, $eventId, $body);

        if ($delivery->status === 'success') {
            return;
        }

        // A duplicate queue message must not produce a concurrent HTTP POST.
        // Locks automatically expire so a worker crash remains recoverable.
        $claimed = WebhookDelivery::query()
            ->whereKey($delivery->id)
            ->where('status', '!=', 'success')
            ->where(function ($query): void {
                $query->whereNull('locked_at')
                    ->orWhere('locked_at', '<=', now()->subSeconds(self::LOCK_TIMEOUT_SECONDS));
            })
            ->update([
                'status' => 'sending',
                'attempts' => DB::raw('attempts + 1'),
                'last_attempted_at' => now(),
                'locked_at' => now(),
            ]);

        if ($claimed !== 1) {
            return;
        }

        $delivery->refresh();
        $body = $delivery->request_body ?: $body;
        $response = null;

        try {
            $timestamp = (string) CarbonImmutable::parse($occurredAt)->timestamp;
            $this->validateDestination($webhook->delivery_url, $webhook->secret, "Webhook [{$webhook->id}]");

            $response = $this->post(
                $webhook->delivery_url,
                $webhook->secret,
                $topic,
                $eventId,
                $timestamp,
                $webhook->api_version ?: 'v1',
                $body,
            );

            if (! $response->successful()) {
                throw new RuntimeException($this->responseFailureMessage($response));
            }

            $deliveredAt = now();
            $delivery->update([
                'status' => 'success',
                'response_status' => $response->status(),
                'response_body' => $this->truncate($response->body()),
                'response_headers' => $this->responseHeaders($response),
                'error' => null,
                'locked_at' => null,
                'delivered_at' => $deliveredAt,
            ]);
            $webhook->update([
                'last_delivered_at' => $deliveredAt,
                'last_response_status' => $response->status(),
                'last_error' => null,
            ]);
        } catch (\Throwable $exception) {
            $error = $this->truncate($exception->getMessage());
            $status = $response?->status();

            $delivery->update([
                'status' => $attempt >= $maxAttempts ? 'failed' : 'retrying',
                'response_status' => $status,
                'response_body' => $response ? $this->truncate($response->body()) : null,
                'response_headers' => $response ? $this->responseHeaders($response) : null,
                'error' => $error,
                'locked_at' => null,
            ]);
            $webhook->update([
                'last_response_status' => $status,
                'last_error' => $error,
            ]);

            Log::warning('Webhook delivery failed.', [
                'webhook_id' => $webhook->id,
                'event_id' => $eventId,
                'topic' => $topic,
                'attempt' => $attempt,
                'status' => $status,
                'error' => $error,
            ]);

            throw $exception;
        }
    }

    /**
     * Backwards-compatible delivery for the old Integration Settings order
     * webhook. New configured Webhooks always use deliver() and its logs.
     *
     * @param  array<string, mixed>  $data
     */
    public function deliverLegacy(
        string $deliveryUrl,
        string $secret,
        string $topic,
        array $data,
        string $eventId,
        string $occurredAt,
    ): void {
        $this->validateDestination($deliveryUrl, $secret, 'Legacy order webhook');

        $body = $this->encodePayload($topic, $eventId, $occurredAt, 'v1', $data);
        $timestamp = (string) CarbonImmutable::parse($occurredAt)->timestamp;
        $response = $this->post($deliveryUrl, $secret, $topic, $eventId, $timestamp, 'v1', $body);

        if (! $response->successful()) {
            throw new RuntimeException($this->responseFailureMessage($response));
        }
    }

    public function markFailed(int $webhookId, string $eventId, \Throwable $exception): void
    {
        WebhookDelivery::query()
            ->where('webhook_id', $webhookId)
            ->where('event_id', $eventId)
            ->where('status', '!=', 'success')
            ->update([
                'status' => 'failed',
                'error' => $this->truncate($exception->getMessage()),
                'locked_at' => null,
            ]);
    }

    /** @param array<string, mixed> $data */
    private function encodePayload(string $topic, string $eventId, string $occurredAt, string $apiVersion, array $data): string
    {
        return json_encode([
            'event_id' => $eventId,
            'event' => $topic,
            'timestamp' => $occurredAt,
            // Kept for receivers that adopted the first version of this API.
            'created_at' => $occurredAt,
            'api_version' => $apiVersion,
            'data' => $data,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    private function findOrCreateDelivery(Webhook $webhook, string $topic, string $eventId, string $body): WebhookDelivery
    {
        try {
            return WebhookDelivery::query()->firstOrCreate(
                [
                    'webhook_id' => $webhook->id,
                    'event_id' => $eventId,
                ],
                [
                    'topic' => $topic,
                    'status' => 'pending',
                    'attempts' => 0,
                    'payload_hash' => hash('sha256', $body),
                    'request_body' => $body,
                ],
            );
        } catch (QueryException) {
            // The unique index is the final line of defence if two workers
            // receive the same event at the same time.
            return WebhookDelivery::query()
                ->where('webhook_id', $webhook->id)
                ->where('event_id', $eventId)
                ->firstOrFail();
        }
    }

    private function validateDestination(string $deliveryUrl, ?string $secret, string $label): void
    {
        if (blank($secret)) {
            throw new RuntimeException("{$label} does not have a signing secret.");
        }

        if (! str_starts_with($deliveryUrl, 'https://')) {
            throw new RuntimeException("{$label} delivery URL must use HTTPS.");
        }
    }

    private function post(
        string $deliveryUrl,
        string $secret,
        string $topic,
        string $eventId,
        string $timestamp,
        string $apiVersion,
        string $body,
    ): Response {
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

        return Http::acceptJson()
            ->timeout(15)
            ->connectTimeout(5)
            ->withHeaders([
                'User-Agent' => 'Merza-Webhooks/1.0',
                'X-Merza-Event' => $topic,
                'X-Merza-Event-ID' => $eventId,
                'Idempotency-Key' => $eventId,
                'X-Merza-API-Version' => $apiVersion,
                'X-Merza-Timestamp' => $timestamp,
                'X-Merza-Signature' => 'sha256='.$signature,
            ])
            ->withBody($body, 'application/json')
            ->post($deliveryUrl);
    }

    /** @return array<string, array<int, string>> */
    private function responseHeaders(Response $response): array
    {
        return collect($response->headers())
            ->reject(fn ($value, string $key): bool => in_array(strtolower($key), ['set-cookie', 'authorization'], true))
            ->map(fn ($values): array => collect($values)
                ->map(fn ($value): string => $this->truncate((string) $value, 1000) ?? '')
                ->values()
                ->all())
            ->all();
    }

    private function responseFailureMessage(Response $response): string
    {
        $body = $this->truncate(trim($response->body()), 1000);

        return 'Webhook endpoint returned HTTP '.$response->status().($body !== '' ? ': '.$body : '');
    }

    private function truncate(?string $value, int $length = 16000): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::limit($value, $length, '...');
    }
}
