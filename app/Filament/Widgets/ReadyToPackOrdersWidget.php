<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Part of the "Today's Pipeline" trio (see also FollowUpQueueWidget,
 * PaymentPendingOrdersWidget) — confirmed orders waiting to be packed,
 * regardless of whether they came from the website, WhatsApp, or a
 * repeat-customer Quick Order.
 */
class ReadyToPackOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = 'Ready to Pack';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::where('status', 'confirmed')->withCount('items')->latest())
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Order')->weight('bold'),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->description(fn (Order $r) => $r->customer_phone),
                Tables\Columns\TextColumn::make('channel')
                    ->badge()
                    ->color(fn (Order $r) => $r->channel_badge_color),
                Tables\Columns\TextColumn::make('items_count')->label('Items'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn ($state) => $state === 'paid' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('created_at')->since()->label('Placed'),
            ])
            ->actions([
                Action::make('markPreparing')
                    ->label('Start Packing')
                    ->icon('heroicon-m-archive-box')
                    ->color('warning')
                    ->action(function (Order $r) {
                        $r->update(['status' => 'preparing']);
                        Notification::make()->title("Order {$r->order_number} moved to Preparing")->success()->send();
                    }),
                Action::make('view')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Order $r) => OrderResource::getUrl('view', ['record' => $r])),
            ])
            ->paginated([5, 10, 25]);
    }
}
