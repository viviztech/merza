<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Part of the Delivery Dashboard pipeline — orders delivered today, so
 * staff can confirm the day's dispatches actually landed.
 */
class DeliveredTodayWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected static ?string $heading = 'Delivered Today';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::where('status', 'delivered')->whereDate('delivered_at', today())->latest('delivered_at'))
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Order')->weight('bold'),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->description(fn (Order $r) => $r->customer_phone),
                Tables\Columns\TextColumn::make('total')->money('INR'),
                Tables\Columns\TextColumn::make('tracking_number')->placeholder('—'),
                Tables\Columns\TextColumn::make('delivered_at')->since()->label('Delivered'),
            ])
            ->actions([
                Action::make('view')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Order $r) => OrderResource::getUrl('view', ['record' => $r])),
            ])
            ->poll('15s')
            ->paginated([5, 10, 25]);
    }
}
