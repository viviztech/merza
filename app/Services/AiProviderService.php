<?php

namespace App\Services;

use App\Models\BotSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Single entry point for every AI-generation feature in the admin panel
 * (WhatsApp auto-reply, Meta Lead follow-ups, product description drafts,
 * campaign message drafts, order status updates). Routes strictly to
 * whichever provider is set as `BotSetting::ai_provider` — no silent
 * cross-provider fallback — so "the active API" in Bot Settings is always
 * the one actually used, everywhere.
 */
class AiProviderService
{
    private const ANTHROPIC_URL     = 'https://api.anthropic.com/v1/messages';
    private const ANTHROPIC_VERSION = '2023-06-01';

    public function __construct(private readonly BotSetting $settings) {}

    public function isConfigured(): bool
    {
        return match ($this->settings->ai_provider) {
            'groq'      => filled($this->settings->groq_api_key),
            'openai'    => filled($this->settings->openai_api_key),
            'anthropic' => filled($this->settings->anthropic_api_key),
            default     => false,
        };
    }

    public function providerLabel(): string
    {
        return match ($this->settings->ai_provider) {
            'groq'      => 'Groq',
            'openai'    => 'ChatGPT',
            'anthropic' => 'Claude',
            default     => 'AI',
        };
    }

    /**
     * @param array<array{role: string, content: string}> $messages
     */
    public function chat(string $systemPrompt, array $messages, int $maxTokens = 400): ?string
    {
        return match ($this->settings->ai_provider) {
            'groq'      => $this->chatGroq($systemPrompt, $messages, $maxTokens),
            'openai'    => $this->chatOpenAi($systemPrompt, $messages, $maxTokens),
            'anthropic' => $this->chatAnthropic($systemPrompt, $messages, $maxTokens),
            default     => null,
        };
    }

    private function chatGroq(string $systemPrompt, array $messages, int $maxTokens): ?string
    {
        if (empty($this->settings->groq_api_key)) {
            Log::warning('AiProviderService: Groq selected but no groq_api_key configured');
            return null;
        }

        $groq = new GroqService($this->settings->groq_api_key, $this->settings->groq_model ?? 'llama-3.1-8b-instant');

        return $groq->chat($systemPrompt, $messages, $maxTokens);
    }

    private function chatOpenAi(string $systemPrompt, array $messages, int $maxTokens): ?string
    {
        if (empty($this->settings->openai_api_key)) {
            Log::warning('AiProviderService: ChatGPT selected but no openai_api_key configured');
            return null;
        }

        $openAi = new OpenAiService($this->settings->openai_api_key, $this->settings->openai_model ?? 'gpt-4o-mini');

        return $openAi->chat($systemPrompt, $messages, $maxTokens);
    }

    private function chatAnthropic(string $systemPrompt, array $messages, int $maxTokens): ?string
    {
        if (empty($this->settings->anthropic_api_key)) {
            Log::warning('AiProviderService: Claude selected but no anthropic_api_key configured');
            return null;
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'x-api-key'         => $this->settings->anthropic_api_key,
                'anthropic-version' => self::ANTHROPIC_VERSION,
                'content-type'      => 'application/json',
            ])
            ->post(self::ANTHROPIC_URL, [
                'model'      => $this->settings->anthropic_model ?? 'claude-sonnet-4-6',
                'max_tokens' => $maxTokens,
                'system'     => $systemPrompt,
                'messages'   => $messages,
            ]);

        if ($response->failed()) {
            Log::error('AiProviderService(Anthropic): API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        return $response->json('content.0.text');
    }
}
