<?php

namespace App\Livewire\Storefront;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AnalyticsTracker;
use App\Services\CartService;
use App\Services\DeliveryCalculatorService;
use App\Services\PincodeService;
use App\Services\SabPaisaService;
use Illuminate\Support\Facades\Http;
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
    public string $customer_email   = '';
    public string $delivery_address = '';
    public string $postcode         = '';
    public string $city             = '';
    public string $state            = '';
    public string $landmark         = '';
    public bool   $pincodeAutoFilled = false;
    public bool   $pincodeLookupFailed = false;
    public bool   $locationLookupFailed = false;

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

    public function mount(): void
    {
        app(AnalyticsTracker::class)->track('checkout_start');
    }

    /**
     * True once SabPaisa credentials are set and config('payments.gateway')
     * is flipped to 'sabpaisa' — the only switch between this and the
     * manual UPI-QR + screenshot flow.
     */
    public function gatewayActive(): bool
    {
        return config('payments.gateway') === 'sabpaisa'
            && app(SabPaisaService::class)->isConfigured();
    }

    protected function rules(): array
    {
        return [
            'customer_name'      => 'required|string|max:120',
            'customer_phone'     => 'required|string|max:20',
            'customer_email'     => 'nullable|email|max:255',
            'delivery_address'   => 'required|string|max:500',
            'postcode'           => ['required', 'regex:/^\d{6}$/'],
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
            'customer_email.email'      => 'Please enter a valid email address.',
            'delivery_address.required' => 'Please enter the full address where we should deliver.',
            'postcode.required'         => 'Please enter your area pincode.',
            'postcode.regex'            => 'Please enter a valid 6-digit pincode.',
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

        $digits = preg_replace('/\D/', '', $this->customer_phone) ?? '';

        if (strlen($digits) < 10) {
            return;
        }

        $digits = substr($digits, -10);

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

        if (blank($this->customer_name)) {
            $this->customer_name = $lastOrder->customer_name;
        }

        if (blank($this->customer_email) && filled($lastOrder->customer_email)) {
            $this->customer_email = $lastOrder->customer_email;
        }

        if ($this->hasPreviousAddress) {
            $this->useSameAddress();
        }
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
        $this->pincodeAutoFilled       = true;
        $this->pincodeLookupFailed     = false;
        $this->resetErrorBag(['delivery_address', 'postcode', 'city', 'state']);
    }

    public function changeAddress(): void
    {
        $this->previousAddressApplied = false;
    }

    public function updatedPostcode(): void
    {
        $this->pincodeAutoFilled   = false;
        $this->pincodeLookupFailed = false;

        // Mobile keyboards and browser autofill may insert spaces or dashes.
        // Validate the canonical digits instead of rejecting formatted input.
        $this->postcode = preg_replace('/\D/', '', $this->postcode) ?? '';

        if (! preg_match('/^\d{6}$/', $this->postcode)) {
            // Surface "must be 6 digits" as soon as the customer pauses on an
            // incomplete/invalid value, instead of only at final submit.
            if (! empty($this->postcode)) {
                $this->validateOnly('postcode');
            }
            return;
        }

        $this->resetErrorBag('postcode');

        $result = (new PincodeService())->lookup($this->postcode);

        if (! $result || empty($result['district']) || empty($result['state'])) {
            $this->pincodeLookupFailed = true;
            return;
        }

        $this->city              = $result['district'];
        $this->state             = $result['state'];
        $this->pincodeAutoFilled = true;
    }

    public function autofillFromLocation(float $latitude, float $longitude): void
    {
        $this->locationLookupFailed = false;

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            $this->locationLookupFailed = true;
            return;
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'MerzaStorefront/1.0 (checkout address autofill)'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'addressdetails' => 1,
                    'zoom' => 18,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Reverse geocoding request failed.');
            }

            $address = $response->json('address', []);
            $postcode = preg_replace('/\D/', '', (string) ($address['postcode'] ?? '')) ?? '';
            $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['county'] ?? '';
            $state = $address['state'] ?? '';
            $line = collect([
                $address['house_number'] ?? null,
                $address['road'] ?? null,
                $address['suburb'] ?? $address['neighbourhood'] ?? null,
            ])->filter()->implode(', ');

            $this->delivery_address = $line ?: (string) $response->json('display_name', '');
            $this->city = (string) $city;
            $this->state = (string) $state;
            $this->postcode = strlen($postcode) === 6 ? $postcode : '';
            $this->pincodeAutoFilled = filled($this->postcode) && filled($this->city) && filled($this->state);
            $this->pincodeLookupFailed = ! $this->pincodeAutoFilled;
            $this->resetErrorBag(['delivery_address', 'postcode', 'city', 'state']);
        } catch (\Throwable $e) {
            Log::warning('Checkout location autofill failed', ['error' => $e->getMessage()]);
            $this->locationLookupFailed = true;
        }
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
        $this->postcode = preg_replace('/\D/', '', $this->postcode) ?? '';
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
                'customer_email'           => $this->customer_email ?: null,
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
                    'is_preorder'          => $item->is_preorder ?? false,
                    'available_from'       => $item->available_from ?? null,
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

        app(AnalyticsTracker::class)->track('order_placed', null, $order->id);

        $this->sendWhatsAppConfirmation($order);

        if ($this->gatewayActive()) {
            $session = app(SabPaisaService::class)->createPaymentSession($order, route('payment.return'));

            if ($session && filled($session['checkoutUrl']) && filled($session['clientSecret'])) {
                $this->redirect($session['checkoutUrl'] . '?clientSecret=' . urlencode($session['clientSecret']));
                return;
            }

            // Session creation failed — order still exists as pending/unpaid,
            // same recoverable state as any other placeOrder() hiccup. Fall
            // through to the normal confirmation screen so the customer
            // isn't stuck, and staff can chase payment manually.
            Log::error('CheckoutForm: SabPaisa session creation failed', ['order_id' => $order->id]);
        }

        $this->orderPlaced       = true;
        $this->orderId           = $order->id;
        $this->orderNumber       = $order->order_number;
        $preorderDate = $order->items()->where('is_preorder', true)->max('available_from');
        $dispatchBase = $preorderDate ? \Carbon\Carbon::parse($preorderDate) : now();
        $this->expectedDelivery = $dispatchBase->copy()->addDays($zone->eta_days ?? 2)->format('D, d M Y');
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
