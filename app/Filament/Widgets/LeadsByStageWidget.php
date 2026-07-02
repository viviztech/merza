<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class LeadsByStageWidget extends ChartWidget
{
    protected ?string $heading = 'Leads by Stage';
    protected static ?int $sort = 2;
    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $stages = Lead::$stages;
        $counts = [];
        foreach (array_keys($stages) as $stage) {
            $counts[] = Lead::where('stage', $stage)->count();
        }

        return [
            'datasets' => [
                [
                    'data'            => $counts,
                    'backgroundColor' => [
                        '#94a3b8', // new - slate
                        '#38bdf8', // contacted - sky
                        '#fbbf24', // interested - amber
                        '#818cf8', // quoted - violet
                        '#34d399', // converted - emerald
                        '#f87171', // lost - red
                    ],
                ],
            ],
            'labels' => array_values($stages),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
