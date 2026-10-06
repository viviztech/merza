<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Services\OrderNotificationService;
use App\Services\OrderTrackingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['tracking_url'] ?? null) && filled($data['tracking_number'] ?? null)) {
            $data['tracking_url'] = app(OrderTrackingService::class)->completeUrl(
                $data['tracking_url'], trim($data['tracking_number'])
            );
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record->wasChanged(['tracking_number', 'tracking_url', 'courier_name'])
            || $this->record->wasChanged(['status', 'payment_status'])
            || ! $this->record->tracking_number
            || ! $this->record->tracking_url) {
            return;
        }

        $queued = app(OrderNotificationService::class)->sendStatusUpdate($this->record);
        Notification::make()
            ->title($queued ? 'Courier tracking update queued' : 'Courier tracking saved')
            ->body($queued ? 'Customer will receive the tracking ID and link on WhatsApp.' : 'WhatsApp could not be queued outside the 24-hour reply window.')
            ->color($queued ? 'success' : 'warning')
            ->send();
    }
}
