<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Exports\ContactExporter;
use App\Filament\Filters\CreatedAtRangeFilter;
use App\Filament\Pages\QuickOrder;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use App\Services\ExcelExportService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Enums\ExportFormat;
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
                        ->options(Contact::SOURCE_LABELS)
                        ->default('other'),

                    Forms\Components\Select::make('customer_category')
                        ->label('Customer Category')
                        ->options(Contact::CATEGORY_LABELS)
                        ->default('new_lead')
                        ->required(),

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
                    Infolists\Components\TextEntry::make('source')
                        ->badge()
                        ->formatStateUsing(fn (?string $state) => $state ? Contact::SOURCE_LABELS[$state] ?? $state : null),
                    Infolists\Components\TextEntry::make('meta_ad_name')
                        ->label('Latest Meta Ad')
                        ->state(fn (Contact $r) => $r->meta_ad_name ?: $r->meta_ad_id)
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('customer_category')
                        ->label('Customer Category')
                        ->badge()
                        ->formatStateUsing(fn (?string $state) => $state ? Contact::CATEGORY_LABELS[$state] ?? $state : null),
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
                    ->formatStateUsing(fn (?string $state) => $state ? Contact::SOURCE_LABELS[$state] ?? $state : null)
                    ->color(fn ($state) => match ($state) {
                        'meta_ads', 'instagram', 'facebook' => 'warning',
                        'whatsapp'          => 'success',
                        'referral'          => 'info',
                        'old_excel_import'  => 'gray',
                        default             => 'gray',
                    }),

                Tables\Columns\TextColumn::make('meta_ad_name')
                    ->label('Latest Meta Ad')
                    ->state(fn (Contact $r) => $r->meta_ad_name ?: $r->meta_ad_id)
                    ->placeholder('—')
                    ->searchable()
                    ->tooltip(fn (Contact $r) => $r->meta_ad_id ? "Ad ID: {$r->meta_ad_id}" : null),

                Tables\Columns\TextColumn::make('customer_category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? Contact::CATEGORY_LABELS[$state] ?? $state : null)
                    ->color(fn ($state) => match ($state) {
                        'purchased_customer' => 'success',
                        'repeat_customer'    => 'info',
                        'enquiry_only'       => 'warning',
                        default              => 'gray',
                    })
                    ->sortable(),

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
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export to Excel (.xlsx)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($livewire) {
                        return app(ExcelExportService::class)
                            ->exportContacts($livewire->getFilteredTableQuery(), 'contacts_export');
                    }),
                Action::make('sync_meta_audience')
                    ->label('Sync to Meta Audience')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Sync Filtered Contacts to Meta Audience')
                    ->modalDescription('Upload all contacts matching the current filters to your Meta Custom Audience list.')
                    ->action(function ($livewire) {
                        $service = app(\App\Services\MetaCustomAudienceService::class);
                        if (! $service->isConfigured()) {
                            Notification::make()
                                ->title('Meta Custom Audience Not Configured')
                                ->body('Please configure META_CUSTOM_AUDIENCE_ID and META_CONVERSIONS_API_ACCESS_TOKEN in .env.')
                                ->warning()
                                ->send();
                            return;
                        }
                        $contacts = $livewire->getFilteredTableQuery()->where('is_blocked', false)->get();
                        $result = $service->syncCustomerList($contacts);
                        if ($result['success']) {
                            Notification::make()
                                ->title('Sync Complete')
                                ->body("Successfully synced {$result['uploaded_count']} contacts to Meta Audience.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Sync Failed')
                                ->body($result['error'] ?? 'API error')
                                ->danger()
                                ->send();
                        }
                    }),
                ExportAction::make()
                    ->exporter(ContactExporter::class)
                    ->formats([ExportFormat::Xlsx, ExportFormat::Csv]),
            ])
            ->filters([
                CreatedAtRangeFilter::make('Date Added'),
                Tables\Filters\SelectFilter::make('lead_stage')
                    ->label('Lead Status')
                    ->options(Lead::$stages)
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $v) => $q->whereHas('leads', fn ($lq) => $lq->where('stage', $v))
                    )),
                Tables\Filters\SelectFilter::make('customer_category')
                    ->label('Contact Status')
                    ->options(Contact::CATEGORY_LABELS),
                Tables\Filters\SelectFilter::make('source')
                    ->label('Lead Source')
                    ->options(Contact::SOURCE_LABELS),
                Tables\Filters\Filter::make('customer_name')
                    ->label('Customer Name')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Customer Name')
                            ->placeholder('Filter by name...'),
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        $data['name'] ?? null,
                        fn ($q, $name) => $q->where('name', 'like', "%{$name}%")
                    ))
                    ->indicateUsing(fn (array $data) => filled($data['name'] ?? null) ? "Name: {$data['name']}" : null),
                Tables\Filters\Filter::make('phone')
                    ->label('Phone Number')
                    ->form([
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->placeholder('Filter by phone...'),
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        $data['phone'] ?? null,
                        fn ($q, $phone) => $q->where('phone', 'like', "%{$phone}%")
                    ))
                    ->indicateUsing(fn (array $data) => filled($data['phone'] ?? null) ? "Phone: {$data['phone']}" : null),
                Tables\Filters\Filter::make('email')
                    ->label('Email Address')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->placeholder('Filter by email...'),
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        $data['email'] ?? null,
                        fn ($q, $email) => $q->where('email', 'like', "%{$email}%")
                    ))
                    ->indicateUsing(fn (array $data) => filled($data['email'] ?? null) ? "Email: {$data['email']}" : null),
                Tables\Filters\Filter::make('product_interest')
                    ->label('Product Interest')
                    ->form([
                        Forms\Components\TextInput::make('product_interest')
                            ->label('Product Interest')
                            ->placeholder('e.g. Mango'),
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        $data['product_interest'] ?? null,
                        fn ($q, $prod) => $q->whereHas('leads', fn ($lq) => $lq->where('product_interest', 'like', "%{$prod}%"))
                    ))
                    ->indicateUsing(fn (array $data) => filled($data['product_interest'] ?? null) ? "Interest: {$data['product_interest']}" : null),
                Tables\Filters\SelectFilter::make('order_status')
                    ->label('Order Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'preparing' => 'Preparing',
                        'delivering' => 'Delivering',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $v) => $q->whereHas('orders', fn ($oq) => $oq->where('status', $v))
                    )),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                        'refunded' => 'Refunded',
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $v) => $q->whereHas('orders', fn ($oq) => $oq->where('payment_status', $v))
                    )),
                Tables\Filters\TernaryFilter::make('is_customer')->label('Is Customer'),
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
                    Actions\BulkAction::make('updateCategoryAndSource')
                        ->label('Update Category / Source…')
                        ->icon('heroicon-o-tag')
                        ->form([
                            Forms\Components\Select::make('customer_category')
                                ->label('Customer Category')
                                ->options(Contact::CATEGORY_LABELS)
                                ->helperText('Leave blank to keep each contact\'s current category.'),
                            Forms\Components\Select::make('source')
                                ->label('Source')
                                ->options(Contact::SOURCE_LABELS)
                                ->helperText('Leave blank to keep each contact\'s current source.'),
                            Forms\Components\Toggle::make('mark_as_customer')
                                ->label('Also mark as customer'),
                        ])
                        ->action(function ($records, array $data) {
                            $update = array_filter([
                                'customer_category' => $data['customer_category'] ?? null,
                                'source'             => $data['source'] ?? null,
                            ]);

                            if (! empty($data['mark_as_customer'])) {
                                $update['is_customer'] = true;
                            }

                            if (empty($update)) {
                                return;
                            }

                            $records->each->update($update);

                            Notification::make()
                                ->title(count($records) . ' contact(s) updated')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    ExportBulkAction::make()
                        ->exporter(ContactExporter::class),
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
