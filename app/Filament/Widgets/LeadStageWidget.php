<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class LeadStageWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected ?string $heading = 'Lead Pipeline';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $stages = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
        $labels = ['New', 'Contacted', 'Qualified', 'Proposal', 'Negotiation', 'Won', 'Lost'];
        $counts = collect($stages)->map(fn ($s) => Lead::where('stage', $s)->count());

        return [
            'datasets' => [
                [
                    'label'           => 'Leads',
                    'data'            => $counts->toArray(),
                    'backgroundColor' => [
                        '#94a3b8', // new - slate
                        '#60a5fa', // contacted - blue
                        '#a78bfa', // qualified - violet
                        '#f59e0b', // proposal - amber
                        '#f97316', // negotiation - orange
                        '#10b981', // won - emerald
                        '#ef4444', // lost - red
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
