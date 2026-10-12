<?php

namespace App\Filament\Resources\WebhookResource\Pages;

use App\Filament\Resources\WebhookResource;
use App\Models\Webhook;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateWebhook extends CreateRecord
{
    protected static string $resource = WebhookResource::class;

    protected ?string $generatedSecret = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->generatedSecret = filled($data['secret'] ?? null)
            ? $data['secret']
            : Webhook::generateSecret();

        $data['secret'] = $this->generatedSecret;
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Webhook created — copy the secret now')
            ->body("Secret:\n\n{$this->generatedSecret}\n\nThis secret is used to verify the X-Merza-Signature header and will not be shown again.")
            ->warning()
            ->persistent()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
