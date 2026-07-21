<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Filters\CreatedAtRangeFilter;
use App\Filament\Pages\QuickOrder;
use App\Models\Contact;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group as SchemaGroup;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = 'Sales & CRM';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            SchemaGroup::make()->schema([
                SchemaSection::make('Contact Info')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()->maxLength(120),

                    Forms\Components\TextInput::make('phone')
                        ->required()->tel()->unique(ignoreRecord: true)->maxLength(20),

                    Forms\Components\TextInput::make('email')
                        ->email()->unique(ignoreRecord: true)->nullable(),

                    Forms\Components\Select::make('source')
                        ->options([
                            'meta_ads'  => 'Meta Ads',
                            'whatsapp'  => 'WhatsApp',
                            'referral'  => 'Referral',
                            'walk_in'   => 'Walk-in',
                            'website'   => 'Website',
                            'other'     => 'Other',
                        ])
                        ->default('other'),

                    Forms\Components\TextInput::make('city')->nullable(),
                    Forms\Components\TextInput::make('state')->nullable(),

                    Forms\Components\TagsInput::make('tags')
                        ->placeholder('Add tag')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->rows(3)->columnSpanFull(),
                ])->columns(2),
            ])->columnSpan(2),

            SchemaGroup::make()->schema([
                SchemaSection::make('Assignment & Status')->schema([
                    Forms\Components\Select::make('assigned_to')
                        ->label('Assigned To')
                        ->options(User::pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),

                    Forms\Components\Toggle::make('is_customer')
                        ->label('Is a customer'),

                    Forms\Components\Toggle::make('is_blocked')
                        ->label('Blocked'),

                    Forms\Components\DateTimePicker::make('last_contacted_at')
                        ->label('Last Contacted'),
                ]),
            ])->columnSpan(1),
        ])->columns(3);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            SchemaGroup::make()->schema([
                SchemaSection::make('Contact Details')->schema([
                    Infolists\Components\TextEntry::make('name'),
                    Infolists\Components\TextEntry::make('phone')
                        ->url(fn (Contact $r) => $r->whatsapp_url)
                        ->openUrlInNewTab()
                        ->icon('heroicon-m-chat-bubble-left-ellipsis')
                        ->hintAction(
                            Action::make('call')
                                ->label('Call')
                                ->icon('heroicon-m-phone')
                                ->url(fn (Contact $r) => $r->call_url)
                        ),
                    Infolists\Components\TextEntry::make('email')->icon('heroicon-m-envelope'),
                    Infolists\Components\TextEntry::make('source')->badge(),
                    Infolists\Components\TextEntry::make('city'),
                    Infolists\Components\TextEntry::make('state'),
                    Infolists\Components\TextEntry::make('tags')
                        ->badge()
                        ->separator(','),
                    Infolists\Components\TextEntry::make('notes')->columnSpanFull(),
                ])->columns(2),

                SchemaSection::make('Active Enquiry')
                    ->visible(fn (Contact $r) => $r->active_lead !== null)
                    ->schema([
                        Infolists\Components\TextEntry::make('active_lead.stage_label')
                            ->label('Stage')->badge()
                            ->color(fn (Contact $r) => $r->active_lead?->stage_color ?? 'gray'),
                        Infolists\Components\TextEntry::make('active_lead.product_interest')
                            ->label('Interested In')->placeholder('—'),
                        Infolists\Components\TextEntry::make('active_lead.estimated_value')
                            ->label('Est. Value')->money('INR')->placeholder('—'),
                        Infolists\Components\TextEntry::make('active_lead.notes')
                            ->label('Notes')->placeholder('—')->columnSpanFull(),
                    ])->columns(3),

                SchemaSection::make('Orders')->schema([
                    Infolists\Components\RepeatableEntry::make('orders')
                        ->schema([
                            Infolists\Components\TextEntry::make('order_number'),
                            Infolists\Components\TextEntry::make('status')->badge(),
                            Infolists\Components\TextEntry::make('total')->money('INR'),
                            Infolists\Components\TextEntry::make('created_at')->dateTime('d M Y'),
                        ])
                        ->columns(4)
                        ->label(''),
                ]),
            ])->columnSpan(2),

            SchemaGroup::make()->schema([
                SchemaSection::make('CRM Status')->schema([
                    Infolists\Components\IconEntry::make('is_customer')->boolean()->label('Customer'),
                    Infolists\Components\IconEntry::make('is_blocked')->boolean()->label('Blocked'),
                    Infolists\Components\TextEntry::make('assignedTo.name')->label('Assigned To'),
                    Infolists\Components\TextEntry::make('last_contacted_at')->dateTime('d M Y H:i')->label('Last Contacted'),
                    Infolists\Components\TextEntry::make('leads_count')
                        ->label('Total Leads')
                        ->state(fn (Contact $r) => $r->leads()->count()),
                    Infolists\Components\TextEntry::make('orders_count')
                        ->label('Total Orders')
                        ->state(fn (Contact $r) => $r->orders()->count()),
                    Infolists\Components\TextEntry::make('total_spent')
                        ->label('Total Spent')
                        ->state(fn (Contact $r) => "\u{20B9}" . number_format($r->orders()->sum('total'), 2)),
                ]),
            ])->columnSpan(1),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->url(fn (Contact $r) => $r->call_url)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'meta_ads' => 'warning',
                        'whatsapp' => 'success',
                        'referral' => 'info',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('city')
                    ->searchable()->toggleable(),

                Tables\Columns\IconColumn::make('is_customer')->boolean()->label('Customer'),

                Tables\Columns\TextColumn::make('leads_count')->counts('leads')->label('Leads'),
                Tables\Columns\TextColumn::make('orders_count')->counts('orders')->label('Orders'),

                Tables\Columns\TextColumn::make('last_contacted_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->label('Last Contact'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->label('Added')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                CreatedAtRangeFilter::make(),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'meta_ads' => 'Meta Ads', 'whatsapp' => 'WhatsApp',
                        'referral' => 'Referral', 'walk_in' => 'Walk-in',
                        'website' => 'Website', 'other' => 'Other',
                    ]),
                Tables\Filters\TernaryFilter::make('is_customer')->label('Customer'),
                Tables\Filters\TernaryFilter::make('is_blocked')->label('Blocked'),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assigned To')
                    ->options(User::pluck('name', 'id')),
            ])
            ->actions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(fn (Contact $r) => $r->whatsapp_url)
                    ->openUrlInNewTab(),
                Action::make('convertToOrder')
                    ->label('Convert to Order')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('success')
                    ->visible(fn (Contact $r) => $r->active_lead !== null)
                    ->modalWidth('3xl')
                    ->modalHeading('Convert Enquiry to Order')
                    ->modalSubmitActionLabel('Create Order')
                    ->steps(fn (Contact $r) => LeadResource::convertToOrderSteps($r->active_lead))
                    ->fillForm(fn (Contact $r) => LeadResource::convertToOrderFormDefaults($r->active_lead))
                    ->action(function (array $data, Contact $r) {
                        $order = LeadResource::handleConvertToOrder($data, $r->active_lead);

                        Notification::make()->title("Order {$order->order_number} created")->success()->send();

                        return redirect(OrderResource::getUrl('view', ['record' => $order]));
                    }),
                Action::make('quickOrder')
                    ->label('Quick Order')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->visible(fn (Contact $r) => $r->active_lead === null)
                    ->url(fn (Contact $r) => QuickOrder::getUrl(['phone' => $r->phone])),

                Action::make('createOrder')
                    ->label('New Order')
                    ->icon('heroicon-o-plus-circle')
                    ->color('gray')
                    ->visible(fn (Contact $r) => $r->active_lead === null)
                    ->url(fn (Contact $r) => OrderResource::getUrl('create', ['contact_id' => $r->id])),

                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\BulkAction::make('assign')
                        ->label('Assign to…')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Forms\Components\Select::make('assigned_to')
                                ->label('Sales Rep')
                                ->options(User::pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(fn ($records, array $data) =>
                            $records->each->update(['assigned_to' => $data['assigned_to']])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view'   => Pages\ViewContact::route('/{record}'),
            'edit'   => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
