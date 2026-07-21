<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\OrderResource;
use App\Filament\Filters\CreatedAtRangeFilter;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\AdminOrderService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
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
                    ->searchable()->sortable(),

                Tables\Columns\TextColumn::make('contact.phone')
                    ->label('Phone')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->url(fn (Lead $r) => $r->contact?->call_url)
                    ->color('primary'),

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

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->label('Added')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->groups([
                Tables\Grouping\Group::make('stage')
                    ->label('Stage')
                    ->getTitleFromRecordUsing(fn (Lead $r) => Lead::$stages[$r->stage] ?? $r->stage),
            ])
            ->filters([
                CreatedAtRangeFilter::make(),
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
                static::convertToOrderAction(),
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

    /**
     * "Convert to Order" — a 3-step wizard modal (Customer & Delivery →
     * Items → Payment & Confirm) that never leaves the current page.
     * Used both as a Lead table row action (Filament auto-injects the
     * row's Lead as $record) and, with a custom fillForm/action pair, as
     * a header action on the Contact Workspace page.
     */
    public static function convertToOrderAction(): Action
    {
        return Action::make('convertToOrder')
            ->label('Convert to Order')
            ->icon('heroicon-o-shopping-bag')
            ->color('success')
            ->visible(fn (Lead $r) => ! in_array($r->stage, ['converted', 'lost']) && $r->contact_id)
            ->modalWidth('3xl')
            ->modalHeading('Convert Enquiry to Order')
            ->modalSubmitActionLabel('Create Order')
            ->steps(fn (Lead $r) => static::convertToOrderSteps($r))
            ->fillForm(fn (Lead $r) => static::convertToOrderFormDefaults($r))
            ->action(function (array $data, Lead $r) {
                $order = static::handleConvertToOrder($data, $r);

                Notification::make()
                    ->title("Order {$order->order_number} created")
                    ->success()
                    ->send();

                return redirect(OrderResource::getUrl('view', ['record' => $order]));
            });
    }

    /**
     * @return array<Step>
     */
    public static function convertToOrderSteps(Lead $lead): array
    {
        return [
            Step::make('customer')
                ->label('Customer & Delivery')
                ->schema([
                    Forms\Components\TextInput::make('customer_name')->required(),
                    Forms\Components\TextInput::make('customer_phone')->required()->tel(),
                    Forms\Components\TextInput::make('customer_email')->email()->nullable(),
                    Forms\Components\Textarea::make('delivery_address')->required()->columnSpanFull(),
                    Forms\Components\TextInput::make('city')->label('District / City'),
                    Forms\Components\TextInput::make('state'),
                    Forms\Components\TextInput::make('postcode')
                        ->label('Pincode')
                        ->rule('digits:6'),
                    Forms\Components\TextInput::make('landmark'),
                ])->columns(2),

            Step::make('items')
                ->label('Items')
                ->schema([
                    Forms\Components\Placeholder::make('interest_hint')
                        ->label('Customer mentioned')
                        ->visible(fn () => filled($lead->product_interest))
                        ->content($lead->product_interest ?? ''),

                    Forms\Components\Repeater::make('items')
                        ->schema([
                            Forms\Components\Select::make('product_variant_id')
                                ->label('Product')
                                ->options(fn () => ProductVariant::with('product')
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(fn (ProductVariant $v) => [
                                        $v->id => "{$v->product->name} – {$v->name} (\u{20B9}{$v->price})",
                                    ]))
                                ->searchable()
                                ->required(),

                            Forms\Components\TextInput::make('quantity')
                                ->numeric()->default(1)->minValue(1)->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(1)
                        ->addActionLabel('Add Item')
                        ->columnSpanFull(),
                ]),

            Step::make('payment')
                ->label('Payment & Confirm')
                ->schema([
                    Forms\Components\Select::make('payment_method')
                        ->options([
                            'cod'           => 'Cash on Delivery',
                            'upi'           => 'UPI Payment',
                            'bank_transfer' => 'Bank Transfer',
                            'whatsapp'      => 'WhatsApp Order',
                        ])
                        ->default('cod')
                        ->required(),

                    Forms\Components\Select::make('payment_status')
                        ->options([
                            'unpaid'   => 'Unpaid',
                            'paid'     => 'Paid',
                            'refunded' => 'Refunded',
                        ])
                        ->default('unpaid')
                        ->required(),

                    Forms\Components\TextInput::make('delivery_fee')
                        ->numeric()->prefix("\u{20B9}")->default(0)->required(),

                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Notes')->rows(2)->columnSpanFull(),
                ])->columns(3),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function convertToOrderFormDefaults(Lead $lead): array
    {
        $contact = $lead->contact;

        // Prefer the contact's most recent order for the delivery address —
        // Contact itself only stores city/state, not a full street address,
        // so without this a returning customer's address gets retyped every time.
        $lastOrder = $contact
            ? \App\Models\Order::where('contact_id', $contact->id)->latest()->first()
            : null;

        return [
            'customer_name'    => $contact?->name,
            'customer_phone'   => $contact?->phone,
            'customer_email'   => $contact?->email,
            'delivery_address' => $lastOrder?->delivery_address,
            'city'             => $lastOrder?->city ?? $contact?->city,
            'state'            => $lastOrder?->state ?? $contact?->state,
            'postcode'         => $lastOrder?->postcode,
            'landmark'         => $lastOrder?->landmark,
            'admin_notes'      => $lead->notes,
        ];
    }

    public static function handleConvertToOrder(array $data, Lead $lead): \App\Models\Order
    {
        $items = $data['items'] ?? [];
        unset($data['items'], $data['interest_hint']);

        $service = new AdminOrderService();

        $stockIssues = $service->checkAvailability($items);

        if (! empty($stockIssues)) {
            Notification::make()
                ->title('Not enough stock to confirm this order')
                ->body(implode("\n", $stockIssues))
                ->danger()
                ->send();

            throw new Halt();
        }

        return $service->createOrder(
            items: $items,
            customerData: array_intersect_key($data, array_flip([
                'customer_name', 'customer_phone', 'customer_email',
                'delivery_address', 'city', 'state', 'postcode', 'landmark',
            ])),
            orderData: array_diff_key($data, array_flip([
                'customer_name', 'customer_phone', 'customer_email',
                'delivery_address', 'city', 'state', 'postcode', 'landmark',
            ])),
            contact: $lead->contact,
            lead: $lead,
        );
    }
}
