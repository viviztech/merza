<?php

namespace Tests\Feature;

use App\Models\BotActivityLog;
use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Order;
use App\Models\WhatsAppSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NudgeStalledCheckoutsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'graph.facebook.com/*' => fn () => Http::response(
                ['messages' => [['id' => 'wamid.' . \Illuminate\Support\Str::random(12)]]],
                200
            ),
        ]);

        BotSetting::current()->update([
            'whatsapp_phone_number_id' => '1234567890',
            'whatsapp_access_token'    => 'test-token',
            'wa_bot_enabled'           => true,
        ]);
    }

    private function backdateSession(WhatsAppSession $session, \DateTimeInterface $when): void
    {
        $session->timestamps = false;
        $session->updated_at = $when;
        $session->save();
        $session->timestamps = true;
    }

    public function test_nudges_a_cart_stalled_past_the_threshold(): void
    {
        Contact::create(['name' => 'WA: 9777700000', 'phone' => '9777700000']);

        $session = WhatsAppSession::create([
            'phone' => '9777700000',
            'state' => 'cart',
            'data'  => ['cart' => ['1' => ['product_name' => 'Mango', 'variant_name' => '3kg', 'price' => 900, 'qty' => 1]]],
            'expires_at' => now()->addMinutes(90),
        ]);
        $this->backdateSession($session, now()->subMinutes(15));

        Artisan::call('whatsapp:nudge-stalled-checkouts');

        $session->refresh();
        $this->assertTrue($session->data['nudge_sent'] ?? false);

        $this->assertDatabaseHas('bot_activity_logs', ['event_type' => 'cart_nudge_sent']);
    }

    public function test_does_not_nudge_twice(): void
    {
        Contact::create(['name' => 'WA: 9777700001', 'phone' => '9777700001']);

        $session = WhatsAppSession::create([
            'phone' => '9777700001',
            'state' => 'cart',
            'data'  => ['cart' => ['1' => ['product_name' => 'Mango', 'variant_name' => '3kg', 'price' => 900, 'qty' => 1]]],
            'expires_at' => now()->addMinutes(90),
        ]);
        $this->backdateSession($session, now()->subMinutes(15));

        Artisan::call('whatsapp:nudge-stalled-checkouts');
        Artisan::call('whatsapp:nudge-stalled-checkouts');

        $this->assertSame(1, BotActivityLog::where('event_type', 'cart_nudge_sent')->count());
    }

    public function test_does_not_nudge_a_cart_thats_not_stalled_yet(): void
    {
        Contact::create(['name' => 'WA: 9777700002', 'phone' => '9777700002']);

        $session = WhatsAppSession::create([
            'phone' => '9777700002',
            'state' => 'cart',
            'data'  => ['cart' => ['1' => ['product_name' => 'Mango', 'variant_name' => '3kg', 'price' => 900, 'qty' => 1]]],
            'expires_at' => now()->addMinutes(90),
        ]);
        $this->backdateSession($session, now()->subMinutes(2)); // fresh, not stalled

        Artisan::call('whatsapp:nudge-stalled-checkouts');

        $session->refresh();
        $this->assertFalse($session->data['nudge_sent'] ?? false);
        $this->assertSame(0, BotActivityLog::where('event_type', 'cart_nudge_sent')->count());
    }

    public function test_follows_up_on_an_unpaid_whatsapp_order_with_no_reference(): void
    {
        $contact = Contact::create(['name' => 'WA: 9777700003', 'phone' => '9777700003']);

        $order = Order::create([
            'channel'          => 'whatsapp',
            'contact_id'       => $contact->id,
            'customer_name'    => 'Test Customer',
            'customer_phone'   => '9777700003',
            'delivery_address' => 'Test address',
            'subtotal'         => 900,
            'delivery_fee'     => 50,
            'total'            => 950,
            'payment_method'   => 'whatsapp',
            'payment_status'   => 'unpaid',
        ]);
        $order->timestamps = false;
        $order->created_at = now()->subHours(3);
        $order->save();
        $order->timestamps = true;

        Artisan::call('whatsapp:nudge-stalled-checkouts');

        $this->assertDatabaseHas('bot_activity_logs', [
            'event_type' => 'payment_followup_sent',
        ]);
    }

    public function test_does_not_follow_up_twice_on_the_same_order(): void
    {
        $contact = Contact::create(['name' => 'WA: 9777700004', 'phone' => '9777700004']);

        $order = Order::create([
            'channel'          => 'whatsapp',
            'contact_id'       => $contact->id,
            'customer_name'    => 'Test Customer',
            'customer_phone'   => '9777700004',
            'delivery_address' => 'Test address',
            'subtotal'         => 900,
            'delivery_fee'     => 50,
            'total'            => 950,
            'payment_method'   => 'whatsapp',
            'payment_status'   => 'unpaid',
        ]);
        $order->timestamps = false;
        $order->created_at = now()->subHours(3);
        $order->save();
        $order->timestamps = true;

        Artisan::call('whatsapp:nudge-stalled-checkouts');
        Artisan::call('whatsapp:nudge-stalled-checkouts');

        $this->assertSame(1, BotActivityLog::where('event_type', 'payment_followup_sent')->count());
    }
}
