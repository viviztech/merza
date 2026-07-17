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

            Action::make('confirmedChallans')
                ->label('Download Confirmed Challans')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Download Confirmed Delivery Challans')
                ->modalDescription(fn () => 'This will combine the delivery challans of all ' . Order::where('status', 'confirmed')->count() . ' confirmed order(s) into a single PDF.')
                ->modalSubmitActionLabel('Download')
                ->action(function () {
                    $this->redirect(route('admin.orders.delivery-challans.confirmed'));
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
