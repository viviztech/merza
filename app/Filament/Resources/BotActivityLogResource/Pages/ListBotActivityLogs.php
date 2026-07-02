<?php

namespace App\Filament\Resources\BotActivityLogResource\Pages;

use App\Filament\Resources\BotActivityLogResource;
use App\Models\BotActivityLog;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;

class ListBotActivityLogs extends ListRecords
{
    protected static string $resource = BotActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')->badge(BotActivityLog::count()),
            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn ($query) => $query->whereDate('created_at', today()))
                ->badge(BotActivityLog::whereDate('created_at', today())->count()),
            'message_generated' => Tab::make('Messages Generated')
                ->modifyQueryUsing(fn ($query) => $query->where('event_type', 'message_generated'))
                ->badge(BotActivityLog::where('event_type', 'message_generated')->count())
                ->badgeColor('primary'),
            'errors' => Tab::make('Errors')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'failed'))
                ->badge(BotActivityLog::where('status', 'failed')->count())
                ->badgeColor('danger'),
        ];
    }
}
