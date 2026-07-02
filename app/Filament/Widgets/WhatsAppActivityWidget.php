<?php

namespace App\Filament\Widgets;

use App\Models\Conversation;
use Filament\Widgets\ChartWidget;

class WhatsAppActivityWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected ?string $heading = 'WhatsApp Activity — Last 14 Days';
    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(function (int $daysAgo) {
            $date = now()->subDays($daysAgo)->startOfDay();

            $inbound = Conversation::where('channel', 'whatsapp')
                ->where('direction', 'inbound')
                ->whereDate('created_at', $date)
                ->count();

            $outbound = Conversation::where('channel', 'whatsapp')
                ->where('direction', 'outbound')
                ->whereDate('created_at', $date)
                ->count();

            return [
                'label'    => $date->format('d M'),
                'inbound'  => $inbound,
                'outbound' => $outbound,
            ];
        });

        return [
            'datasets' => [
                [
                    'label'           => 'Inbound',
                    'data'            => $days->pluck('inbound')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.75)',
                ],
                [
                    'label'           => 'Outbound',
                    'data'            => $days->pluck('outbound')->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.75)',
                ],
            ],
            'labels' => $days->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
