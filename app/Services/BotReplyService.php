<?php

namespace App\Services;

use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotReplyService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    public function __construct(private readonly BotSetting $settings) {}

    /**
     * Generate an AI reply for an inbound WhatsApp message.
     */
    public function generateReply(Contact $contact, string $inboundMessage, Conversation $inboundConversation): ?string
    {
        $apiKey = $this->settings->anthropic_api_key;

        if (empty($apiKey)) {
            Log::warning('BotReplyService: No Anthropic API key configured');
            return null;
        }

        // Get context: most recent lead for this contact
        $lead = Lead::where('contact_id', $contact->id)
                    ->latest()
                    ->first();

        // Get recent conversation history (last 5 messages)
        $history = Conversation::where('contact_id', $contact->id)
                               ->where('channel', 'whatsapp')
                               ->where('id', '!=', $inboundConversation->id)
                               ->latest('sent_at')
                               ->limit(5)
                               ->get()
                               ->reverse();

        $prompt = $this->buildPrompt($contact, $inboundMessage, $lead, $history->all());

        $response = Http::timeout(30)
            ->withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => self::API_VERSION,
                'content-type'      => 'application/json',
            ])
            ->post(self::API_URL, [
                'model'      => $this->settings->anthropic_model ?? 'claude-sonnet-4-6',
                'max_tokens' => 300,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            Log::error('BotReplyService: API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        return $response->json('content.0.text');
    }

    private function buildPrompt(Contact $contact, string $inboundMessage, ?Lead $lead, array $history): string
    {
        $template = $this->settings->wa_reply_prompt_template ?? BotSetting::defaultWaReplyPrompt();

        $productInterest = $lead?->product_interest ?? 'Merza products';

        // Build conversation history context
        $historyText = '';
        foreach ($history as $msg) {
            $role = $msg->direction === 'inbound' ? 'Customer' : 'Merza Bot';
            $historyText .= "{$role}: {$msg->message}\n";
        }

        $contextBlock = $historyText
            ? "\nRecent conversation history:\n{$historyText}\n"
            : '';

        return str_replace(
            ['{{customer_name}}', '{{customer_message}}', '{{product_interest}}', '{{conversation_history}}'],
            [$contact->name, $inboundMessage, $productInterest, $contextBlock],
            $template
        ) . $contextBlock;
    }
}
