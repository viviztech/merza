<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMetaLeadJob;
use App\Models\BotSetting;
use App\Services\LeadFollowUpAiService;
use App\Services\MetaLeadsService;
use Illuminate\Console\Command;

class SimulateMetaLead extends Command
{
    protected $signature = 'meta:simulate-lead
        {--name= : Customer full name}
        {--phone= : Customer phone number}
        {--city= : Customer city}
        {--product= : Product interest}
        {--sync : Run synchronously (skip queue, call job handle() directly)}';

    protected $description = 'Simulate a Meta Lead Ad webhook to test the full pipeline';

    public function handle(): int
    {
        $name    = $this->option('name')    ?? 'Anjali Mehta';
        $phone   = $this->option('phone')   ?? '9876501234';
        $city    = $this->option('city')    ?? 'Pune';
        $product = $this->option('product') ?? 'Alphonso Mangoes 5kg Box';

        $fakeLeadId = 'SIMULATE_' . strtoupper(substr(md5(uniqid()), 0, 10));
        $fakeFormId = 'FORM_TEST_001';
        $fakePageId = 'PAGE_TEST_001';

        $fakePayload = [
            'object' => 'page',
            'entry'  => [[
                'id'      => $fakePageId,
                'time'    => time(),
                'changes' => [[
                    'field' => 'leadgen',
                    'value' => [
                        'leadgen_id' => $fakeLeadId,
                        'page_id'    => $fakePageId,
                        'form_id'    => $fakeFormId,
                        'created_time' => time(),
                        'field_data'   => [
                            ['name' => 'full_name',     'values' => [$name]],
                            ['name' => 'phone_number',  'values' => [$phone]],
                            ['name' => 'city',          'values' => [$city]],
                            ['name' => 'product',       'values' => [$product]],
                        ],
                    ],
                ]],
            ]],
        ];

        $this->info('Simulating Meta Lead Ad webhook...');
        $this->table(
            ['Field', 'Value'],
            [
                ['Name',    $name],
                ['Phone',   $phone],
                ['City',    $city],
                ['Product', $product],
                ['Lead ID', $fakeLeadId],
            ]
        );

        // Enable bot temporarily for simulation
        $settings = BotSetting::current();
        $wasEnabled = $settings->bot_enabled;
        $settings->update(['bot_enabled' => true]);

        if ($this->option('sync')) {
            $this->info('Running synchronously (bypassing Graph API fetch)...');
            $this->runSyncSimulation($settings, $fakeLeadId, $fakeFormId, $fakePageId, $fakePayload, [
                'full_name'    => $name,
                'phone_number' => $phone,
                'city'         => $city,
                'product'      => $product,
            ]);
        } else {
            ProcessMetaLeadJob::dispatch($fakeLeadId, $fakeFormId, $fakePageId, $fakePayload);
            $this->info('Job dispatched to queue.');
            $this->line('Run <comment>php artisan queue:work --once</comment> to process it.');
            $this->line('Or use <comment>--sync</comment> flag to bypass queue and Graph API.');
        }

        if (! $wasEnabled) {
            $settings->update(['bot_enabled' => false]);
        }

        return self::SUCCESS;
    }

    private function runSyncSimulation(
        BotSetting $settings,
        string $leadId,
        string $formId,
        string $pageId,
        array $payload,
        array $fields,
    ): void {
        // Skip Graph API — use simulated fields directly
        $this->line('Skipping Graph API — using simulated lead fields directly.');

        // Create contact
        $contact = \App\Models\Contact::updateOrCreate(
            ['phone' => $fields['phone_number']],
            [
                'name'   => $fields['full_name'],
                'source' => 'meta_ads',
                'tags'   => ['meta_lead', 'simulated'],
            ]
        );
        $this->info("Contact: [{$contact->id}] {$contact->name}");

        // Create lead
        $lead = \App\Models\Lead::create([
            'contact_id'       => $contact->id,
            'stage'            => 'new',
            'source'           => 'meta_ads',
            'product_interest' => $fields['product'],
            'notes'            => 'Simulated via meta:simulate-lead command',
        ]);
        $this->info("Lead created: [{$lead->id}]");

        // Generate AI message
        $this->info('Calling AI to generate follow-up message (active provider: ' . $settings->ai_provider . ')...');
        $followUpAi = new LeadFollowUpAiService($settings);
        $message    = $followUpAi->generateFollowUpMessage($fields, $fields['product']);

        if ($message) {
            $this->newLine();
            $this->line('<fg=cyan>--- Generated Follow-up Message ---</>');
            $this->line($message);
            $this->newLine();

            // Store as draft conversation
            $conv = \App\Models\Conversation::create([
                'contact_id' => $contact->id,
                'channel'    => 'whatsapp',
                'direction'  => 'outbound',
                'message'    => $message,
                'is_bot'     => true,
                'sent_at'    => null,
            ]);
            $this->info("Draft conversation saved: [{$conv->id}]");
        } else {
            $this->warn('No message generated (API key for the active provider not configured?)');
        }

        // Log everything
        \App\Models\BotActivityLog::create([
            'event_type'        => 'message_generated',
            'meta_lead_id'      => $leadId,
            'meta_form_id'      => $formId,
            'contact_id'        => $contact->id,
            'lead_id'           => $lead->id,
            'raw_payload'       => $payload,
            'generated_message' => $message,
            'status'            => 'success',
        ]);

        $this->info('Simulation complete. Check Admin > Marketing > Bot Activity for logs.');
    }
}
