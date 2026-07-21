<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactResource\Pages\ListContacts;
use App\Filament\Resources\LeadResource\Pages\ListLeads;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreatedAtRangeFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $this->admin = User::create(['name' => 'Test Admin', 'email' => 'admin-test@merza.com', 'password' => bcrypt('password')]);
        $this->admin->assignRole('Admin');
    }

    public function test_contacts_created_at_filter_narrows_the_list(): void
    {
        $old = Contact::create(['name' => 'Ancient Persimmon', 'phone' => '9700000001', 'source' => 'website']);
        $old->timestamps = false;
        $old->created_at = now()->subDays(10);
        $old->save();

        Contact::create(['name' => 'Fresh Mango Buyer', 'phone' => '9700000002', 'source' => 'website']);

        $this->actingAs($this->admin);

        Livewire::test(ListContacts::class)
            ->assertSee('Ancient Persimmon')
            ->assertSee('Fresh Mango Buyer')
            ->filterTable('created_at', ['created_from' => now()->subDays(2)->toDateString()])
            ->assertSee('Fresh Mango Buyer')
            ->assertDontSee('Ancient Persimmon');
    }

    public function test_leads_created_at_filter_narrows_the_list(): void
    {
        $contact1 = Contact::create(['name' => 'Old Lead Person', 'phone' => '9700000003', 'source' => 'website']);
        $oldLead  = Lead::create(['contact_id' => $contact1->id, 'stage' => 'new', 'source' => 'website']);
        $oldLead->timestamps = false;
        $oldLead->created_at = now()->subDays(10);
        $oldLead->save();

        $contact2 = Contact::create(['name' => 'New Lead Person', 'phone' => '9700000004', 'source' => 'website']);
        Lead::create(['contact_id' => $contact2->id, 'stage' => 'new', 'source' => 'website']);

        $this->actingAs($this->admin);

        Livewire::test(ListLeads::class)
            ->assertSee('Old Lead Person')
            ->assertSee('New Lead Person')
            ->filterTable('created_at', ['created_from' => now()->subDays(2)->toDateString()])
            ->assertSee('New Lead Person')
            ->assertDontSee('Old Lead Person');
    }
}
