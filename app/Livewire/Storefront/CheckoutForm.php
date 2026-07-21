<?php

namespace App\Livewire\Storefront;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartService;
use App\Services\DeliveryCalculatorService;
use App\Services\PincodeService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.storefront')]
#[Title('Checkout — Merza')]
class CheckoutForm extends Component
{
    use WithFileUploads;

    public string $customer_name    = '';
    public string $customer_phone   = '';
    public string $delivery_address = '';
    public string $postcode         = '';
    public string $city             = '';
    public string $state            = '';
    public string $landmark         = '';
    public bool   $pincodeAutoFilled = false;
    public bool   $pincodeLookupFailed = false;

    public ?string $returningCustomerName = null;
    public bool     $hasPreviousAddress   = false;
    public bool     $previousAddressApplied = false;
    public ?Order   $lastOrderForPhone    = null;

    public bool   $orderPlaced        = false;
    public ?int   $orderId            = null;
    public string $orderNumber        = '';
    public ?string $expectedDelivery  = null;

    public $paymentScreenshot        = null;
    public bool $screenshotUploaded  = false;

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

    protected function messages(): array
    {
        return [
            'customer_name.required'    => "Please tell us your name so we know who's ordering.",
            'customer_phone.required'   => "We need a phone number to reach you about delivery.",
            'delivery_address.required' => 'Please enter the full address where we should deliver.',
            'postcode.required'         => 'Please enter your area pincode.',
            'postcode.digits'           => 'That pincode looks off — it should be exactly 6 digits.',
            'city.required'             => 'Please enter your district/city.',
            'state.required'            => 'Please select your state.',
        ];
    }

    public function updatedCustomerPhone(): void
    {
        $this->returningCustomerName   = null;
        $this->hasPreviousAddress      = false;
        $this->previousAddressApplied  = false;
        $this->lastOrderForPhone       = null;

        $digits = preg_replace('/[^0-9+]/', '', $this->customer_phone);

        if (strlen($digits) < 10) {
            return;
        }

        $contact = Contact::where('phone', $digits)
            ->orWhere('phone', ltrim($digits, '+'))
            ->first();

        $lastOrder = $contact
            ? Order::where('contact_id', $contact->id)->latest()->first()
            : Order::where('customer_phone', $digits)->latest()->first();

        if (! $lastOrder) {
            return;
        }

        $this->returningCustomerName = $contact?->name ?: $lastOrder->customer_name;
        $this->hasPreviousAddress    = filled($lastOrder->delivery_address);
        $this->lastOrderForPhone     = $lastOrder;
    }

    public function useSameAddress(): void
    {
        if (! $this->lastOrderForPhone) {
            return;
        }

        $this->delivery_address = $this->lastOrderForPhone->delivery_address;
        $this->city             = $this->lastOrderForPhone->city;
        $this->state            = $this->lastOrderForPhone->state;
        $this->postcode         = $this->lastOrderForPhone->postcode;
        $this->landmark         = $this->lastOrderForPhone->landmark ?? '';

        $this->previousAddressApplied = true;
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

        try {
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
        } catch (\Throwable $e) {
            Log::error('CheckoutForm: order placement failed', ['error' => $e->getMessage()]);
            $this->addError('cart', "Something went wrong placing your order — nothing was charged. Please try again, or message us on WhatsApp and we'll help right away.");
            return;
        }

        $cart->clear();
        $this->dispatch('cart-updated', count: 0);

        $this->sendWhatsAppConfirmation($order);

        $this->orderPlaced       = true;
        $this->orderId           = $order->id;
        $this->orderNumber       = $order->order_number;
        $this->expectedDelivery  = now()->addDays($zone->eta_days ?? 2)->format('D, d M Y');
    }

    /**
     * Best-effort WhatsApp order confirmation, reusing the same
     * Contact/Conversation/SendWhatsAppMessageJob pipeline the admin
     * "Send WhatsApp Order Update" action already uses. Must never break
     * order placement itself, so failures are logged and swallowed.
     */
    private function sendWhatsAppConfirmation(Order $order): void
    {
        try {
            $phone   = preg_replace('/[^0-9+]/', '', $order->customer_phone);
            $contact = Contact::where('phone', $phone)
                             ->orWhere('phone', ltrim($phone, '+'))
                             ->first();

            if (! $contact) {
                $contact = Contact::create([
                    'name'        => $order->customer_name,
                    'phone'       => $phone,
                    'source'      => 'website',
                    'is_customer' => true,
                ]);
            }

            if ($contact->wa_opted_out || $contact->is_blocked) {
                return;
            }

            $conversation = Conversation::create([
                'contact_id' => $contact->id,
                'channel'    => 'whatsapp',
                'direction'  => 'outbound',
                'message'    => "Hi {$order->customer_name}! 🥭 Your Merza order *{$order->order_number}* has been placed successfully. We'll confirm your delivery details here shortly. Thank you for choosing Merza Bodi!",
                'is_bot'     => false,
                'status'     => 'sent',
            ]);

            SendWhatsAppMessageJob::dispatch($conversation->id);
        } catch (\Throwable $e) {
            Log::error('CheckoutForm: failed to queue WhatsApp order confirmation', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    public function uploadScreenshot(): void
    {
        $this->validate([
            'paymentScreenshot' => 'required|image|max:5120',
        ]);

        $order = Order::find($this->orderId);

        if (! $order) {
            return;
        }

        $disk = config('media-library.disk_name', 'r2');
        $path = $this->paymentScreenshot->storeAs(
            'payment-screenshots',
            $order->order_number . '.' . $this->paymentScreenshot->getClientOriginalExtension(),
            $disk
        );

        $order->update(['payment_screenshot_path' => $path]);

        $this->paymentScreenshot   = null;
        $this->screenshotUploaded  = true;
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
