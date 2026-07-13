<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryZoneResource\Pages;
use App\Models\DeliveryZone;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DeliveryZoneResource extends Resource
{
    protected static ?string $model = DeliveryZone::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Delivery Zones';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->placeholder('e.g. Tamil Nadu, Bangalore'),

                Forms\Components\Select::make('match_type')
                    ->options(['state' => 'State', 'city' => 'City'])
                    ->required()
                    ->helperText('City zones are matched before state zones.'),

                Forms\Components\TagsInput::make('match_values')
                    ->label('Match Values')
                    ->required()
                    ->helperText('Enter each spelling/alias and press Enter. e.g. Tamil Nadu, Tamilnadu, TN'),

                Forms\Components\TextInput::make('rate_per_kg')
                    ->label('Rate per kg (₹)')
                    ->numeric()
                    ->required()
                    ->prefix('₹')
                    ->suffix('/ kg'),

                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->onColor('success'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('match_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'state' => 'info',
                        'city'  => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('match_values')
                    ->label('Matches')
                    ->formatStateUsing(fn ($state) => implode(', ', is_array($state) ? $state : (json_decode($state ?? '[]', true) ?? []))),
                Tables\Columns\TextColumn::make('rate_per_kg')
                    ->label('Rate / kg')
                    ->formatStateUsing(fn ($state) => "\u{20B9}" . number_format((float) $state, 0)),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('sort_order')->label('Order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
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
            'index'  => Pages\ListDeliveryZones::route('/'),
            'create' => Pages\CreateDeliveryZone::route('/create'),
            'edit'   => Pages\EditDeliveryZone::route('/{record}/edit'),
        ];
    }
}
