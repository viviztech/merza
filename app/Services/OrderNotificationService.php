<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Order;

/**
 * Single place that turns an order's current status into a customer-facing
 * WhatsApp message and queues it. Used both automatically (Order::booted()
 * on status/payment changes) and manually (OrderResource/ViewOrder
 * "WhatsApp Update" action), so the two never drift apart.
 */
class OrderNotificationService
{
    private const STATUS_DESCRIPTIONS = [
        'pending'    => 'received and is pending confirmation',
        'confirmed'  => 'has been confirmed and we\'re getting it ready',
        'preparing'  => 'is currently being prepared',
        'delivering' => 'is out for delivery',
        'delivered'  => 'has been delivered successfully',
    ];

    public function buildMessage(Order $order): string
    {
        $settings  = BotSetting::current();
        $statusDesc = self::STATUS_DESCRIPTIONS[$order->status] ?? $order->status;
        $ai        = new AiProviderService($settings);

        $trackingDetails = '';
        if ($order->tracking_number) {
            $trackingDetails = "\nCourier: ".($order->courier_name ?: 'Courier')
                ."\nTracking ID: {$order->tracking_number}";
            if ($order->tracking_url) {
                $trackingDetails .= "\nTrack your shipment: {$order->tracking_url}";
            }
        }

        if (! $ai->isConfigured()) {
            return "Hi {$order->customer_name}! Your order {$order->order_number} {$statusDesc}."
                . " Thank you for choosing Merza Bodi! \u{1F96D}"
                . $trackingDetails;
        }

        $prompt = "Generate a friendly WhatsApp order status update message.
Customer name: {$order->customer_name}
Order number: {$order->order_number}
Order status: {$statusDesc}
Order total: \u{20B9}{$order->total}"
            . "\n\nWrite 2-4 sentences. Warm and professional. Include the order number. End with 'Merza Bodi Team'. Plain text only, no markdown or asterisks.";

        $message = $ai->chat(
            'You are a customer service representative for Merza Bodi, a premium tropical fruit brand. Write warm, professional WhatsApp order update messages in plain text.',
            [['role' => 'user', 'content' => $prompt]],
            200
        );

        return ($message ?? "Hi {$order->customer_name}! Your order {$order->order_number} {$statusDesc}. Thank you for choosing Merza Bodi!")
            . $trackingDetails;
    }

    public function findOrCreateContact(Order $order): Contact
    {
        $phone = preg_replace('/[^0-9+]/', '', $order->customer_phone);

        $contact = Contact::where('phone', $phone)
            ->orWhere('phone', ltrim($phone, '+'))
            ->first();

        if ($contact) {
            return $contact;
        }

        return Contact::create([
            'name'   => $order->customer_name,
            'phone'  => $phone,
            'email'  => $order->customer_email,
            'source' => 'website',
        ]);
    }

    public function sendStatusUpdate(Order $order, ?string $message = null): bool
    {
        $contact = $this->findOrCreateContact($order);

        if (! app(WhatsAppMessagePolicy::class)->canSendFreeform($contact)) {
            return false;
        }

        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel'    => 'whatsapp',
            'direction'  => 'outbound',
            'message'    => $message ?? $this->buildMessage($order),
            'is_bot'     => false,
            'status'     => 'sent',
        ]);

        SendWhatsAppMessageJob::dispatch($conversation->id);

        return true;
    }
}
