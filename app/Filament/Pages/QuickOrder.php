<?php

namespace App\Filament\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\AdminOrderService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as ActionsGroup;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group as SchemaGroup;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

/**
 * One-screen order entry for WhatsApp/phone orders and repeat customers —
 * type a phone number, the previous address and last order surface
 * immediately, no Lead record required first. See AdminOrderService for
 * the shared stock/duplicate checks this reuses with CreateOrder and the
 * Lead "Convert to Order" wizard.
 *
 * Confirmation is two-step: "Generate Order Message" builds a copy-paste
 * WhatsApp confirmation text from the entered items (a temporary order that
 * only exists in this page's state, never the database), then staff copy it
 * to the customer on the call. Only after that does "Confirm Order" appear,
 * which creates the real Order — already status=confirmed with a real
 * confirmed_at timestamp, since by then payment has genuinely been agreed.
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

    public ?string $previewMessage = null;
    public bool $previewReady = false;

    public bool $editingCustomerDetails = false;

    public function mount(): void
    {
        $this->data = [
            'customer_phone' => request()->query('phone', ''),
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'delivery_fee'   => 0,
            'items'          => [['product_variant_id' => null, 'quantity' => 1]],
        ];

        if (filled($this->data['customer_phone'])) {
            $this->lookupCustomer($this->data['customer_phone'], fn ($field, $value) => $this->data[$field] = $value);
        }
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([
                SchemaSection::make('1. Find Customer')
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

                        Forms\Components\Placeholder::make('recent_chat')
                            ->label('')
                            ->visible(fn () => $this->foundContact !== null)
                            ->content(fn () => $this->renderRecentChat())
                            ->columnSpanFull(),

                        ActionsGroup::make([
                            Action::make('repeatLastOrder')
                                ->label('Repeat Last Order')
                                ->icon('heroicon-o-arrow-path')
                                ->color('success')
                                ->visible(fn () => $this->lastOrder !== null)
                                ->action(fn (Set $set) => $this->applyRepeatOrder($set)),
                        ])->columnSpanFull(),
                    ])->columns(2),

                SchemaSection::make('2. Customer & Delivery')->schema([
                    Forms\Components\Placeholder::make('customer_summary')
                        ->label('')
                        ->visible(fn () => $this->hasKnownCustomer() && ! $this->editingCustomerDetails)
                        ->content(fn () => $this->renderCustomerSummary())
                        ->columnSpanFull(),

                    ActionsGroup::make([
                        Action::make('editCustomerDetails')
                            ->label('Edit name / address')
                            ->icon('heroicon-o-pencil-square')
                            ->color('gray')
                            ->action(fn () => $this->editingCustomerDetails = true),
                    ])
                        ->visible(fn () => $this->hasKnownCustomer() && ! $this->editingCustomerDetails)
                        ->columnSpanFull(),

                    SchemaGroup::make([
                        Forms\Components\TextInput::make('customer_name')->required(),
                        Forms\Components\TextInput::make('customer_email')->label('Email (optional)')->email()->nullable(),
                        Forms\Components\Textarea::make('delivery_address')
                            ->required()
                            ->helperText('House/street/area — mention a nearby landmark if it helps the courier.')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('city')->label('District / City'),
                        Forms\Components\TextInput::make('state'),
                        Forms\Components\TextInput::make('postcode')
                            ->label('Pincode')
                            ->rule('digits:6'),
                    ])
                        ->visible(fn () => ! $this->hasKnownCustomer() || $this->editingCustomerDetails)
                        ->columns(2),
                ]),

                SchemaSection::make('3. Add Items')
                    ->description('Tap a product to add it to the order. Tap it again to bump the quantity up.')
                    ->schema([
                        SchemaView::make('filament.pages.quick-order.product-grid')
                            ->viewData(fn () => ['variants' => $this->activeVariants()])
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('order_totals')
                            ->label('')
                            ->content(fn () => $this->renderOrderTotals())
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('items')
                            ->label('Order items')
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
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->previewReady = false)
                                    ->required(),

                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn () => $this->previewReady = false),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('+ Add a line manually')
                            ->reorderable(false)
                            ->deleteAction(fn (\Filament\Actions\Action $action) => $action->after(fn () => $this->previewReady = false))
                            ->columnSpanFull(),
                    ]),

                SchemaSection::make('4. Payment & Delivery')->schema([
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

                    Forms\Components\Placeholder::make('delivery_fee_presets')
                        ->label('Delivery fee — quick pick')
                        ->content(fn () => new HtmlString(
                            '<div class="flex flex-wrap gap-2">' . collect([0, 50, 100, 150])
                                ->map(fn ($amount) => '<button type="button" wire:click="setDeliveryFee(' . $amount . ')" '
                                    . 'class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 '
                                    . 'shadow-sm hover:border-primary-400 hover:text-primary-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-200">'
                                    . "\u{20B9}{$amount}</button>")
                                ->implode('') . '</div>'
                        ))
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('delivery_fee')
                        ->numeric()
                        ->prefix("\u{20B9}")
                        ->default(0)
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn () => $this->previewReady = false),

                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Notes')->rows(2)->nullable()->columnSpanFull(),
                ])->columns(3),

                ActionsGroup::make([
                    Action::make('previewMessage')
                        ->label(fn () => $this->previewReady ? 'Regenerate Message' : 'Generate Order Message')
                        ->color('gray')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->size('lg')
                        ->action(fn () => $this->generatePreview()),
                ])->fullWidth(),

                SchemaSection::make('5. Send & Confirm')
                    ->description('Copy this and send it to the customer on the call/WhatsApp. Edit the items above and click "Regenerate Message" if anything changes.')
                    ->visible(fn () => $this->previewReady)
                    ->schema([
                        Forms\Components\Placeholder::make('preview_display')
                            ->label('')
                            ->content(fn () => new HtmlString(
                                '<textarea readonly rows="16" id="wa-preview-textarea" '
                                . 'class="fi-input block w-full rounded-lg border-none bg-white text-sm text-gray-950 '
                                . 'shadow-sm ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-white dark:ring-white/20 '
                                . 'focus:ring-2 focus:ring-primary-600">'
                                . e($this->previewMessage) . '</textarea>'
                            ))
                            ->columnSpanFull(),

                        ActionsGroup::make([
                            Action::make('copyMessage')
                                ->label('Copy Message')
                                ->color('gray')
                                ->icon('heroicon-o-clipboard-document')
                                // Filament always sets an 'x-on:click' key on the button (null for a
                                // plain action like this), and that null wins over anything passed to
                                // ->extraAttributes(['x-on:click' => ...]) during Blade's attribute
                                // merge — silently dropping the clipboard write. A distinctly-named
                                // event modifier avoids the key collision, and wire:click (which
                                // mounts the action and fires the notification below) is untouched.
                                ->extraAttributes([
                                    'x-on:click.capture' => <<<'JS'
                                        (() => {
                                            const el = document.getElementById('wa-preview-textarea');
                                            if (navigator.clipboard && window.isSecureContext) {
                                                navigator.clipboard.writeText(el.value);
                                            } else {
                                                el.select();
                                                document.execCommand('copy');
                                            }
                                        })()
                                        JS,
                                ])
                                ->action(fn () => Notification::make()->title('Copied — paste it into WhatsApp')->success()->send()),

                            Action::make('confirmOrder')
                                ->label('Confirm Order — Payment Received')
                                ->color('success')
                                ->icon('heroicon-o-check-badge')
                                ->size('lg')
                                ->action(fn () => $this->createOrder()),
                        ]),
                    ]),
            ])->statePath('data'),
        ]);
    }

    /**
     * All active, sellable product variants for the tap-to-add grid.
     */
    protected function activeVariants(): Collection
    {
        return ProductVariant::with('product')
            ->where('is_active', true)
            ->get()
            ->sortBy(fn (ProductVariant $v) => [$v->product->name, (float) $v->weight_value]);
    }

    /**
     * Resolves the current $this->data['items'] rows against their
     * ProductVariant records, skipping any row that doesn't have a product
     * picked yet. Shared by the running-total display and the message
     * builder so they can never disagree on totals.
     *
     * @return \Illuminate\Support\Collection<int, array{variant: ProductVariant, quantity: int, lineTotal: float}>
     */
    protected function cartLines(): \Illuminate\Support\Collection
    {
        $rows = collect($this->data['items'] ?? [])
            ->filter(fn ($row) => filled($row['product_variant_id'] ?? null));

        $variants = ProductVariant::with('product')
            ->whereIn('id', $rows->pluck('product_variant_id'))
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function ($row) use ($variants) {
                $variant = $variants->get($row['product_variant_id']);

                if (! $variant) {
                    return null;
                }

                $qty = max(1, (int) ($row['quantity'] ?? 1));

                return ['variant' => $variant, 'quantity' => $qty, 'lineTotal' => (float) $variant->price * $qty];
            })
            ->filter()
            ->values();
    }

    /**
     * Adds a tapped product to the cart — bumps the quantity if it's
     * already in there, otherwise fills the first empty row or appends a
     * new one. Mutating $this->data directly (rather than via a form Set
     * closure) is safe here since Livewire re-hydrates the schema from this
     * property on every render.
     */
    public function addItemToCart(int $variantId): void
    {
        $items = $this->data['items'] ?? [];

        foreach ($items as $key => $row) {
            if ((int) ($row['product_variant_id'] ?? 0) === $variantId) {
                $items[$key]['quantity'] = (int) ($row['quantity'] ?? 1) + 1;
                $this->data['items']     = $items;
                $this->previewReady      = false;

                return;
            }
        }

        foreach ($items as $key => $row) {
            if (blank($row['product_variant_id'] ?? null)) {
                $items[$key]         = ['product_variant_id' => $variantId, 'quantity' => 1];
                $this->data['items'] = $items;
                $this->previewReady  = false;

                return;
            }
        }

        $items[]              = ['product_variant_id' => $variantId, 'quantity' => 1];
        $this->data['items']  = $items;
        $this->previewReady   = false;
    }

    public function setDeliveryFee(int $amount): void
    {
        $this->data['delivery_fee'] = $amount;
        $this->previewReady         = false;
    }

    /**
     * The always-visible running total shown above the item list — so
     * staff can quote a price mid-call without generating the message
     * first.
     */
    protected function renderOrderTotals(): HtmlString
    {
        $lines = $this->cartLines();

        if ($lines->isEmpty()) {
            return new HtmlString('<div class="text-sm text-gray-400">No items added yet — tap a product above.</div>');
        }

        $count       = $lines->sum('quantity');
        $subtotal    = $lines->sum('lineTotal');
        $deliveryFee = (float) ($this->data['delivery_fee'] ?? 0);
        $total       = $subtotal + $deliveryFee;

        return new HtmlString(
            '<div class="flex flex-wrap items-center gap-x-6 gap-y-1 rounded-lg bg-primary-50 px-4 py-3 text-sm dark:bg-primary-500/10">'
            . '<span>' . $count . ' item' . ($count === 1 ? '' : 's') . '</span>'
            . '<span>Subtotal: <strong>' . "\u{20B9}" . number_format($subtotal, 0) . '</strong></span>'
            . '<span>Delivery: <strong>' . "\u{20B9}" . number_format($deliveryFee, 0) . '</strong></span>'
            . '<span class="text-base font-bold text-primary-700 dark:text-primary-300">Total: '
            . "\u{20B9}" . number_format($total, 0) . '</span>'
            . '</div>'
        );
    }

    /**
     * Builds the copy-paste-ready WhatsApp order confirmation text from the
     * items/delivery fee currently entered, without touching the database —
     * this is the "temporary order" preview staff read out / send to the
     * customer before payment is confirmed.
     */
    protected function buildConfirmationMessage(): string
    {
        $lines      = [];
        $itemsTotal = 0.0;

        foreach ($this->cartLines() as $line) {
            $variant = $line['variant'];
            $qty     = $line['quantity'];
            $itemsTotal += $line['lineTotal'];

            $weightValue = rtrim(rtrim(number_format((float) $variant->weight_value, 3, '.', ''), '0'), '.');
            $pack        = trim($weightValue . $variant->weight_unit);
            $qtyLabel    = $qty > 1 ? " x{$qty}" : '';

            $lines[] = "{$variant->product->name} ({$pack}){$qtyLabel}";
            $lines[] = 'Item Cost: ' . "\u{20B9}" . number_format($line['lineTotal'], 0);
        }

        $deliveryFee = (float) ($this->data['delivery_fee'] ?? 0);
        $total       = $itemsTotal + $deliveryFee;
        $bot         = BotSetting::current();

        return implode("\n", array_filter([
            '*MERZA BODI - ORDER CONFIRMATION*',
            '',
            'Dear Customer,',
            'Thank you for your order.',
            "\u{1F49A} Trusted by 1000+ Happy Customers",
            '',
            '*ORDER DETAILS*',
            implode("\n", $lines),
            'Courier Charge: ' . "\u{20B9}" . number_format($deliveryFee, 0),
            '*Total Amount: ' . "\u{20B9}" . number_format($total, 0) . '*',
            '',
            '*PAYMENT DETAILS*',
            'GPay Number: *' . ($bot->upi_id ?: 'Not set in Settings') . '*',
            'Account Name: ' . ($bot->upi_payee_name ?: 'Not set in Settings'),
            'Please share a screenshot of the payment after you pay.',
            '',
            '*COURIER DISPATCH TIMING*',
            "Payment before 12:00 PM \u{2192} 12:00 PM Dispatch",
            "Payment before 5:00 PM \u{2192} 5:00 PM Dispatch",
            "Payment before 7:00 PM \u{2192} 7:00 PM Dispatch",
            $bot->whatsapp_group_link ? '' : null,
            $bot->whatsapp_group_link ? '*TRACK YOUR ORDER*' : null,
            $bot->whatsapp_group_link
                ? "Join our WhatsApp group for your tracking number and order status updates:\n{$bot->whatsapp_group_link}"
                : null,
            $bot->whatsapp_group_link ? "\u{1F4E6} Tracking ID will be shared after dispatch." : null,
        ], fn ($line) => $line !== null));
    }

    public function generatePreview(): void
    {
        // Triggers validation the same way createOrder() does, so a
        // preview is never generated from an incomplete/invalid form.
        $this->content->getState();

        if ($this->cartLines()->isEmpty()) {
            Notification::make()->title('Add at least one item first')->danger()->send();

            return;
        }

        $this->previewMessage = $this->buildConfirmationMessage();
        $this->previewReady   = true;
    }

    /**
     * @param  callable(string, mixed): void  $set  Set (or the mount-time closure fallback)
     */
    protected function lookupCustomer(?string $phone, callable $set): void
    {
        $this->foundContact           = null;
        $this->lastOrder              = null;
        $this->duplicateWarning       = null;
        $this->editingCustomerDetails = false;

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

        // A known address is filled in straight away — that's the whole
        // point of the collapsed summary card. Staff only see open fields
        // when there's genuinely nothing on file yet, or they hit Edit.
        if ($this->lastOrder) {
            $this->applyPreviousAddress($set);
        }

        $recentDuplicate = (new AdminOrderService())->findRecentDuplicate($digits);

        if ($recentDuplicate) {
            $this->duplicateWarning = "Heads up: {$recentDuplicate->customer_name} already placed order "
                . "{$recentDuplicate->order_number} {$recentDuplicate->created_at->diffForHumans()}. "
                . 'Check before creating another.';
        }
    }

    protected function hasKnownCustomer(): bool
    {
        return $this->foundContact !== null || $this->lastOrder !== null;
    }

    protected function renderCustomerSummary(): HtmlString
    {
        $name    = $this->data['customer_name'] ?? '';
        $address = trim(implode(', ', array_filter([
            $this->data['delivery_address'] ?? null,
            $this->data['city'] ?? null,
            $this->data['state'] ?? null,
            $this->data['postcode'] ?? null,
        ])));

        return new HtmlString(
            '<div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm dark:bg-emerald-500/10">'
            . '<div class="font-semibold text-emerald-700 dark:text-emerald-400">✓ ' . e($name) . '</div>'
            . '<div class="text-gray-600 dark:text-gray-400">' . e($address ?: 'No address on file yet — click Edit to add one.') . '</div>'
            . '</div>'
        );
    }

    /**
     * The last few WhatsApp messages for this contact — lets staff read
     * what the customer already said (which may include an address) instead
     * of asking again. There's no reliable automated address extraction, so
     * this shows the raw chat rather than guessing.
     */
    protected function renderRecentChat(): ?HtmlString
    {
        if (! $this->foundContact) {
            return null;
        }

        $messages = Conversation::where('contact_id', $this->foundContact->id)
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->reverse();

        if ($messages->isEmpty()) {
            return null;
        }

        $rows = $messages->map(function (Conversation $message) {
            $from = $message->direction === 'inbound' ? 'Customer' : ($message->is_bot ? 'Bot' : 'Staff');

            return '<div class="border-b border-gray-100 py-1.5 last:border-0 dark:border-white/10">'
                . '<span class="font-medium text-gray-500 dark:text-gray-400">' . e($from) . ':</span> '
                . '<span class="text-gray-700 dark:text-gray-300">' . e((string) str($message->message)->limit(140)) . '</span>'
                . '</div>';
        })->implode('');

        return new HtmlString(
            '<details class="rounded-lg border border-gray-200 px-4 py-2 text-sm dark:border-white/10">'
            . '<summary class="cursor-pointer font-medium text-gray-600 dark:text-gray-300">Recent WhatsApp messages</summary>'
            . '<div class="mt-2">' . $rows . '</div>'
            . '</details>'
        );
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

    /**
     * @param  callable(string, mixed): void  $set  Set (or the mount-time closure fallback)
     */
    protected function applyPreviousAddress(callable $set): void
    {
        if (! $this->lastOrder) {
            return;
        }

        // Landmark no longer has its own field — fold it into the address
        // text so the information isn't lost when pulling in a past order.
        $address = $this->lastOrder->delivery_address;

        if (filled($this->lastOrder->landmark) && ! str_contains((string) $address, $this->lastOrder->landmark)) {
            $address = trim($address . ' (Near: ' . $this->lastOrder->landmark . ')');
        }

        $set('delivery_address', $address);
        $set('city', $this->lastOrder->city);
        $set('state', $this->lastOrder->state);
        $set('postcode', $this->lastOrder->postcode);
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
        // The customer has already confirmed and paid on the call, so this
        // isn't a "pending" order — it's created already confirmed, with a
        // real confirmed_at timestamp instead of the null one a fresh
        // pending order would get. Staff can't reach this action without
        // generating the message first (button only appears once
        // previewReady is true), so payment has genuinely been confirmed.
        if (! $this->previewReady) {
            Notification::make()->title('Generate the order message first')->danger()->send();

            return;
        }

        // Triggers validation (required(), digits:6 on postcode, etc.) —
        // throws a ValidationException that Filament renders as field
        // errors if anything's invalid. The returned state is discarded;
        // $this->data is already kept in sync by the live form bindings.
        $this->content->getState();

        $data  = $this->data;
        $items = $data['items'] ?? [];
        unset($data['items'], $data['preview_display']);

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

        $customerFields = ['customer_name', 'customer_phone', 'customer_email', 'delivery_address', 'city', 'state', 'postcode'];

        $order = $service->createOrder(
            items: $items,
            customerData: array_intersect_key($data, array_flip($customerFields)),
            orderData: array_diff_key($data, array_flip($customerFields)) + [
                'status'       => 'confirmed',
                'confirmed_at' => now(),
            ],
            contact: $this->foundContact,
        );

        Notification::make()
            ->title("Order {$order->order_number} confirmed")
            ->success()
            ->send();

        $this->previewReady   = false;
        $this->previewMessage = null;

        $this->redirect(OrderResource::getUrl('view', ['record' => $order]));
    }
}
