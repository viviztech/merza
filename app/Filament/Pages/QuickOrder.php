<?php

namespace App\Filament\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Contact;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\AdminOrderService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as ActionsGroup;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * One-screen order entry for WhatsApp/phone orders and repeat customers —
 * type a phone number, the previous address and last order surface
 * immediately, no Lead record required first. See AdminOrderService for
 * the shared stock/duplicate checks this reuses with CreateOrder and the
 * Lead "Convert to Order" wizard.
 */
class QuickOrder extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';
    protected static string|\UnitEnum|null $navigationGroup = 'Sales & CRM';
    protected static ?string $navigationLabel = 'Quick Order';
    protected static ?string $title = 'Quick Order';
    protected static ?int $navigationSort = 0;

    public ?array $data = [];

    public ?Contact $foundContact = null;
    public ?Order $lastOrder = null;
    public ?string $duplicateWarning = null;

    public function mount(): void
    {
        $this->data = [
            'customer_phone' => request()->query('phone', ''),
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'delivery_fee'   => 0,
            'items'          => [[]],
        ];

        if (filled($this->data['customer_phone'])) {
            $this->lookupCustomer($this->data['customer_phone'], fn ($field, $value) => $this->data[$field] = $value);
        }
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([
                SchemaSection::make('Find Customer')
                    ->description('Type the phone number first — if they\'ve ordered before, their details and last order appear below.')
                    ->schema([
                        Forms\Components\TextInput::make('customer_phone')
                            ->label('Phone Number')
                            ->tel()
                            ->required()
                            ->live(debounce: 600)
                            ->afterStateUpdated(fn ($state, Set $set) => $this->lookupCustomer($state, $set))
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('lookup_result')
                            ->label('')
                            ->content(fn () => $this->renderLookupResult())
                            ->columnSpanFull(),

                        ActionsGroup::make([
                            Action::make('usePreviousAddress')
                                ->label('Use Previous Address')
                                ->icon('heroicon-o-map-pin')
                                ->color('gray')
                                ->visible(fn () => $this->lastOrder !== null)
                                ->action(fn (Set $set) => $this->applyPreviousAddress($set)),

                            Action::make('repeatLastOrder')
                                ->label('Repeat Last Order')
                                ->icon('heroicon-o-arrow-path')
                                ->color('success')
                                ->visible(fn () => $this->lastOrder !== null)
                                ->action(fn (Set $set) => $this->applyRepeatOrder($set)),
                        ])->columnSpanFull(),
                    ])->columns(2),

                SchemaSection::make('Customer & Delivery')->schema([
                    Forms\Components\TextInput::make('customer_name')->required(),
                    Forms\Components\TextInput::make('customer_email')->email()->nullable(),
                    Forms\Components\Textarea::make('delivery_address')->required()->columnSpanFull(),
                    Forms\Components\TextInput::make('city')->label('District / City'),
                    Forms\Components\TextInput::make('state'),
                    Forms\Components\TextInput::make('postcode')
                        ->label('Pincode')
                        ->rule('digits:6'),
                    Forms\Components\TextInput::make('landmark'),
                ])->columns(2),

                SchemaSection::make('Items')->schema([
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
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(1)
                        ->addActionLabel('Add Item')
                        ->columnSpanFull(),
                ]),

                SchemaSection::make('Payment & Delivery')->schema([
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
                        ->numeric()
                        ->prefix("\u{20B9}")
                        ->default(0)
                        ->required(),

                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Notes')->rows(2)->nullable()->columnSpanFull(),
                ])->columns(3),
            ])->statePath('data'),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('createOrder')
                ->label('Create Order')
                ->color('success')
                ->icon('heroicon-o-shopping-bag')
                ->action('createOrder'),
        ];
    }

    /**
     * @param  callable(string, mixed): void  $set  Set (or the mount-time closure fallback)
     */
    protected function lookupCustomer(?string $phone, callable $set): void
    {
        $this->foundContact    = null;
        $this->lastOrder       = null;
        $this->duplicateWarning = null;

        $digits = preg_replace('/[^0-9+]/', '', (string) $phone);

        if (strlen($digits) < 10) {
            return;
        }

        $this->foundContact = Contact::where('phone', $digits)
            ->orWhere('phone', ltrim($digits, '+'))
            ->first();

        $this->lastOrder = $this->foundContact
            ? Order::where('contact_id', $this->foundContact->id)->latest()->first()
            : Order::where('customer_phone', $digits)->latest()->first();

        if ($this->foundContact) {
            $set('customer_name', $this->foundContact->name);
        } elseif ($this->lastOrder) {
            $set('customer_name', $this->lastOrder->customer_name);
        }

        $recentDuplicate = (new AdminOrderService())->findRecentDuplicate($digits);

        if ($recentDuplicate) {
            $this->duplicateWarning = "Heads up: {$recentDuplicate->customer_name} already placed order "
                . "{$recentDuplicate->order_number} {$recentDuplicate->created_at->diffForHumans()}. "
                . 'Check before creating another.';
        }
    }

    protected function renderLookupResult(): ?HtmlString
    {
        if ($this->duplicateWarning) {
            return new HtmlString('<div class="text-sm font-semibold text-amber-600">⚠️ ' . e($this->duplicateWarning) . '</div>');
        }

        if ($this->foundContact) {
            $count = $this->foundContact->orders()->count();

            return new HtmlString(
                '<div class="text-sm font-semibold text-emerald-600">✓ Existing customer: '
                . e($this->foundContact->name) . " — {$count} previous order(s)</div>"
            );
        }

        if ($this->lastOrder) {
            return new HtmlString(
                '<div class="text-sm font-semibold text-emerald-600">✓ Found a previous order under this number: '
                . e($this->lastOrder->customer_name) . '</div>'
            );
        }

        if (filled($this->data['customer_phone'] ?? null)) {
            return new HtmlString('<div class="text-sm text-stone-400">New customer — no match found.</div>');
        }

        return null;
    }

    protected function applyPreviousAddress(Set $set): void
    {
        if (! $this->lastOrder) {
            return;
        }

        $set('delivery_address', $this->lastOrder->delivery_address);
        $set('city', $this->lastOrder->city);
        $set('state', $this->lastOrder->state);
        $set('postcode', $this->lastOrder->postcode);
        $set('landmark', $this->lastOrder->landmark);
    }

    protected function applyRepeatOrder(Set $set): void
    {
        if (! $this->lastOrder) {
            return;
        }

        $this->applyPreviousAddress($set);

        $set('items', $this->lastOrder->items->map(fn ($item) => [
            'product_variant_id' => $item->product_variant_id,
            'quantity'           => $item->quantity,
        ])->toArray());
    }

    public function createOrder(): void
    {
        // Triggers validation (required(), digits:6 on postcode, etc.) —
        // throws a ValidationException that Filament renders as field
        // errors if anything's invalid. The returned state is discarded;
        // $this->data is already kept in sync by the live form bindings.
        $this->content->getState();

        $data  = $this->data;
        $items = $data['items'] ?? [];
        unset($data['items']);

        $service = new AdminOrderService();

        $stockIssues = $service->checkAvailability($items);

        if (! empty($stockIssues)) {
            Notification::make()
                ->title('Not enough stock to confirm this order')
                ->body(implode("\n", $stockIssues))
                ->danger()
                ->send();

            return;
        }

        $order = $service->createOrder(
            items: $items,
            customerData: array_intersect_key($data, array_flip([
                'customer_name', 'customer_phone', 'customer_email',
                'delivery_address', 'city', 'state', 'postcode', 'landmark',
            ])),
            orderData: array_diff_key($data, array_flip([
                'customer_name', 'customer_phone', 'customer_email',
                'delivery_address', 'city', 'state', 'postcode', 'landmark',
            ])),
            contact: $this->foundContact,
        );

        Notification::make()
            ->title("Order {$order->order_number} created")
            ->success()
            ->send();

        $this->redirect(OrderResource::getUrl('view', ['record' => $order]));
    }
}
