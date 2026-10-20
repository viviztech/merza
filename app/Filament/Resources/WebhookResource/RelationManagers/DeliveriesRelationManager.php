<?php

namespace App\Filament\Resources\WebhookResource\RelationManagers;

use App\Models\WebhookDelivery;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DeliveriesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveries';

    protected static ?string $title = 'Delivery log';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event_id')
            ->columns([
                Tables\Columns\TextColumn::make('event_id')
                    ->label('Event ID')
                    ->copyable()
                    ->limit(18)
                    ->tooltip(fn (WebhookDelivery $record): ?string => $record->event_id),

                Tables\Columns\TextColumn::make('topic')
                    ->label('Event')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'retrying', 'sending', 'pending' => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('attempts')
                    ->label('Attempts')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('response_status')
                    ->label('HTTP')
                    ->placeholder('-')
                    ->badge()
                    ->color(fn (?int $state): string => $state !== null && $state >= 200 && $state < 300 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('response_body')
                    ->label('Response')
                    ->limit(60)
                    ->copyable()
                    ->tooltip(fn (WebhookDelivery $record): ?string => $record->response_body),

                Tables\Columns\TextColumn::make('error')
                    ->label('Error')
                    ->limit(60)
                    ->copyable()
                    ->tooltip(fn (WebhookDelivery $record): ?string => $record->error),

                Tables\Columns\TextColumn::make('last_attempted_at')
                    ->label('Last attempt')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('request_body')
                    ->label('Request JSON')
                    ->limit(60)
                    ->copyable()
                    ->tooltip(fn (WebhookDelivery $record): ?string => $record->request_body)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
