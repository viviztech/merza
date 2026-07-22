<?php

namespace App\Services;

use App\Models\BotActivityLog;
use App\Models\BotSetting;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WhatsAppSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

// Meta rejects the whole interactive message if any reply-button title exceeds
// 20 characters (list row titles are more lenient, up to 24) — a rejected send
// returns null from WhatsAppService and the customer silently gets no reply,
// so double-check length whenever a button title below is hardcoded or changed.
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
        'zone_manual_entry', 'checkout_name', 'checkout_address', 'awaiting_payment_ref',
    ];

    // Phrases that mean "I want to (re)start ordering", checked anywhere mid-conversation
    // (including while chatting with the AI) so the customer can jump back into the
    // structured cart/checkout flow without restating everything as free text.
    private const ORDER_INTENT_PHRASES = [
        'i want to order', 'want to order', 'place an order', 'place order',
        'continue order', 'continue my order', 'resume order', 'resume my order',
        'add to cart', 'proceed to checkout', 'go to checkout',
        'checkout', 'check out', 'buy now', 'i want to buy',
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
        ?string $mediaPath = null,
    ): bool {
        $session = WhatsAppSession::getOrCreate($contact->phone);

        // Interactive button/list tap — always handle in flow (unless opted out)
        if ($messageType === 'interactive' && $interactiveId) {
            if (! $contact->wa_opted_out) {
                $this->handleButton($contact, $session, $interactiveId);
            }
            return true;
        }

        // Session expired with a cart still in it — WhatsAppSession stashed the
        // progress and is waiting on resume_cart/fresh_start (handled above via
        // the interactive branch). Anything else re-shows the same prompt rather
        // than silently proceeding as if nothing had happened.
        if ($session->state === 'resume_prompt') {
            $this->sendResumePrompt($contact, $session);
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

        // Payment screenshot — only meaningful reply to an image, so check it
        // ahead of the general checkout-text branch below.
        if ($session->state === 'awaiting_payment_ref' && $messageType === 'image' && $mediaPath) {
            $this->capturePaymentScreenshot($contact, $session, $mediaPath);
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

        // First contact / expired session → welcome
        if ($session->state === 'start') {
            $this->sendWelcome($contact, $session);
            return true;
        }

        // Explicit ordering intent, from ANY state (including mid-AI-chat) → resume
        // the structured flow instead of leaving them to restate the order as free text.
        if ($this->looksLikeOrderIntent($lower)) {
            $this->logDistraction($contact, $session, 'resumed_ordering');
            $this->resumeOrdering($contact, $session);
            return true;
        }

        // AI mode or legacy free-text ordering → let AI handle free text
        if (in_array($session->state, ['ai', 'ordering'], true)) {
            return false;
        }

        // Free text mid-flow (browsing/cart/checkout-confirm) → hand off to the AI,
        // which now sees the live cart via BotReplyService, instead of hard-resetting
        // to Welcome. Only when AI is actually configured — otherwise the old safe
        // fallback keeps working for deployments with no AI key set.
        if ((new AiProviderService($this->settings))->isConfigured()) {
            $this->logDistraction($contact, $session, 'ai_handoff');
            return false;
        }

        $this->logDistraction($contact, $session, 'welcome_reset');
        $this->sendWelcome($contact, $session);
        return true;
    }

    /**
     * Baseline telemetry for the conversion-gap fixes — how often, and from which
     * state, a customer goes off-script mid-flow, and how it was handled.
     */
    private function logDistraction(Contact $contact, WhatsAppSession $session, string $action): void
    {
        BotActivityLog::create([
            'event_type'  => 'flow_distraction',
            'contact_id'  => $contact->id,
            'raw_payload' => [
                'state'      => $session->state,
                'action'     => $action, // 'resumed_ordering' | 'ai_handoff' | 'welcome_reset'
                'cart_items' => count($session->data['cart'] ?? []),
            ],
            'status' => 'success',
        ]);
    }

    /**
     * Cheap, deterministic ordering-intent check — no extra AI call needed.
     * Tuneable later from real BotActivityLog data once flow_reset events (F6) are logged.
     */
    private function looksLikeOrderIntent(string $lower): bool
    {
        foreach (self::ORDER_INTENT_PHRASES as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function resumeOrdering(Contact $contact, WhatsAppSession $session): void
    {
        $cart = $session->data['cart'] ?? [];

        if (! empty($cart)) {
            $this->sendCart($contact, $session);
            return;
        }

        $this->startOrdering($contact, $session);
    }

    // ─── Soft session resume (replaces the old silent 30-min wipe) ────────────

    private function sendResumePrompt(Contact $contact, WhatsAppSession $session): void
    {
        $cart  = $session->data['cart'] ?? [];
        $count = array_sum(array_column($cart, 'qty'));
        $itemsLabel = $count === 1 ? '1 item' : "{$count} items";

        $this->sendInteractive($contact->phone, [
            'type' => 'button',
            'body' => [
                'text' => "Welcome back! 👋\n\nYou still have *{$itemsLabel}* waiting in your cart from before.\n\nWant to pick up where you left off?",
            ],
            'action' => [
                'buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => 'resume_cart', 'title' => '🛒 Resume Cart']],
                    ['type' => 'reply', 'reply' => ['id' => 'fresh_start', 'title' => '🔄 Start Fresh']],
                ],
            ],
        ], $contact);
    }

    private function resumeStashedSession(Contact $contact, WhatsAppSession $session): void
    {
        $data = $session->data;
        unset($data['stashed_state']);

        // setState() (not updateSession()) — it replaces the data blob outright,
        // so the now-unset 'stashed_state' key doesn't get merged back in.
        // Land everyone back at the cart regardless of the exact stashed sub-state
        // (e.g. mid checkout_price_confirm) — simplest safe re-entry point, and
        // the normal Checkout button carries them forward from there.
        $session->setState('cart', $data);
        $this->sendCart($contact, $session);
    }

    private function startFresh(Contact $contact, WhatsAppSession $session): void
    {
        // setState(), not updateSession() — a full replace so the stashed
        // 'stashed_state' key from the resume prompt doesn't linger.
        $session->setState('menu', ['cart' => [], 'draft' => [], 'zone' => null, 'zone_manual' => null]);
        $this->sendWelcome($contact, $session);
    }

    // ─── Button router ────────────────────────────────────────────────────────

    private function handleButton(Contact $contact, WhatsAppSession $session, string $id): void
    {
        match (true) {
            $id === 'resume_cart'           => $this->resumeStashedSession($contact, $session),
            $id === 'fresh_start'           => $this->startFresh($contact, $session),
            $id === 'order_fruits'          => $this->startOrdering($contact, $session),
            $id === 'my_orders'             => $this->sendOrders($contact, $session),
            $id === 'talk_to_us'            => $this->sendTalkToUs($contact, $session),
            $id === 'back_menu'             => $this->sendWelcome($contact, $session),
            $id === 'back_cats'             => $this->sendCategories($contact, $session),
            $id === 'cart_view'             => $this->sendCart($contact, $session),
            $id === 'cart_checkout'         => $this->startCheckout($contact, $session),
            $id === 'cart_add_more'         => $this->sendCategories($contact, $session),
            $id === 'cart_clear'            => $this->clearCart($contact, $session),
            $id === 'checkout_continue'     => $this->askCheckoutName($contact, $session),
            $id === 'pay_upi'               => $this->completeOrder($contact, $session),
            $id === 'zone_other'            => $this->promptManualZone($contact, $session),
            str_starts_with($id, 'zone_')     => $this->selectZone($contact, $session, (int) substr($id, 5)),
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

    // ─── Delivery zone (asked first, so courier charges are known up front) ──

    private function startOrdering(Contact $contact, WhatsAppSession $session): void
    {
        // Legacy fallback (commerce flow disabled) doesn't need a zone up front —
        // it collects everything as free text and a human/AI handles pricing.
        if (! $this->settings->wa_commerce_enabled) {
            $this->sendCategories($contact, $session);
            return;
        }

        // Zone already picked earlier this session — no need to ask again.
        if (! empty($session->data['zone']) || ! empty($session->data['zone_manual'])) {
            $this->sendCategories($contact, $session);
            return;
        }

        $this->sendZoneSelection($contact, $session);
    }

    private function sendZoneSelection(Contact $contact, WhatsAppSession $session): void
    {
        $this->updateSession($session, 'selecting_zone');

        $zones = DeliveryZone::active()->get();

        $rows = $zones->map(fn (DeliveryZone $z) => [
            'id'          => "zone_{$z->id}",
            'title'       => $this->truncate($z->name, 24),
            'description' => "\u{20B9}{$z->rate_per_kg}/kg courier charge",
        ])->toArray();

        $rows[] = [
            'id'          => 'zone_other',
            'title'       => 'Other Location',
            'description' => 'Not listed above',
        ];

        $this->sendInteractive($contact->phone, [
            'type' => 'list',
            'body' => ['text' => "🚚 *Where are we delivering?*\n\nSelect your location so we can show accurate courier charges:"],
            'action' => [
                'button'   => 'Choose Location',
                'sections' => [['title' => 'Delivery Zones', 'rows' => $rows]],
            ],
        ], $contact);
    }

    private function selectZone(Contact $contact, WhatsAppSession $session, int $zoneId): void
    {
        $zone = DeliveryZone::active()->find($zoneId);

        if (! $zone) {
            $this->sendZoneSelection($contact, $session);
            return;
        }

        $this->updateSession($session, 'categories', [
            'zone'        => ['id' => $zone->id, 'name' => $zone->name, 'rate_per_kg' => (float) $zone->rate_per_kg],
            'zone_manual' => null,
        ]);

        $this->sendCategories($contact, $session);
    }

    private function promptManualZone(Contact $contact, WhatsAppSession $session): void
    {
        $this->updateSession($session, 'zone_manual_entry');

        $this->waService->sendTextMessage(
            $contact->phone,
            "No problem! Please reply with your *city and state*, like this:\n_Salem, Tamil Nadu_"
        );
    }

    private function captureManualZone(Contact $contact, WhatsAppSession $session, string $body): void
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $body)), fn ($p) => $p !== ''));

        if (count($parts) < 2) {
            $this->waService->sendTextMessage($contact->phone, "Please include both city and state, like this:\n_Salem, Tamil Nadu_");
            return;
        }

        [$city, $state] = [$parts[0], $parts[1]];
        $zone = (new DeliveryCalculatorService())->findZone($city, $state);

        if (! $zone) {
            $this->waService->sendTextMessage(
                $contact->phone,
                "😔 Sorry, we don't currently deliver to *{$city}, {$state}*.\n\nMessage us at +91 93600 64278 for help, or try a different location.\n\nType *menu* to go back."
            );
            return;
        }

        $this->updateSession($session, 'categories', [
            'zone' => ['id' => $zone->id, 'name' => $zone->name, 'rate_per_kg' => (float) $zone->rate_per_kg],
        ]);

        $this->sendCategories($contact, $session);
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
        $text .= "\n*Subtotal: \u{20B9}" . number_format($subtotal, 2) . "*";

        $zone = $session->data['zone'] ?? null;
        if ($zone) {
            $text .= "\n_Courier to {$zone['name']}: \u{20B9}{$zone['rate_per_kg']}/kg (added at checkout)_";
        } else {
            $text .= "\n_(Courier charges calculated at checkout)_";
        }

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

        // Shouldn't normally happen (zone is asked before browsing), but guard
        // against a stale/older session reaching checkout with no zone at all.
        if (empty($session->data['zone']) && empty($session->data['zone_manual'])) {
            $this->sendZoneSelection($contact, $session);
            return;
        }

        $this->sendCheckoutPricePreview($contact, $session);
    }

    private function sendCheckoutPricePreview(Contact $contact, WhatsAppSession $session): void
    {
        $zoneInfo = $session->data['zone'] ?? null;
        $zone     = $zoneInfo ? DeliveryZone::find($zoneInfo['id']) : null;

        if (! $zone) {
            // Session lost its zone somehow — send them back through zone selection.
            $this->waService->sendTextMessage($contact->phone, "Sorry, something went wrong with your delivery location. Let's pick it again.");
            $this->sendZoneSelection($contact, $session);
            return;
        }

        $cart     = $session->data['cart'] ?? [];
        $weightKg = array_sum(array_map(fn ($i) => ($i['weight_kg'] ?? 0) * $i['qty'], $cart));

        $breakdown = (new DeliveryCalculatorService())->calculateForZone($zone, $weightKg);

        $draft = [
            'delivery_fee' => $breakdown['total_fee'],
            'zone_name'    => $zone->name,
        ];

        $this->updateSession($session, 'checkout_price_confirm', ['draft' => $draft]);

        $subtotal = array_sum(array_map(fn ($i) => $i['price'] * $i['qty'], $cart));
        $delivery = $breakdown['total_fee'];
        $total    = $subtotal + $delivery;

        $text = "*Order Total* 📋\n\n";
        foreach ($cart as $item) {
            $text .= "• {$item['product_name']} – {$item['variant_name']} × {$item['qty']}\n";
        }
        $text .= "\nSubtotal: \u{20B9}" . number_format($subtotal, 2);
        $text .= "\nCourier Charges ({$zone->name}): \u{20B9}" . number_format($delivery, 2);
        $text .= "\n*Total: \u{20B9}" . number_format($total, 2) . "*";
        $text .= "\n\nShall we go ahead?";

        $this->sendInteractive($contact->phone, [
            'type'   => 'button',
            'body'   => ['text' => $text],
            'action' => ['buttons' => [
                ['type' => 'reply', 'reply' => ['id' => 'checkout_continue', 'title' => '✅ Continue']],
            ]],
        ], $contact);
    }

    private function askCheckoutName(Contact $contact, WhatsAppSession $session): void
    {
        $this->updateSession($session, 'checkout_name');

        $this->waService->sendTextMessage(
            $contact->phone,
            "Great! 📝\n\nWhat's your *name*?"
        );
    }

    private function handleCheckoutText(Contact $contact, WhatsAppSession $session, string $body): void
    {
        match ($session->state) {
            'zone_manual_entry'    => $this->captureManualZone($contact, $session, $body),
            'checkout_name'        => $this->captureCheckoutName($contact, $session, $body),
            'checkout_address'     => $this->captureCheckoutAddress($contact, $session, $body),
            'awaiting_payment_ref' => $this->capturePaymentReference($contact, $session, $body),
            default                => $this->sendWelcome($contact, $session),
        };
    }

    private function captureCheckoutName(Contact $contact, WhatsAppSession $session, string $body): void
    {
        $name = trim($body);

        if ($name === '') {
            $this->waService->sendTextMessage($contact->phone, "Please reply with your name.");
            return;
        }

        $draft         = $session->data['draft'] ?? [];
        $draft['name'] = $name;

        $this->updateSession($session, 'checkout_address', ['draft' => $draft]);

        $this->waService->sendTextMessage(
            $contact->phone,
            "Thanks, {$name}! 🙏\n\nWhat's your *delivery address*? (any format is fine — just make sure it's complete)"
        );
    }

    private function captureCheckoutAddress(Contact $contact, WhatsAppSession $session, string $body): void
    {
        $address = trim($body);

        if ($address === '') {
            $this->waService->sendTextMessage($contact->phone, "Please reply with your delivery address.");
            return;
        }

        $draft            = $session->data['draft'] ?? [];
        $draft['address'] = $address;

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
        $text .= "\nCourier Charges ({$draft['zone_name']}): \u{20B9}" . number_format($delivery, 2);
        $text .= "\n*Total: \u{20B9}" . number_format($total, 2) . "*";
        $text .= "\n\n📍 {$draft['name']}\n{$draft['address']}";
        $text .= "\n\nTap below to confirm and pay via UPI.";

        $this->sendInteractive($contact->phone, [
            'type'   => 'button',
            'body'   => ['text' => $text],
            'action' => ['buttons' => [
                ['type' => 'reply', 'reply' => ['id' => 'pay_upi', 'title' => '📱 Confirm & Pay']],
            ]],
        ], $contact);
    }

    private function completeOrder(Contact $contact, WhatsAppSession $session): void
    {
        $cart  = $session->data['cart'] ?? [];
        $draft = $session->data['draft'] ?? [];

        if (empty($cart) || empty($draft['name']) || empty($draft['address']) || ! isset($draft['delivery_fee'])) {
            $this->sendWelcome($contact, $session);
            return;
        }

        $subtotal = array_sum(array_map(fn ($i) => $i['price'] * $i['qty'], $cart));
        $delivery = $draft['delivery_fee'];
        $total    = $subtotal + $delivery;

        $order = Order::create([
            'channel'          => 'whatsapp',
            'contact_id'       => $contact->id,
            'customer_name'    => $draft['name'],
            'customer_phone'   => $contact->phone,
            'delivery_address' => $draft['address'],
            'state'            => $draft['zone_name'] ?? null,
            'subtotal'         => $subtotal,
            'delivery_fee'     => $delivery,
            'total'            => $total,
            'payment_method'   => 'whatsapp',
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

        $this->sendInvoicePdf($contact, $order);
        $this->sendUpiQr($contact, $session, $order);
    }

    private function sendInvoicePdf(Contact $contact, Order $order): void
    {
        // Columns like status/payment_status are set by the DB's own default
        // clause, not by the create() call, so the in-memory model won't have
        // them until it's reloaded.
        $order->refresh()->loadMissing('items');

        $pdf = Pdf::loadView('pdf.invoice', ['order' => $order])->setPaper('a4', 'portrait');

        $path = "whatsapp-invoices/{$order->order_number}.pdf";
        $disk = Storage::disk(config('media-library.disk_name', 'r2'));
        $disk->put($path, $pdf->output());
        $documentUrl = $disk->url($path);

        $caption = "🧾 Here's your invoice for order *{$order->order_number}*. Thank you for shopping with Merza! 🥭";

        $waId = $this->waService->sendDocumentMessage($contact->phone, $documentUrl, "Invoice-{$order->order_number}.pdf", $caption);

        if ($waId) {
            Conversation::create([
                'contact_id'    => $contact->id,
                'channel'       => 'whatsapp',
                'direction'     => 'outbound',
                'message'       => "[Invoice PDF sent] {$caption}",
                'wa_message_id' => $waId,
                'is_bot'        => true,
                'sent_at'       => now(),
                'status'        => 'sent',
            ]);
        }
    }

    private function sendUpiQr(Contact $contact, WhatsAppSession $session, Order $order): void
    {
        // UPI not configured yet — capture the order, arrange payment manually instead of a broken QR.
        if (empty($this->settings->upi_id)) {
            $this->updateSession($session, 'menu', ['cart' => [], 'draft' => []]);

            $this->waService->sendTextMessage(
                $contact->phone,
                "✅ *Order Received!*\n\nOrder number: *{$order->order_number}*\nTotal: \u{20B9}" . number_format((float) $order->total, 2) . "\n\nOur team will contact you shortly on WhatsApp to arrange payment. Type *menu* anytime.\n\n— Merza Team 🥭"
            );
            return;
        }

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

        $caption = "Scan to pay \u{20B9}" . number_format((float) $order->total, 2) . " for order *{$order->order_number}*.\n\nOr pay manually to UPI ID: {$this->settings->upi_id}\n\nOnce paid, *send a screenshot of the payment* (or reply with your UTR/reference number) and we'll confirm it right away. 🥭";

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

    private function capturePaymentScreenshot(Contact $contact, WhatsAppSession $session, string $mediaPath): void
    {
        $orderId = $session->data['pending_order_id'] ?? null;
        $order   = $orderId ? Order::find($orderId) : null;

        if (! $order) {
            $this->sendWelcome($contact, $session);
            return;
        }

        $imageUrl = Storage::disk(config('media-library.disk_name', 'r2'))->url($mediaPath);

        $order->update([
            'payment_screenshot_path'     => $mediaPath,
            'payment_verification_status' => 'pending',
        ]);

        // Acknowledge immediately — the vision call below can take a few seconds
        // and the customer shouldn't be left wondering if it went through.
        $this->waService->sendTextMessage($contact->phone, "Got your screenshot! ✅ Verifying now, one moment... 🥭");

        $verification = (new PaymentScreenshotVerificationService($this->settings))->verify($order, $imageUrl);

        $order->update([
            'payment_verification_status' => $verification['status'],
            'payment_verified_amount'     => $verification['extracted_amount'],
            'payment_verification_notes'  => $verification['extracted_reference']
                ? "Reference read from screenshot: {$verification['extracted_reference']}"
                : null,
        ]);

        BotActivityLog::create([
            'event_type'  => 'payment_screenshot_verified',
            'contact_id'  => $contact->id,
            'raw_payload' => [
                'order_id' => $order->id,
                'verdict'  => $verification['status'],
                'amount'   => $verification['extracted_amount'],
                'raw'      => $verification['raw'],
            ],
            'status' => 'success',
        ]);

        $this->updateSession($session, 'menu', ['cart' => [], 'draft' => [], 'pending_order_id' => null]);

        if ($verification['status'] === 'ai_matched') {
            $order->update(['payment_status' => 'paid']);

            $this->waService->sendTextMessage(
                $contact->phone,
                "Payment confirmed! ✅\n\nOrder *{$order->order_number}* is now being prepared. We'll keep you posted. Type *menu* anytime.\n\n— Merza Team 🥭"
            );
            return;
        }

        $this->waService->sendTextMessage(
            $contact->phone,
            "Thanks! We couldn't automatically confirm this from the screenshot, so our team will verify it manually and get back to you shortly on order *{$order->order_number}*. Type *menu* anytime.\n\n— Merza Team 🥭"
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
