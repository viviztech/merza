<?php

namespace App\Jobs;

use App\Models\BotSetting;
use App\Models\Conversation;
use App\Services\WhatsAppService;
use App\Services\WhatsAppMessagePolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public readonly int $conversationId) {}

    public function handle(): void
    {
        $conversation = Conversation::with('contact')->find($this->conversationId);

        if (! $conversation) {
            Log::warning('SendWhatsAppMessageJob: Conversation not found', ['id' => $this->conversationId]);
            return;
        }

        if ($conversation->sent_at !== null) {
            Log::info('SendWhatsAppMessageJob: Already sent, skipping', ['id' => $this->conversationId]);
            return;
        }

        $phone = $conversation->contact?->phone;
        if (! $phone) {
            Log::error('SendWhatsAppMessageJob: Contact has no phone', ['conversation' => $this->conversationId]);
            $conversation->update(['status' => 'failed']);
            return;
        }

        if (! app(WhatsAppMessagePolicy::class)->canSendFreeform($conversation->contact)) {
            $conversation->update([
                'status' => 'failed',
                'failure_reason' => 'Free-form reply unavailable: contact opted out, is blocked, or the 24-hour reply window closed.',
            ]);
            return;
        }

        $settings = BotSetting::current();
        $service  = new WhatsAppService($settings);

        $waMessageId = $service->sendTextMessage($phone, $conversation->message);

        if ($waMessageId) {
            $conversation->update([
                'wa_message_id' => $waMessageId,
                'sent_at'       => now(),
                'status'        => 'sent',
            ]);

            Log::info('SendWhatsAppMessageJob: Sent', [
                'conversation' => $this->conversationId,
                'wa_id'        => $waMessageId,
            ]);
        } else {
            $conversation->update([
                'status' => 'failed',
                'failure_reason' => 'WhatsApp did not accept this message. Check the application logs before retrying.',
            ]);
            $this->fail('WhatsApp API returned no message ID');
        }
    }
}
