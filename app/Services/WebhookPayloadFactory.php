<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Order;
use App\Services\CartService;

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
                'customer' => [
                    'name' => $order->customer_name,
                    'phone' => $order->customer_phone,
                    'email' => $order->customer_email,
                ],
                'delivery' => [
                    'address' => $order->delivery_address,
                    'city' => $order->city,
                    'state' => $order->state,
                    'postcode' => $order->postcode,
                    'landmark' => $order->landmark,
                ],
                'subtotal' => $order->subtotal,
                'gst_total' => $order->gst_total,
                'delivery_fee' => $order->delivery_fee,
                'packaging_fee' => $order->packaging_fee,
                'total' => $order->total,
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
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ])->all(),
        ];
    }

    public function cart(CartService $cart): array
    {
        return [
            'cart' => [
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
                'phone' => $contact->phone,
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
}
