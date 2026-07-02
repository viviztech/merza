<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends Widget
{
    protected static bool $isDiscovered = false;
    protected static ?int $sort = 7;
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.top-products-widget';

    public function getTopProducts(): \Illuminate\Support\Collection
    {
        return OrderItem::query()
            ->select(
                'product_name',
                'sku',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT order_id) as order_count')
            )
            ->groupBy('product_name', 'sku')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();
    }
}
