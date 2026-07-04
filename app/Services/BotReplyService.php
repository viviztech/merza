<?php

namespace App\Services;

use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotReplyService
{
    // Fallback: Anthropic (legacy)
    private const ANTHROPIC_URL     = 'https://api.anthropic.com/v1/messages';
    private const ANTHROPIC_VERSION = '2023-06-01';

    public function __construct(private readonly BotSetting $settings) {}

    /**
     * Generate an AI reply for an inbound WhatsApp message.
     * Supports Groq (primary) and Anthropic (fallback).
     */
    public function generateReply(Contact $contact, string $inboundMessage, Conversation $inboundConversation): ?string
    {
        $systemPrompt = $this->buildSystemPrompt($contact, $inboundMessage);
        $history      = $this->buildMessageHistory($contact, $inboundConversation);

        // Prefer Groq when configured
        if ($this->settings->ai_provider === 'groq' && ! empty($this->settings->groq_api_key)) {
            $groq = new GroqService($this->settings->groq_api_key, $this->settings->groq_model ?? 'llama-3.1-8b-instant');
            return $groq->chat($systemPrompt, $history, 400);
        }

        // Fallback to Anthropic
        if (! empty($this->settings->anthropic_api_key)) {
            return $this->callAnthropic($systemPrompt, $history, $inboundMessage);
        }

        Log::warning('BotReplyService: No AI provider configured (no groq_api_key or anthropic_api_key)');
        return null;
    }

    // ─── System prompt ───────────────────────────────────────────────────────

    private function buildSystemPrompt(Contact $contact, string $inboundMessage): string
    {
        $products   = $this->getProductSummary();
        $orderInfo  = $this->getOrderContext($contact);
        $customerContext = $this->getCustomerContext($contact);

        return <<<PROMPT
You are the WhatsApp assistant for Merza Natural Squash, a premium tropical fruit brand in Bodinayakanur, Tamil Nadu, India.

BUSINESS INFO:
- Shop: HP Petrol Bunk, Pankajam School Opp., Thevaram Road, Bodinayakanur — 625513
- Phone: +91 86676 96278
- Email: merzabodinayakanur@gmail.com
- Hours: Monday–Saturday, 9 AM – 6 PM

OUR PRODUCTS (Mukkani — Three Fruits, One Promise):
{$products}

CUSTOMER:
{$customerContext}

{$orderInfo}

INSTRUCTIONS:
- Detect whether the customer writes in Tamil or English, and reply in the SAME language.
- Be warm, helpful, and concise — this is WhatsApp, keep replies under 80 words.
- If the customer asks about order status, use the ORDER INFO above.
- If they want to order, ask for: product name, quantity, and delivery address.
- If they ask for prices, share the product info above.
- Never make up information. If you don't know something, say you'll check and get back.
- End every reply with "— Merza Team 🥭"
- Do NOT use markdown, bullet points, or headers in your reply.
PROMPT;
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

    // ─── Anthropic fallback ──────────────────────────────────────────────────

    private function callAnthropic(string $systemPrompt, array $messages, string $inboundMessage): ?string
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'x-api-key'         => $this->settings->anthropic_api_key,
                'anthropic-version' => self::ANTHROPIC_VERSION,
                'content-type'      => 'application/json',
            ])
            ->post(self::ANTHROPIC_URL, [
                'model'      => $this->settings->anthropic_model ?? 'claude-haiku-4-5-20251001',
                'max_tokens' => 400,
                'system'     => $systemPrompt,
                'messages'   => $messages,
            ]);

        if ($response->failed()) {
            Log::error('BotReplyService(Anthropic): API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        return $response->json('content.0.text');
    }
}
