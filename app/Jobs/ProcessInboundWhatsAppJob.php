<?php

namespace App\Jobs;

use App\Models\BotActivityLog;
use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\WhatsAppSession;
use App\Services\BotReplyService;
use App\Services\SarvamService;
use App\Services\WhatsAppFlowService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessInboundWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly string  $fromPhone,
        public readonly string  $waMessageId,
        public readonly string  $body,
        public readonly int     $timestamp,
        public readonly string  $messageType    = 'text',  // 'text' | 'audio' | 'image' | 'interactive'
        public readonly ?string $mediaId        = null,
        public readonly ?array  $referral       = null,    // CTWA ad referral data
        public readonly ?string $interactiveId  = null,   // button/list reply ID
    ) {}

    public function handle(): void
    {
        // Deduplicate
        if (Conversation::where('wa_message_id', $this->waMessageId)->exists()) {
            Log::info('ProcessInboundWhatsAppJob: duplicate, skipping', ['wa_id' => $this->waMessageId]);
            return;
        }

        $settings = BotSetting::current();

        // ── Resolve message text ──────────────────────────────────────────────
        $messageText = $this->body;
        $wasVoice    = false;
        $mediaPath   = null;  // relative disk path — matches Order.payment_screenshot_path's convention
        $mediaUrl    = null;  // resolved public URL — what Conversation.media_url renders directly

        if ($this->messageType === 'image' && $this->mediaId) {
            $mediaPath = $this->downloadAndStoreImage($settings);

            if (empty($mediaPath)) {
                Log::warning('ProcessInboundWhatsAppJob: image download/store failed', [
                    'media_id' => $this->mediaId,
                    'from'     => $this->fromPhone,
                ]);
            } else {
                $mediaUrl = Storage::disk(config('media-library.disk_name', 'r2'))->url($mediaPath);
            }

            // Caption (if any) becomes the visible message text; otherwise a placeholder.
            $messageText = $messageText !== '' ? $messageText : '[Image]';
        }

        if ($this->messageType === 'audio' && $this->mediaId) {
            $messageText = $this->transcribeAudio($settings);

            if (empty($messageText)) {
                Log::warning('ProcessInboundWhatsAppJob: audio transcription failed', [
                    'media_id' => $this->mediaId,
                    'from'     => $this->fromPhone,
                ]);
                // Still record the conversation as "[Voice message]" and skip bot reply
                $messageText = '[Voice message — transcription unavailable]';
            } else {
                $wasVoice = true;
                Log::info('ProcessInboundWhatsAppJob: voice transcribed', ['text' => $messageText]);
            }
        }

        // ── Find or create contact ────────────────────────────────────────────
        $phone   = preg_replace('/[^0-9+]/', '', $this->fromPhone);
        $contact = Contact::where('phone', $phone)
                          ->orWhere('phone', ltrim($phone, '+'))
                          ->first();

        $isCTWA = ! empty($this->referral);

        if (! $contact) {
            $contact = Contact::create([
                'name'   => 'WA: ' . $phone,
                'phone'  => $phone,
                'source' => $isCTWA ? 'meta_ads' : 'whatsapp',
                'tags'   => $isCTWA ? ['whatsapp_inbound', 'ctwa_lead'] : ['whatsapp_inbound'],
            ]);
        } elseif ($isCTWA) {
            // Tag existing contact as coming from ad if not already tagged
            $tags = $contact->tags ?? [];
            if (! in_array('ctwa_lead', $tags)) {
                $contact->update(['tags' => array_merge($tags, ['ctwa_lead'])]);
            }
        }

        // ── Store inbound conversation ────────────────────────────────────────
        $conversation = Conversation::create([
            'contact_id'    => $contact->id,
            'channel'       => 'whatsapp',
            'direction'     => 'inbound',
            'message'       => $wasVoice ? "🎤 {$messageText}" : $messageText,
            'wa_message_id' => $this->waMessageId,
            'ctwa_referral' => $this->referral,
            'media_url'     => $mediaUrl,
            'is_bot'        => false,
            'sent_at'       => now()->setTimestamp($this->timestamp),
            'status'        => 'read',
        ]);

        // Mark read (double blue tick)
        $waService = new WhatsAppService($settings);
        $waService->markRead($this->waMessageId);

        // ── Activity log ──────────────────────────────────────────────────────
        BotActivityLog::create([
            'event_type'   => 'webhook_received',
            'meta_lead_id' => $this->waMessageId,
            'contact_id'   => $contact->id,
            'raw_payload'  => [
                'from'      => $this->fromPhone,
                'message'   => $messageText,
                'wa_id'     => $this->waMessageId,
                'type'      => $this->messageType,
                'was_voice' => $wasVoice,
                'ctwa'      => $isCTWA,
                'referral'  => $this->referral,
            ],
            'status' => 'success',
        ]);

        // ── Auto-create Lead for every enquiry with no active lead ────────────
        // Every inbound WhatsApp message enters the pipeline automatically —
        // not just Click-to-WhatsApp ad clicks — so staff never have to
        // remember to log an organic "hi, do you have mangoes?" enquiry by
        // hand. A Contact already mid-pipeline (stage not converted/lost)
        // doesn't get a duplicate.
        if ($settings->auto_create_lead) {
            $hasActiveLead = Lead::where('contact_id', $contact->id)
                ->whereNotIn('stage', ['converted', 'lost'])
                ->exists();

            if (! $hasActiveLead) {
                $lead = Lead::create([
                    'contact_id' => $contact->id,
                    'stage'      => 'new',
                    'source'     => $isCTWA ? 'meta_ads' : 'whatsapp',
                    'notes'      => $isCTWA
                        ? 'Auto-created from Click-to-WhatsApp ad.' . ($this->referral['headline'] ? ' Ad: ' . $this->referral['headline'] : '')
                        : 'Auto-created from inbound WhatsApp message.',
                ]);

                BotActivityLog::create([
                    'event_type'   => 'lead_created',
                    'meta_lead_id' => $this->waMessageId,
                    'contact_id'   => $contact->id,
                    'lead_id'      => $lead->id,
                    'status'       => 'success',
                ]);
            }
        }

        // ── Bot auto-reply ────────────────────────────────────────────────────
        $botEnabled   = $settings->wa_bot_enabled;
        $voiceEnabled = $settings->voice_bot_enabled;

        // Skip bot reply for untranscribable audio
        if ($this->messageType === 'audio' && ! $wasVoice) {
            return;
        }

        // Skip voice bot reply if voice bot is not enabled
        if ($wasVoice && ! $voiceEnabled) {
            return;
        }

        if (! $botEnabled) {
            return;
        }

        // ── Meta policy: never send automated messages to opted-out contacts ──
        if ($contact->wa_opted_out) {
            return;
        }

        // Images are captured and attached to the conversation above. Only the
        // payment-screenshot moment does anything further with one — anywhere
        // else, a photo is logged but doesn't trigger a flow/AI reply.
        if ($this->messageType === 'image') {
            $session = WhatsAppSession::getOrCreate($contact->phone);
            if ($session->state !== 'awaiting_payment_ref' || empty($mediaPath)) {
                return;
            }
        }

        // ── Structured flow first ─────────────────────────────────────────────
        $flowService = new WhatsAppFlowService($waService, $settings);
        $flowHandled = $flowService->handle(
            $contact,
            $this->messageType,
            $messageText,
            $this->interactiveId ?? '',
            $mediaPath,
        );

        if ($flowHandled) {
            BotActivityLog::create([
                'event_type'   => 'flow_reply_sent',
                'meta_lead_id' => $this->waMessageId,
                'contact_id'   => $contact->id,
                'status'       => 'success',
            ]);
            return;
        }

        // ── AI fallback for free text (ordering/talk-to-us states, or a mid-flow
        // distraction handed off by WhatsAppFlowService) — session is passed so
        // the reply is aware of a cart/checkout already in progress.
        $session      = WhatsAppSession::getOrCreate($contact->phone);
        $replyService = new BotReplyService($settings);
        $replyMessage = $replyService->generateReply($contact, $messageText, $conversation, $session);

        if ($replyMessage) {
            $draft = Conversation::create([
                'contact_id'    => $contact->id,
                'channel'       => 'whatsapp',
                'direction'     => 'outbound',
                'message'       => $replyMessage,
                'is_bot'        => true,
                'replied_to_id' => $conversation->id,
                'sent_at'       => null,
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

            if ($settings->wa_auto_send) {
                SendWhatsAppMessageJob::dispatch($draft->id);
            }
        }
    }

    // ── Private: inbound image storage ──────────────────────────────────────

    private function downloadAndStoreImage(BotSetting $settings): ?string
    {
        $waService = new WhatsAppService($settings);
        $media     = $waService->downloadMedia($this->mediaId);

        if (! $media) {
            return null;
        }

        $extension = match ($media['mime_type']) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        $path = "whatsapp-inbound/{$this->waMessageId}.{$extension}";
        Storage::disk(config('media-library.disk_name', 'r2'))->put($path, $media['content']);

        // Relative path, not a URL — matches Order.payment_screenshot_path's
        // existing convention (see getPaymentScreenshotUrlAttribute()); the
        // caller resolves a URL separately when one is actually needed.
        return $path;
    }

    // ── Private: audio transcription ──────────────────────────────────────────

    private function transcribeAudio(BotSetting $settings): ?string
    {
        if (empty($settings->sarvam_api_key)) {
            Log::warning('ProcessInboundWhatsAppJob: sarvam_api_key not configured');
            return null;
        }

        $waService = new WhatsAppService($settings);
        $media     = $waService->downloadMedia($this->mediaId);

        if (! $media) {
            return null;
        }

        $sarvam = new SarvamService($settings->sarvam_api_key);

        return $sarvam->transcribe(
            audioContent: $media['content'],
            mimeType:     $media['mime_type'],
            languageCode: 'unknown', // auto-detect Tamil / English
        );
    }
}
