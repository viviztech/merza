<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BotActivityLogResource\Pages;
use App\Models\BotActivityLog;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BotActivityLogResource extends Resource
{
    protected static ?string $model = BotActivityLog::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Bot Activity';
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make('Event Details')->schema([
                TextEntry::make('event_type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'error'            => 'danger',
                        'webhook_received' => 'gray',
                        'contact_created'  => 'success',
                        'lead_created'     => 'info',
                        'message_generated'=> 'primary',
                        default            => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => BotActivityLog::$eventLabels[$state] ?? $state),
                TextEntry::make('status')->badge()
                    ->color(fn ($state) => $state === 'success' ? 'success' : 'danger'),
                TextEntry::make('meta_lead_id')->label('Meta Lead ID')->placeholder('—'),
                TextEntry::make('meta_form_id')->label('Form ID')->placeholder('—'),
                TextEntry::make('created_at')->dateTime('d M Y, h:i A'),
            ])->columns(3),

            SchemaSection::make('CRM Links')->schema([
                TextEntry::make('contact.name')->label('Contact')->placeholder('—'),
                TextEntry::make('lead.id')->label('Lead ID')->placeholder('—'),
                TextEntry::make('conversation.id')->label('Conversation ID')->placeholder('—'),
            ])->columns(3),

            SchemaSection::make('Generated Message')->schema([
                TextEntry::make('generated_message')
                    ->label('AI Follow-up Message')
                    ->placeholder('No message generated')
                    ->columnSpanFull(),
            ]),

            SchemaSection::make('Error Details')->schema([
                TextEntry::make('error_message')
                    ->label('Error')
                    ->placeholder('None')
                    ->color('danger')
                    ->columnSpanFull(),
            ])->visible(fn ($record) => filled($record->error_message)),

            SchemaSection::make('Raw Payload')->schema([
                TextEntry::make('raw_payload')
                    ->label('Webhook / API Payload')
                    ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                    ->fontFamily('mono')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event_type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'error'             => 'danger',
                        'webhook_received'  => 'gray',
                        'contact_created'   => 'success',
                        'lead_created'      => 'info',
                        'message_generated' => 'primary',
                        default             => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => BotActivityLog::$eventLabels[$state] ?? $state),

                Tables\Columns\TextColumn::make('meta_lead_id')
                    ->label('Lead ID')
                    ->searchable()
                    ->limit(16),

                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Contact')
                    ->placeholder('—')
                    ->url(fn (BotActivityLog $r) => $r->contact_id
                        ? route('filament.admin.resources.contacts.view', $r->contact_id)
                        : null),

                Tables\Columns\TextColumn::make('generated_message')
                    ->label('AI Message')
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state === 'success' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->label('Time'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->label('Event')
                    ->options(BotActivityLog::$eventLabels),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['success' => 'Success', 'failed' => 'Failed']),
                Tables\Filters\Filter::make('today')
                    ->label("Today's Activity")
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBotActivityLogs::route('/'),
            'view'  => Pages\ViewBotActivityLog::route('/{record}'),
        ];
    }
}
