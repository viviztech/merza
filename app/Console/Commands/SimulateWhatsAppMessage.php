<?php

namespace App\Console\Commands;

use App\Jobs\ProcessInboundWhatsAppJob;
use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Services\BotReplyService;
use Illuminate\Console\Command;

class SimulateWhatsAppMessage extends Command
{
    protected $signature = 'whatsapp:simulate
        {--phone=9876501234 : Sender phone number (must match an existing contact or will create one)}
        {--message= : Inbound message text}
        {--sync : Run synchronously without queue}';

    protected $description = 'Simulate an inbound WhatsApp message to test the bot reply pipeline';

    public function handle(): int
    {
        $phone   = $this->option('phone');
        $message = $this->option('message') ?? 'Hi, I want to order some mangoes. What is the price?';
        $waId    = 'SIMULATE_WA_' . strtoupper(substr(md5(uniqid()), 0, 8));

        $this->info('Simulating inbound WhatsApp message...');
        $this->table(
            ['Field', 'Value'],
            [
                ['From Phone',  $phone],
                ['Message',     $message],
                ['WA Message ID', $waId],
            ]
        );

        if ($this->option('sync')) {
            $this->runSync($phone, $message, $waId);
        } else {
            ProcessInboundWhatsAppJob::dispatch($phone, $waId, $message, now()->timestamp);
            $this->info('Job dispatched. Run <comment>php artisan queue:work --once</comment> to process.');
        }

        return self::SUCCESS;
    }

    private function runSync(string $phone, string $message, string $waId): void
    {
        $settings = BotSetting::current();
        $settings->update(['wa_bot_enabled' => true]);

        // Find or create contact
        $contact = Contact::where('phone', $phone)->first()
            ?? Contact::create([
                'name'   => 'WA: ' . $phone,
                'phone'  => $phone,
                'source' => 'whatsapp',
                'tags'   => ['whatsapp_inbound', 'simulated'],
            ]);

        $this->info("Contact: [{$contact->id}] {$contact->name}");

        // Store inbound conversation
        $inbound = Conversation::create([
            'contact_id'    => $contact->id,
            'channel'       => 'whatsapp',
            'direction'     => 'inbound',
            'message'       => $message,
            'wa_message_id' => $waId,
            'is_bot'        => false,
            'sent_at'       => now(),
            'status'        => 'read',
        ]);
        $this->info("Inbound conversation stored: [{$inbound->id}]");

        // Generate AI reply
        $this->info('Calling AI for bot reply (active provider: ' . $settings->ai_provider . ')...');
        $replyService = new BotReplyService($settings);
        $reply        = $replyService->generateReply($contact, $message, $inbound);

        if ($reply) {
            $this->newLine();
            $this->line('<fg=cyan>--- Bot Reply ---</>');
            $this->line($reply);
            $this->newLine();

            $draft = Conversation::create([
                'contact_id'    => $contact->id,
                'channel'       => 'whatsapp',
                'direction'     => 'outbound',
                'message'       => $reply,
                'is_bot'        => true,
                'replied_to_id' => $inbound->id,
                'sent_at'       => null,
                'status'        => 'sent',
            ]);

            $this->info("Draft reply saved: [{$draft->id}] — go to Admin > Conversations to Send it.");
        } else {
            $this->warn('No reply generated (Anthropic API key not set?)');
        }

        $this->info('Done. Check Admin > Sales & CRM > Conversations > Drafts tab.');
    }
}
