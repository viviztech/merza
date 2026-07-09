<?php

namespace App\Jobs;

use App\Models\BotActivityLog;
use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Lead;
use App\Services\BotReplyService;
use App\Services\SarvamService;
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

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly string  $fromPhone,
        public readonly string  $waMessageId,
        public readonly string  $body,
        public readonly int     $timestamp,
        public readonly string  $messageType = 'text',  // 'text' | 'audio'
        public readonly ?string $mediaId     = null,
        public readonly ?array  $referral    = null,    // CTWA ad referral data
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

        // ── CTWA: auto-create Lead ────────────────────────────────────────────
        if ($isCTWA && $settings->auto_create_lead) {
            $alreadyHasLead = Lead::where('contact_id', $contact->id)
                ->where('source', 'meta_ads')
                ->whereDate('created_at', today())
                ->exists();

            if (! $alreadyHasLead) {
                $lead = Lead::create([
                    'contact_id' => $contact->id,
                    'stage'      => 'new',
                    'source'     => 'meta_ads',
                    'notes'      => 'Auto-created from Click-to-WhatsApp ad.'
                        . ($this->referral['headline'] ? ' Ad: ' . $this->referral['headline'] : ''),
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

        if ($botEnabled) {
            $replyService = new BotReplyService($settings);
            $replyMessage = $replyService->generateReply($contact, $messageText, $conversation);

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
