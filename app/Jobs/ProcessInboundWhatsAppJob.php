<?php

namespace App\Jobs;

use App\Models\BotActivityLog;
use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Services\BotReplyService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInboundWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly string $fromPhone,
        public readonly string $waMessageId,
        public readonly string $body,
        public readonly int    $timestamp,
    ) {}

    public function handle(): void
    {
        // Deduplicate by wa_message_id
        if (Conversation::where('wa_message_id', $this->waMessageId)->exists()) {
            Log::info('ProcessInboundWhatsAppJob: Duplicate message, skipping', ['wa_id' => $this->waMessageId]);
            return;
        }

        $settings = BotSetting::current();

        // Find or create contact by phone
        $phone   = preg_replace('/[^0-9+]/', '', $this->fromPhone);
        $contact = Contact::where('phone', $phone)
                          ->orWhere('phone', ltrim($phone, '+'))
                          ->first();

        if (! $contact) {
            $contact = Contact::create([
                'name'   => 'WA: ' . $phone,
                'phone'  => $phone,
                'source' => 'whatsapp',
                'tags'   => ['whatsapp_inbound'],
            ]);
        }

        // Store the inbound message
        $conversation = Conversation::create([
            'contact_id'    => $contact->id,
            'channel'       => 'whatsapp',
            'direction'     => 'inbound',
            'message'       => $this->body,
            'wa_message_id' => $this->waMessageId,
            'is_bot'        => false,
            'sent_at'       => now()->setTimestamp($this->timestamp),
            'status'        => 'read',
        ]);

        // Mark as read (blue ticks)
        $waService = new WhatsAppService($settings);
        $waService->markRead($this->waMessageId);

        // Log
        BotActivityLog::create([
            'event_type'   => 'webhook_received',
            'meta_lead_id' => $this->waMessageId,
            'contact_id'   => $contact->id,
            'raw_payload'  => [
                'from'    => $this->fromPhone,
                'message' => $this->body,
                'wa_id'   => $this->waMessageId,
            ],
            'status' => 'success',
        ]);

        // Trigger bot auto-reply if enabled
        if ($settings->wa_bot_enabled) {
            $replyService = new BotReplyService($settings);
            $replyMessage = $replyService->generateReply($contact, $this->body, $conversation);

            if ($replyMessage) {
                $draft = Conversation::create([
                    'contact_id'    => $contact->id,
                    'channel'       => 'whatsapp',
                    'direction'     => 'outbound',
                    'message'       => $replyMessage,
                    'is_bot'        => true,
                    'replied_to_id' => $conversation->id,
                    'sent_at'       => null, // draft until sent
                    'status'        => 'sent',
                ]);

                BotActivityLog::create([
                    'event_type'        => 'message_generated',
                    'meta_lead_id'      => $this->waMessageId,
                    'contact_id'        => $contact->id,
                    'conversation_id'   => $draft->id,
                    'generated_message' => $replyMessage,
                    'status'            => 'success',
                ]);

                // Auto-send if configured
                if ($settings->wa_auto_send) {
                    SendWhatsAppMessageJob::dispatch($draft->id);
                }
            }
        }
    }
}
