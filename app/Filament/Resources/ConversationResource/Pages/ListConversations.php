<?php

namespace App\Filament\Resources\ConversationResource\Pages;

use App\Filament\Resources\ConversationResource;
use App\Models\Conversation;
use Filament\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;

class ListConversations extends ListRecords
{
    protected static string $resource = ConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        $drafts = Conversation::where('is_bot', true)->whereNull('sent_at')->count();

        return [
            'all' => Tab::make('All')
                ->badge(Conversation::count()),

            'whatsapp' => Tab::make('WhatsApp')
                ->modifyQueryUsing(fn ($query) => $query->where('channel', 'whatsapp'))
                ->badge(Conversation::where('channel', 'whatsapp')->count())
                ->badgeColor('success'),

            'inbound' => Tab::make('Inbound')
                ->modifyQueryUsing(fn ($query) => $query->where('direction', 'inbound'))
                ->badge(Conversation::where('direction', 'inbound')->count())
                ->badgeColor('info'),

            'drafts' => Tab::make('Drafts')
                ->modifyQueryUsing(fn ($query) => $query->where('is_bot', true)->whereNull('sent_at'))
                ->badge($drafts)
                ->badgeColor($drafts > 0 ? 'warning' : 'gray'),

            'bot' => Tab::make('Bot Messages')
                ->modifyQueryUsing(fn ($query) => $query->where('is_bot', true))
                ->badge(Conversation::where('is_bot', true)->count()),
        ];
    }
}
