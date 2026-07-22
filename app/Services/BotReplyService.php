<?php

namespace App\Services;

use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Product;
use App\Models\WhatsAppSession;

class BotReplyService
{
    public function __construct(private readonly BotSetting $settings) {}

    /**
     * Generate an AI reply for an inbound WhatsApp message, using whichever
     * provider is set active in Bot Settings (Groq, ChatGPT, or Claude).
     *
     * $session (when passed) lets the reply be aware of a cart/checkout already
     * in progress, so an off-script question doesn't get answered in a vacuum.
     */
    public function generateReply(
        Contact $contact,
        string $inboundMessage,
        Conversation $inboundConversation,
        ?WhatsAppSession $session = null,
    ): ?string {
        $systemPrompt = $this->buildSystemPrompt($contact, $inboundMessage, $session);
        $history      = $this->buildMessageHistory($contact, $inboundConversation);

        return (new AiProviderService($this->settings))->chat($systemPrompt, $history, 400);
    }

    // ─── System prompt ───────────────────────────────────────────────────────

    private function buildSystemPrompt(Contact $contact, string $inboundMessage, ?WhatsAppSession $session = null): string
    {
        $products    = $this->getProductSummary();
        $orderInfo   = $this->getOrderContext($contact);
        $customerContext = $this->getCustomerContext($contact);
        $cartContext = $session ? $this->getCartContext($session) : '';
        $ownerNotes  = $this->buildOwnerInstructions($contact, $inboundMessage);

        return <<<PROMPT
You are the WhatsApp assistant for Merza Natural Squash, a premium tropical fruit brand in Bodinayakanur, Tamil Nadu, India.

BUSINESS INFO:
- Shop: HP Petrol Bunk, Pankajam School Opp., Thevaram Road, Bodinayakanur — 625513
- Phone: +91 93600 64278
- Email: merzabodinayakanur@gmail.com
- Hours: Monday–Saturday, 9 AM – 6 PM

OUR PRODUCTS (Mukkani — Three Fruits, One Promise):
{$products}

CUSTOMER:
{$customerContext}

{$cartContext}

{$orderInfo}

INSTRUCTIONS:
- Detect whether the customer writes in Tamil or English, and reply in the SAME language.
- Be warm, helpful, and concise — this is WhatsApp, keep replies under 80 words.
- If the customer asks about order status, use the ORDER INFO above.
- If CURRENT CART is shown above, they already have items waiting — answer their question, then gently point them back to it (tell them to reply "checkout" to continue instantly). Don't ask them to re-describe what they already added.
- If CURRENT CART is empty and they want to order, ask for: product name, quantity, and delivery address.
- If they ask for prices, share the product info above.
- Never make up information. If you don't know something, say you'll check and get back.
- NEVER agree to, confirm, or describe steps for cancelling an order, issuing a refund, or resolving a complaint/dispute — you have no ability to actually do any of this, only staff can. Do not say things like "we can cancel it immediately" or ask for an account/payment number to refund to. Instead say only that our team will personally contact them during working hours (Mon–Sat, 9 AM–6 PM) to sort it out, and give the phone/email below. Do not imply the request has already been actioned.
- If the customer is frustrated, asks to speak to a person, or the issue is complex, always provide human contact: "Call +91 93600 64278 or email merzabodinayakanur@gmail.com (Mon–Sat 9 AM–6 PM)."
- If the customer wants to stop receiving messages, tell them to reply STOP.
- You are an automated assistant. Do not claim to be human if directly asked.
- End every reply with "— Merza Automated Assistant 🥭"
- Do NOT use markdown, bullet points, or headers in your reply.
{$ownerNotes}
PROMPT;
    }

