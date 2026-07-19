<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    private const BASE_URL = 'https://api.openai.com/v1';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'gpt-4o-mini',
    ) {}

    /**
     * Chat completion. Returns the assistant message text or null on failure.
     *
     * @param array<array{role: string, content: string}> $messages
     */
    public function chat(string $systemPrompt, array $messages, int $maxTokens = 400): ?string
    {
        $payload = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$messages,
        ];

        $response = Http::timeout(30)
            ->withToken($this->apiKey)
            ->post(self::BASE_URL . '/chat/completions', [
                'model'       => $this->model,
                'messages'    => $payload,
                'max_tokens'  => $maxTokens,
                'temperature' => 0.7,
            ]);

        if ($response->failed()) {
            Log::error('OpenAiService: chat completion failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'model'  => $this->model,
            ]);
            return null;
        }

        return $response->json('choices.0.message.content');
    }
}
