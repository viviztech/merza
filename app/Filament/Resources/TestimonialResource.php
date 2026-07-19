<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestimonialResource\Pages;
use App\Models\Testimonial;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';
    protected static ?string $navigationLabel = 'Testimonials';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make()->schema([
                Forms\Components\TextInput::make('customer_name')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('e.g. Priya S.'),

                Forms\Components\TextInput::make('location')
                    ->maxLength(100)
                    ->placeholder('e.g. Chennai'),

                Forms\Components\Select::make('rating')
                    ->options([5 => '5 stars', 4 => '4 stars', 3 => '3 stars'])
                    ->default(5)
                    ->required(),

                Forms\Components\TextInput::make('product_tag')
                    ->maxLength(100)
                    ->placeholder('e.g. Kasa Lattu Mango — 10kg')
                    ->helperText('Optional short tag shown on the review card.'),

                Forms\Components\Textarea::make('quote')
                    ->required()
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull()
                    ->helperText('Only real, submitted customer reviews — this renders directly on the homepage.'),

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
                Tables\Columns\TextColumn::make('sort_order')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('customer_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('location')->searchable(),
                Tables\Columns\TextColumn::make('rating')->formatStateUsing(fn ($state) => str_repeat('★', (int) $state)),
                Tables\Columns\TextColumn::make('quote')->limit(60),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
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
            'index'  => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit'   => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
