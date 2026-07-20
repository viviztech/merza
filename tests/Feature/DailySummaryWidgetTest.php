<?php

namespace Tests\Feature;

use App\Filament\Widgets\DailySummaryWidget;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DailySummaryWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_summary_reflects_todays_activity(): void
    {
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin = User::create(['name' => 'Test Admin', 'email' => 'admin-test@merza.com', 'password' => bcrypt('password')]);
        $admin->assignRole('Admin');

        $contact = Contact::create(['name' => 'Today Lead', 'phone' => '9200000001', 'source' => 'whatsapp']);
        Lead::create(['contact_id' => $contact->id, 'stage' => 'new', 'source' => 'whatsapp']);

        $contactedContact = Contact::create(['name' => 'Contacted Lead', 'phone' => '9200000002', 'source' => 'whatsapp']);
        Lead::create(['contact_id' => $contactedContact->id, 'stage' => 'contacted', 'source' => 'whatsapp']);

        Order::create([
            'channel' => 'website', 'customer_name' => 'Confirmed', 'customer_phone' => '9200000003',
            'delivery_address' => 'x', 'subtotal' => 100, 'delivery_fee' => 10, 'total' => 110,
            'payment_method' => 'upi', 'payment_status' => 'paid', 'status' => 'confirmed',
        ]);
        Order::create([
            'channel' => 'website', 'customer_name' => 'Unpaid', 'customer_phone' => '9200000004',
            'delivery_address' => 'x', 'subtotal' => 100, 'delivery_fee' => 10, 'total' => 110,
            'payment_method' => 'upi', 'payment_status' => 'unpaid', 'status' => 'pending',
        ]);
        Order::create([
            'channel' => 'website', 'customer_name' => 'Preparing', 'customer_phone' => '9200000005',
            'delivery_address' => 'x', 'subtotal' => 100, 'delivery_fee' => 10, 'total' => 110,
            'payment_method' => 'upi', 'payment_status' => 'paid', 'status' => 'preparing',
        ]);
        Order::create([
            'channel' => 'website', 'customer_name' => 'Delivering', 'customer_phone' => '9200000006',
            'delivery_address' => 'x', 'subtotal' => 100, 'delivery_fee' => 10, 'total' => 110,
            'payment_method' => 'upi', 'payment_status' => 'paid', 'status' => 'delivering',
        ]);

        $this->actingAs($admin);

        Livewire::test(DailySummaryWidget::class)
            ->assertSee('2') // 2 enquiries today
            ->assertSee('Enquiries Today')
            ->assertSee('Contacted Today')
            ->assertSee('Confirmed Today')
            ->assertSee('Payment Pending')
            ->assertSee('Packed Today')
            ->assertSee('Dispatched Today')
            ->assertSee('Conversion Rate');
    }
}
