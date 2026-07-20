<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Order'),

            Action::make('dailyReport')
                ->label('Daily Report PDF')
                ->icon('heroicon-o-document-chart-bar')
                ->color('gray')
                ->form([
                    Forms\Components\DatePicker::make('date')
                        ->label('Report Date')
                        ->default(today())
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->redirect(route('admin.orders.daily-report', ['date' => $data['date']]));
                }),

            Action::make('deliveryChallans')
                ->label('Download Confirmed Challans')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->modalHeading('Download Delivery Challans')
                ->modalDescription('Combine the delivery challans of orders matching the selected status and date range into a single PDF.')
                ->modalSubmitActionLabel('Download')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Order Status')
                        ->options([
                            'all'        => 'All Statuses',
                            'pending'    => 'Pending',
                            'confirmed'  => 'Confirmed',
                            'preparing'  => 'Preparing',
                            'delivering' => 'Delivering',
                            'delivered'  => 'Delivered',
                            'cancelled'  => 'Cancelled',
                        ])
                        ->default('confirmed')
                        ->required(),
                    Forms\Components\DatePicker::make('date_from')
                        ->label('From Date')
                        ->native(false),
                    Forms\Components\DatePicker::make('date_to')
                        ->label('To Date')
                        ->native(false)
                        ->afterOrEqual('date_from'),
                ])
                ->action(function (array $data) {
                    $this->redirect(route('admin.orders.delivery-challans', [
                        'status'    => $data['status'],
                        'date_from' => $data['date_from'] ?? null,
                        'date_to'   => $data['date_to'] ?? null,
                    ]));
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\OrderStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(Order::count()),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'pending'))
                ->badge(Order::where('status', 'pending')->count())
                ->badgeColor('warning'),
            'confirmed' => Tab::make('Confirmed')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'confirmed'))
                ->badge(Order::where('status', 'confirmed')->count())
                ->badgeColor('info'),
            'preparing' => Tab::make('Preparing')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'preparing'))
                ->badge(Order::where('status', 'preparing')->count())
                ->badgeColor('primary'),
            'delivering' => Tab::make('Delivering')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'delivering'))
                ->badge(Order::where('status', 'delivering')->count())
                ->badgeColor('success'),
            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'delivered'))
                ->badge(Order::where('status', 'delivered')->count()),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'cancelled'))
                ->badge(Order::where('status', 'cancelled')->count())
                ->badgeColor('danger'),
        ];
    }
}
