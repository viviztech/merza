<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactResource\Pages\ListContacts;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContactCategoryBulkUpdateTest extends TestCase
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

    public function test_bulk_action_updates_category_and_source_for_selected_contacts(): void
    {
        $excelContact = Contact::create([
            'name' => 'Old Excel Buyer', 'phone' => '9700000010', 'source' => 'other',
        ]);
        $untouched = Contact::create([
            'name' => 'Website Enquiry', 'phone' => '9700000011', 'source' => 'website',
        ]);

        $this->assertSame('new_lead', $excelContact->refresh()->customer_category);

        $this->actingAs($this->admin);

        Livewire::test(ListContacts::class)
            ->callTableBulkAction('updateCategoryAndSource', [$excelContact], [
                'customer_category' => 'purchased_customer',
                'source'            => 'old_excel_import',
                'mark_as_customer'  => true,
            ]);

        $excelContact->refresh();
        $untouched->refresh();

        $this->assertSame('purchased_customer', $excelContact->customer_category);
        $this->assertSame('old_excel_import', $excelContact->source);
        $this->assertTrue($excelContact->is_customer);

        $this->assertSame('new_lead', $untouched->customer_category);
        $this->assertSame('website', $untouched->source);
    }

    public function test_category_filter_narrows_the_list(): void
    {
        Contact::create(['name' => 'Purchased Person', 'phone' => '9700000012', 'source' => 'website', 'customer_category' => 'purchased_customer']);
        Contact::create(['name' => 'Fresh Enquiry', 'phone' => '9700000013', 'source' => 'website', 'customer_category' => 'new_lead']);

        $this->actingAs($this->admin);

        Livewire::test(ListContacts::class)
            ->assertSee('Purchased Person')
            ->assertSee('Fresh Enquiry')
            ->filterTable('customer_category', 'purchased_customer')
            ->assertSee('Purchased Person')
            ->assertDontSee('Fresh Enquiry');
    }
}
