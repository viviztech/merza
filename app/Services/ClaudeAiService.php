<?php

namespace App\Services;

use App\Models\BotSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeAiService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    public function __construct(private readonly BotSetting $settings) {}

    /**
     * Generate a WhatsApp follow-up message for a Meta Ads lead.
     *
     * @param array $leadFields  Parsed lead form fields (name, phone, city, etc.)
     * @param string|null $productInterest  Product the lead enquired about
     */
    public function generateFollowUpMessage(array $leadFields, ?string $productInterest = null): ?string
    {
        $apiKey = $this->settings->anthropic_api_key;

        if (empty($apiKey)) {
            Log::warning('ClaudeAiService: No Anthropic API key configured');
            return null;
        }

        $prompt = $this->buildPrompt($leadFields, $productInterest);

        $response = Http::timeout(30)
            ->withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => self::API_VERSION,
                'content-type'      => 'application/json',
            ])
            ->post(self::API_URL, [
                'model'      => $this->settings->anthropic_model ?? 'claude-sonnet-4-6',
                'max_tokens' => 400,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            Log::error('ClaudeAiService: API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        return $response->json('content.0.text');
    }

    private function buildPrompt(array $leadFields, ?string $productInterest): string
    {
        $template = $this->settings->follow_up_prompt_template ?? '';

        $name    = $leadFields['full_name'] ?? $leadFields['name'] ?? 'there';
        $city    = $leadFields['city'] ?? $leadFields['location'] ?? 'your city';
        $product = $productInterest ?? $leadFields['product'] ?? 'our premium fruits';

        return str_replace(
            ['{{customer_name}}', '{{city}}', '{{product_interest}}'],
            [$name, $city, $product],
            $template
        );
    }
}
