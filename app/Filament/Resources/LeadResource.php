<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\OrderResource;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-funnel';
    protected static string|\UnitEnum|null $navigationGroup = 'Sales & CRM';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make()->schema([
                Forms\Components\Select::make('contact_id')
                    ->label('Contact')
                    ->relationship('contact', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\TextInput::make('phone')->required()->tel(),
                        Forms\Components\TextInput::make('email')->email()->nullable(),
                    ]),

                Forms\Components\Select::make('stage')
                    ->options(Lead::$stages)
                    ->default('new')
                    ->required(),

                Forms\Components\Select::make('source')
                    ->options([
                        'meta_ads' => 'Meta Ads', 'whatsapp' => 'WhatsApp',
                        'referral' => 'Referral', 'walk_in' => 'Walk-in',
                        'website'  => 'Website', 'other' => 'Other',
                    ])
                    ->default('other'),

                Forms\Components\Select::make('assigned_to')
                    ->label('Assigned To')
                    ->options(User::pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),

                Forms\Components\TextInput::make('product_interest')
                    ->placeholder('e.g. Premium Mangoes 3kg')
                    ->nullable(),

                Forms\Components\TextInput::make('estimated_value')
                    ->numeric()->prefix("\u{20B9}")->nullable(),

                Forms\Components\DateTimePicker::make('due_at')
                    ->label('Follow-up Date')->nullable(),

                Forms\Components\Textarea::make('notes')
                    ->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contact.name')
                    ->searchable()->sortable()
                    ->description(fn (Lead $r) => $r->contact?->phone),

                Tables\Columns\TextColumn::make('stage')
                    ->badge()
                    ->color(fn ($state) => Lead::$stageColors[$state] ?? 'gray')
                    ->formatStateUsing(fn ($state) => Lead::$stages[$state] ?? $state),

                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'meta_ads' => 'warning', 'whatsapp' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('product_interest')
                    ->label('Interest')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('estimated_value')
                    ->money('INR')->label('Value'),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned'),

                Tables\Columns\TextColumn::make('due_at')
                    ->dateTime('d M Y')
                    ->label('Follow-up')
                    ->sortable()
                    ->color(fn (Lead $r) =>
                        $r->due_at?->isPast() && !in_array($r->stage, ['converted','lost'])
                            ? 'danger' : null),
            ])
            ->defaultSort('created_at', 'desc')
            ->groups([
                Tables\Grouping\Group::make('stage')
                    ->label('Stage')
                    ->getTitleFromRecordUsing(fn (Lead $r) => Lead::$stages[$r->stage] ?? $r->stage),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->options(Lead::$stages),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'meta_ads' => 'Meta Ads', 'whatsapp' => 'WhatsApp',
                        'referral' => 'Referral', 'walk_in' => 'Walk-in',
                        'website' => 'Website', 'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assigned To')
                    ->options(User::pluck('name', 'id')),
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue follow-ups')
                    ->query(fn ($query) => $query
                        ->whereNotNull('due_at')
                        ->where('due_at', '<', now())
                        ->whereNotIn('stage', ['converted', 'lost'])),
            ])
            ->actions([
                Action::make('advance')
                    ->label('Advance Stage')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->visible(fn (Lead $r) => !in_array($r->stage, ['converted', 'lost']))
                    ->action(function (Lead $record) {
                        $order = array_keys(Lead::$stages);
                        $current = array_search($record->stage, $order);
                        if ($current !== false && isset($order[$current + 1])) {
                            $next = $order[$current + 1];
                            $record->update([
                                'stage'        => $next,
                                'converted_at' => $next === 'converted' ? now() : null,
                            ]);
                        }
                    }),
                Action::make('whatsapp')
                    ->label('')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(fn (Lead $r) => $r->contact?->whatsapp_url)
                    ->openUrlInNewTab(),
                Action::make('createOrder')
                    ->label('Create Order')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('primary')
                    ->visible(fn (Lead $r) => $r->stage === 'converted' && $r->contact_id)
                    ->url(fn (Lead $r) => OrderResource::getUrl('create', ['contact_id' => $r->contact_id])),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('changeStage')
                        ->label('Change Stage')
                        ->icon('heroicon-o-funnel')
                        ->form([
                            Forms\Components\Select::make('stage')
                                ->options(Lead::$stages)->required(),
                        ])
                        ->action(fn ($records, array $data) =>
                            $records->each->update(['stage' => $data['stage']])),
                    Actions\BulkAction::make('assign')
                        ->label('Assign to…')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Forms\Components\Select::make('assigned_to')
                                ->options(User::pluck('name', 'id'))->required(),
                        ])
                        ->action(fn ($records, array $data) =>
                            $records->each->update(['assigned_to' => $data['assigned_to']])),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit'   => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
