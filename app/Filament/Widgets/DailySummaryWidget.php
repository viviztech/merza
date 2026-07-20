<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * "How did today go" — a strictly today-scoped summary distinct from the
 * broader month/all-time widgets (CrmStatsWidget, OrderStatsWidget). Meant
 * to be checked at the end of the day alongside the "Today's Pipeline"
 * widgets (FollowUpQueueWidget, PaymentPendingOrdersWidget,
 * ReadyToPackOrdersWidget), which show what's still outstanding right now.
 */
class DailySummaryWidget extends BaseWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $enquiriesToday = Lead::whereDate('created_at', today())->count();

        // No explicit call-log model exists — a lead moving off "new" today
        // is the closest signal we have that someone actually followed up.
        $contactedToday = Lead::whereDate('updated_at', today())
            ->where('stage', '!=', 'new')
            ->count();

        $confirmedToday = Order::whereDate('created_at', today())
            ->whereNotIn('status', ['pending', 'cancelled'])
            ->count();

        $paymentPendingToday = Order::whereDate('created_at', today())
            ->where('payment_status', 'unpaid')
            ->count();

        $packedToday = Order::where('status', 'preparing')
            ->whereDate('updated_at', today())
            ->count();

        $dispatchedToday = Order::where('status', 'delivering')
            ->whereDate('updated_at', today())
            ->count();

        $conversionRate = $enquiriesToday > 0
            ? round(($confirmedToday / $enquiriesToday) * 100, 1)
            : null;

        return [
            Stat::make('Enquiries Today', $enquiriesToday)
                ->description('New leads, all sources')
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->color('primary'),

            Stat::make('Contacted Today', $contactedToday)
                ->description('Leads followed up on')
                ->descriptionIcon('heroicon-m-phone')
                ->color('info'),

            Stat::make('Confirmed Today', $confirmedToday)
                ->description('Orders placed & confirmed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Payment Pending', $paymentPendingToday)
                ->description("Today's orders awaiting payment")
                ->descriptionIcon('heroicon-m-clock')
                ->color($paymentPendingToday > 0 ? 'danger' : 'success'),

            Stat::make('Packed Today', $packedToday)
                ->description('Moved to Preparing today')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('warning'),

            Stat::make('Dispatched Today', $dispatchedToday)
                ->description('Moved to Delivering today')
                ->descriptionIcon('heroicon-m-truck')
                ->color('success'),

            Stat::make('Conversion Rate', $conversionRate === null ? '—' : "{$conversionRate}%")
                ->description('Confirmed ÷ enquiries today')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }
}
