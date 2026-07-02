<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use App\Models\Campaign;
use Filament\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(Campaign::count()),

            'active' => Tab::make('Active')
                ->badge(Campaign::where('status', 'active')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'active')),

            'scheduled' => Tab::make('Scheduled')
                ->badge(Campaign::where('status', 'scheduled')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'scheduled')),

            'draft' => Tab::make('Draft')
                ->badge(Campaign::where('status', 'draft')->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'draft')),

            'paused' => Tab::make('Paused')
                ->badge(Campaign::where('status', 'paused')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'paused')),

            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'completed')),
        ];
    }
}
