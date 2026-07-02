<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use Filament\Widgets\ChartWidget;

class ContactsBySourceWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected ?string $heading = 'Contacts by Source';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $rows = Contact::query()
            ->selectRaw('COALESCE(source, \'unknown\') as source, COUNT(*) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();

        $palette = [
            '#1B6B2F', '#3b82f6', '#f59e0b', '#8b5cf6',
            '#ef4444', '#06b6d4', '#f97316', '#94a3b8',
        ];

        return [
            'datasets' => [
                [
                    'data'            => $rows->pluck('total')->toArray(),
                    'backgroundColor' => array_values(array_slice($palette, 0, $rows->count())),
                    'hoverOffset'     => 6,
                ],
            ],
            'labels' => $rows->pluck('source')->map(fn ($s) => ucfirst($s))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
