<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebhookResource\Pages;
use App\Filament\Resources\WebhookResource\RelationManagers\DeliveriesRelationManager;
use App\Models\Webhook;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class WebhookResource extends Resource
{
    protected static ?string $model = Webhook::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Webhooks';

    protected static ?string $modelLabel = 'webhook';

    protected static ?string $pluralModelLabel = 'webhooks';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make('Webhook data')
                ->description('Send selected Merza events to another application as signed JSON. The secret is shown after creation and is used to verify every request.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->placeholder('e.g. CRM order sync')
                        ->required()
                        ->maxLength(120),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'disabled' => 'Disabled',
                            'active' => 'Active',
                        ])
                        ->default('disabled')
                        ->required(),

                    Forms\Components\Select::make('topic')
                        ->label('Event / Topic')
                        ->options(Webhook::TOPICS)
                        ->placeholder('Select an event...')
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('delivery_url')
                        ->label('Delivery URL')
                        ->url()
                        ->rule('starts_with:https://')
                        ->required()
                        ->placeholder('https://your-app.example.com/webhooks/merza')
                        ->helperText('Only HTTPS URLs are accepted.'),

                    Forms\Components\TextInput::make('secret')
                        ->label('Secret')
                        ->password()
                        ->revealable()
                        ->suffixAction(
                            Actions\Action::make('generateSecret')
                                ->label('Generate key')
                                ->icon('heroicon-o-key')
                                ->tooltip('Generate a new webhook secret')
                                ->action(fn (Set $set): mixed => $set('secret', Webhook::generateSecret()))
                        )
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText('Leave blank on create to generate a secure whsec_... key automatically. On edit, leave blank to keep the existing secret.')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('api_version')
                        ->label('API Version')
                        ->options([
                            'v1' => 'Merza Webhook API v1',
                        ])
                        ->default('v1')
                        ->required(),
                ])->columns(2),

            SchemaSection::make('Request format')
                ->description('Requests are POST JSON. Headers include X-Merza-Event, X-Merza-Event-ID, X-Merza-Timestamp, X-Merza-API-Version, and X-Merza-Signature. The signature is sha256=HMAC-SHA256(secret, timestamp + "." + exact request body). Receivers should deduplicate using the event ID.')
                ->schema([
                    Forms\Components\Placeholder::make('payload_format')
                        ->label('Payload')
                        ->content('{ "event_id": "evt_...", "event": "cart.added", "timestamp": "2026-10-12T10:00:00+05:30", "data": { ... } }')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('topic')
                    ->label('Event / Topic')
                    ->formatStateUsing(fn (string $state): string => Webhook::TOPICS[$state] ?? $state)
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('delivery_url')
                    ->label('Delivery URL')
                    ->copyable()
                    ->limit(48)
                    ->tooltip(fn (Webhook $record): string => $record->delivery_url),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('last_delivered_at')
                    ->label('Last Delivered')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Never')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_response_status')
                    ->label('Last HTTP')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn (?int $state): string => $state !== null && $state >= 200 && $state < 300 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_error')
                    ->label('Last Error')
                    ->limit(40)
                    ->tooltip(fn (Webhook $record): ?string => $record->last_error)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
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
            'index' => Pages\ListWebhooks::route('/'),
            'create' => Pages\CreateWebhook::route('/create'),
            'edit' => Pages\EditWebhook::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            DeliveriesRelationManager::class,
        ];
    }
}
