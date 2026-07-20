<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Part of the "Today's Pipeline" trio (see also FollowUpQueueWidget,
 * ReadyToPackOrdersWidget) — orders sitting unpaid across every source.
 */
class PaymentPendingOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $heading = 'Payment Pending';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::where('payment_status', 'unpaid')
                    ->whereNotIn('status', ['cancelled', 'delivered'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Order')->weight('bold'),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->description(fn (Order $r) => $r->customer_phone),
                Tables\Columns\TextColumn::make('channel')
                    ->badge()
                    ->color(fn (Order $r) => $r->channel_badge_color),
                Tables\Columns\TextColumn::make('total')->money('INR'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (Order $r) => $r->status_badge_color),
                Tables\Columns\TextColumn::make('created_at')->since()->label('Placed'),
            ])
            ->actions([
                Action::make('view')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Order $r) => OrderResource::getUrl('view', ['record' => $r])),
            ])
            ->paginated([5, 10, 25]);
    }
}
