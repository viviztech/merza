<?php

namespace App\Services;

use App\Models\BotSetting;

class LeadFollowUpAiService
{
    public function __construct(private readonly BotSetting $settings) {}

    /**
     * Generate a WhatsApp follow-up message for a Meta Ads lead, using
     * whichever AI provider is set active in Bot Settings.
     *
     * @param array $leadFields  Parsed lead form fields (name, phone, city, etc.)
     * @param string|null $productInterest  Product the lead enquired about
     */
    public function generateFollowUpMessage(array $leadFields, ?string $productInterest = null): ?string
    {
        $prompt = $this->buildPrompt($leadFields, $productInterest);

        return (new AiProviderService($this->settings))->chat($prompt, [
            ['role' => 'user', 'content' => 'Write the follow-up message now.'],
        ], 400);
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
