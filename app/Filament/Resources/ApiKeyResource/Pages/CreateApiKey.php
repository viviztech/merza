<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use App\Models\ApiKey;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateApiKey extends CreateRecord
{
    protected static string $resource = ApiKeyResource::class;

    protected ?string $generatedSecret = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->generatedSecret = ApiKey::generateSecret();

        $data['created_by'] = auth()->id();
        $data['key_prefix'] = ApiKey::prefixFor($this->generatedSecret);
        $data['key_hash'] = ApiKey::hashSecret($this->generatedSecret);

        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('API key created — copy it now')
            ->body("This secret will not be shown again:\n\n{$this->generatedSecret}")
            ->warning()
            ->persistent()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
