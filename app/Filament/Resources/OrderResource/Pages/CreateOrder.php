<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section as SchemaSection;
use Illuminate\Database\Eloquent\Model;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    public function mount(): void
    {
        parent::mount();

        $contact = Contact::find(request()->query('contact_id'));

        if ($contact) {
            $this->form->fill([
                'customer_name'  => $contact->name,
                'customer_phone' => $contact->phone,
                'customer_email' => $contact->email,
                'city'           => $contact->city,
                'state'          => $contact->state,
            ]);
        }
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components([
            SchemaSection::make('Customer Information')->schema([
                Forms\Components\TextInput::make('customer_name')->required(),
                Forms\Components\TextInput::make('customer_phone')->required()->tel(),
                Forms\Components\TextInput::make('customer_email')->email()->nullable(),
                Forms\Components\Textarea::make('delivery_address')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('city'),
                Forms\Components\TextInput::make('state'),
                Forms\Components\TextInput::make('postcode'),
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
                        'bank_transfer' => 'Bank Transfer',
                        'whatsapp'      => 'UPI Payment',
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
                    ->label('Admin Notes')->rows(2)->nullable()->columnSpanFull(),
            ])->columns(3),
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $variants = ProductVariant::with('product')
            ->whereIn('id', collect($items)->pluck('product_variant_id'))
            ->get()
            ->keyBy('id');

        $subtotal = 0;
        $lines    = [];

        foreach ($items as $row) {
            $variant = $variants->get($row['product_variant_id']);
            if (! $variant) {
                continue;
            }

            $qty       = max(1, (int) $row['quantity']);
            $lineTotal = (float) $variant->price * $qty;
            $subtotal += $lineTotal;

            $lines[] = [
                'product_variant_id' => $variant->id,
                'product_name'       => $variant->product->name,
                'variant_name'       => $variant->name,
                'sku'                => $variant->sku,
                'quantity'           => $qty,
                'unit_price'         => $variant->price,
                'subtotal'           => $lineTotal,
            ];
        }

        $data['channel']  = 'manual';
        $data['subtotal'] = $subtotal;
        $data['total']    = $subtotal + (float) ($data['delivery_fee'] ?? 0);

        $contact = Contact::find(request()->query('contact_id'));
        if ($contact) {
            $data['contact_id'] = $contact->id;
            $contact->update(['is_customer' => true]);
        }

        $order = Order::create($data);

        foreach ($lines as $line) {
            OrderItem::create(['order_id' => $order->id, ...$line]);
        }

        return $order;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
