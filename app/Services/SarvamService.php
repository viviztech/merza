<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SarvamService
{
    private const STT_URL = 'https://api.sarvam.ai/speech-to-text';

    public function __construct(private readonly string $apiKey) {}

    /**
     * Transcribe audio content to text.
     *
     * @param string $audioContent  Raw binary audio content
     * @param string $mimeType      MIME type (e.g. 'audio/ogg', 'audio/mpeg')
     * @param string $languageCode  BCP-47 code or 'unknown' for auto-detect
     */
    public function transcribe(string $audioContent, string $mimeType = 'audio/ogg', string $languageCode = 'unknown'): ?string
    {
        // Derive file extension from mime type for the multipart upload
        $extension = match (true) {
            str_contains($mimeType, 'ogg')  => 'ogg',
            str_contains($mimeType, 'mp4')  => 'mp4',
            str_contains($mimeType, 'mpeg') => 'mp3',
            str_contains($mimeType, 'wav')  => 'wav',
            str_contains($mimeType, 'webm') => 'webm',
            default                          => 'ogg',
        };

        $response = Http::timeout(60)
            ->withHeaders(['api-subscription-key' => $this->apiKey])
            ->attach('file', $audioContent, "audio.{$extension}", ['Content-Type' => $mimeType])
            ->post(self::STT_URL, [
                'model'         => 'saarika:v1',
                'language_code' => $languageCode,
            ]);

        if ($response->failed()) {
            Log::error('SarvamService: transcription failed', [
                'status'    => $response->status(),
                'body'      => $response->body(),
                'mime_type' => $mimeType,
            ]);
            return null;
        }

        return $response->json('transcript') ?? $response->json('text');
    }
}
