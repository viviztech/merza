<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderNotificationService;
use App\Services\OrderTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_scanner_extracts_ids_from_qr_values_and_builds_a_courier_link(): void
    {
        $tracking = app(OrderTrackingService::class);

        $this->assertSame(['tracking_number' => 'ABC123456', 'tracking_url' => null], $tracking->parseQrValue('AWB: ABC123456'));
        $this->assertSame([
            'tracking_number' => 'ABC123456',
            'tracking_url' => 'https://courier.example/track?awb=ABC123456',
        ], $tracking->parseQrValue('https://courier.example/track?awb=ABC123456'));
        $this->assertSame(
            'https://courier.example/track/ABC123456',
            $tracking->completeUrl('https://courier.example/track/{tracking_id}', 'ABC123456')
        );
    }

    public function test_tracking_link_must_be_public_https_and_contain_the_id(): void
    {
        $tracking = app(OrderTrackingService::class);

        foreach (['http://courier.example/ABC123456', 'https://localhost/ABC123456', 'https://courier.example/other'] as $url) {
            try {
                $tracking->completeUrl($url, 'ABC123456');
                $this->fail("Accepted invalid tracking URL: {$url}");
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_dispatch_saves_tracking_and_queues_a_whatsapp_update_in_an_open_window(): void
    {
        Queue::fake();
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin);

        $contact = Contact::create(['name' => 'Customer', 'phone' => '919444398406', 'source' => 'whatsapp']);
        Conversation::create([
            'contact_id' => $contact->id,
            'channel' => 'whatsapp',
            'direction' => 'inbound',
            'message' => 'Where is my order?',
            'status' => 'received',
        ]);

        $order = Order::create([
            'customer_name' => 'Customer',
            'customer_phone' => '919444398406',
            'delivery_address' => 'Test address',
            'status' => 'preparing',
            'payment_status' => 'paid',
            'payment_method' => 'cod',
        ]);

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('nextAction', [
                'tracking_number' => 'ABC123456',
                'courier_name' => 'Test Courier',
                'tracking_url' => 'https://courier.example/track/{tracking_id}',
            ])
            ->assertHasNoActionErrors();

        $order->refresh();
        $this->assertSame('delivering', $order->status);
        $this->assertSame('ABC123456', $order->tracking_number);
        $this->assertSame('Test Courier', $order->courier_name);
        $this->assertSame('https://courier.example/track/ABC123456', $order->tracking_url);
        $this->assertNotNull($order->dispatched_at);
        $this->assertStringContainsString($order->tracking_url, app(OrderNotificationService::class)->buildMessage($order));
        Queue::assertPushed(SendWhatsAppMessageJob::class, 1);
    }

    public function test_qr_scan_fills_edit_form_fields(): void
    {
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin);

        $order = Order::create([
            'customer_name' => 'Customer',
            'customer_phone' => '919444398406',
            'delivery_address' => 'Test address',
            'status' => 'preparing',
            'payment_status' => 'paid',
            'payment_method' => 'cod',
        ]);

        Livewire::test(EditOrder::class, ['record' => $order->id])
            ->set('data.tracking_qr', 'https://courier.example/track?awb=ABC123456')
            ->assertSet('data.tracking_number', 'ABC123456')
            ->assertSet('data.tracking_url', 'https://courier.example/track?awb=ABC123456');
    }

    public function test_dispatch_does_not_queue_a_freeform_message_without_an_open_window(): void
    {
        Queue::fake();
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin);

        $order = Order::create([
            'customer_name' => 'Customer',
            'customer_phone' => '919444398406',
            'delivery_address' => 'Test address',
            'status' => 'preparing',
            'payment_status' => 'paid',
            'payment_method' => 'cod',
        ]);

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('nextAction', [
                'tracking_number' => 'ABC123456',
                'courier_name' => 'Test Courier',
                'tracking_url' => 'https://courier.example/track/{tracking_id}',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('delivering', $order->fresh()->status);
        Queue::assertNotPushed(SendWhatsAppMessageJob::class);
    }
}
