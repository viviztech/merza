<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use App\Models\ApiKey;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListApiKeys extends ListRecords
{
    protected static string $resource = ApiKeyResource::class;

    public ?string $generatedSecret = null;

    protected function getHeaderActions(): array
    {
        return [
            $this->generateApiKeyAction(),
        ];
    }

    public function generateApiKeyAction(): Action
    {
        return Action::make('generateApiKey')
            ->label('Generate API Key')
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->modalHeading(fn (): string => $this->generatedSecret ? 'API Key Generated Successfully' : 'Generate API Key')
            ->modalDescription(fn (): string => $this->generatedSecret
                ? 'Please copy your secret key now. This secret will NOT be shown again!'
                : 'Specify key details. The secret will be displayed in this popup once generated.')
            ->modalSubmitActionLabel(fn (): string => $this->generatedSecret ? 'Done' : 'Generate Key')
            ->modalCancelAction(fn (Action $action) => $this->generatedSecret ? false : null)
            ->form(function (): array {
                return [
                    Forms\Components\TextInput::make('name')
                        ->label('Key Name')
                        ->placeholder('e.g. Inventory platform')
                        ->required()
                        ->maxLength(120)
                        ->visible(fn (): bool => blank($this->generatedSecret)),

                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Expires At (optional)')
                        ->native(false)
                        ->helperText('Leave blank for a key with no expiry.')
                        ->visible(fn (): bool => blank($this->generatedSecret)),

                    Forms\Components\CheckboxList::make('abilities')
                        ->label('Permissions')
                        ->options(ApiKey::availableAbilities())
                        ->default(['orders:read', 'orders:write'])
                        ->required()
                        ->columns(1)
                        ->visible(fn (): bool => blank($this->generatedSecret)),

                    Forms\Components\TextInput::make('secret_key_display')
                        ->label('API Secret Key')
                        ->default(fn (): ?string => $this->generatedSecret)
                        ->formatStateUsing(fn (): ?string => $this->generatedSecret)
                        ->readOnly()
                        ->visible(fn (): bool => filled($this->generatedSecret))
                        ->copyable(condition: fn (): bool => filled($this->generatedSecret), copyMessage: 'API key copied')
                        ->helperText('Store this secret key securely. Click the field or Copy to copy it.')
                ];
            })
            ->action(function (array $data, Action $action): void {
                if ($this->generatedSecret) {
                    $this->generatedSecret = null;
                    return;
                }

                $secret = ApiKey::generateSecret();

                ApiKey::create([
                    'created_by' => auth()->id(),
                    'name' => $data['name'],
                    'key_prefix' => ApiKey::prefixFor($secret),
                    'key_hash' => ApiKey::hashSecret($secret),
                    'abilities' => !empty($data['abilities']) ? $data['abilities'] : ['orders:read', 'orders:write'],
                    'expires_at' => $data['expires_at'] ?? null,
                ]);

                $this->generatedSecret = $secret;
                $this->mountedActions[array_key_last($this->mountedActions)]['data']['secret_key_display'] = $secret;

                Notification::make()
                    ->title('API Key Generated Successfully!')
                    ->success()
                    ->send();

                $action->halt();
            });
    }
}
