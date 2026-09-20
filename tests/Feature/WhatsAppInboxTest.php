<?php

namespace Tests\Feature;

use App\Filament\Pages\WhatsAppInbox;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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
            ->assertSet('selectedContactId', $first->id);

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
