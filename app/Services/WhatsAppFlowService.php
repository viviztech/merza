<?php

namespace App\Services;

use App\Models\BotSetting;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Product;
use App\Models\WhatsAppSession;
use Illuminate\Support\Facades\Log;

class WhatsAppFlowService
{
    // Keywords that restart the menu flow
    private const MENU_KEYWORDS = [
        'hi', 'hello', 'hey', 'helo', 'hai', 'start', 'menu', 'help',
        'hola', 'vanakkam', 'namaste', 'home', 'back',
    ];

    // Per Meta WhatsApp Business Messaging Policy — must honour these opt-out keywords
    private const OPT_OUT_KEYWORDS = [
        'stop', 'unsubscribe', 'opt out', 'optout', 'opt-out',
        'cancel', 'no messages', 'remove me', 'நிறுத்து',
    ];

    public function __construct(
        private readonly WhatsAppService $waService,
        private readonly BotSetting $settings,
    ) {}

    /**
     * Handle an inbound message through the structured flow.
     * Returns true if the flow handled it (reply sent), false to fall back to AI.
     */
    public function handle(
        Contact $contact,
        string  $messageType,
        string  $body,
        string  $interactiveId = '',
    ): bool {
        $session = WhatsAppSession::getOrCreate($contact->phone);

        // Interactive button/list tap — always handle in flow (unless opted out)
        if ($messageType === 'interactive' && $interactiveId) {
            if (! $contact->wa_opted_out) {
                $this->handleButton($contact, $session, $interactiveId);
            }
            return true;
        }

        // Text message
        $lower = mb_strtolower(trim($body));

        // Opt-out keywords — Meta policy requires immediate honouring
        if (in_array($lower, self::OPT_OUT_KEYWORDS, true)) {
            $this->handleOptOut($contact, $session);
            return true;
        }

        // Re-opt-in: if previously opted out and user sends START, re-enable
        if ($contact->wa_opted_out && in_array($lower, ['start', 'hi', 'hello', 'yes', 'ஆம்'], true)) {
            $contact->update(['wa_opted_out' => false, 'wa_opted_out_at' => null]);
            $session->setState('start');
            $this->waService->sendTextMessage(
                $contact->phone,
                "Welcome back! 🎉 You've been re-subscribed to Merza messages.\n\nReply *menu* to see what we have for you today. 🥭"
            );
            return true;
        }

        // If opted out, silently drop — do not send any automated message
        if ($contact->wa_opted_out) {
            Log::info('WhatsApp message from opted-out contact ignored', ['phone' => $contact->phone]);
            return true;
        }

        // Menu keyword → always show welcome
        if (in_array($lower, self::MENU_KEYWORDS, true)) {
            $this->sendWelcome($contact, $session);
            return true;
        }

        // AI mode or ordering — let AI handle free text
        if (in_array($session->state, ['ai', 'ordering'], true)) {
            return false;
        }

        // First contact / expired session → welcome
        if ($session->state === 'start') {
            $this->sendWelcome($contact, $session);
            return true;
        }

        // Free text while in structured menu → show welcome again
        $this->sendWelcome($contact, $session);
        return true;
    }

    // ─── Button router ────────────────────────────────────────────────────────

    private function handleButton(Contact $contact, WhatsAppSession $session, string $id): void
    {
        match (true) {
            $id === 'order_fruits'          => $this->sendCategories($contact, $session),
            $id === 'my_orders'             => $this->sendOrders($contact, $session),
            $id === 'talk_to_us'            => $this->sendTalkToUs($contact, $session),
            $id === 'back_menu'             => $this->sendWelcome($contact, $session),
            $id === 'back_cats'             => $this->sendCategories($contact, $session),
            str_starts_with($id, 'cat_')   => $this->sendProducts($contact, $session, substr($id, 4)),
            str_starts_with($id, 'prod_')  => $this->sendProductDetail($contact, $session, (int) substr($id, 5)),
            str_starts_with($id, 'order_') => $this->sendOrderStart($contact, $session, $id),
            default                         => $this->sendWelcome($contact, $session),
        };
    }

    // ─── Screens ─────────────────────────────────────────────────────────────

