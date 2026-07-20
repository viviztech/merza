<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductReviewResource\Pages;
use App\Models\ProductReview;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';
    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';
    protected static ?string $navigationLabel = 'Product Reviews';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('is_approved', false)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make()->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->required(),

                Forms\Components\TextInput::make('customer_name')
                    ->required()
                    ->maxLength(100),

                Forms\Components\Select::make('rating')
                    ->options([5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star'])
                    ->default(5)
                    ->required(),

                Forms\Components\TextInput::make('video_url')
                    ->label('Video URL (optional)')
                    ->url()
                    ->maxLength(255),

                Forms\Components\Textarea::make('comment')
                    ->rows(3)
                    ->maxLength(1000)
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('photo_path')
                    ->label('Photo (optional)')
                    ->disk(config('media-library.disk_name', 'r2'))
                    ->directory('product-review-photos')
                    ->image()
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_approved')
                    ->label('Approved (visible on product page)')
                    ->default(false)
                    ->onColor('success'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->disk(config('media-library.disk_name', 'r2'))
                    ->label('')
                    ->width(48)
                    ->height(48)
                    ->circular(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(fn ($state) => str_repeat('★', (int) $state)),

                Tables\Columns\TextColumn::make('comment')->limit(50),

                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')->label('Approved'),
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable(),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (ProductReview $record) => ! $record->is_approved)
                    ->action(function (ProductReview $record) {
                        $record->update(['is_approved' => true]);
                        Notification::make()->title('Review approved')->success()->send();
                    }),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('approve')
                        ->label('Approve selected')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['is_approved' => true])),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProductReviews::route('/'),
            'create' => Pages\CreateProductReview::route('/create'),
            'edit'   => Pages\EditProductReview::route('/{record}/edit'),
        ];
    }
}
