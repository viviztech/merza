<?php

namespace App\Livewire\Storefront;

use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartService;
use App\Services\DeliveryCalculatorService;
use App\Services\PincodeService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
#[Title('Checkout — Merza')]
class CheckoutForm extends Component
{
    public string $customer_name    = '';
    public string $customer_phone   = '';
    public string $delivery_address = '';
    public string $postcode         = '';
    public string $city             = '';
    public string $state            = '';
    public string $landmark         = '';
    public bool   $pincodeAutoFilled = false;
    public bool   $pincodeLookupFailed = false;

    public bool   $orderPlaced        = false;
    public string $orderNumber        = '';
    public ?string $expectedDelivery  = null;

    protected function rules(): array
    {
        return [
            'customer_name'      => 'required|string|max:120',
            'customer_phone'     => 'required|string|max:20',
            'delivery_address'   => 'required|string|max:500',
            'postcode'           => 'required|digits:6',
            'city'               => 'required|string|max:80',
            'state'              => 'required|string|max:80',
            'landmark'           => 'nullable|string|max:150',
        ];
    }

    public function updatedPostcode(): void
    {
        $this->pincodeAutoFilled   = false;
        $this->pincodeLookupFailed = false;

        if (! preg_match('/^\d{6}$/', $this->postcode)) {
            // Surface "must be 6 digits" as soon as the customer pauses on an
            // incomplete/invalid value, instead of only at final submit.
            if (! empty($this->postcode)) {
                $this->validateOnly('postcode');
            }
            return;
        }

        $result = (new PincodeService())->lookup($this->postcode);

        if (! $result || empty($result['district']) || empty($result['state'])) {
            $this->pincodeLookupFailed = true;
            return;
        }

        $this->city              = $result['district'];
        $this->state             = $result['state'];
        $this->pincodeAutoFilled = true;
    }

    private function resolveZone(): ?DeliveryZone
    {
        if (empty(trim($this->city)) && empty(trim($this->state))) {
            return null;
        }

        return (new DeliveryCalculatorService())->findZone($this->city, $this->state);
    }

    private function getDeliveryBreakdown(): ?array
    {
        $zone = $this->resolveZone();

        if (! $zone) {
            return null;
        }

        $cart       = app(CartService::class);
        $weightKg   = $cart->totalWeightKg();
        $calculator = new DeliveryCalculatorService();

        return $calculator->calculateForZone($zone, $weightKg);
    }

    public function placeOrder(): void
    {
        $this->validate();

        $cart = app(CartService::class);

        if ($cart->count() === 0) {
            $this->addError('cart', 'Your cart is empty.');
            return;
        }

        $zone      = $this->resolveZone();
        $breakdown = $zone ? (new DeliveryCalculatorService())->calculateForZone($zone, $cart->totalWeightKg()) : null;

        // No courier charge could be calculated for this area — do not let the
        // customer proceed to payment/order confirmation without a real charge.
        if (! $breakdown) {
            $this->addError('city', "Sorry, we don't currently deliver to {$this->city}, {$this->state}. Please double-check the pincode, or contact us on WhatsApp for help arranging delivery.");
            return;
        }

        $subtotal    = $cart->subtotal();
        $deliveryFee = $breakdown['total_fee'];
        $total       = $subtotal + $deliveryFee;

        $order = Order::create([
            'channel'                  => 'website',
            'user_id'                  => auth()->id(),
            'customer_name'            => $this->customer_name,
            'customer_phone'           => $this->customer_phone,
            'delivery_address'         => $this->delivery_address,
            'city'                     => $this->city,
            'postcode'                 => $this->postcode,
            'state'                    => $this->state,
            'landmark'                 => $this->landmark ?: null,
            'subtotal'                 => $subtotal,
            'delivery_fee'             => $deliveryFee,
            'total'                    => $total,
            'payment_method'           => 'upi',
        ]);

        foreach ($cart->items() as $item) {
            OrderItem::create([
                'order_id'             => $order->id,
                'product_variant_id'   => $item->variant_id,
                'product_name'         => $item->product_name,
                'variant_name'         => $item->variant_name,
                'free_gift_label'      => $item->free_gift_label ?? null,
                'free_gift_weight_kg'  => $item->free_gift_weight_kg ?? null,
                'sku'                  => $item->sku,
                'quantity'             => $item->qty,
                'unit_price'           => $item->price,
                'subtotal'             => $item->price * $item->qty,
            ]);
        }

        $cart->clear();

        $this->orderPlaced       = true;
        $this->orderNumber       = $order->order_number;
        $this->expectedDelivery  = now()->addDays($zone->eta_days ?? 2)->format('D, d M Y');
    }

    public function render()
    {
        $cart              = app(CartService::class);
        $items             = $cart->items();
        $subtotal          = $cart->subtotal();
        $weightKg          = $cart->totalWeightKg();
        $giftWeightKg      = $cart->totalFreeGiftWeightKg();
        $breakdown         = $this->getDeliveryBreakdown();

        $deliveryFee = $breakdown ? $breakdown['total_fee'] : null;
        $total       = $subtotal + ($deliveryFee ?? 0);

        // Only states we actually have a delivery zone for — keeps customers
        // from typing a state we don't serve (and from typos that would
        // silently fail zone matching later at checkout).
        $stateOptions = DeliveryZone::active()
            ->where('match_type', 'state')
            ->orderBy('name')
            ->pluck('name');

        return view('livewire.storefront.checkout-form',
            compact('items', 'subtotal', 'weightKg', 'giftWeightKg', 'breakdown', 'deliveryFee', 'total', 'stateOptions'));
    }
}
