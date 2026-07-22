<?php

namespace Tests\Feature;

use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\WhatsAppSession;
use App\Services\WhatsAppFlowService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppSessionResumeTest extends TestCase
{
    use RefreshDatabase;

    private Contact $contact;
    private BotSetting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        // A unique id per call — a real test scenario here sends several outbound
        // messages, and wa_message_id is unique in the conversations table.
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

        $this->contact = Contact::create(['name' => 'WA: 9888800000', 'phone' => '9888800000']);
    }

    private function flow(): WhatsAppFlowService
    {
        return new WhatsAppFlowService(new WhatsAppService($this->settings), $this->settings);
    }

    private function cartPayload(): array
    {
        return [
            '1' => [
                'variant_id' => 1, 'product_id' => 1,
                'product_name' => 'Imam Pasand', 'variant_name' => '3 kg',
                'sku' => 'IP-3KG', 'price' => 900.0, 'qty' => 1, 'weight_kg' => 3,
            ],
        ];
    }

    public function test_expired_session_with_cart_gets_a_resume_prompt_instead_of_being_wiped(): void
    {
        $session = WhatsAppSession::create([
            'phone'      => $this->contact->phone,
            'state'      => 'cart',
            'data'       => ['cart' => $this->cartPayload()],
            'expires_at' => now()->subMinutes(5), // expired
        ]);

        $fetched = WhatsAppSession::getOrCreate($this->contact->phone);

        $this->assertSame($session->id, $fetched->id, 'Should reuse the existing row, not silently create a new blank one');
        $this->assertSame('resume_prompt', $fetched->state);
        $this->assertSame('cart', $fetched->data['stashed_state']);
        $this->assertNotEmpty($fetched->data['cart'] ?? []);
        $this->assertTrue($fetched->expires_at->isFuture());
    }

    public function test_expired_session_with_empty_cart_resets_cleanly_with_no_prompt(): void
    {
        WhatsAppSession::create([
            'phone'      => $this->contact->phone,
            'state'      => 'categories',
            'data'       => [],
            'expires_at' => now()->subMinutes(5),
        ]);

        $fetched = WhatsAppSession::getOrCreate($this->contact->phone);

        $this->assertSame('start', $fetched->state);
        $this->assertSame([], $fetched->data);
    }

    public function test_anything_but_the_resume_buttons_re_shows_the_prompt(): void
    {
        WhatsAppSession::create([
            'phone'      => $this->contact->phone,
            'state'      => 'cart',
            'data'       => ['cart' => $this->cartPayload()],
            'expires_at' => now()->subMinutes(5),
        ]);

        $handled = $this->flow()->handle($this->contact, 'text', 'hello');

        $this->assertTrue($handled);

        $reply = Conversation::where('contact_id', $this->contact->id)->where('direction', 'outbound')->orderByDesc('id')->first();
        $this->assertStringContainsString('Welcome back', $reply->message);
        $this->assertStringContainsString('1 item', $reply->message);
    }

    public function test_resume_cart_button_restores_the_stashed_cart(): void
    {
        WhatsAppSession::create([
            'phone'      => $this->contact->phone,
            'state'      => 'cart',
            'data'       => ['cart' => $this->cartPayload()],
            'expires_at' => now()->subMinutes(5),
        ]);

        // First message flips it into resume_prompt (mirrors what a real inbound message does)
        $this->flow()->handle($this->contact, 'text', 'hi');

        $handled = $this->flow()->handle($this->contact, 'interactive', '', 'resume_cart');
        $this->assertTrue($handled);

        $session = WhatsAppSession::where('phone', $this->contact->phone)->first();
        $this->assertSame('cart', $session->state);
        $this->assertArrayNotHasKey('stashed_state', $session->data);
        $this->assertNotEmpty($session->data['cart']);

        $reply = Conversation::where('contact_id', $this->contact->id)->where('direction', 'outbound')->orderByDesc('id')->first();
        $this->assertStringContainsString('Your Cart', $reply->message);
    }

    public function test_fresh_start_button_clears_the_cart(): void
    {
        WhatsAppSession::create([
            'phone'      => $this->contact->phone,
            'state'      => 'cart',
            'data'       => ['cart' => $this->cartPayload()],
            'expires_at' => now()->subMinutes(5),
        ]);

        $this->flow()->handle($this->contact, 'text', 'hi');

        $handled = $this->flow()->handle($this->contact, 'interactive', '', 'fresh_start');
        $this->assertTrue($handled);

        $session = WhatsAppSession::where('phone', $this->contact->phone)->first();
        $this->assertSame('menu', $session->state);
        $this->assertSame([], $session->data['cart']);
        $this->assertArrayNotHasKey('stashed_state', $session->data);
    }
}
