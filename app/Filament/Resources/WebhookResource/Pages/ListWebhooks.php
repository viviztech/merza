<?php

namespace App\Filament\Resources\WebhookResource\Pages;

use App\Filament\Resources\WebhookResource;
use App\Models\Webhook;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Set;

class ListWebhooks extends ListRecords
{
    protected static string $resource = WebhookResource::class;

    public ?string $generatedSecret = null;

    protected function getHeaderActions(): array
    {
        return [
            $this->createWebhookAction(),
        ];
    }

    public function createWebhookAction(): Action
    {
        return Action::make('createWebhook')
            ->label('Create Webhook')
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->modalHeading(fn (): string => $this->generatedSecret
                ? 'Webhook created successfully'
                : 'Create Webhook')
            ->modalDescription(fn (): string => $this->generatedSecret
                ? 'Copy this secret now. It will not be shown again after this popup is closed.'
                : 'Configure the destination and event. A secret is generated automatically when the Secret field is left blank.')
            ->modalSubmitActionLabel(fn (): string => $this->generatedSecret ? 'Done' : 'Create Webhook')
            ->modalCancelAction(fn (Action $action) => $this->generatedSecret ? false : null)
            ->form(function (): array {
                return [
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->placeholder('e.g. CRM order sync')
                        ->required()
                        ->maxLength(120)
                        ->visible(fn (): bool => blank($this->generatedSecret)),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'disabled' => 'Disabled',
                            'active' => 'Active',
                        ])
                        ->default('disabled')
                        ->required()
                        ->visible(fn (): bool => blank($this->generatedSecret)),

                    Forms\Components\Select::make('topic')
                        ->label('Event / Topic')
                        ->options(Webhook::TOPICS)
                        ->placeholder('Select an event...')
                        ->required()
                        ->searchable()
                        ->visible(fn (): bool => blank($this->generatedSecret)),

                    Forms\Components\TextInput::make('delivery_url')
                        ->label('Delivery URL')
                        ->url()
                        ->rule('starts_with:https://')
                        ->required()
                        ->placeholder('https://your-app.example.com/webhooks/merza')
                        ->visible(fn (): bool => blank($this->generatedSecret)),

                    Forms\Components\TextInput::make('secret')
                        ->label('Secret')
                        ->password()
                        ->revealable()
                        ->suffixAction(
                            Action::make('generateSecret')
                                ->label('Generate key')
                                ->icon('heroicon-o-key')
                                ->tooltip('Generate a new webhook secret')
                                ->action(fn (Set $set): mixed => $set('secret', Webhook::generateSecret()))
                        )
                        ->helperText('Leave blank to generate a secure whsec_... key automatically.')
                        ->visible(fn (): bool => blank($this->generatedSecret)),

                    Forms\Components\Select::make('api_version')
                        ->label('API Version')
                        ->options(['v1' => 'Merza Webhook API v1'])
                        ->default('v1')
                        ->required()
                        ->visible(fn (): bool => blank($this->generatedSecret)),

                    Forms\Components\TextInput::make('secret_key_display')
                        ->label('Webhook Secret Key')
                        ->default(fn (): ?string => $this->generatedSecret)
                        ->formatStateUsing(fn (): ?string => $this->generatedSecret)
                        ->readOnly()
                        ->copyable(condition: fn (): bool => filled($this->generatedSecret), copyMessage: 'Webhook secret copied')
                        ->helperText('Store this secret in the receiving application. Use it to verify X-Merza-Signature.')
                        ->visible(fn (): bool => filled($this->generatedSecret))
                        ->columnSpanFull(),
                ];
            })
            ->action(function (array $data, Action $action): void {
                if ($this->generatedSecret) {
                    $this->generatedSecret = null;

                    return;
                }

                $secret = filled($data['secret'] ?? null)
                    ? $data['secret']
                    : Webhook::generateSecret();

                Webhook::create([
                    'created_by' => auth()->id(),
                    'name' => $data['name'],
                    'status' => $data['status'] ?? 'disabled',
                    'topic' => $data['topic'],
                    'delivery_url' => $data['delivery_url'],
                    'secret' => $secret,
                    'api_version' => $data['api_version'] ?? 'v1',
                ]);

                $this->generatedSecret = $secret;
                $this->mountedActions[array_key_last($this->mountedActions)]['data']['secret_key_display'] = $secret;

                Notification::make()
                    ->title('Webhook created successfully')
                    ->success()
                    ->send();

                $action->halt();
            });
    }
}
