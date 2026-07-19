<?php

namespace App\Filament\Pages;

use App\Models\BotSetting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;

class MetaBotSettingsPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Meta Bot Settings';
    protected static ?string $title = 'Meta & WhatsApp Bot Settings';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->data = BotSetting::current()->toArray();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([
                SchemaSection::make('Bot Status')->schema([
                    Forms\Components\Toggle::make('bot_enabled')
                        ->label('Enable Meta Lead Ads Bot')
                        ->helperText('Process incoming Meta Lead Ad form submissions.')
                        ->onColor('success'),

                    Forms\Components\Toggle::make('auto_create_contact')
                        ->label('Auto-create Contact')
                        ->helperText('Automatically create a CRM Contact from each new lead.'),

                    Forms\Components\Toggle::make('auto_create_lead')
                        ->label('Auto-create Lead')
                        ->helperText('Automatically create a Lead pipeline entry for each new contact.'),

                    Forms\Components\Toggle::make('wa_bot_enabled')
                        ->label('Enable WhatsApp Bot')
                        ->helperText('Auto-generate AI replies for inbound WhatsApp messages.')
                        ->onColor('success'),

                    Forms\Components\Toggle::make('wa_auto_send')
                        ->label('Auto-send Replies')
                        ->helperText('Send AI replies immediately. Off = save as draft for review.')
                        ->onColor('warning'),
                ])->columns(3),

                SchemaSection::make('Facebook / Meta Credentials')
                    ->description('Get these from your Meta for Developers app dashboard.')
                    ->schema([
                        Forms\Components\TextInput::make('meta_app_id')
                            ->label('App ID')
                            ->placeholder('1234567890123456'),

                        Forms\Components\TextInput::make('meta_app_secret')
                            ->label('App Secret')
                            ->password()->revealable()
                            ->placeholder('Your Meta App Secret'),

                        Forms\Components\TextInput::make('meta_page_id')
                            ->label('Facebook Page ID'),

                        Forms\Components\Textarea::make('meta_page_access_token')
                            ->label('Page Access Token')
                            ->rows(3)
                            ->placeholder('EAAxxxxx...')
                            ->helperText('Long-lived page access token from Meta Business account.'),

                        Forms\Components\TextInput::make('meta_verify_token')
                            ->label('Webhook Verify Token')
                            ->helperText('Paste this value into your Meta app webhook configuration.'),

                        Forms\Components\TextInput::make('meta_lead_form_id')
                            ->label('Lead Form ID (optional)')
                            ->helperText('Filter to a specific form. Leave blank to process all forms.'),
                    ])->columns(2),

                SchemaSection::make('Webhook URL')
                    ->description('Register this URL in your Meta app webhook settings (Leadgen subscription).')
                    ->schema([
                        Forms\Components\Placeholder::make('webhook_url')
                            ->label('Webhook Endpoint')
                            ->content(fn () => url('/webhook/meta')),
                    ]),

                SchemaSection::make('Active AI Provider')
                    ->description('Every AI feature — WhatsApp auto-reply, Meta Lead follow-ups, product description drafts, campaign message drafts, order status updates — uses whichever provider is selected here. Fill in the matching API key below.')
                    ->schema([
                        Forms\Components\Select::make('ai_provider')
                            ->label('Active AI Provider')
                            ->options([
                                'groq'      => '⚡ Groq (Fast, Free)',
                                'openai'    => '🧠 ChatGPT (OpenAI)',
                                'anthropic' => '🤖 Claude (Anthropic)',
                            ])
                            ->default('groq')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                SchemaSection::make('🤖 Groq AI')
                    ->description('Fast, free LLM API. Get your key at console.groq.com.')
                    ->schema([
                        Forms\Components\Select::make('groq_model')
                            ->label('Groq Model')
                            ->options([
                                'llama-3.1-8b-instant'    => 'Llama 3.1 8B Instant (Fast, Free)',
                                'llama-3.3-70b-versatile' => 'Llama 3.3 70B Versatile (Better quality)',
                                'mixtral-8x7b-32768'      => 'Mixtral 8x7B (Long context)',
                            ])
                            ->default('llama-3.1-8b-instant'),

                        Forms\Components\TextInput::make('groq_api_key')
                            ->label('Groq API Key')
                            ->password()->revealable()
                            ->placeholder('gsk_...')
                            ->helperText('From console.groq.com — free account, no card required.')
                            ->columnSpanFull(),
                    ])->columns(2),

                SchemaSection::make('🧠 ChatGPT (OpenAI)')
                    ->description('Get your key at platform.openai.com/api-keys.')
                    ->schema([
                        Forms\Components\Select::make('openai_model')
                            ->label('OpenAI Model')
                            ->options([
                                'gpt-4o-mini' => 'GPT-4o mini (Fast, cheap)',
                                'gpt-4o'      => 'GPT-4o (Better quality)',
                                'gpt-4.1'     => 'GPT-4.1',
                            ])
                            ->default('gpt-4o-mini'),

                        Forms\Components\TextInput::make('openai_api_key')
                            ->label('OpenAI API Key')
                            ->password()->revealable()
                            ->placeholder('sk-...')
                            ->helperText('From platform.openai.com/api-keys.')
                            ->columnSpanFull(),
                    ])->columns(2),

                SchemaSection::make('🎤 Sarvam AI (Voice Bot — Indian Languages)')
                    ->description('Speech-to-Text for Tamil/Hindi/English WhatsApp voice messages. Get your key at dashboard.sarvam.ai.')
                    ->schema([
                        Forms\Components\Toggle::make('voice_bot_enabled')
                            ->label('Enable Voice Bot')
                            ->helperText('Transcribe incoming voice messages and reply with AI.')
                            ->onColor('success'),

                        Forms\Components\TextInput::make('sarvam_api_key')
                            ->label('Sarvam API Key')
                            ->password()->revealable()
                            ->placeholder('sk_...')
                            ->helperText('From dashboard.sarvam.ai — supports Tamil, Hindi, English.')
                            ->columnSpanFull(),
                    ])->columns(2),

                SchemaSection::make('🤖 Claude (Anthropic)')
                    ->description('Get your key at console.anthropic.com.')
                    ->schema([
                        Forms\Components\TextInput::make('anthropic_api_key')
                            ->label('Anthropic API Key')
                            ->password()->revealable()
                            ->placeholder('sk-ant-api03-...'),

                        Forms\Components\Select::make('anthropic_model')
                            ->label('Claude Model')
                            ->options([
                                'claude-haiku-4-5-20251001'  => 'Claude Haiku 4.5 (Fastest)',
                                'claude-sonnet-4-6'          => 'Claude Sonnet 4.6',
                                'claude-opus-4-8'            => 'Claude Opus 4.8 (Best)',
                            ])
                            ->default('claude-haiku-4-5-20251001'),
                    ])->columns(2),

                SchemaSection::make('WhatsApp Business API')
                    ->description('Required to send and receive WhatsApp messages via Meta Cloud API.')
                    ->schema([
                        Forms\Components\TextInput::make('whatsapp_phone_number_id')
                            ->label('Phone Number ID')
                            ->placeholder('1234567890123456')
                            ->helperText('Found in Meta for Developers > WhatsApp > API Setup.'),

                        Forms\Components\Textarea::make('whatsapp_access_token')
                            ->label('WhatsApp Access Token')
                            ->rows(2)
                            ->placeholder('EAAxxxxx...')
                            ->helperText('Permanent System User token or temporary test token.'),
                    ])->columns(2),

                SchemaSection::make('WhatsApp Ordering & Payment (UPI)')
                    ->description('Turns on the full tap-to-order flow (cart → address → payment → confirmation) inside WhatsApp. While off, "Order Now" taps fall back to the old free-text + AI handoff.')
                    ->schema([
                        Forms\Components\Toggle::make('wa_commerce_enabled')
                            ->label('Enable WhatsApp Ordering')
                            ->helperText('Lets customers build a cart and complete an order directly in WhatsApp.')
                            ->onColor('success')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('upi_id')
                            ->label('UPI ID')
                            ->placeholder('merza@upi')
                            ->helperText('Shown as a QR code + text for WhatsApp UPI payments. Leave blank to offer Cash on Delivery only.'),

                        Forms\Components\TextInput::make('upi_payee_name')
                            ->label('UPI Payee Name')
                            ->placeholder('Merza Bodi'),
                    ])->columns(2),

                SchemaSection::make('Lead Follow-up Prompt')
                    ->description('AI prompt for Meta Lead Ad follow-ups, sent to whichever provider is active above. Placeholders: {{customer_name}}, {{city}}, {{product_interest}}')
                    ->schema([
                        Forms\Components\Textarea::make('follow_up_prompt_template')
                            ->label('Prompt Template')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                SchemaSection::make('WhatsApp Auto-reply Prompt')
                    ->description('AI prompt for replying to inbound WhatsApp messages, sent to whichever provider is active above. Placeholders: {{customer_name}}, {{customer_message}}, {{product_interest}}')
                    ->schema([
                        Forms\Components\Textarea::make('wa_reply_prompt_template')
                            ->label('Reply Prompt Template')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                SchemaActions::make([
                    Action::make('save')
                        ->label('Save Settings')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action('save'),
                ]),
            ])->statePath('data'),
        ]);
    }

    public function save(): void
    {
        $settings = BotSetting::current();
        $settings->update($this->data);

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save'),
        ];
    }
}
