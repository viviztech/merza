<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Order;
use App\Services\GroqService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
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
                        'bank_transfer' => 'Bank Transfer',
                        'whatsapp'      => 'WhatsApp Payment',
                    ])
                    ->required(),

                Forms\Components\Select::make('payment_status')
                    ->options([
                        'unpaid'   => 'Unpaid',
                        'paid'     => 'Paid',
                        'refunded' => 'Refunded',
                    ])
                    ->required(),
            ])->columns(2),

            SchemaSection::make('Customer Information')->schema([
                Forms\Components\TextInput::make('customer_name')->required(),
                Forms\Components\TextInput::make('customer_phone')->required()->tel(),
                Forms\Components\TextInput::make('customer_email')->email()->nullable(),
                Forms\Components\Textarea::make('delivery_address')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('city'),
                Forms\Components\TextInput::make('state'),
                Forms\Components\TextInput::make('postcode'),
            ])->columns(2),

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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make('Order Summary')->schema([
                TextEntry::make('order_number')->badge()->color('primary'),
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
                        'bank_transfer' => 'Bank Transfer',
                        'whatsapp'      => 'WhatsApp Payment',
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
                TextEntry::make('created_at')->dateTime('d M Y, h:i A')->label('Ordered At'),
            ])->columns(3),

            SchemaSection::make('Customer Information')->schema([
                TextEntry::make('customer_name'),
                TextEntry::make('customer_phone'),
                TextEntry::make('customer_email')->placeholder('—'),
                TextEntry::make('delivery_address')->columnSpanFull(),
                TextEntry::make('city')->placeholder('—'),
                TextEntry::make('state')->placeholder('—'),
                TextEntry::make('postcode')->placeholder('—'),
            ])->columns(3),

            SchemaSection::make('Order Items')->schema([
                RepeatableEntry::make('items')->schema([
                    TextEntry::make('product_name')->label('Product'),
                    TextEntry::make('variant_name')->label('Variant')->placeholder('—'),
                    TextEntry::make('quantity')->label('Qty'),
                    TextEntry::make('unit_price')->money('INR')->label('Unit Price'),
                    TextEntry::make('subtotal')->money('INR')->label('Subtotal'),
                ])->columns(5),
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

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')->sortable()->label('Date'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
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
                        'bank_transfer' => 'Bank Transfer',
                        'whatsapp'      => 'WhatsApp Payment',
                    ]),
                Tables\Filters\Filter::make('today')
                    ->label('Today\'s Orders')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn (Order $r) => $r->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn (Order $r) => $r->update([
                        'status'       => 'confirmed',
                        'confirmed_at' => now(),
                    ])),

                Action::make('prepare')
                    ->label('Prepare')
                    ->icon('heroicon-o-cube')
                    ->color('primary')
                    ->visible(fn (Order $r) => $r->status === 'confirmed')
                    ->action(fn (Order $r) => $r->update(['status' => 'preparing'])),

                Action::make('dispatch')
                    ->label('Dispatch')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(fn (Order $r) => $r->status === 'preparing')
                    ->form([
                        Forms\Components\TextInput::make('tracking_number')
                            ->label('Tracking Number (optional)')
                            ->placeholder('e.g. DTDC123456789'),
                    ])
                    ->action(fn (Order $r, array $data) => $r->update([
                        'status'         => 'delivering',
                        'dispatched_at'  => now(),
                        'tracking_number' => $data['tracking_number'] ?? null,
                    ])),

                Action::make('deliver')
                    ->label('Delivered')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Order $r) => $r->status === 'delivering')
                    ->requiresConfirmation()
                    ->action(fn (Order $r) => $r->update([
                        'status'       => 'delivered',
                        'delivered_at' => now(),
                    ])),

                Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Order $r) => $r->payment_status === 'unpaid' && $r->status !== 'cancelled')
                    ->requiresConfirmation()
                    ->action(fn (Order $r) => $r->update(['payment_status' => 'paid'])),

                Action::make('invoice')
                    ->label('')
                    ->tooltip('Download Invoice PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (Order $r) => route('admin.orders.invoice', $r))
                    ->openUrlInNewTab(),

                Action::make('deliverySlip')
                    ->label('')
                    ->tooltip('Download Delivery Slip')
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
                    ->fillForm(function (Order $r): array {
                        $settings = BotSetting::current();
                        $statusDescriptions = [
                            'pending'    => 'received and is pending confirmation',
                            'confirmed'  => 'has been confirmed and we\'re getting it ready',
                            'preparing'  => 'is currently being prepared',
                            'delivering' => 'is out for delivery',
                            'delivered'  => 'has been delivered successfully',
                        ];
                        $statusDesc = $statusDescriptions[$r->status] ?? $r->status;

                        if (empty($settings->groq_api_key)) {
                            $fallback = "Hi {$r->customer_name}! Your order {$r->order_number} {$statusDesc}."
                                . ($r->tracking_number ? " Tracking: {$r->tracking_number}." : '')
                                . " Thank you for choosing Merza Bodi! 🥭";
                            return ['wa_message' => $fallback];
                        }

                        $groq   = new GroqService($settings->groq_api_key, $settings->groq_model ?? 'llama-3.1-8b-instant');
                        $prompt = "Generate a friendly WhatsApp order status update message.
Customer name: {$r->customer_name}
Order number: {$r->order_number}
Order status: {$statusDesc}
Order total: ₹{$r->total}"
                            . ($r->tracking_number ? "\nTracking number: {$r->tracking_number}" : '')
                            . "\n\nWrite 2-4 sentences. Warm and professional. Include the order number. End with 'Merza Bodi Team'. Plain text only, no markdown or asterisks.";

                        $message = $groq->chat(
                            'You are a customer service representative for Merza Bodi, a premium tropical fruit brand. Write warm, professional WhatsApp order update messages in plain text.',
                            [['role' => 'user', 'content' => $prompt]],
                            200
                        );
                        return ['wa_message' => $message ?? "Hi {$r->customer_name}! Your order {$r->order_number} {$statusDesc}. Thank you for choosing Merza Bodi!"];
                    })
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
                        $phone   = preg_replace('/[^0-9+]/', '', $r->customer_phone);
                        $contact = Contact::where('phone', $phone)
                                         ->orWhere('phone', ltrim($phone, '+'))
                                         ->first();

                        if (! $contact) {
                            $contact = Contact::create([
                                'name'   => $r->customer_name,
                                'phone'  => $phone,
                                'email'  => $r->customer_email,
                                'source' => 'storefront',
                            ]);
                        }

                        $conversation = Conversation::create([
                            'contact_id' => $contact->id,
                            'channel'    => 'whatsapp',
                            'direction'  => 'outbound',
                            'message'    => $data['wa_message'],
                            'is_bot'     => false,
                            'status'     => 'sent',
                        ]);

                        SendWhatsAppMessageJob::dispatch($conversation->id);

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
            'view'   => Pages\ViewOrder::route('/{record}'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
