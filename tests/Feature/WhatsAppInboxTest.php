<?php

namespace Tests\Feature;

use App\Filament\Pages\WhatsAppInbox;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Contact;
use App\Models\BotSetting;
use App\Models\Conversation;
use App\Models\User;
use App\Services\WhatsAppMessagePolicy;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WhatsAppInboxTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');
    }

    public function test_inbox_groups_messages_into_one_contact_thread(): void
    {
        $contact = Contact::create([
            'name' => 'Mango Customer',
            'phone' => '919999999999',
            'source' => 'whatsapp',
        ]);

        $this->message($contact, 'inbound', 'Hello from WhatsApp');
        $this->message($contact, 'outbound', 'Hello from Merza', true);

        $this->actingAs($this->admin)
            ->get('/admin/whatsapp-inbox')
            ->assertSuccessful()
            ->assertSee('Mango Customer')
            ->assertSee('Hello from WhatsApp')
            ->assertSee('Hello from Merza');
    }

    public function test_selecting_a_thread_marks_its_inbound_messages_seen(): void
    {
        $first = Contact::create(['name' => 'First', 'phone' => '911111111111', 'source' => 'whatsapp']);
        $second = Contact::create(['name' => 'Second', 'phone' => '922222222222', 'source' => 'whatsapp']);
        $firstMessage = $this->message($first, 'inbound', 'Unread first');
        $this->message($second, 'inbound', 'Newest unread')->update(['created_at' => now()->addMinute()]);

        $this->actingAs($this->admin);

        Livewire::test(WhatsAppInbox::class)
            ->call('selectThread', $first->id)
            ->assertSet('selectedContactId', $first->id)
            ->assertSet('mobileThreadOpen', true)
            ->call('closeThread')
            ->assertSet('mobileThreadOpen', false);

        $this->assertNotNull($firstMessage->fresh()->seen_at);
    }

    public function test_team_can_queue_a_reply_from_the_inbox(): void
    {
        Queue::fake();

        $contact = Contact::create([
            'name' => 'Reply Customer',
            'phone' => '933333333333',
            'source' => 'whatsapp',
        ]);
        $this->message($contact, 'inbound', 'Can you help?');

        $this->actingAs($this->admin);

        Livewire::test(WhatsAppInbox::class)
            ->set('replyText', 'Yes, our team can help.')
            ->call('sendReply')
            ->assertHasNoErrors()
            ->assertSet('replyText', '');

        $reply = Conversation::where('contact_id', $contact->id)
            ->where('direction', 'outbound')
            ->first();

        $this->assertNotNull($reply);
        $this->assertSame($this->admin->id, $reply->handled_by);
        Queue::assertPushed(SendWhatsAppMessageJob::class, fn ($job) => $job->conversationId === $reply->id);
    }

    public function test_meta_status_webhook_updates_delivery_receipts_without_regressing(): void
    {
        $contact = Contact::create(['name' => 'Receipt Customer', 'phone' => '944444444444', 'source' => 'whatsapp']);
        $message = $this->message($contact, 'outbound', 'On the way');
        $message->update(['wa_message_id' => 'wamid.receipt-test', 'status' => 'sent']);

        $this->postJson('/webhook/meta', $this->statusPayload('read'))->assertOk();
        $this->assertSame('read', $message->fresh()->status);

        $this->postJson('/webhook/meta', $this->statusPayload('delivered'))->assertOk();
        $this->assertSame('read', $message->fresh()->status);
    }

    public function test_reply_is_blocked_when_customer_service_window_expires(): void
    {
        Queue::fake();
        $contact = Contact::create(['name' => 'Older Customer', 'phone' => '955555555555', 'source' => 'whatsapp']);
        $this->message($contact, 'inbound', 'Yesterday')->update(['sent_at' => now()->subHours(25), 'created_at' => now()->subHours(25)]);

        $this->actingAs($this->admin);
        Livewire::test(WhatsAppInbox::class)
            ->set('replyText', 'Late reply')
            ->call('sendReply')
            ->assertSet('replyText', 'Late reply')
            ->assertSee('Reply window closed');

        $this->assertFalse(Conversation::where('contact_id', $contact->id)->where('direction', 'outbound')->exists());
        Queue::assertNotPushed(SendWhatsAppMessageJob::class);
    }

    public function test_opt_out_revokes_existing_outreach_consent(): void
    {
        $contact = Contact::create(['name' => 'Opt in Customer', 'phone' => '966666666666', 'source' => 'whatsapp']);
        $this->message($contact, 'inbound', 'Hello');
        $contact->whatsAppConsents()->create([
            'recorded_by' => $this->admin->id,
            'category' => 'marketing',
            'source' => 'checkout',
            'evidence' => 'Customer explicitly opted in',
            'granted_at' => now(),
        ]);

        $this->assertTrue(app(WhatsAppMessagePolicy::class)->hasOutreachConsent($contact, 'marketing'));
        $contact->optOutWhatsApp();
        $this->assertFalse(app(WhatsAppMessagePolicy::class)->hasOutreachConsent($contact->fresh(), 'marketing'));
        $this->assertNotNull($contact->whatsAppConsents()->first()->revoked_at);
    }

    public function test_queued_reply_is_rechecked_when_the_window_closes(): void
    {
        Http::fake();
        $contact = Contact::create(['name' => 'Queue Customer', 'phone' => '977777777777', 'source' => 'whatsapp']);
        $this->message($contact, 'inbound', 'Hello')->update(['sent_at' => now()->subHours(25)]);
        $reply = Conversation::create([
            'contact_id' => $contact->id,
            'channel' => 'whatsapp',
            'direction' => 'outbound',
            'message' => 'Late reply',
            'status' => 'sent',
        ]);

        (new SendWhatsAppMessageJob($reply->id))->handle();

        $this->assertSame('failed', $reply->fresh()->status);
        $this->assertNotNull($reply->fresh()->failure_reason);
        Http::assertNothingSent();
    }

    public function test_failed_meta_receipt_shows_reason(): void
    {
        $contact = Contact::create(['name' => 'Receipt failure', 'phone' => '988888888888', 'source' => 'whatsapp']);
        $message = $this->message($contact, 'outbound', 'On the way');
        $message->update(['wa_message_id' => 'wamid.receipt-test']);
        $payload = $this->statusPayload('failed');
        $payload['entry'][0]['changes'][0]['value']['statuses'][0]['errors'] = [['code' => 131026, 'title' => 'Message undeliverable']];

        $this->postJson('/webhook/meta', $payload)->assertOk();

        $this->assertSame('failed', $message->fresh()->status);
        $this->assertStringContainsString('131026', $message->fresh()->failure_reason);
    }

    public function test_only_approved_template_with_category_consent_can_be_sent(): void
    {
        BotSetting::current()->update([
            'whatsapp_business_account_id' => '123456789',
            'whatsapp_phone_number_id' => '987654321',
            'whatsapp_access_token' => 'test-token',
        ]);
        Http::fake([
            'graph.facebook.com/*/message_templates*' => Http::response(['data' => [[
                'name' => 'support_followup', 'language' => 'en_US', 'status' => 'APPROVED',
                'category' => 'UTILITY', 'components' => [['type' => 'BODY', 'text' => 'Your order is ready.']],
            ]]], 200),
            'graph.facebook.com/*/messages' => Http::response(['messages' => [['id' => 'wamid.template']]], 200),
        ]);
        $contact = Contact::create(['name' => 'Template Customer', 'phone' => '999999999999', 'source' => 'whatsapp']);
        $this->message($contact, 'inbound', 'Old message')->update(['sent_at' => now()->subHours(25)]);
        $this->actingAs($this->admin);

        Livewire::test(WhatsAppInbox::class)
            ->call('loadApprovedTemplates')
            ->set('selectedTemplate', 'support_followup|en_US')
            ->call('sendApprovedTemplate');
        $this->assertFalse(Conversation::where('wa_message_id', 'wamid.template')->exists());

        $contact->whatsAppConsents()->create([
            'recorded_by' => $this->admin->id,
            'category' => 'utility',
            'source' => 'checkout',
            'evidence' => 'Explicit order-update opt-in',
            'granted_at' => now(),
        ]);
        Livewire::test(WhatsAppInbox::class)
            ->call('loadApprovedTemplates')
            ->set('selectedTemplate', 'support_followup|en_US')
            ->call('sendApprovedTemplate');

        $this->assertTrue(Conversation::where('wa_message_id', 'wamid.template')->exists());
    }

    public function test_inbox_does_not_show_removed_workflow_sections(): void
    {
        $contact = Contact::create(['name' => 'Team Customer', 'phone' => '900000000001', 'source' => 'whatsapp']);
        $this->message($contact, 'inbound', 'Please help');
        $this->actingAs($this->admin);

        Livewire::test(WhatsAppInbox::class)
            ->assertDontSee('Team workflow')
            ->assertDontSee('Saved replies')
            ->assertDontSee('Outreach consent')
            ->assertSee('Meta-approved templates');
    }

    public function test_shared_whatsapp_sender_blocks_freeform_without_recent_inbound(): void
    {
        BotSetting::current()->update(['whatsapp_phone_number_id' => '123', 'whatsapp_access_token' => 'test-token']);
        Http::fake();
        $contact = Contact::create(['name' => 'Outbound Customer', 'phone' => '911111111119', 'source' => 'website']);
        $service = new WhatsAppService(BotSetting::current());

        $this->assertNull($service->sendTextMessage($contact->phone, 'Hello'));
        Http::assertNothingSent();
    }

    private function message(Contact $contact, string $direction, string $body, bool $isBot = false): Conversation
    {
        return Conversation::create([
            'contact_id' => $contact->id,
            'channel' => 'whatsapp',
            'direction' => $direction,
            'message' => $body,
            'status' => $direction === 'inbound' ? 'read' : 'sent',
            'is_bot' => $isBot,
            'sent_at' => now(),
        ]);
    }

    private function statusPayload(string $status): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.receipt-test',
                            'status' => $status,
                            'timestamp' => (string) now()->timestamp,
                            'recipient_id' => '944444444444',
                        ]],
                    ],
                ]],
            ]],
        ];
    }
}
