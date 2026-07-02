<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderStatusChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected ?string $heading = 'Orders by Status';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $statuses = ['pending', 'confirmed', 'preparing', 'delivering', 'delivered', 'cancelled'];
        $counts   = collect($statuses)->map(fn ($s) => Order::where('status', $s)->count());

        return [
            'datasets' => [
                [
                    'data'            => $counts->toArray(),
                    'backgroundColor' => [
                        '#f59e0b', // pending - amber
                        '#3b82f6', // confirmed - blue
                        '#8b5cf6', // preparing - violet
                        '#06b6d4', // delivering - cyan
                        '#10b981', // delivered - emerald
                        '#ef4444', // cancelled - red
                    ],
                    'hoverOffset' => 6,
                ],
            ],
            'labels' => ['Pending', 'Confirmed', 'Preparing', 'Delivering', 'Delivered', 'Cancelled'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
