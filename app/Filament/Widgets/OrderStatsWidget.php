<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $todayOrders   = Order::whereDate('created_at', today())->count();
        $activeOrders  = Order::whereIn('status', ['pending', 'confirmed', 'preparing', 'delivering'])->count();
        $todayRevenue  = Order::whereDate('created_at', today())
                              ->where('status', '!=', 'cancelled')
                              ->sum('total');
        $monthRevenue  = Order::whereMonth('created_at', now()->month)
                              ->whereYear('created_at', now()->year)
                              ->where('status', '!=', 'cancelled')
                              ->sum('total');
        $unpaidOrders  = Order::where('payment_status', 'unpaid')
                              ->whereNotIn('status', ['cancelled'])
                              ->count();

        return [
            Stat::make("Today's Orders", $todayOrders)
                ->description('New orders today')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Active Orders', $activeOrders)
                ->description('Pending to delivering')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($activeOrders > 0 ? 'warning' : 'gray'),

            Stat::make('Unpaid Orders', $unpaidOrders)
                ->description('Awaiting payment')
                ->descriptionIcon('heroicon-m-clock')
                ->color($unpaidOrders > 0 ? 'danger' : 'success'),

            Stat::make("Today's Revenue", "\u{20B9}" . number_format($todayRevenue, 2))
                ->description('Confirmed + delivered')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Monthly Revenue', "\u{20B9}" . number_format($monthRevenue, 2))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
        ];
    }
}
