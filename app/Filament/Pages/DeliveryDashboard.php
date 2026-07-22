<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DeliveredTodayWidget;
use App\Filament\Widgets\PaymentPendingOrdersWidget;
use App\Filament\Widgets\ReadyForDispatchWidget;
use App\Filament\Widgets\ReadyToPackOrdersWidget;
use Filament\Pages\Dashboard;

class DeliveryDashboard extends Dashboard
{
    protected static string $routePath = 'delivery';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';
    protected static string|\UnitEnum|null $navigationGroup = 'Orders & Delivery';
    protected static ?string $navigationLabel = 'Delivery Dashboard';
    protected static ?string $title = 'Delivery Dashboard';
    protected static ?int $navigationSort = 2;

    public function getWidgets(): array
    {
        return [
            PaymentPendingOrdersWidget::class,
            ReadyToPackOrdersWidget::class,
            ReadyForDispatchWidget::class,
            DeliveredTodayWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
