<?php

namespace Tests\Feature;

use App\Filament\Widgets\FollowUpQueueWidget;
use App\Filament\Widgets\PaymentPendingOrdersWidget;
use App\Filament\Widgets\ReadyToPackOrdersWidget;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TodaysPipelineWidgetsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin-test@merza.com',
            'password' => bcrypt('password'),
        ]);
        $this->admin->assignRole('Admin');
    }

    public function test_follow_up_widget_shows_overdue_leads_only(): void
    {
        $contact = Contact::create(['name' => 'Overdue Lead', 'phone' => '9100000001', 'source' => 'whatsapp']);
        Lead::create(['contact_id' => $contact->id, 'stage' => 'new', 'source' => 'whatsapp', 'due_at' => now()->subHour()]);

        $convertedContact = Contact::create(['name' => 'Already Converted', 'phone' => '9100000002', 'source' => 'whatsapp']);
        Lead::create(['contact_id' => $convertedContact->id, 'stage' => 'converted', 'source' => 'whatsapp', 'due_at' => now()->subHour(), 'converted_at' => now()]);

        $this->actingAs($this->admin);

        Livewire::test(FollowUpQueueWidget::class)
            ->assertSee('Overdue Lead')
            ->assertDontSee('Already Converted');
    }

    public function test_payment_pending_widget_excludes_cancelled_and_delivered(): void
    {
        Order::create([
            'channel' => 'website', 'customer_name' => 'Unpaid Order', 'customer_phone' => '9100000003',
            'delivery_address' => 'x', 'subtotal' => 100, 'delivery_fee' => 10, 'total' => 110,
            'payment_method' => 'upi', 'payment_status' => 'unpaid', 'status' => 'pending',
        ]);
        Order::create([
            'channel' => 'website', 'customer_name' => 'Cancelled Unpaid', 'customer_phone' => '9100000004',
            'delivery_address' => 'x', 'subtotal' => 100, 'delivery_fee' => 10, 'total' => 110,
            'payment_method' => 'upi', 'payment_status' => 'unpaid', 'status' => 'cancelled',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(PaymentPendingOrdersWidget::class)
            ->assertSee('Unpaid Order')
            ->assertDontSee('Cancelled Unpaid');
    }

    public function test_ready_to_pack_widget_and_start_packing_action(): void
    {
        $order = Order::create([
            'channel' => 'whatsapp', 'customer_name' => 'Confirmed Order', 'customer_phone' => '9100000005',
            'delivery_address' => 'x', 'subtotal' => 200, 'delivery_fee' => 10, 'total' => 210,
            'payment_method' => 'cod', 'payment_status' => 'paid', 'status' => 'confirmed',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ReadyToPackOrdersWidget::class)
            ->assertSee('Confirmed Order')
            ->callTableAction('markPreparing', $order);

        $this->assertEquals('preparing', $order->fresh()->status);
    }
}
