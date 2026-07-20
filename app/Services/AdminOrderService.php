<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;

/**
 * Shared order-creation logic for admin-panel flows (manual "New Order" and
 * the Lead/Contact "Convert to Order" wizard) — keeps both entry points
 * behaving identically instead of duplicating the item/total assembly.
 */
class AdminOrderService
{
    /**
     * @param  array<int, array{product_variant_id: int, quantity: int}>  $items
     * @param  array<string, mixed>  $customerData  customer_name/phone/email/delivery_address/city/state/postcode/landmark
     * @param  array<string, mixed>  $orderData     status/payment_method/payment_status/delivery_fee/notes/admin_notes/...
     */
    public function createOrder(
        array $items,
        array $customerData,
        array $orderData = [],
        ?Contact $contact = null,
        ?Lead $lead = null,
    ): Order {
        $variants = ProductVariant::with('product')
            ->whereIn('id', collect($items)->pluck('product_variant_id'))
            ->get()
            ->keyBy('id');

        $order = Order::create(array_merge($customerData, $orderData, [
            'channel'    => 'manual',
            'contact_id' => $contact?->id,
            'lead_id'    => $lead?->id,
            'subtotal'   => 0,
            'total'      => (float) ($orderData['delivery_fee'] ?? 0),
        ]));

        foreach ($items as $row) {
            $variant = $variants->get($row['product_variant_id']);

            if (! $variant) {
                continue;
            }

            $qty = max(1, (int) $row['quantity']);

            OrderItem::create([
                'order_id'             => $order->id,
                'product_variant_id'   => $variant->id,
                'product_name'         => $variant->product->name,
                'variant_name'         => $variant->name,
                'free_gift_label'      => $variant->free_gift_label,
                'free_gift_weight_kg'  => $variant->free_gift_weight_kg,
                'sku'                  => $variant->sku,
                'quantity'             => $qty,
                'unit_price'           => $variant->price,
                'subtotal'             => (float) $variant->price * $qty,
            ]);
        }

        $order->recalculateTotals();

        if ($contact) {
            $contact->update(['is_customer' => true]);
        }

        if ($lead) {
            $lead->update(['stage' => 'converted', 'converted_at' => now()]);
        }

        return $order->fresh();
    }

    /**
     * Check each requested item against live stock before an order is
     * created. Returns one message per line item that can't be fulfilled —
     * an empty array means everything is available.
     *
     * @param  array<int, array{product_variant_id: int, quantity: int}>  $items
     * @return array<int, string>
     */
    public function checkAvailability(array $items): array
    {
        $variants = ProductVariant::with('product')
            ->whereIn('id', collect($items)->pluck('product_variant_id'))
            ->get()
            ->keyBy('id');

        $issues = [];

        foreach ($items as $row) {
            $variant = $variants->get($row['product_variant_id'] ?? null);

            if (! $variant) {
                continue;
            }

            $qty = max(1, (int) ($row['quantity'] ?? 1));

            if ($qty > $variant->stock_qty) {
                $issues[] = "{$variant->product->name} ({$variant->name}): only {$variant->stock_qty} in stock, {$qty} requested.";
            }
        }

        return $issues;
    }

    /**
     * Heuristic duplicate-order check: same phone number, order placed in
     * the last 2 hours. Staff decide whether to proceed — this only warns,
     * it never blocks (repeat customers legitimately order again quickly).
     */
    public function findRecentDuplicate(string $phone): ?Order
    {
        $digits = preg_replace('/[^0-9+]/', '', $phone);

        if (strlen($digits) < 10) {
            return null;
        }

        return Order::where('customer_phone', $digits)
            ->where('created_at', '>=', now()->subHours(2))
            ->latest()
            ->first();
    }
}
