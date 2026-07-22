<?php

namespace App\Filament\Widgets;

use App\Models\AnalyticsEvent;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The funnel the owner draws by hand every day: Visitors -> Product Views ->
 * Add to Cart -> Checkout Started -> Orders Placed, with % of visitors at
 * each stage, so a real drop-off point replaces guessing. Counts distinct
 * sessions per stage (a person who views 5 products still counts once).
 */
class ConversionFunnelWidget extends BaseWidget
{
    protected static bool $isDiscovered = false;
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $sessionsFor = fn (string $eventType) => AnalyticsEvent::query()
            ->where('event_type', $eventType)
            ->whereDate('created_at', today())
            ->distinct('session_id')
            ->count('session_id');

        $visitors        = AnalyticsEvent::query()->whereDate('created_at', today())->distinct('session_id')->count('session_id');
        $productViews     = $sessionsFor('product_view');
        $addToCart        = $sessionsFor('add_to_cart');
        $checkoutStarted  = $sessionsFor('checkout_start');
        $ordersPlaced     = $sessionsFor('order_placed');

        $pct = fn (int $n) => $visitors > 0 ? round(($n / $visitors) * 100) . '% of visitors' : 'No visitors yet today';

        return [
            Stat::make('Visitors Today', $visitors)
                ->description('Unique sessions')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('Product Views', $productViews)
                ->description($pct($productViews))
                ->descriptionIcon('heroicon-m-eye')
                ->color('primary'),

            Stat::make('Add to Cart', $addToCart)
                ->description($pct($addToCart))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),

            Stat::make('Checkout Started', $checkoutStarted)
                ->description($pct($checkoutStarted))
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('info'),

            Stat::make('Orders Placed', $ordersPlaced)
                ->description($pct($ordersPlaced))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
