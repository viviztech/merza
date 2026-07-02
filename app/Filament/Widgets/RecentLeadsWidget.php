<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentLeadsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected static ?string $heading = 'Recent Leads';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Lead::with('contact')->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Contact')
                    ->description(fn (Lead $r) => $r->contact?->phone),
                Tables\Columns\TextColumn::make('stage')
                    ->badge()
                    ->color(fn ($state) => Lead::$stageColors[$state] ?? 'gray')
                    ->formatStateUsing(fn ($state) => Lead::$stages[$state] ?? $state),
                Tables\Columns\TextColumn::make('source')->badge(),
                Tables\Columns\TextColumn::make('product_interest')->label('Interest')->limit(30),
                Tables\Columns\TextColumn::make('estimated_value')->money('INR')->label('Value'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y H:i')->label('Created'),
            ])
            ->actions([
                Action::make('view')
                    ->url(fn (Lead $r) => LeadResource::getUrl('edit', ['record' => $r]))
                    ->icon('heroicon-m-pencil-square'),
            ]);
    }
}
