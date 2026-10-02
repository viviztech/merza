<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactResource\Pages\ListContacts;
use App\Filament\Resources\LeadResource\Pages\ListLeads;
use App\Jobs\ProcessMetaLeadJob;
use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MetaAdAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_ad_name_is_saved_on_each_lead_and_shown_for_the_contact(): void
    {
        BotSetting::current()->update([
            'meta_page_access_token' => 'test-token',
            'ai_provider' => 'none',
        ]);

        Http::fake(function ($request) {
            $second = str_contains($request->url(), '/META_LEAD_2');

            return Http::response([
                'id' => $second ? 'META_LEAD_2' : 'META_LEAD_1',
                'ad_id' => $second ? 'AD_2' : 'AD_1',
                'ad_name' => $second ? 'October Jackfruit Ad' : 'September Mango Ad',
                'field_data' => [
                    ['name' => 'full_name', 'values' => ['Test Customer']],
                    ['name' => 'phone_number', 'values' => ['9876543210']],
                ],
            ]);
        });

        (new ProcessMetaLeadJob('META_LEAD_1', 'FORM_1', 'PAGE_1'))->handle();
        (new ProcessMetaLeadJob('META_LEAD_2', 'FORM_1', 'PAGE_1'))->handle();

        $contact = Contact::where('phone', '9876543210')->firstOrFail();
        $this->assertSame('October Jackfruit Ad', $contact->meta_ad_name);
        $this->assertSame('AD_2', $contact->meta_ad_id);
        $this->assertSame('September Mango Ad', Lead::where('meta_lead_id', 'META_LEAD_1')->firstOrFail()->meta_ad_name);
        $this->assertSame('October Jackfruit Ad', Lead::where('meta_lead_id', 'META_LEAD_2')->firstOrFail()->meta_ad_name);

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin = User::create(['name' => 'Test Admin', 'email' => 'meta-admin@merza.com', 'password' => bcrypt('password')]);
        $admin->assignRole('Admin');
        $this->actingAs($admin);

        Livewire::test(ListLeads::class)->assertSee('September Mango Ad')->assertSee('October Jackfruit Ad');
        Livewire::test(ListContacts::class)->assertSee('October Jackfruit Ad');
    }
}
