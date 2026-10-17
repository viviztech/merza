<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Order;

class WebhookPayloadFactory
{
    public function order(Order $order): array
    {
        $order->loadMissing('items');

        return [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'channel' => $order->channel,
                'currency' => 'INR',
                'customer' => [
                    'name' => $order->customer_name,
                    // Webhook consumers such as GoWhats need an international
                    // number. Preserve the captured value as well for auditing.
                    'phone' => $this->phoneWithCountryCode($order->customer_phone),
                    'phone_e164' => $this->phoneWithCountryCode($order->customer_phone),
                    'phone_raw' => $order->customer_phone,
                    'email' => $order->customer_email,
                ],
                'delivery' => [
                    'address' => $order->delivery_address,
                    'city' => $order->city,
                    'state' => $order->state,
                    'postcode' => $order->postcode,
                    'landmark' => $order->landmark,
                ],
                'subtotal' => (float) $order->subtotal,
                'gst_total' => (float) $order->gst_total,
                'delivery_fee' => (float) $order->delivery_fee,
                'packaging_fee' => (float) $order->packaging_fee,
                'total' => (float) $order->total,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'tracking_number' => $order->tracking_number,
                'courier_name' => $order->courier_name,
                'tracking_url' => $order->tracking_url,
                'created_at' => $order->created_at?->toIso8601String(),
                'updated_at' => $order->updated_at?->toIso8601String(),
            ],
            'items' => $order->items->map(fn ($item): array => [
                'id' => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'sku' => $item->sku,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'currency' => 'INR',
            ])->all(),
        ];
    }

    public function cart(CartService $cart): array
    {
        return [
            'cart' => [
                'id' => session()->getId(),
                'currency' => 'INR',
                'item_count' => $cart->count(),
                'subtotal' => round($cart->subtotal(), 2),
                'items' => $cart->items()->map(fn ($item): array => [
                    'variant_id' => $item->variant_id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'sku' => $item->sku,
                    'quantity' => (int) $item->qty,
                    'unit_price' => (float) $item->price,
                    'subtotal' => round((float) $item->line_total, 2),
                ])->values()->all(),
            ],
        ];
    }

    public function contact(Contact $contact): array
    {
        return [
            'customer' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $this->phoneWithCountryCode($contact->phone),
                'phone_e164' => $this->phoneWithCountryCode($contact->phone),
                'phone_raw' => $contact->phone,
                'email' => $contact->email,
                'source' => $contact->source,
                'category' => $contact->customer_category,
                'city' => $contact->city,
                'state' => $contact->state,
                'is_customer' => (bool) $contact->is_customer,
                'created_at' => $this->formatTimestamp($contact->created_at),
                'updated_at' => $this->formatTimestamp($contact->updated_at),
            ],
        ];
    }

    private function formatTimestamp(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        return filled($value) ? (string) $value : null;
    }

    private function phoneWithCountryCode(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Merza storefront numbers are Indian 10-digit numbers. Numbers that
        // already include their country code are left intact.
        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        return '+'.$digits;
    }
}
