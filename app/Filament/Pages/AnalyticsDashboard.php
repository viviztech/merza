<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ContactsBySourceWidget;
use App\Filament\Widgets\ConversionFunnelWidget;
use App\Filament\Widgets\LeadStageWidget;
use App\Filament\Widgets\OrderStatusChartWidget;
use App\Filament\Widgets\OverviewStatsWidget;
use App\Filament\Widgets\RevenueTrendWidget;
use App\Filament\Widgets\TopProductsWidget;
use App\Filament\Widgets\WhatsAppActivityWidget;
use Filament\Pages\Dashboard;

class AnalyticsDashboard extends Dashboard
{
    protected static string $routePath = 'analytics';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = 'Analytics';
    protected static ?string $navigationLabel = 'Analytics';
    protected static ?string $title = 'Analytics Dashboard';
    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            OverviewStatsWidget::class,
            ConversionFunnelWidget::class,
            RevenueTrendWidget::class,
            OrderStatusChartWidget::class,
            LeadStageWidget::class,
            ContactsBySourceWidget::class,
            WhatsAppActivityWidget::class,
            TopProductsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 3;
    }
}
