<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')
                ->label('Confirm Order')
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->visible(fn () => $this->record->status === 'pending')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update([
                    'status'       => 'confirmed',
                    'confirmed_at' => now(),
                ])),

            Action::make('prepare')
                ->label('Start Preparing')
                ->icon('heroicon-o-cube')
                ->color('primary')
                ->visible(fn () => $this->record->status === 'confirmed')
                ->action(fn () => $this->record->update(['status' => 'preparing'])),

            Action::make('dispatch')
                ->label('Mark Dispatched')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->visible(fn () => $this->record->status === 'preparing')
                ->form([
                    Forms\Components\TextInput::make('tracking_number')
                        ->label('Tracking Number (optional)')
                        ->placeholder('e.g. DTDC123456789'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status'          => 'delivering',
                        'dispatched_at'   => now(),
                        'tracking_number' => $data['tracking_number'] ?? null,
                    ]);
                }),

            Action::make('deliver')
                ->label('Mark Delivered')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->status === 'delivering')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update([
                    'status'       => 'delivered',
                    'delivered_at' => now(),
                ])),

            Action::make('markPaid')
                ->label('Mark Paid')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn () => $this->record->payment_status === 'unpaid' && $this->record->status !== 'cancelled')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['payment_status' => 'paid'])),

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
