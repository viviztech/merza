<?php

namespace Tests\Feature;

use App\Models\BotActivityLog;
use App\Models\BotSetting;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WhatsAppSession;
use App\Services\WhatsAppFlowService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppFlowDistractionTest extends TestCase
{
    use RefreshDatabase;

    private Contact $contact;
    private WhatsAppSession $session;
    private BotSetting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        // A unique id per call — some scenarios below send more than one
        // outbound message, and wa_message_id is unique in conversations.
        Http::fake([
            'graph.facebook.com/*' => fn () => Http::response(
                ['messages' => [['id' => 'wamid.' . \Illuminate\Support\Str::random(12)]]],
                200
            ),
        ]);

        $this->settings = BotSetting::current();
        $this->settings->update([
            'whatsapp_phone_number_id' => '1234567890',
            'whatsapp_access_token'    => 'test-token',
            'wa_bot_enabled'           => true,
        ]);

        $this->contact = Contact::create([
            'name'  => 'WA: 9999900000',
            'phone' => '9999900000',
        ]);

        $category = Category::create(['name' => 'Mangoes', 'slug' => 'mangoes', 'is_active' => true]);
        $product  = Product::create([
            'category_id' => $category->id, 'name' => 'Imam Pasand', 'slug' => 'imam-pasand',
            'base_price'  => 400, 'unit' => 'kg', 'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'name' => '3 kg', 'sku' => 'IP-3KG',
            'price' => 900, 'weight_value' => 3, 'weight_unit' => 'kg',
            'stock_qty' => 10, 'is_active' => true,
        ]);

        $this->session = WhatsAppSession::create([
            'phone' => $this->contact->phone,
            'state' => 'categories',
            'data'  => [
                'cart' => [
                    (string) $variant->id => [
                        'variant_id' => $variant->id, 'product_id' => $product->id,
                        'product_name' => $product->name, 'variant_name' => $variant->name,
                        'sku' => $variant->sku, 'price' => (float) $variant->price,
                        'qty' => 1, 'weight_kg' => 3,
                    ],
                ],
            ],
            'expires_at' => now()->addMinutes(90),
        ]);
    }

    private function flow(): WhatsAppFlowService
    {
        return new WhatsAppFlowService(new WhatsAppService($this->settings), $this->settings);
    }

    public function test_off_script_text_mid_flow_hands_off_to_ai_when_configured(): void
    {
        $this->settings->update(['ai_provider' => 'openai', 'openai_api_key' => 'sk-test']);

        $handled = $this->flow()->handle($this->contact, 'text', 'do you deliver to Chennai?');

        $this->assertFalse($handled, 'Expected the flow to defer to AI (return false) instead of resetting');

        $log = BotActivityLog::where('event_type', 'flow_distraction')->orderByDesc('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('ai_handoff', $log->raw_payload['action']);
        $this->assertSame('categories', $log->raw_payload['state']);
    }

    public function test_off_script_text_mid_flow_falls_back_to_welcome_when_ai_not_configured(): void
    {
        // No AI provider key set — old safe behaviour should still fire, not silence.
        $handled = $this->flow()->handle($this->contact, 'text', 'do you deliver to Chennai?');

        $this->assertTrue($handled);

        $reply = Conversation::where('contact_id', $this->contact->id)
            ->where('direction', 'outbound')->orderByDesc('id')->first();
        $this->assertNotNull($reply);
        $this->assertStringContainsString('Welcome to', $reply->message);

        $log = BotActivityLog::where('event_type', 'flow_distraction')->orderByDesc('id')->first();
        $this->assertSame('welcome_reset', $log->raw_payload['action']);
    }

    public function test_explicit_order_intent_resumes_cart_instead_of_hard_reset(): void
    {
        $handled = $this->flow()->handle($this->contact, 'text', 'checkout');

        $this->assertTrue($handled);

        $reply = Conversation::where('contact_id', $this->contact->id)
            ->where('direction', 'outbound')->orderByDesc('id')->first();
        $this->assertNotNull($reply);
        $this->assertStringContainsString('Your Cart', $reply->message);

        $log = BotActivityLog::where('event_type', 'flow_distraction')->orderByDesc('id')->first();
        $this->assertSame('resumed_ordering', $log->raw_payload['action']);

        $this->session->refresh();
        $this->assertNotEmpty($this->session->data['cart'] ?? []);
    }

    public function test_order_intent_from_ai_chat_state_hands_back_into_structured_flow(): void
    {
        $this->session->update(['state' => 'ai']);

        $handled = $this->flow()->handle($this->contact, 'text', 'ok add to cart, i want to buy now');

        $this->assertTrue($handled, 'Ordering intent should be caught before the ai/ordering free-text passthrough');

        $reply = Conversation::where('contact_id', $this->contact->id)
            ->where('direction', 'outbound')->orderByDesc('id')->first();
        $this->assertStringContainsString('Your Cart', $reply->message);
    }
}
