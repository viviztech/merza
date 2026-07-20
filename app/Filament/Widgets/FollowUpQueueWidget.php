<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Part of the "Today's Pipeline" trio (see also PaymentPendingOrdersWidget,
 * ReadyToPackOrdersWidget) — leads due for a follow-up call today or overdue,
 * across every source (website/WhatsApp/phone/old customer), in one queue.
 */
class FollowUpQueueWidget extends BaseWidget
{
    protected static ?int $sort = 0;
    protected static ?string $heading = "Needs Follow-up Today";
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Lead::with('contact')
                    ->whereNotIn('stage', ['converted', 'lost'])
                    ->where(fn ($q) => $q->whereDate('due_at', '<=', today())->orWhereNull('due_at'))
                    ->orderByRaw('due_at IS NULL, due_at asc')
            )
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
                Tables\Columns\TextColumn::make('due_at')
                    ->label('Due')
                    ->dateTime('d M, h:i A')
                    ->color(fn (Lead $r) => $r->due_at?->isPast() ? 'danger' : 'gray')
                    ->placeholder('No date set'),
            ])
            ->actions([
                Action::make('call')
                    ->label('WhatsApp')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn (Lead $r) => filled($r->contact?->phone))
                    ->url(fn (Lead $r) => $r->contact->whatsapp_url)
                    ->openUrlInNewTab(),
                Action::make('view')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Lead $r) => LeadResource::getUrl('edit', ['record' => $r])),
            ])
            ->paginated([5, 10, 25]);
    }
}