    private function sendWelcome(Contact $contact, WhatsAppSession $session): void
    {
        $name     = $this->customerName($contact);
        $greeting = $name ? "Hello {$name}! 👋" : "Hello! 👋";

        $session->setState('menu');

        $this->sendInteractive($contact->phone, [
            'type' => 'button',
            'body' => [
                'text' => "{$greeting} Welcome to *Merza Bodi* 🥭\n\nFresh tropical fruits from the hills of Bodinayakanur, Tamil Nadu.\n\nWhat can we help you with today?",
            ],
            'action' => [
                'buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => 'order_fruits', 'title' => '🛒 Order Fruits']],
                    ['type' => 'reply', 'reply' => ['id' => 'my_orders',    'title' => '📦 My Orders']],
                    ['type' => 'reply', 'reply' => ['id' => 'talk_to_us',   'title' => '💬 Merza Team']],
                ],
            ],
        ], $contact);
    }

    private function sendCategories(Contact $contact, WhatsAppSession $session): void
    {
        $session->setState('categories');

        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();

        $rows = $categories->map(fn ($c) => [
            'id'          => 'cat_' . $c->slug,
            'title'       => $this->truncate($c->name, 24),
            'description' => $this->truncate("Browse {$c->name} products", 72),
        ])->toArray();

        $rows[] = [
            'id'          => 'cat_all',
            'title'       => 'All Products',
            'description' => 'Browse our full range',
        ];

        $this->sendInteractive($contact->phone, [
            'type' => 'list',
            'body' => ['text' => "🥭 *Choose a Category*\n\nSelect what you'd like to browse:"],
            'action' => [
                'button'   => 'View Categories',
                'sections' => [['title' => 'Categories', 'rows' => $rows]],
            ],
        ], $contact);
    }

    private function sendProducts(Contact $contact, WhatsAppSession $session, string $categorySlug): void
    {
        $session->setState("cat_{$categorySlug}", ['category' => $categorySlug]);

        if ($categorySlug === 'all') {
            $products = Product::where('is_active', true)
                ->with('activeVariants')
                ->orderBy('sort_order')
                ->take(10)
                ->get();
            $heading = 'All Products';
        } else {
            $category = Category::where('slug', $categorySlug)->first();
            $products = Product::where('is_active', true)
                ->when($category, fn ($q) => $q->where('category_id', $category->id))
                ->with('activeVariants')
                ->orderBy('sort_order')
                ->take(10)
                ->get();
            $heading = $category?->name ?? 'Products';
        }

        if ($products->isEmpty()) {
            $this->waService->sendTextMessage(
                $contact->phone,
                "Sorry, no products available right now. Type *menu* to go back. 🥭"
            );
            return;
        }

        $rows = $products->map(function ($p) {
            $minPrice = $p->activeVariants->min('price') ?? $p->base_price;
            $price    = $minPrice ? 'From ₹' . number_format((float) $minPrice, 0) : '';
            $desc     = trim(($price ? $price . ' ' : '') . ($p->short_description ?? ''));

            return [
                'id'          => 'prod_' . $p->id,
                'title'       => $this->truncate($p->name, 24),
                'description' => $this->truncate($desc, 72),
            ];
        })->toArray();

        $this->sendInteractive($contact->phone, [
            'type' => 'list',
            'body' => ['text' => "*{$heading}* 🥭\n\nTap a product to see details and pricing:"],
            'action' => [
                'button'   => 'View Products',
                'sections' => [['title' => $heading, 'rows' => $rows]],
            ],
        ], $contact);
    }

    private function sendProductDetail(Contact $contact, WhatsAppSession $session, int $productId): void
    {
        $product = Product::with('activeVariants')->find($productId);

        if (! $product) {
            $this->sendWelcome($contact, $session);
            return;
        }

        $session->setState("product_{$productId}", ['product_id' => $productId]);

        // Build body text
        $text = "*{$product->name}* 🥭\n";
        if ($product->short_description) {
            $text .= "\n_{$product->short_description}_\n";
        }
        $text .= "\n*Available sizes:*\n";

        foreach ($product->activeVariants as $v) {
            $stock = $v->stock_qty > 0 ? '' : ' _(Out of stock)_';
            $text .= "• {$v->name}: \u{20B9}{$v->price}{$stock}\n";
        }

        if ($product->activeVariants->isEmpty()) {
            $text .= "• From \u{20B9}{$product->base_price}\n";
        }

        // Cheapest in-stock variant for the order button
        $variant = $product->activeVariants->where('stock_qty', '>', 0)->sortBy('price')->first()
            ?? $product->activeVariants->sortBy('price')->first();

        $orderId     = $variant ? "{$product->id}_{$variant->id}" : (string) $product->id;
        $orderLabel  = $variant ? $this->truncate("Order {$variant->name}", 20) : 'Order Now';

        $buttons = [
            ['type' => 'reply', 'reply' => ['id' => "order_{$orderId}", 'title' => $orderLabel]],
        ];

        if ($product->activeVariants->count() > 1) {
            $buttons[] = ['type' => 'reply', 'reply' => ['id' => 'back_cats', 'title' => '🔙 More Products']];
        }

        $buttons[] = ['type' => 'reply', 'reply' => ['id' => 'back_menu', 'title' => '🏠 Main Menu']];

        $this->sendInteractive($contact->phone, [
            'type'   => 'button',
            'body'   => ['text' => $text],
            'action' => ['buttons' => $buttons],
        ], $contact);
    }

    private function sendOrderStart(Contact $contact, WhatsAppSession $session, string $buttonId): void
    {
        // buttonId: "order_{productId}_{variantId}" or "order_{productId}"
        $parts     = explode('_', $buttonId); // ['order', productId, variantId?]
        $productId = (int) ($parts[1] ?? 0);
        $variantId = isset($parts[2]) ? (int) $parts[2] : null;

        $product = Product::with('activeVariants')->find($productId);
        $variant = $variantId ? $product?->activeVariants->find($variantId) : null;

        if (! $product) {
            $this->sendWelcome($contact, $session);
            return;
        }

        $session->setState('ordering', ['product_id' => $productId, 'variant_id' => $variantId]);

        $item = $variant
            ? "{$product->name} – {$variant->name} (\u{20B9}{$variant->price})"
            : "{$product->name}";

        $this->waService->sendTextMessage(
            $contact->phone,
            "✅ Great choice!\n\n*Your order:* {$item}\n\nTo complete your order, please reply with:\n1️⃣ Your full name\n2️⃣ Delivery address (with PIN code)\n3️⃣ Preferred delivery date\n\nWe'll confirm and collect payment. 🥭\n\n— Merza Team"
        );
    }

    private function sendOrders(Contact $contact, WhatsAppSession $session): void
    {
        $session->setState('orders');

        $orders = Order::where('contact_id', $contact->id)
            ->orWhere('customer_phone', $contact->phone)
            ->latest()
            ->take(3)
            ->get();

        if ($orders->isEmpty()) {
            $this->sendInteractive($contact->phone, [
                'type' => 'button',
                'body' => ['text' => "You don't have any orders with us yet.\n\nWould you like to place your first order? 🥭"],
                'action' => [
                    'buttons' => [
                        ['type' => 'reply', 'reply' => ['id' => 'order_fruits', 'title' => '🛒 Order Now']],
                        ['type' => 'reply', 'reply' => ['id' => 'back_menu',    'title' => '🏠 Main Menu']],
                    ],
                ],
            ], $contact);
            return;
        }

        $text = "*Your Recent Orders* 📦\n\n";
        foreach ($orders as $order) {
            $text .= "• *#{$order->order_number}* — {$order->status}\n";
            $text .= "  \u{20B9}{$order->total} · " . $order->created_at->format('d M Y') . "\n";
        }
        $text .= "\nType *menu* anytime to go back.";

        $this->sendInteractive($contact->phone, [
            'type' => 'button',
            'body' => ['text' => $text],
            'action' => [
                'buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => 'order_fruits', 'title' => '🛒 Order More']],
                    ['type' => 'reply', 'reply' => ['id' => 'talk_to_us',   'title' => '💬 Merza Team']],
                    ['type' => 'reply', 'reply' => ['id' => 'back_menu',    'title' => '🏠 Main Menu']],
                ],
            ],
        ], $contact);
    }

    private function sendTalkToUs(Contact $contact, WhatsAppSession $session): void
    {
        $session->setState('ai', ['expires_at' => now()->addHour()->toDateTimeString()]);

        $this->waService->sendTextMessage(
            $contact->phone,
            "Sure! 😊 You're now chatting with our *automated assistant*.\n\nAsk me anything about products, delivery, pricing, or orders and I'll help right away!\n\n📞 *Need a real person?*\nCall us: +91 93600 64278\nEmail: merzabodinayakanur@gmail.com\nHours: Mon–Sat, 9 AM – 6 PM\n\nType *menu* anytime to go back.\n\n— Merza Automated Assistant 🥭"
        );
    }

    private function handleOptOut(Contact $contact, WhatsAppSession $session): void
    {
        $contact->optOutWhatsApp();

        // Expire the session so no further automated flows trigger
        $session->update(['state' => 'opted_out', 'expires_at' => now()->addYears(10)]);

        Log::info('WhatsApp opt-out received', ['phone' => $contact->phone]);

        $this->waService->sendTextMessage(
            $contact->phone,
            "You have been unsubscribed from Merza automated messages. ✅\n\nYou will no longer receive automated WhatsApp messages from us.\n\nIf you ever want to reconnect, simply send *START* and we'll be happy to help!\n\n— Merza Team 🥭"
        );
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function sendInteractive(string $phone, array $interactive, Contact $contact): void
    {
        $waId = $this->waService->sendInteractiveMessage($phone, $interactive);

        // Save outbound conversation record
        if ($waId) {
            $body = $interactive['body']['text'] ?? '';
            Conversation::create([
                'contact_id'    => $contact->id,
                'channel'       => 'whatsapp',
                'direction'     => 'outbound',
                'message'       => $body,
                'wa_message_id' => $waId,
                'is_bot'        => true,
                'sent_at'       => now(),
                'status'        => 'sent',
            ]);
        }
    }

    private function customerName(Contact $contact): ?string
    {
        return ($contact->name && ! str_starts_with($contact->name, 'WA:'))
            ? $contact->name
            : null;
    }

    private function truncate(string $str, int $max): string
    {
        return mb_strlen($str) > $max ? mb_substr($str, 0, $max - 1) . '…' : $str;
    }
}
