<?php

namespace App\Livewire\Storefront;

use App\Models\BotSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartService;
use App\Services\DeliveryCalculatorService;
use App\Services\UpiQrService;
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
    public string $city             = '';
    public string $postcode         = '';
    public string $state            = '';
    public string $transaction_id   = '';
    public $paymentScreenshot       = null;
    public string $notes            = '';

    public bool   $orderPlaced  = false;
    public string $orderNumber  = '';

    protected function rules(): array
    {
        return [
            'customer_name'      => 'required|string|max:120',
            'customer_phone'     => 'required|string|max:20',
            'customer_email'     => 'nullable|email|max:150',
            'delivery_address'   => 'required|string|max:500',
            'city'               => 'required|string|max:80',
            'postcode'           => 'required|string|max:10',
            'state'              => 'required|string|max:80',
            'transaction_id'     => 'nullable|string|max:100',
            'paymentScreenshot'  => 'nullable|image|max:5120',
            'notes'              => 'nullable|string|max:500',
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

        if (empty(trim($this->transaction_id)) && ! $this->paymentScreenshot) {
            $this->addError('payment_proof', 'Please enter your UPI transaction ID or upload a payment screenshot.');
            return;
        }

        $cart = app(CartService::class);

        if ($cart->count() === 0) {
            $this->addError('cart', 'Your cart is empty.');
            return;
        }

        $breakdown = $this->getDeliveryBreakdown();

        // No courier charge could be calculated for this area — do not let the
        // customer proceed to payment/order confirmation without a real charge.
        if (! $breakdown) {
            $this->addError('city', "Sorry, we don't currently deliver to {$this->city}, {$this->state}. Please double-check the spelling, or contact us on WhatsApp for help arranging delivery.");
            return;
        }

        $subtotal    = $cart->subtotal();
        $deliveryFee = $breakdown['total_fee'];
        $total       = $subtotal + $deliveryFee;

        $screenshotPath = $this->paymentScreenshot
            ? $this->paymentScreenshot->store('payment-screenshots', config('media-library.disk_name', 'r2'))
            : null;

        $order = Order::create([
            'channel'                  => 'website',
            'user_id'                  => auth()->id(),
            'customer_name'            => $this->customer_name,
            'customer_phone'           => $this->customer_phone,
            'customer_email'           => $this->customer_email ?: null,
            'delivery_address'         => $this->delivery_address,
            'city'                     => $this->city,
            'postcode'                 => $this->postcode,
            'state'                    => $this->state,
            'subtotal'                 => $subtotal,
            'delivery_fee'             => $deliveryFee,
            'total'                    => $total,
            'payment_method'           => 'whatsapp',
            'payment_reference'        => $this->transaction_id ?: null,
            'payment_screenshot_path'  => $screenshotPath,
            'notes'                    => $this->notes ?: null,
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

        $settings   = BotSetting::current();
        $upiId      = $settings->upi_id;
        $upiPayee   = $settings->upi_payee_name ?: 'Merza';
        $qrDataUri  = null;

        if (! empty($upiId) && $total > 0) {
            $qrService = new UpiQrService();
            $uri       = $qrService->buildUpiUri($upiId, $upiPayee, $total, 'Merza Order');
            $qrDataUri = 'data:image/png;base64,' . base64_encode($qrService->generatePng($uri));
        }

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
            compact('items', 'subtotal', 'weightKg', 'breakdown', 'deliveryFee', 'total', 'states', 'upiId', 'upiPayee', 'qrDataUri'));
    }
}
