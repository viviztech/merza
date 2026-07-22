<?php

namespace Tests\Feature;

use App\Models\BotActivityLog;
use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Order;
use App\Models\WhatsAppSession;
use App\Services\WhatsAppFlowService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentScreenshotVerificationTest extends TestCase
{
    use RefreshDatabase;

    private Contact $contact;
    private Order $order;
    private BotSetting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('r2');

        $this->settings = BotSetting::current();
        $this->settings->update([
            'whatsapp_phone_number_id' => '1234567890',
            'whatsapp_access_token'    => 'test-token',
            'wa_bot_enabled'           => true,
            'ai_provider'              => 'openai',
            'openai_api_key'           => 'sk-test',
        ]);

        $this->contact = Contact::create(['name' => 'WA: 9666600000', 'phone' => '9666600000']);

        $this->order = Order::create([
            'channel'          => 'whatsapp',
            'contact_id'       => $this->contact->id,
            'customer_name'    => 'Test Customer',
            'customer_phone'   => '9666600000',
            'delivery_address' => 'Test address',
            'subtotal'         => 900,
            'delivery_fee'     => 50,
            'total'            => 950,
            'payment_method'   => 'whatsapp',
            'payment_status'   => 'unpaid',
        ]);

        WhatsAppSession::create([
            'phone' => $this->contact->phone,
            'state' => 'awaiting_payment_ref',
            'data'  => ['pending_order_id' => $this->order->id],
            'expires_at' => now()->addMinutes(90),
        ]);

        Storage::disk('r2')->put('whatsapp-inbound/test.jpg', 'fake-image-bytes');
    }

    private function flow(): WhatsAppFlowService
    {
        return new WhatsAppFlowService(new WhatsAppService($this->settings), $this->settings);
    }

    public function test_a_matching_screenshot_auto_confirms_the_order(): void
    {
        Http::fake([
            'graph.facebook.com/*' => fn () => Http::response(
                ['messages' => [['id' => 'wamid.' . \Illuminate\Support\Str::random(12)]]],
                200
            ),
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode(['status' => 'success', 'amount' => 950, 'reference' => 'UTR123456'])]],
                ],
            ], 200),
        ]);

        $handled = $this->flow()->handle($this->contact, 'image', '', '', 'whatsapp-inbound/test.jpg');

        $this->assertTrue($handled);

        $this->order->refresh();
        $this->assertSame('paid', $this->order->payment_status);
        $this->assertSame('ai_matched', $this->order->payment_verification_status);
        $this->assertEquals(950.0, (float) $this->order->payment_verified_amount);
        $this->assertStringContainsString('UTR123456', $this->order->payment_verification_notes);

        $this->assertDatabaseHas('bot_activity_logs', ['event_type' => 'payment_screenshot_verified']);

        $session = WhatsAppSession::where('phone', $this->contact->phone)->first();
        $this->assertSame('menu', $session->state);
        $this->assertNull($session->data['pending_order_id'] ?? null);
    }

    public function test_a_mismatched_amount_flags_for_review_instead_of_confirming(): void
    {
        Http::fake([
            'graph.facebook.com/*' => fn () => Http::response(
                ['messages' => [['id' => 'wamid.' . \Illuminate\Support\Str::random(12)]]],
                200
            ),
            'api.openai.com/*' => Http::response([
                'choices' => [
                    // Screenshot says success but a different amount than the order total.
                    ['message' => ['content' => json_encode(['status' => 'success', 'amount' => 500, 'reference' => 'UTR000'])]],
                ],
            ], 200),
        ]);

        $this->flow()->handle($this->contact, 'image', '', '', 'whatsapp-inbound/test.jpg');

        $this->order->refresh();
        $this->assertSame('unpaid', $this->order->payment_status, 'A mismatched amount must never auto-confirm payment');
        $this->assertSame('ai_unclear', $this->order->payment_verification_status);
    }

    public function test_a_failed_payment_screenshot_does_not_confirm_the_order(): void
    {
        Http::fake([
            'graph.facebook.com/*' => fn () => Http::response(
                ['messages' => [['id' => 'wamid.' . \Illuminate\Support\Str::random(12)]]],
                200
            ),
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode(['status' => 'failed', 'amount' => 950, 'reference' => null])]],
                ],
            ], 200),
        ]);

        $this->flow()->handle($this->contact, 'image', '', '', 'whatsapp-inbound/test.jpg');

        $this->order->refresh();
        $this->assertSame('unpaid', $this->order->payment_status);
        $this->assertSame('ai_mismatch', $this->order->payment_verification_status);
    }

    public function test_an_unparseable_ai_response_is_treated_as_unclear_not_a_crash(): void
    {
        Http::fake([
            'graph.facebook.com/*' => fn () => Http::response(
                ['messages' => [['id' => 'wamid.' . \Illuminate\Support\Str::random(12)]]],
                200
            ),
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'not valid json at all']],
                ],
            ], 200),
        ]);

        $handled = $this->flow()->handle($this->contact, 'image', '', '', 'whatsapp-inbound/test.jpg');

        $this->assertTrue($handled);

        $this->order->refresh();
        $this->assertSame('unpaid', $this->order->payment_status);
        $this->assertSame('ai_unclear', $this->order->payment_verification_status);
    }

    public function test_text_utr_reply_still_works_alongside_screenshot_capture(): void
    {
        Http::fake([
            'graph.facebook.com/*' => fn () => Http::response(
                ['messages' => [['id' => 'wamid.' . \Illuminate\Support\Str::random(12)]]],
                200
            ),
        ]);

        $handled = $this->flow()->handle($this->contact, 'text', 'UTR987654321');

        $this->assertTrue($handled);

        $this->order->refresh();
        $this->assertSame('UTR987654321', $this->order->payment_reference);
    }
}
