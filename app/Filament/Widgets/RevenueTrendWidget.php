<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueTrendWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected ?string $heading = 'Revenue — Last 30 Days';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(function (int $daysAgo) {
            $date = now()->subDays($daysAgo)->startOfDay();
            $revenue = Order::whereDate('created_at', $date)
                ->where('status', '!=', 'cancelled')
                ->sum('total');

            return [
                'label'   => $date->format('d M'),
                'revenue' => (float) $revenue,
            ];
        });

        return [
            'datasets' => [
                [
                    'label'           => 'Revenue (INR)',
                    'data'            => $days->pluck('revenue')->toArray(),
                    'borderColor'     => '#1B6B2F',
                    'backgroundColor' => 'rgba(27, 107, 47, 0.08)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 3,
                ],
            ],
            'labels' => $days->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
