<?php

namespace App\Services;

use App\Models\BotSetting;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WhatsAppSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

    // Free-text states handled by the structured checkout flow (not AI)
    private const CHECKOUT_TEXT_STATES = [
        'checkout_name', 'checkout_address', 'checkout_citystate', 'awaiting_payment_ref',
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

        // Structured checkout steps — capture the reply, do not hand off to AI
        if (in_array($session->state, self::CHECKOUT_TEXT_STATES, true)) {
            $this->handleCheckoutText($contact, $session, trim($body));
            return true;
        }

        // Menu keyword → always show welcome
        if (in_array($lower, self::MENU_KEYWORDS, true)) {
            $this->sendWelcome($contact, $session);
            return true;
        }

        // AI mode or legacy free-text ordering → let AI handle free text
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
            $id === 'cart_view'             => $this->sendCart($contact, $session),
            $id === 'cart_checkout'         => $this->startCheckout($contact, $session),
            $id === 'cart_add_more'         => $this->sendCategories($contact, $session),
            $id === 'cart_clear'            => $this->clearCart($contact, $session),
            $id === 'pay_cod'               => $this->completeOrder($contact, $session, 'cod'),
            $id === 'pay_upi'               => $this->completeOrder($contact, $session, 'whatsapp'),
            str_starts_with($id, 'cat_')     => $this->sendProducts($contact, $session, substr($id, 4)),
            str_starts_with($id, 'prod_')    => $this->sendProductDetail($contact, $session, (int) substr($id, 5)),
            str_starts_with($id, 'addcart_') => $this->addToCart($contact, $session, substr($id, 8)),
            str_starts_with($id, 'order_')   => $this->sendLegacyOrderPrompt($contact, $session, $id),
            default                          => $this->sendWelcome($contact, $session),
        };
    }

    // ─── Screens ─────────────────────────────────────────────────────────────

    private function sendWelcome(Contact $contact, WhatsAppSession $session): void
    {
        $name     = $this->customerName($contact);
        $greeting = $name ? "Hello {$name}! 👋" : "Hello! 👋";

        $this->updateSession($session, 'menu');

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
        $this->updateSession($session, 'categories');

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
        $this->updateSession($session, "cat_{$categorySlug}", ['category' => $categorySlug]);

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

        $this->updateSession($session, "product_{$productId}", ['product_id' => $productId]);

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

        // Legacy (commerce flow disabled) — single "Order Now" button, free-text collection via AI
        if (! $this->settings->wa_commerce_enabled) {
            $variant = $product->activeVariants->where('stock_qty', '>', 0)->sortBy('price')->first()
                ?? $product->activeVariants->sortBy('price')->first();

            $orderId    = $variant ? "{$product->id}_{$variant->id}" : (string) $product->id;
            $orderLabel = $variant ? $this->truncate("Order {$variant->name}", 20) : 'Order Now';

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
            return;
        }

        // Commerce flow enabled — let customer pick the exact size to add to cart
        $inStock = $product->activeVariants->where('stock_qty', '>', 0);

        if ($inStock->isEmpty()) {
            $text .= "\nSorry, all sizes are currently out of stock.";
            $this->sendInteractive($contact->phone, [
                'type'   => 'button',
                'body'   => ['text' => $text],
                'action' => ['buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => 'back_cats', 'title' => '🔙 More Products']],
                    ['type' => 'reply', 'reply' => ['id' => 'back_menu', 'title' => '🏠 Main Menu']],
                ]],
            ], $contact);
            return;
        }

        if ($inStock->count() === 1) {
            $variant = $inStock->first();
            $this->sendInteractive($contact->phone, [
                'type'   => 'button',
                'body'   => ['text' => $text],
                'action' => ['buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => "addcart_{$product->id}_{$variant->id}", 'title' => $this->truncate("Add {$variant->name}", 20)]],
                    ['type' => 'reply', 'reply' => ['id' => 'back_cats', 'title' => '🔙 More Products']],
                    ['type' => 'reply', 'reply' => ['id' => 'back_menu', 'title' => '🏠 Main Menu']],
                ]],
            ], $contact);
            return;
        }

        $rows = $inStock->map(fn ($v) => [
            'id'          => "addcart_{$product->id}_{$v->id}",
            'title'       => $this->truncate($v->name, 24),
            'description' => "\u{20B9}{$v->price}",
        ])->values()->toArray();

        $this->sendInteractive($contact->phone, [
            'type' => 'list',
            'body' => ['text' => $text . "\nSelect a size to add it to your cart:"],
            'action' => [
                'button'   => 'Choose Size',
                'sections' => [['title' => $product->name, 'rows' => $rows]],
            ],
        ], $contact);
    }

    private function sendLegacyOrderPrompt(Contact $contact, WhatsAppSession $session, string $buttonId): void
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

        $this->updateSession($session, 'ordering', ['product_id' => $productId, 'variant_id' => $variantId]);

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
        $this->updateSession($session, 'orders');

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
        $this->updateSession($session, 'ai', ['expires_at' => now()->addHour()->toDateTimeString()]);

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

    // ─── Cart ────────────────────────────────────────────────────────────────

    private function addToCart(Contact $contact, WhatsAppSession $session, string $ids): void
    {
        [$productId, $variantId] = array_pad(explode('_', $ids), 2, null);

        $variant = ProductVariant::with('product')->find((int) $variantId);

        if (! $variant || $variant->stock_qty <= 0) {
            $this->waService->sendTextMessage($contact->phone, "Sorry, that size is no longer available. Type *menu* to browse other options. 🥭");
            $this->sendWelcome($contact, $session);
            return;
        }

        $cart = $session->data['cart'] ?? [];
        $key  = (string) $variant->id;

        if (isset($cart[$key])) {
            $cart[$key]['qty'] = min($cart[$key]['qty'] + 1, $variant->stock_qty);
        } else {
            $weightKg = $variant->weight_unit === 'g'
                ? ((float) $variant->weight_value / 1000)
                : (float) $variant->weight_value;

            $cart[$key] = [
                'variant_id'   => $variant->id,
                'product_id'   => $variant->product_id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'sku'          => $variant->sku,
                'price'        => (float) $variant->price,
                'qty'          => 1,
                'weight_kg'    => $weightKg,
            ];
        }

        $this->updateSession($session, 'cart', ['cart' => $cart]);

        $itemLine = "{$variant->product->name} – {$variant->name}";

        $this->sendInteractive($contact->phone, [
            'type' => 'button',
            'body' => ['text' => "✅ Added to cart: *{$itemLine}*\n\nWhat next?"],
            'action' => [
                'buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => 'cart_checkout', 'title' => '✅ Checkout']],
                    ['type' => 'reply', 'reply' => ['id' => 'cart_view',     'title' => '🛒 View Cart']],
                    ['type' => 'reply', 'reply' => ['id' => 'cart_add_more', 'title' => '➕ Add More']],
                ],
            ],
        ], $contact);
    }

    private function sendCart(Contact $contact, WhatsAppSession $session): void
    {
        $cart = $session->data['cart'] ?? [];

        if (empty($cart)) {
            $this->sendInteractive($contact->phone, [
                'type' => 'button',
                'body' => ['text' => "Your cart is empty. 🛒\n\nWould you like to browse our fruits?"],
                'action' => ['buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => 'order_fruits', 'title' => '🛒 Order Fruits']],
                    ['type' => 'reply', 'reply' => ['id' => 'back_menu',    'title' => '🏠 Main Menu']],
                ]],
            ], $contact);
            return;
        }

        $text = "*Your Cart* 🛒\n\n";
        $subtotal = 0;
        foreach ($cart as $item) {
            $lineTotal = $item['price'] * $item['qty'];
            $subtotal += $lineTotal;
            $text .= "• {$item['product_name']} – {$item['variant_name']} × {$item['qty']} = \u{20B9}" . number_format($lineTotal, 2) . "\n";
        }
        $text .= "\n*Subtotal: \u{20B9}" . number_format($subtotal, 2) . "*\n_(Delivery fee calculated at checkout)_";

        $this->sendInteractive($contact->phone, [
            'type' => 'button',
            'body' => ['text' => $text],
            'action' => ['buttons' => [
                ['type' => 'reply', 'reply' => ['id' => 'cart_checkout', 'title' => '✅ Checkout']],
                ['type' => 'reply', 'reply' => ['id' => 'cart_add_more', 'title' => '➕ Add More']],
                ['type' => 'reply', 'reply' => ['id' => 'cart_clear',    'title' => '🗑 Clear Cart']],
            ]],
        ], $contact);
    }

    private function clearCart(Contact $contact, WhatsAppSession $session): void
    {
        $this->updateSession($session, 'menu', ['cart' => []]);

        $this->waService->sendTextMessage($contact->phone, "🗑 Your cart has been cleared. Type *menu* anytime to start again. 🥭");
        $this->sendWelcome($contact, $session);
    }

    // ─── Checkout ────────────────────────────────────────────────────────────

    private function startCheckout(Contact $contact, WhatsAppSession $session): void
    {
        $cart = $session->data['cart'] ?? [];

        if (empty($cart)) {
            $this->sendCart($contact, $session);
            return;
        }

        $this->updateSession($session, 'checkout_name', ['draft' => []]);

        $this->waService->sendTextMessage(
            $contact->phone,
            "Great, let's get your order ready! 📝\n\nWhat's your *full name* for this order?"
        );
    }

    private function handleCheckoutText(Contact $contact, WhatsAppSession $session, string $body): void
    {
        match ($session->state) {
            'checkout_name'        => $this->captureName($contact, $session, $body),
            'checkout_address'     => $this->captureAddress($contact, $session, $body),
            'checkout_citystate'   => $this->captureCityState($contact, $session, $body),
            'awaiting_payment_ref' => $this->capturePaymentReference($contact, $session, $body),
            default                => $this->sendWelcome($contact, $session),
        };
    }

    private function captureName(Contact $contact, WhatsAppSession $session, string $body): void
    {
        if (mb_strlen($body) < 2) {
            $this->waService->sendTextMessage($contact->phone, "That doesn't look like a name — please reply with your *full name*.");
            return;
        }

        $draft         = $session->data['draft'] ?? [];
        $draft['name'] = $body;

        $this->updateSession($session, 'checkout_address', ['draft' => $draft]);

        $this->waService->sendTextMessage(
            $contact->phone,
            "Thanks, {$body}! 🙏\n\nWhat's your *delivery address* (house/street/area)?"
        );
    }

    private function captureAddress(Contact $contact, WhatsAppSession $session, string $body): void
    {
        if (mb_strlen($body) < 5) {
            $this->waService->sendTextMessage($contact->phone, "Please share the full delivery address (house/street/area).");
            return;
        }

        $draft            = $session->data['draft'] ?? [];
        $draft['address'] = $body;

        $this->updateSession($session, 'checkout_citystate', ['draft' => $draft]);

        $this->waService->sendTextMessage(
            $contact->phone,
            "Got it. ✅\n\nNow send your *city, state and PIN code* in one line, like this:\n_Bodinayakanur, Tamil Nadu, 625513_"
        );
    }

    private function captureCityState(Contact $contact, WhatsAppSession $session, string $body): void
    {
        if (! preg_match('/\b(\d{6})\b/', $body, $pinMatch)) {
            $this->waService->sendTextMessage(
                $contact->phone,
                "I couldn't find a valid 6-digit PIN code. Please resend as:\n_Bodinayakanur, Tamil Nadu, 625513_"
            );
            return;
        }

        $postcode = $pinMatch[1];
        $rest     = trim(str_replace($postcode, '', $body), " ,\t\n\r\0\x0B");
        $parts    = array_values(array_filter(array_map('trim', explode(',', $rest))));

        if (count($parts) < 2) {
            $this->waService->sendTextMessage(
                $contact->phone,
                "Please include city, state and PIN code, like this:\n_Bodinayakanur, Tamil Nadu, 625513_"
            );
            return;
        }

        [$city, $state] = $parts;

        $draft             = $session->data['draft'] ?? [];
        $draft['city']     = $city;
        $draft['state']    = $state;
        $draft['postcode'] = $postcode;

        $cart     = $session->data['cart'] ?? [];
        $weightKg = array_sum(array_map(fn ($i) => ($i['weight_kg'] ?? 0) * $i['qty'], $cart));

        $breakdown             = (new DeliveryCalculatorService())->calculate($city, $state, $weightKg);
        $draft['delivery_fee'] = $breakdown['total_fee'] ?? 0;

        $this->updateSession($session, 'checkout_confirm', ['draft' => $draft]);

        $this->sendOrderSummary($contact, $session);
    }

    private function sendOrderSummary(Contact $contact, WhatsAppSession $session): void
    {
        $cart     = $session->data['cart'] ?? [];
        $draft    = $session->data['draft'] ?? [];
        $subtotal = array_sum(array_map(fn ($i) => $i['price'] * $i['qty'], $cart));
        $delivery = $draft['delivery_fee'] ?? 0;
        $total    = $subtotal + $delivery;

        $text = "*Order Summary* 📋\n\n";
        foreach ($cart as $item) {
            $text .= "• {$item['product_name']} – {$item['variant_name']} × {$item['qty']}\n";
        }
        $text .= "\nSubtotal: \u{20B9}" . number_format($subtotal, 2);
        $text .= "\nDelivery: \u{20B9}" . number_format($delivery, 2);
        $text .= "\n*Total: \u{20B9}" . number_format($total, 2) . "*";
        $text .= "\n\n📍 {$draft['name']}\n{$draft['address']}\n{$draft['city']}, {$draft['state']} - {$draft['postcode']}";
        $text .= "\n\nHow would you like to pay?";

        $buttons = [
            ['type' => 'reply', 'reply' => ['id' => 'pay_cod', 'title' => '💵 Cash on Delivery']],
        ];
        if (! empty($this->settings->upi_id)) {
            $buttons[] = ['type' => 'reply', 'reply' => ['id' => 'pay_upi', 'title' => '📱 Pay via UPI']];
        }

        $this->sendInteractive($contact->phone, [
            'type'   => 'button',
            'body'   => ['text' => $text],
            'action' => ['buttons' => $buttons],
        ], $contact);
    }

    private function completeOrder(Contact $contact, WhatsAppSession $session, string $paymentMethod): void
    {
        $cart  = $session->data['cart'] ?? [];
        $draft = $session->data['draft'] ?? [];

        if (empty($cart) || empty($draft['name']) || empty($draft['address'])) {
            $this->sendWelcome($contact, $session);
            return;
        }

        $subtotal = array_sum(array_map(fn ($i) => $i['price'] * $i['qty'], $cart));
        $delivery = $draft['delivery_fee'] ?? 0;
        $total    = $subtotal + $delivery;

        $order = Order::create([
            'channel'          => 'whatsapp',
            'contact_id'       => $contact->id,
            'customer_name'    => $draft['name'],
            'customer_phone'   => $contact->phone,
            'delivery_address' => $draft['address'],
            'city'             => $draft['city'] ?? null,
            'postcode'         => $draft['postcode'] ?? null,
            'state'            => $draft['state'] ?? null,
            'subtotal'         => $subtotal,
            'delivery_fee'     => $delivery,
            'total'            => $total,
            'payment_method'   => $paymentMethod,
        ]);

        foreach ($cart as $item) {
            OrderItem::create([
                'order_id'           => $order->id,
                'product_variant_id' => $item['variant_id'],
                'product_name'       => $item['product_name'],
                'variant_name'       => $item['variant_name'],
                'sku'                => $item['sku'],
                'quantity'           => $item['qty'],
                'unit_price'         => $item['price'],
                'subtotal'           => $item['price'] * $item['qty'],
            ]);
        }

        if ($paymentMethod === 'whatsapp') {
            $this->sendUpiQr($contact, $session, $order);
            return;
        }

        // Cash on Delivery — order is complete
        $this->updateSession($session, 'menu', ['cart' => [], 'draft' => []]);

        $this->waService->sendTextMessage(
            $contact->phone,
            "🎉 *Order Confirmed!*\n\nOrder number: *{$order->order_number}*\nTotal: \u{20B9}" . number_format($total, 2) . " (Cash on Delivery)\n\nWe'll start preparing your fruits and confirm delivery shortly. Type *menu* anytime.\n\n— Merza Team 🥭"
        );
    }

    private function sendUpiQr(Contact $contact, WhatsAppSession $session, Order $order): void
    {
        $qrService = new UpiQrService();
        $uri = $qrService->buildUpiUri(
            $this->settings->upi_id,
            $this->settings->upi_payee_name ?: 'Merza',
            (float) $order->total,
            "Order {$order->order_number}"
        );

        $path = "whatsapp-qr/{$order->order_number}.png";
        $disk = Storage::disk(config('media-library.disk_name', 'r2'));
        $disk->put($path, $qrService->generatePng($uri));
        $imageUrl = $disk->url($path);

        $caption = "Scan to pay \u{20B9}" . number_format((float) $order->total, 2) . " for order *{$order->order_number}*.\n\nOr pay manually to UPI ID: {$this->settings->upi_id}\n\nOnce paid, reply with your payment reference/UTR number so we can confirm it. 🥭";

        $waId = $this->waService->sendImageMessage($contact->phone, $imageUrl, $caption);

        if ($waId) {
            Conversation::create([
                'contact_id'    => $contact->id,
                'channel'       => 'whatsapp',
                'direction'     => 'outbound',
                'message'       => "[UPI QR code sent] {$caption}",
                'wa_message_id' => $waId,
                'is_bot'        => true,
                'sent_at'       => now(),
                'status'        => 'sent',
            ]);
        }

        $this->updateSession($session, 'awaiting_payment_ref', [
            'cart' => [], 'draft' => [], 'pending_order_id' => $order->id,
        ]);
    }

    private function capturePaymentReference(Contact $contact, WhatsAppSession $session, string $body): void
    {
        $orderId = $session->data['pending_order_id'] ?? null;
        $order   = $orderId ? Order::find($orderId) : null;

        if (! $order) {
            $this->sendWelcome($contact, $session);
            return;
        }

        $order->update(['payment_reference' => $body]);

        $this->updateSession($session, 'menu', ['cart' => [], 'draft' => [], 'pending_order_id' => null]);

        $this->waService->sendTextMessage(
            $contact->phone,
            "Thank you! ✅ We've noted your payment reference for order *{$order->order_number}*.\n\nOur team will verify and confirm your order shortly. Type *menu* anytime.\n\n— Merza Team 🥭"
        );
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function updateSession(WhatsAppSession $session, string $state, array $patch = []): void
    {
        $session->setState($state, array_merge($session->data ?? [], $patch));
    }

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
