<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\BotSetting;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\OrderNotificationService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static string|\UnitEnum|null $navigationGroup = 'Orders & Delivery';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make('Order Details')->schema([
                Forms\Components\TextInput::make('order_number')
                    ->disabled()->dehydrated(false),

                Forms\Components\TextInput::make('channel')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'website'  => 'Website',
                        'whatsapp' => 'WhatsApp',
                        'manual'   => 'Manual',
                        default    => $state,
                    })
                    ->disabled()->dehydrated(false),

                Forms\Components\Select::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'confirmed'  => 'Confirmed',
                        'preparing'  => 'Preparing',
                        'delivering' => 'Delivering',
                        'delivered'  => 'Delivered',
                        'cancelled'  => 'Cancelled',
                    ])
                    ->required(),

                Forms\Components\Select::make('payment_method')
                    ->options([
                        'cod'           => 'Cash on Delivery',
                        'upi'           => 'UPI Payment',
                        'bank_transfer' => 'Bank Transfer',
                        'whatsapp'      => 'WhatsApp Order',
                    ])
                    ->required(),

                Forms\Components\Select::make('payment_status')
                    ->options([
                        'unpaid'   => 'Unpaid',
                        'paid'     => 'Paid',
                        'refunded' => 'Refunded',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('payment_reference')
                    ->label('Payment Reference / UTR')
                    ->placeholder('Customer-reported UPI transaction ref')
                    ->nullable(),
            ])->columns(2),

            SchemaSection::make('Customer Information')->schema([
                Forms\Components\TextInput::make('customer_name')->required(),
                Forms\Components\TextInput::make('customer_phone')->required()->tel(),
                Forms\Components\TextInput::make('customer_email')->email()->nullable(),
                Forms\Components\Textarea::make('delivery_address')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('city')->label('District / City'),
                Forms\Components\TextInput::make('state'),
                Forms\Components\TextInput::make('postcode')->label('Pincode'),
                Forms\Components\TextInput::make('landmark'),
            ])->columns(2),

            SchemaSection::make('Order Items')->schema([
                Forms\Components\Repeater::make('items')
                    ->relationship('items')
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
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                    ])
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => static::hydrateOrderItemData($data))
                    ->mutateRelationshipDataBeforeSaveUsing(fn (array $data) => static::hydrateOrderItemData($data))
                    ->columns(2)
                    ->addActionLabel('Add Item')
                    ->helperText('Adding, removing, or changing quantity here updates the order subtotal/total automatically.')
                    ->columnSpanFull(),
            ]),

            SchemaSection::make('Delivery Tracking')->schema([
                Forms\Components\TextInput::make('tracking_number')
                    ->placeholder('e.g. DTDC123456789')
                    ->nullable(),

                Forms\Components\DateTimePicker::make('confirmed_at')->nullable(),
                Forms\Components\DateTimePicker::make('dispatched_at')->nullable(),
                Forms\Components\DateTimePicker::make('delivered_at')->nullable(),
            ])->columns(2),

            SchemaSection::make('Notes')->schema([
                Forms\Components\Textarea::make('notes')
                    ->label('Customer Notes')->rows(2)->nullable(),
                Forms\Components\Textarea::make('admin_notes')
                    ->label('Admin Notes')->rows(2)->nullable(),
            ])->columns(2),
        ]);
    }

    /**
     * Fill in the snapshot fields (product/variant name, sku, price, free
     * gift) from the selected variant whenever an order-item repeater row
     * is created or edited — mirrors what AdminOrderService does at
     * creation time, so items added later look identical.
     */
    protected static function hydrateOrderItemData(array $data): array
    {
        $variant = ProductVariant::with('product')->find($data['product_variant_id'] ?? null);

        if (! $variant) {
            return $data;
        }

        $qty = max(1, (int) ($data['quantity'] ?? 1));

        return array_merge($data, [
            'product_name'         => $variant->product->name,
            'variant_name'         => $variant->name,
            'free_gift_label'      => $variant->free_gift_label,
            'free_gift_weight_kg'  => $variant->free_gift_weight_kg,
            'sku'                  => $variant->sku,
            'quantity'             => $qty,
            'unit_price'           => $variant->price,
            'subtotal'             => (float) $variant->price * $qty,
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make('Next Step')
                ->schema([
                    TextEntry::make('next_action')
                        ->hiddenLabel()
                        ->state(fn (Order $r) => $r->nextAction()
                            ? "Next: {$r->nextAction()['label']}"
                            : 'No further action needed — order is ' . $r->status . '.')
                        ->size('lg')
                        ->weight('bold')
                        ->color(fn (Order $r) => $r->nextAction()['color'] ?? 'gray'),
                ])
                ->visible(fn (Order $r) => ! in_array($r->status, ['cancelled'])),

            SchemaSection::make('Order Summary')->schema([
                TextEntry::make('order_number')->badge()->color('primary'),
                TextEntry::make('channel')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'website'  => 'gray',
                        'whatsapp' => 'success',
                        'manual'   => 'info',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'website'  => 'Website',
                        'whatsapp' => 'WhatsApp',
                        'manual'   => 'Manual',
                        default    => $state,
                    }),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'    => 'warning',
                        'confirmed'  => 'info',
                        'preparing'  => 'primary',
                        'delivering' => 'success',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    }),
                TextEntry::make('payment_method')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cod'           => 'Cash on Delivery',
                        'upi'           => 'UPI Payment',
                        'bank_transfer' => 'Bank Transfer',
                        'whatsapp'      => 'WhatsApp Order',
                        default         => $state,
                    }),
                TextEntry::make('payment_status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid'     => 'success',
                        'unpaid'   => 'warning',
                        'refunded' => 'danger',
                        default    => 'gray',
                    }),
                TextEntry::make('payment_reference')
                    ->label('Payment Reference / UTR')
                    ->placeholder('—'),
                TextEntry::make('created_at')->dateTime('d M Y, h:i A')->label('Ordered At'),

                TextEntry::make('payment_verification_status')
                    ->label('Screenshot Verification')
                    ->visible(fn (Order $r) => ! empty($r->payment_verification_status))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'ai_matched'         => 'AI Matched',
                        'ai_mismatch'        => 'AI: Mismatch — Review',
                        'ai_unclear'         => 'AI: Unclear — Review',
                        'manually_confirmed' => 'Manually Confirmed',
                        'pending'            => 'Verifying...',
                        default              => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'ai_matched', 'manually_confirmed' => 'success',
                        'ai_mismatch', 'ai_unclear'         => 'danger',
                        'pending'                           => 'warning',
                        default                             => 'gray',
                    }),
                TextEntry::make('payment_verified_amount')
                    ->label('AI-Read Amount')
                    ->money('INR')
                    ->visible(fn (Order $r) => ! is_null($r->payment_verified_amount)),
                TextEntry::make('payment_verification_notes')
                    ->label('Verification Notes')
                    ->visible(fn (Order $r) => ! empty($r->payment_verification_notes))
                    ->columnSpanFull(),

                ImageEntry::make('payment_screenshot_url')
                    ->label('Payment Screenshot')
                    ->visible(fn (Order $r) => ! empty($r->payment_screenshot_path))
                    ->height(300)
                    ->columnSpanFull(),
            ])->columns(3),

            SchemaSection::make('Customer Information')->schema([
                TextEntry::make('customer_name'),
                TextEntry::make('customer_phone'),
                TextEntry::make('customer_email')->placeholder('—'),
                TextEntry::make('delivery_address')->columnSpanFull(),
                TextEntry::make('city')->label('District / City')->placeholder('—'),
                TextEntry::make('state')->placeholder('—'),
                TextEntry::make('postcode')->label('Pincode')->placeholder('—'),
                TextEntry::make('landmark')->placeholder('—'),
            ])->columns(3),

            SchemaSection::make('Order Items')->schema([
                RepeatableEntry::make('items')->schema([
                    TextEntry::make('product_name')->label('Product'),
                    TextEntry::make('variant_name')->label('Variant')->placeholder('—'),
                    TextEntry::make('free_gift_label')->label('Free Gift')->placeholder('—')->color('success'),
                    TextEntry::make('quantity')->label('Qty'),
                    TextEntry::make('unit_price')->money('INR')->label('Unit Price'),
                    TextEntry::make('subtotal')->money('INR')->label('Subtotal'),
                ])->columns(6),
            ]),

            SchemaSection::make('Financials')->schema([
                TextEntry::make('subtotal')->money('INR'),
                TextEntry::make('delivery_fee')->money('INR'),
                TextEntry::make('total')->money('INR')->weight('bold'),
            ])->columns(3),

            SchemaSection::make('Delivery Tracking')->schema([
                TextEntry::make('tracking_number')->placeholder('Not assigned'),
                TextEntry::make('confirmed_at')->dateTime('d M Y, h:i A')->placeholder('—'),
                TextEntry::make('dispatched_at')->dateTime('d M Y, h:i A')->placeholder('—'),
                TextEntry::make('delivered_at')->dateTime('d M Y, h:i A')->placeholder('—'),
            ])->columns(2),

            SchemaSection::make('Notes')->schema([
                TextEntry::make('notes')->label('Customer Notes')->placeholder('—'),
                TextEntry::make('admin_notes')->label('Admin Notes')->placeholder('—'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable()->sortable()
                    ->badge()->color('primary'),

                Tables\Columns\TextColumn::make('channel')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'website'  => 'gray',
                        'whatsapp' => 'success',
                        'manual'   => 'info',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'website'  => 'Website',
                        'whatsapp' => 'WhatsApp',
                        'manual'   => 'Manual',
                        default    => $state,
                    }),

                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable()
                    ->description(fn (Order $r) => $r->customer_phone),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()->color('gray'),

                Tables\Columns\TextColumn::make('total')
                    ->money('INR')->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'    => 'warning',
                        'confirmed'  => 'info',
                        'preparing'  => 'primary',
                        'delivering' => 'success',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cod'           => 'COD',
                        'upi'           => 'UPI',
                        'bank_transfer' => 'Bank Transfer',
                        'whatsapp'      => 'WhatsApp',
                        default         => $state,
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid'     => 'success',
                        'unpaid'   => 'warning',
                        'refunded' => 'danger',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_verification_status')
                    ->label('Screenshot')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'ai_matched'         => 'AI Matched',
                        'ai_mismatch'        => 'Mismatch',
                        'ai_unclear'         => 'Unclear',
                        'manually_confirmed' => 'Confirmed',
                        'pending'            => 'Verifying',
                        default              => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'ai_matched', 'manually_confirmed' => 'success',
                        'ai_mismatch', 'ai_unclear'         => 'danger',
                        'pending'                           => 'warning',
                        default                             => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')->sortable()->label('Date'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->options([
                        'website'  => 'Website',
                        'whatsapp' => 'WhatsApp',
                        'manual'   => 'Manual',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'confirmed'  => 'Confirmed',
                        'preparing'  => 'Preparing',
                        'delivering' => 'Delivering',
                        'delivered'  => 'Delivered',
                        'cancelled'  => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'unpaid'   => 'Unpaid',
                        'paid'     => 'Paid',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cod'           => 'Cash on Delivery',
                        'upi'           => 'UPI Payment',
                        'bank_transfer' => 'Bank Transfer',
                        'whatsapp'      => 'WhatsApp Order',
                    ]),
                Tables\Filters\Filter::make('today')
                    ->label('Today\'s Orders')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                static::nextActionButton(),

                Action::make('invoice')
                    ->label('')
                    ->tooltip('Download Invoice PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (Order $r) => route('admin.orders.invoice', $r))
                    ->openUrlInNewTab(),

                Action::make('deliverySlip')
                    ->label('')
                    ->tooltip('Download Delivery Challan')
                    ->icon('heroicon-o-truck')
                    ->color('gray')
                    ->url(fn (Order $r) => route('admin.orders.delivery-slip', $r))
                    ->openUrlInNewTab(),

                Actions\ViewAction::make(),
                Actions\EditAction::make(),

                Action::make('whatsappUpdate')
                    ->label('WhatsApp Update')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn (Order $r) => ! in_array($r->status, ['cancelled']) && ! empty($r->customer_phone))
                    ->fillForm(fn (Order $r) => ['wa_message' => app(OrderNotificationService::class)->buildMessage($r)])
                    ->form([
                        Forms\Components\Textarea::make('wa_message')
                            ->label('WhatsApp Message')
                            ->rows(6)
                            ->required(),
                    ])
                    ->modalHeading('Send WhatsApp Order Update')
                    ->modalDescription(fn (Order $r) => "Send order update to {$r->customer_name} ({$r->customer_phone})")
                    ->modalSubmitActionLabel('Send via WhatsApp')
                    ->action(function (Order $r, array $data) {
                        app(OrderNotificationService::class)->sendStatusUpdate($r, $data['wa_message']);

                        Notification::make()
                            ->title('WhatsApp message queued for ' . $r->customer_name)
                            ->success()
                            ->send();
                    }),

                Action::make('cancel')
                    ->label('')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $r) => !in_array($r->status, ['delivered', 'cancelled']))
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Order')
                    ->modalDescription('Are you sure you want to cancel this order?')
                    ->action(fn (Order $r) => $r->update(['status' => 'cancelled'])),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('markPaid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-banknotes')
                        ->action(fn ($records) => $records->each->update(['payment_status' => 'paid']))
                        ->requiresConfirmation(),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view'   => Pages\ViewOrder::route('/{record}'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    /**
     * The single "what's next" button, shared by the orders table and
     * ViewOrder's header actions, driven entirely by Order::nextAction() so
     * the flow (Confirm → Pack → Payment → Dispatch → Deliver) is defined
     * in exactly one place.
     */
    public static function nextActionButton(): Action
    {
        return Action::make('nextAction')
            ->label(fn (Order $r) => $r->nextAction()['label'] ?? 'Next Action')
            ->icon(fn (Order $r) => $r->nextAction()['icon'] ?? 'heroicon-o-arrow-right-circle')
            ->color(fn (Order $r) => $r->nextAction()['color'] ?? 'gray')
            ->visible(fn (Order $r) => $r->nextAction() !== null)
            ->requiresConfirmation()
            ->modalHeading(fn (Order $r) => $r->nextAction()['label'] ?? 'Next Action')
            ->modalDescription(fn (Order $r) => static::nextActionModalDescription($r))
            ->modalSubmitActionLabel(fn (Order $r) => $r->nextAction()['label'] ?? 'Confirm')
            ->form(fn (Order $r) => ($r->nextAction()['trackingForm'] ?? false)
                ? [
                    Forms\Components\TextInput::make('tracking_number')
                        ->label('Tracking Number (optional)')
                        ->placeholder('e.g. DTDC123456789'),
                ]
                : [])
            ->action(function (Order $r, array $data) {
                $next = $r->nextAction();

                if (! $next) {
                    return;
                }

                $updates = $next['updates'];
                if (($next['trackingForm'] ?? false) && ! empty($data['tracking_number'])) {
                    $updates['tracking_number'] = $data['tracking_number'];
                }

                $r->update($updates);

                Notification::make()
                    ->title("Order {$r->order_number} updated")
                    ->success()
                    ->send();
            });
    }

    private static function nextActionModalDescription(Order $r): string
    {
        $next = $r->nextAction();

        if (($next['key'] ?? null) === 'markPaid') {
            $upi = BotSetting::current();

            return "Order total: \u{20B9}{$r->total}. Customer reference: " . ($r->payment_reference ?: '—')
                . '. Check against your UPI/GPay (' . ($upi->upi_id ?: 'not set') . ') before confirming.';
        }

        return "Order {$r->order_number} for {$r->customer_name}.";
    }
}
