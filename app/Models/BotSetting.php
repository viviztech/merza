<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotSetting extends Model
{
    protected $fillable = [
        'meta_app_id', 'meta_app_secret', 'meta_page_id',
        'meta_page_access_token', 'meta_verify_token', 'meta_lead_form_id',
        'whatsapp_phone_number_id', 'whatsapp_access_token',
        'anthropic_api_key', 'anthropic_model',
        'ai_provider', 'groq_api_key', 'groq_model',
        'openai_api_key', 'openai_model', 'sarvam_api_key',
        'follow_up_prompt_template', 'wa_reply_prompt_template',
        'bot_enabled', 'auto_create_contact', 'auto_create_lead',
        'wa_bot_enabled', 'wa_auto_send', 'voice_bot_enabled',
        'wa_commerce_enabled', 'upi_id', 'upi_payee_name',
    ];

    protected $casts = [
        'bot_enabled'          => 'boolean',
        'auto_create_contact'  => 'boolean',
        'auto_create_lead'     => 'boolean',
        'wa_bot_enabled'       => 'boolean',
        'wa_auto_send'         => 'boolean',
        'voice_bot_enabled'    => 'boolean',
        'wa_commerce_enabled'  => 'boolean',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'meta_verify_token'           => 'merza_bot_' . substr(md5('merza'), 0, 8),
            'anthropic_model'             => 'claude-sonnet-4-6',
            'follow_up_prompt_template'   => self::defaultPrompt(),
            'wa_reply_prompt_template'    => self::defaultWaReplyPrompt(),
            'bot_enabled'                 => false,
            'auto_create_contact'         => true,
            'auto_create_lead'            => true,
        ]);
    }

    public static function defaultWaReplyPrompt(): string
    {
        return 'You are a friendly WhatsApp sales assistant for Merza, a premium tropical fruit brand from India. '
            . 'A customer named {{customer_name}} just replied to your message saying: "{{customer_message}}". '
            . 'Their previous enquiry was about: {{product_interest}}. '
            . 'Write a warm, helpful WhatsApp reply in Indian English. '
            . 'Keep it under 60 words. If they want to order, ask for their delivery address. '
            . 'If they ask about price, share that we have multiple pack sizes starting from ₹299. '
            . 'End with "— Merza Team 🥭". Do not use bullet points.';
    }

    private static function defaultPrompt(): string
    {
        return 'You are a friendly sales assistant for Merza, a premium tropical fruit brand from India (mangoes, jackfruit, banana red, freeze-dried fruits). '
            . 'A customer named {{customer_name}} from {{city}} just filled out our Meta Ads lead form enquiring about {{product_interest}}. '
            . 'Write a warm, personalised WhatsApp follow-up message in Indian English. '
            . 'Keep it under 80 words. Be friendly and conversational. Mention their name. '
            . 'Ask if they would like to place an order and mention we offer home delivery. '
            . 'End with "— Merza Team 🥭". Do not use bullet points.';
    }
}
