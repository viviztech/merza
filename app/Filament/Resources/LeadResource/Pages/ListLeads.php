<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use Filament\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')->badge(Lead::count()),
            'new' => Tab::make('New')
                ->modifyQueryUsing(fn ($query) => $query->where('stage', 'new'))
                ->badge(Lead::where('stage', 'new')->count()),
            'contacted' => Tab::make('Contacted')
                ->modifyQueryUsing(fn ($query) => $query->where('stage', 'contacted'))
                ->badge(Lead::where('stage', 'contacted')->count()),
            'interested' => Tab::make('Interested')
                ->modifyQueryUsing(fn ($query) => $query->where('stage', 'interested'))
                ->badge(Lead::where('stage', 'interested')->count()),
            'quoted' => Tab::make('Quoted')
                ->modifyQueryUsing(fn ($query) => $query->where('stage', 'quoted'))
                ->badge(Lead::where('stage', 'quoted')->count()),
            'converted' => Tab::make('Converted')
                ->modifyQueryUsing(fn ($query) => $query->where('stage', 'converted'))
                ->badge(Lead::where('stage', 'converted')->count()),
            'lost' => Tab::make('Lost')
                ->modifyQueryUsing(fn ($query) => $query->where('stage', 'lost'))
                ->badge(Lead::where('stage', 'lost')->count()),
        ];
    }
}
