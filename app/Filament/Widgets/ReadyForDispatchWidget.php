<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Part of the Delivery Dashboard pipeline — paid orders that are packed
 * and just waiting to be handed off for delivery.
 */
class ReadyForDispatchWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected static ?string $heading = 'Ready for Dispatch';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::where('status', 'preparing')->where('payment_status', 'paid')->withCount('items')->latest())
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Order')->weight('bold'),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->description(fn (Order $r) => $r->customer_phone),
                Tables\Columns\TextColumn::make('channel')
                    ->badge()
                    ->color(fn (Order $r) => $r->channel_badge_color),
                Tables\Columns\TextColumn::make('items_count')->label('Items'),
                Tables\Columns\TextColumn::make('created_at')->since()->label('Placed'),
            ])
            ->actions([
                Action::make('dispatch')
                    ->label('Dispatch')
                    ->icon('heroicon-m-truck')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('tracking_number')
                            ->label('Tracking Number (optional)')
                            ->placeholder('e.g. DTDC123456789'),
                    ])
                    ->action(function (Order $r, array $data) {
                        $r->update([
                            'status'          => 'delivering',
                            'dispatched_at'   => now(),
                            'tracking_number' => $data['tracking_number'] ?? null,
                        ]);
                        Notification::make()->title("Order {$r->order_number} dispatched")->success()->send();
                    }),
                Action::make('view')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Order $r) => OrderResource::getUrl('view', ['record' => $r])),
            ])
            ->poll('15s')
            ->paginated([5, 10, 25]);
    }
}
