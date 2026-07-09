<?php

namespace App\Livewire\Storefront;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartService;
use App\Services\DeliveryCalculatorService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
#[Title('Checkout — Merza')]
class CheckoutForm extends Component
{
    public string $customer_name    = '';
    public string $customer_phone   = '';
    public string $customer_email   = '';
    public string $delivery_address = '';
    public string $city             = '';
    public string $postcode         = '';
    public string $state            = '';
    public string $payment_method   = 'cod';
    public string $notes            = '';

    public bool   $orderPlaced  = false;
    public string $orderNumber  = '';

    protected function rules(): array
    {
        return [
            'customer_name'    => 'required|string|max:120',
            'customer_phone'   => 'required|string|max:20',
            'customer_email'   => 'nullable|email|max:150',
            'delivery_address' => 'required|string|max:500',
            'city'             => 'required|string|max:80',
            'postcode'         => 'required|string|max:10',
            'state'            => 'required|string|max:80',
            'payment_method'   => 'required|in:cod,bank_transfer,whatsapp',
            'notes'            => 'nullable|string|max:500',
        ];
    }

    public function updatedCity(): void {}
    public function updatedState(): void {}

    private function getDeliveryBreakdown(): ?array
    {
        if (empty(trim($this->city)) && empty(trim($this->state))) {
            return null;
        }

        $cart        = app(CartService::class);
        $weightKg    = $cart->totalWeightKg();
        $calculator  = new DeliveryCalculatorService();

        return $calculator->calculate($this->city, $this->state, $weightKg);
    }

    public function placeOrder(): void
    {
        $this->validate();

        $cart = app(CartService::class);

        if ($cart->count() === 0) {
            $this->addError('cart', 'Your cart is empty.');
            return;
        }

        $breakdown   = $this->getDeliveryBreakdown();
        $subtotal    = $cart->subtotal();
        $deliveryFee = $breakdown ? $breakdown['total_fee'] : 0;
        $total       = $subtotal + $deliveryFee;

        $order = Order::create([
            'user_id'          => auth()->id(),
            'customer_name'    => $this->customer_name,
            'customer_phone'   => $this->customer_phone,
            'customer_email'   => $this->customer_email ?: null,
            'delivery_address' => $this->delivery_address,
            'city'             => $this->city,
            'postcode'         => $this->postcode,
            'state'            => $this->state,
            'subtotal'         => $subtotal,
            'delivery_fee'     => $deliveryFee,
            'total'            => $total,
            'payment_method'   => $this->payment_method,
            'notes'            => $this->notes ?: null,
        ]);

        foreach ($cart->items() as $item) {
            OrderItem::create([
                'order_id'           => $order->id,
                'product_variant_id' => $item->variant_id,
                'product_name'       => $item->product_name,
                'variant_name'       => $item->variant_name,
                'sku'                => $item->sku,
                'quantity'           => $item->qty,
                'unit_price'         => $item->price,
                'subtotal'           => $item->price * $item->qty,
            ]);
        }

        $cart->clear();

        $this->orderPlaced = true;
        $this->orderNumber = $order->order_number;
    }

    public function render()
    {
        $cart      = app(CartService::class);
        $items     = $cart->items();
        $subtotal  = $cart->subtotal();
        $weightKg  = $cart->totalWeightKg();
        $breakdown = $this->getDeliveryBreakdown();

        $deliveryFee = $breakdown ? $breakdown['total_fee'] : null;
        $total       = $subtotal + ($deliveryFee ?? 0);

        $states = [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar',
            'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh',
            'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra',
            'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
            'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
            'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
            'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
            'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry',
        ];

        return view('livewire.storefront.checkout-form',
            compact('items', 'subtotal', 'weightKg', 'breakdown', 'deliveryFee', 'total', 'states'));
    }
}
