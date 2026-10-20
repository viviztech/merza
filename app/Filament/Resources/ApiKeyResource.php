<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiKeyResource\Pages;
use App\Models\ApiKey;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'API Keys';

    protected static ?string $modelLabel = 'API key';

    protected static ?string $pluralModelLabel = 'API keys';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make('Key Details')
                ->description('The secret is shown only once after creation. Store it safely in the connected platform.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Key Name')
                        ->placeholder('e.g. Inventory platform')
                        ->required()
                        ->maxLength(120),

                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Expires At (optional)')
                        ->native(false)
                        ->helperText('Leave blank for a key with no expiry.'),

                    Forms\Components\CheckboxList::make('abilities')
                        ->label('Permissions')
                        ->options(ApiKey::availableAbilities())
                        ->default(['orders:read', 'orders:write'])
                        ->required()
                        ->columns(1)
                        ->helperText('Use the smallest permission set required by the other platform.')
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('key_prefix')
                    ->label('Key Prefix')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('abilities')
                    ->label('Permissions')
                    ->formatStateUsing(function ($state): string {
                        if (is_array($state)) {
                            return implode(', ', $state);
                        }
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            return is_array($decoded) ? implode(', ', $decoded) : $state;
                        }
                        return '';
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->state(fn (ApiKey $record): string => $record->revoked_at
                        ? 'Revoked'
                        : ($record->expires_at?->isPast() ? 'Expired' : 'Active'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Expired' => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Never')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Never')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Revoke API key?')
                    ->modalDescription('Any platform using this key will lose access immediately. This cannot be undone.')
                    ->visible(fn (ApiKey $record): bool => is_null($record->revoked_at))
                    ->action(fn (ApiKey $record) => $record->update(['revoked_at' => now()])),
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
            'index' => Pages\ListApiKeys::route('/'),
            'create' => Pages\CreateApiKey::route('/create'),
            'edit' => Pages\EditApiKey::route('/{record}/edit'),
        ];
    }
}
