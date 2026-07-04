<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\BotSetting;
use App\Models\Category;
use App\Models\Product;
use App\Services\GroqService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group as SchemaGroup;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            SchemaGroup::make()->schema([

                SchemaSection::make('Product Details')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(200)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                            $set('slug', Str::slug($state))),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(220),

                    Forms\Components\Select::make('category_id')
                        ->label('Category')
                        ->options(Category::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),

                    Forms\Components\Textarea::make('short_description')
                        ->rows(2)
                        ->maxLength(300)
                        ->columnSpanFull()
                        ->hintAction(
                            Action::make('genShortDesc')
                                ->label('✨ Generate')
                                ->icon('heroicon-o-sparkles')
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $name = $get('name');
                                    if (empty($name)) {
                                        Notification::make()->title('Enter a product name first')->warning()->send();
                                        return;
                                    }
                                    $settings = BotSetting::current();
                                    if (empty($settings->groq_api_key)) {
                                        Notification::make()->title('Groq API key not configured in Bot Settings')->warning()->send();
                                        return;
                                    }
                                    $groq   = new GroqService($settings->groq_api_key, $settings->groq_model ?? 'llama-3.1-8b-instant');
                                    $result = $groq->chat(
                                        'You are a product copywriter for Merza Bodi, a premium tropical fruit brand from Tamil Nadu. Write concise, appealing product descriptions in English. No emojis or hashtags.',
                                        [['role' => 'user', 'content' => "Write a 1-sentence short product description for '{$name}'. Maximum 150 characters. Be specific about the fruit's quality or taste."]],
                                        80
                                    );
                                    if ($result) {
                                        $set('short_description', trim($result, " \n\r\"'"));
                                        Notification::make()->title('Short description generated')->success()->send();
                                    }
                                })
                        ),

                    Forms\Components\RichEditor::make('description')
                        ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link'])
                        ->columnSpanFull()
                        ->hintAction(
                            Action::make('genDescription')
                                ->label('✨ Generate')
                                ->icon('heroicon-o-sparkles')
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $name     = $get('name');
                                    $category = Category::find($get('category_id'))?->name ?? 'fruit';
                                    if (empty($name)) {
                                        Notification::make()->title('Enter a product name first')->warning()->send();
                                        return;
                                    }
                                    $settings = BotSetting::current();
                                    if (empty($settings->groq_api_key)) {
                                        Notification::make()->title('Groq API key not configured in Bot Settings')->warning()->send();
                                        return;
                                    }
                                    $groq   = new GroqService($settings->groq_api_key, $settings->groq_model ?? 'llama-3.1-8b-instant');
                                    $result = $groq->chat(
                                        'You are a product copywriter for Merza Bodi, a premium tropical fruit brand from Tamil Nadu. Write engaging, informative product descriptions in English. Format response as clean HTML using only <p> tags (no headings, no divs, no markdown). No emojis.',
                                        [['role' => 'user', 'content' => "Write a 2-3 paragraph product description for '{$name}' (category: {$category}). Cover: what it is and its origin, taste and quality, usage and storage tips. Format as HTML <p> tags only."]],
                                        400
                                    );
                                    if ($result) {
                                        $html = strip_tags($result, '<p><b><i><ul><ol><li><strong><em>');
                                        if (! str_contains($html, '<p>')) {
                                            $html = '<p>' . str_replace("\n\n", '</p><p>', trim($html)) . '</p>';
                                        }
                                        $set('description', $html);
                                        Notification::make()->title('Description generated — review and edit before saving')->success()->send();
                                    }
                                })
                        ),
                ])->columns(2),

                SchemaSection::make('Variants & Pricing')->schema([
                    Forms\Components\Repeater::make('variants')
                        ->relationship('variants')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->placeholder('e.g. 500g, 1kg, 5kg')
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('sku')
                                ->placeholder('Auto-generated if empty')
                                ->maxLength(50),

                            Forms\Components\TextInput::make('price')
                                ->required()
                                ->numeric()
                                ->prefix("\u{20B9}")
                                ->minValue(0),

                            Forms\Components\TextInput::make('weight_value')
                                ->label('Weight')
                                ->numeric()
                                ->minValue(0),

                            Forms\Components\Select::make('weight_unit')
                                ->options(['g' => 'g', 'kg' => 'kg', 'pcs' => 'pcs', 'box' => 'box'])
                                ->default('kg'),

                            Forms\Components\TextInput::make('stock_qty')
                                ->label('Stock')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->default(0),

                            Forms\Components\TextInput::make('low_stock_threshold')
                                ->label('Low Stock Alert')
                                ->numeric()
                                ->minValue(0)
                                ->default(5),

                            Forms\Components\Toggle::make('is_active')
                                ->default(true)
                                ->inline(false),
                        ])
                        ->columns(4)
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->addActionLabel('Add Variant')
                        ->reorderable('sort_order')
                        ->collapsible()
                        ->defaultItems(1),
                ]),

            ])->columnSpan(2),

            SchemaGroup::make()->schema([

                SchemaSection::make('Status')->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Published')
                        ->default(true),

                    Forms\Components\Toggle::make('is_featured')
                        ->label('Featured on homepage')
                        ->default(false),

                    Forms\Components\TextInput::make('base_price')
                        ->label('Starting price (display)')
                        ->numeric()
                        ->prefix("\u{20B9}")
                        ->helperText('Used if no variant selected'),

                    Forms\Components\Select::make('unit')
                        ->options(['kg' => 'kg', 'g' => 'g', 'pcs' => 'pcs', 'box' => 'box', 'pack' => 'pack'])
                        ->default('kg'),

                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),
                ]),

                SchemaSection::make('Product Images')->schema([
                    Forms\Components\SpatieMediaLibraryFileUpload::make('thumbnail')
                        ->collection('thumbnail')
                        ->label('Thumbnail (main)')
                        ->image()
                        ->imageEditor()
                        ->maxFiles(1)
                        ->helperText('Primary image shown in listings'),

                    Forms\Components\SpatieMediaLibraryFileUpload::make('images')
                        ->collection('images')
                        ->label('Gallery Images')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->maxFiles(8),
                ]),

            ])->columnSpan(1),

        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('thumbnail')
                    ->collection('thumbnail')
                    ->conversion('thumb')
                    ->label('')
                    ->width(56)
                    ->height(56),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record) => $record->category?->name),

                Tables\Columns\TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('Variants'),

                Tables\Columns\TextColumn::make('base_price')
                    ->money('INR')
                    ->label('From'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Published'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Featured'),
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
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
