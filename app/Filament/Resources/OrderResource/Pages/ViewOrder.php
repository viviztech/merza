<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            OrderResource::nextActionButton(),

            Action::make('downloadInvoice')
                ->label('Invoice PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn () => route('admin.orders.invoice', $this->record))
                ->openUrlInNewTab(),

            Action::make('downloadDeliverySlip')
                ->label('Delivery Slip')
                ->icon('heroicon-o-truck')
                ->color('gray')
                ->url(fn () => route('admin.orders.delivery-slip', $this->record))
                ->openUrlInNewTab(),

            Actions\EditAction::make(),

            Action::make('cancel')
                ->label('Cancel Order')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => !in_array($this->record->status, ['delivered', 'cancelled']))
                ->requiresConfirmation()
                ->modalHeading('Cancel Order')
                ->modalDescription('This will cancel the order. This action cannot be undone.')
                ->action(fn () => $this->record->update(['status' => 'cancelled'])),
        ];
    }
}