    /**
     * Store owner's own instructions from Bot Settings → WhatsApp Auto-reply
     * Prompt, layered on top of the auto-generated context above rather than
     * replacing it — so they can tweak tone/policy without losing the live
     * product catalogue and order lookup.
     */
    private function buildOwnerInstructions(Contact $contact, string $inboundMessage): string
    {
        $template = trim($this->settings->wa_reply_prompt_template ?? '');

        if ($template === '') {
            return '';
        }

        $name = $contact->name && ! str_starts_with($contact->name, 'WA:') ? $contact->name : 'Customer';

        $filled = str_replace(
            ['{{customer_name}}', '{{customer_message}}', '{{product_interest}}'],
            [$name, $inboundMessage, $contact->active_lead?->product_interest ?? 'our fresh fruits'],
            $template
        );

        return "\nSTORE OWNER'S ADDITIONAL INSTRUCTIONS:\n{$filled}";
    }

    private function getProductSummary(): string
    {
        $products = Product::where('is_active', true)
            ->with('activeVariants')
            ->get();

        if ($products->isEmpty()) {
            return "Imam Pasand Mango, Red Banana, Vietnam Early Gold Jackfruit, Orange Squash, Banana Ice Cream, Mango Jam.";
        }

        return $products->map(function (Product $p) {
            $variants = $p->activeVariants->map(fn ($v) => "₹{$v->price} ({$v->name})")->implode(', ');
            $price    = $variants ?: (isset($p->base_price) ? "from ₹{$p->base_price}" : '');
            return "- {$p->name}" . ($price ? ": {$price}" : '');
        })->implode("\n");
    }

    private function getOrderContext(Contact $contact): string
    {
        $orders = Order::where('contact_id', $contact->id)
            ->orWhere('customer_phone', $contact->phone)
            ->latest()
            ->take(3)
            ->with('items')
            ->get();

        if ($orders->isEmpty()) {
            return '';
        }

        $lines = ["ORDER INFO (recent orders for this customer):"];
        foreach ($orders as $order) {
            $items   = $order->items->map(fn ($i) => "{$i->product_name} x{$i->quantity}")->implode(', ');
            $lines[] = "- #{$order->order_number}: {$items} | Status: {$order->status} | Total: ₹{$order->total}";
            if ($order->tracking_number) {
                $lines[] = "  Tracking: {$order->tracking_number}";
            }
        }

        return implode("\n", $lines);
    }

    private function getCartContext(WhatsAppSession $session): string
    {
        $cart = $session->data['cart'] ?? [];

        if (empty($cart)) {
            return '';
        }

        $lines = ['CURRENT CART (already in progress — do not ask them to repeat it):'];
        $subtotal = 0;

        foreach ($cart as $item) {
            $lineTotal = $item['price'] * $item['qty'];
            $subtotal += $lineTotal;
            $lines[] = "- {$item['product_name']} ({$item['variant_name']}) x{$item['qty']} = ₹" . number_format($lineTotal, 2);
        }

        $lines[] = "Subtotal: ₹" . number_format($subtotal, 2);

        $zone = $session->data['zone'] ?? null;
        if ($zone) {
            $lines[] = "Delivery zone: {$zone['name']} (₹{$zone['rate_per_kg']}/kg courier, added at checkout)";
        }

        return implode("\n", $lines);
    }

    private function getCustomerContext(Contact $contact): string
    {
        $name = $contact->name && ! str_starts_with($contact->name, 'WA:')
            ? $contact->name
            : 'Customer';

        return "Name: {$name} | Phone: {$contact->phone}";
    }

    // ─── Conversation history ────────────────────────────────────────────────

    /**
     * @return array<array{role: string, content: string}>
     */
    private function buildMessageHistory(Contact $contact, Conversation $current): array
    {
        $history = Conversation::where('contact_id', $contact->id)
            ->where('channel', 'whatsapp')
            ->where('id', '!=', $current->id)
            ->latest('sent_at')
            ->take(8)
            ->get()
            ->reverse()
            ->values();

        $messages = $history->map(fn ($msg) => [
            'role'    => $msg->direction === 'inbound' ? 'user' : 'assistant',
            'content' => $msg->message,
        ])->toArray();

        // Append the current inbound message
        $messages[] = ['role' => 'user', 'content' => $current->message];

        return $messages;
    }
}
