<?php

namespace Database\Seeders;

use App\Models\BotSetting;
use Illuminate\Database\Seeder;

class BotSettingsSeeder extends Seeder
{
    /**
     * Sync bot settings from environment variables into the DB.
     * Only overwrites a field when the env var is non-empty.
     * Safe to run repeatedly (idempotent).
     */
    public function run(): void
    {
        $setting = BotSetting::current();

        $updates = array_filter([
            // AI providers
            'ai_provider'    => env('AI_PROVIDER', 'groq'),
            'groq_api_key'   => env('GROQ_API_KEY'),
            'groq_model'     => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
            'openai_api_key' => env('OPENAI_API_KEY'),
            'openai_model'   => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'sarvam_api_key' => env('SARVAM_API_KEY'),

            // Claude / Anthropic
            'anthropic_api_key' => env('ANTHROPIC_API_KEY'),
            'anthropic_model'   => env('ANTHROPIC_MODEL'),

            // WhatsApp
            'whatsapp_phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            'whatsapp_access_token'    => env('WHATSAPP_ACCESS_TOKEN'),
            'meta_verify_token'        => env('META_VERIFY_TOKEN'),
            'meta_app_id'              => env('META_APP_ID'),
            'meta_app_secret'          => env('META_APP_SECRET'),
        ], fn ($v) => $v !== null && $v !== '');

        if (! empty($updates)) {
            $setting->update($updates);
            echo "  BotSettings updated: " . implode(', ', array_keys($updates)) . PHP_EOL;
        }
    }
}
