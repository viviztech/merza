<?php

namespace App\Services;

use App\Models\BotSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private const GRAPH_URL = 'https://graph.facebook.com/v25.0';

    public function __construct(private readonly BotSetting $settings) {}

    /**
     * Send a text message via WhatsApp Cloud API.
     * Returns the wa_message_id on success, null on failure.
     */
    public function sendTextMessage(string $toPhone, string $body): ?string
    {
        $phoneNumberId = $this->settings->whatsapp_phone_number_id;
        $token         = $this->settings->whatsapp_access_token;

        if (empty($phoneNumberId) || empty($token)) {
            Log::warning('WhatsAppService: Missing phone_number_id or access_token');
            return null;
        }

        // Normalise phone: strip non-digit except leading +
        $to = preg_replace('/[^0-9]/', '', $toPhone);

        $response = Http::timeout(15)
            ->withToken($token)
            ->post(self::GRAPH_URL . "/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $body],
            ]);

        if ($response->failed()) {
            Log::error('WhatsAppService: Send failed', [
                'to'     => $to,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        return $response->json('messages.0.id');
    }

    /**
     * Send an interactive message (button or list) via WhatsApp Cloud API.
     */
    public function sendInteractiveMessage(string $toPhone, array $interactive): ?string
    {
        $phoneNumberId = $this->settings->whatsapp_phone_number_id;
        $token         = $this->settings->whatsapp_access_token;

        if (empty($phoneNumberId) || empty($token)) {
            Log::warning('WhatsAppService: Missing phone_number_id or access_token');
            return null;
        }

        $to = preg_replace('/[^0-9]/', '', $toPhone);

        $response = Http::timeout(15)
            ->withToken($token)
            ->post(self::GRAPH_URL . "/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'interactive',
                'interactive'       => $interactive,
            ]);

        if ($response->failed()) {
            Log::error('WhatsAppService: Interactive send failed', [
                'to'     => $to,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        return $response->json('messages.0.id');
    }

    /**
     * Mark a message as read (shows double blue tick to customer).
     */
    public function markRead(string $waMessageId): void
    {
        $phoneNumberId = $this->settings->whatsapp_phone_number_id;
        $token         = $this->settings->whatsapp_access_token;

        if (empty($phoneNumberId) || empty($token)) {
            return;
        }

        Http::timeout(10)
            ->withToken($token)
            ->post(self::GRAPH_URL . "/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'status'            => 'read',
                'message_id'        => $waMessageId,
            ]);
    }

    /**
     * Parse the webhook payload for inbound WhatsApp message events.
     * Handles both text and audio (voice) messages.
     *
     * @return array<array{from: string, wa_message_id: string, body: string, timestamp: string, type: string, media_id: string|null}>
     */
    public function parseInboundMessages(array $payload): array
    {
        $messages = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $msg) {
                    $type = $msg['type'] ?? '';

                    $referral = isset($msg['referral']) ? [
                        'source_url'  => $msg['referral']['source_url'] ?? null,
                        'source_id'   => $msg['referral']['source_id'] ?? null,
                        'source_type' => $msg['referral']['source_type'] ?? null,
                        'headline'    => $msg['referral']['headline'] ?? null,
                        'body'        => $msg['referral']['body'] ?? null,
                        'media_type'  => $msg['referral']['media_type'] ?? null,
                        'ctwa_clid'   => $msg['referral']['ctwa_clid'] ?? null,
                    ] : null;

                    if ($type === 'text') {
                        $messages[] = [
                            'from'            => $msg['from'] ?? '',
                            'wa_message_id'   => $msg['id'] ?? '',
                            'body'            => $msg['text']['body'] ?? '',
                            'timestamp'       => $msg['timestamp'] ?? now()->timestamp,
                            'type'            => 'text',
                            'media_id'        => null,
                            'interactive_id'  => null,
                            'phone_number_id' => $value['metadata']['phone_number_id'] ?? '',
                            'referral'        => $referral,
                        ];
                    } elseif ($type === 'audio') {
                        $messages[] = [
                            'from'            => $msg['from'] ?? '',
                            'wa_message_id'   => $msg['id'] ?? '',
                            'body'            => '',
                            'timestamp'       => $msg['timestamp'] ?? now()->timestamp,
                            'type'            => 'audio',
                            'media_id'        => $msg['audio']['id'] ?? null,
                            'interactive_id'  => null,
                            'phone_number_id' => $value['metadata']['phone_number_id'] ?? '',
                            'referral'        => $referral,
                        ];
                    } elseif ($type === 'interactive') {
                        $iType   = $msg['interactive']['type'] ?? '';
                        $replyId = $iType === 'button_reply'
                            ? ($msg['interactive']['button_reply']['id'] ?? '')
                            : ($msg['interactive']['list_reply']['id'] ?? '');
                        $replyTitle = $iType === 'button_reply'
                            ? ($msg['interactive']['button_reply']['title'] ?? '')
                            : ($msg['interactive']['list_reply']['title'] ?? '');

                        $messages[] = [
                            'from'            => $msg['from'] ?? '',
                            'wa_message_id'   => $msg['id'] ?? '',
                            'body'            => $replyTitle,
                            'timestamp'       => $msg['timestamp'] ?? now()->timestamp,
                            'type'            => 'interactive',
                            'media_id'        => null,
                            'interactive_id'  => $replyId,
                            'phone_number_id' => $value['metadata']['phone_number_id'] ?? '',
                            'referral'        => $referral,
                        ];
                    }
                }
            }
        }

        return $messages;
    }

    /**
     * Download a WhatsApp media file. Returns ['content' => binary, 'mime_type' => '...'] or null.
     */
    public function downloadMedia(string $mediaId): ?array
    {
        $token = $this->settings->whatsapp_access_token;

        if (empty($token)) {
            return null;
        }

        // Step 1: resolve the media URL
        $meta = Http::timeout(15)
            ->withToken($token)
            ->get(self::GRAPH_URL . "/{$mediaId}");

        if ($meta->failed()) {
            Log::error('WhatsAppService: failed to resolve media URL', ['media_id' => $mediaId]);
            return null;
        }

        $url      = $meta->json('url');
        $mimeType = $meta->json('mime_type', 'audio/ogg');

        if (empty($url)) {
            return null;
        }

        // Step 2: download the binary content
        $file = Http::timeout(30)
            ->withToken($token)
            ->get($url);

        if ($file->failed()) {
            Log::error('WhatsAppService: failed to download media', ['url' => $url]);
            return null;
        }

        return [
            'content'   => $file->body(),
            'mime_type' => $mimeType,
        ];
    }

    /**
     * Check if a webhook payload is a WhatsApp messages event.
     */
    public function isWhatsAppWebhook(array $payload): bool
    {
        return ($payload['object'] ?? '') === 'whatsapp_business_account';
    }
}
