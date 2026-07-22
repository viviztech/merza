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

    /**
     * Vision chat completion — sends one image alongside a text instruction and
     * forces strict JSON back (used for reading payment screenshots). Returns
     * the raw JSON string from the model, or null on failure.
     */
    public function chatWithImage(string $systemPrompt, string $userText, string $imageUrl, int $maxTokens = 300): ?string
    {
        $response = Http::timeout(30)
            ->withToken($this->apiKey)
            ->post(self::BASE_URL . '/chat/completions', [
                'model'    => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => [
                        ['type' => 'text', 'text' => $userText],
                        ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
                    ]],
                ],
                'max_tokens'      => $maxTokens,
                'temperature'     => 0.1,
                'response_format' => ['type' => 'json_object'],
            ]);

        if ($response->failed()) {
            Log::error('OpenAiService: vision chat failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'model'  => $this->model,
            ]);
            return null;
        }

        return $response->json('choices.0.message.content');
    }
}
