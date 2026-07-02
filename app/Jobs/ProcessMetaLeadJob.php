<?php

namespace App\Jobs;

use App\Models\BotActivityLog;
use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Lead;
use App\Services\ClaudeAiService;
use App\Services\MetaLeadsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMetaLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly string $metaLeadId,
        public readonly string $metaFormId,
        public readonly string $metaPageId,
        public readonly array  $rawPayload = [],
    ) {}

    public function handle(): void
    {
        $settings = BotSetting::current();
        $metaService   = new MetaLeadsService($settings);
        $claudeService = new ClaudeAiService($settings);

        // Log webhook received
        $log = BotActivityLog::create([
            'event_type'   => 'webhook_received',
            'meta_lead_id' => $this->metaLeadId,
            'meta_form_id' => $this->metaFormId,
            'meta_page_id' => $this->metaPageId,
            'raw_payload'  => $this->rawPayload,
            'status'       => 'success',
        ]);

        try {
            // 1. Fetch lead details from Graph API
            $leadData = $metaService->fetchLead($this->metaLeadId);

            if (! $leadData) {
                $this->logError($log, 'Failed to fetch lead from Graph API');
                return;
            }

            BotActivityLog::create([
                'event_type'   => 'lead_fetched',
                'meta_lead_id' => $this->metaLeadId,
                'meta_form_id' => $this->metaFormId,
                'raw_payload'  => $leadData,
                'status'       => 'success',
            ]);

            $fields = $metaService->parseFields($leadData['field_data'] ?? []);

            // 2. Create or update Contact
            $contact = null;
            if ($settings->auto_create_contact) {
                $contact = $this->upsertContact($fields, $log);
            }

            // 3. Create Lead
            $lead = null;
            if ($settings->auto_create_lead && $contact) {
                $lead = $this->createLead($contact, $fields, $log);
            }

            // 4. Generate AI follow-up message
            $message = $claudeService->generateFollowUpMessage($fields, $fields['product'] ?? null);

            if ($message) {
                BotActivityLog::create([
                    'event_type'        => 'message_generated',
                    'meta_lead_id'      => $this->metaLeadId,
                    'contact_id'        => $contact?->id,
                    'lead_id'           => $lead?->id,
                    'generated_message' => $message,
                    'status'            => 'success',
                ]);

                // 5. Store as draft Conversation
                if ($contact) {
                    $conversation = Conversation::create([
                        'contact_id' => $contact->id,
                        'channel'    => 'whatsapp',
                        'direction'  => 'outbound',
                        'message'    => $message,
                        'is_bot'     => true,
                        'sent_at'    => null,
                    ]);

                    BotActivityLog::create([
                        'event_type'      => 'conversation_created',
                        'meta_lead_id'    => $this->metaLeadId,
                        'contact_id'      => $contact->id,
                        'lead_id'         => $lead?->id,
                        'conversation_id' => $conversation->id,
                        'status'          => 'success',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('ProcessMetaLeadJob failed', [
                'lead_id' => $this->metaLeadId,
                'error'   => $e->getMessage(),
            ]);

            $this->logError($log, $e->getMessage());
            throw $e;
        }
    }

    private function upsertContact(array $fields, BotActivityLog $parentLog): Contact
    {
        $phone = preg_replace('/[^0-9+]/', '', $fields['phone_number'] ?? $fields['phone'] ?? '');
        $name  = $fields['full_name'] ?? $fields['name'] ?? 'Unknown';

        $existing = $phone ? Contact::where('phone', $phone)->first() : null;

        if ($existing) {
            BotActivityLog::create([
                'event_type'   => 'contact_updated',
                'meta_lead_id' => $parentLog->meta_lead_id,
                'contact_id'   => $existing->id,
                'status'       => 'success',
            ]);
            return $existing;
        }

        $contact = Contact::create([
            'name'        => $name,
            'phone'       => $phone ?: 'unknown_' . uniqid(),
            'email'       => $fields['email'] ?? null,
            'source'      => 'meta_ads',
            'tags'        => ['meta_lead'],
            'is_customer' => false,
        ]);

        BotActivityLog::create([
            'event_type'   => 'contact_created',
            'meta_lead_id' => $parentLog->meta_lead_id,
            'contact_id'   => $contact->id,
            'status'       => 'success',
        ]);

        return $contact;
    }

    private function createLead(Contact $contact, array $fields, BotActivityLog $parentLog): Lead
    {
        $lead = Lead::create([
            'contact_id'       => $contact->id,
            'stage'            => 'new',
            'source'           => 'meta_ads',
            'product_interest' => $fields['product'] ?? null,
            'notes'            => 'Auto-created from Meta Lead Ad (form: ' . $parentLog->meta_form_id . ')',
        ]);

        BotActivityLog::create([
            'event_type'   => 'lead_created',
            'meta_lead_id' => $parentLog->meta_lead_id,
            'contact_id'   => $contact->id,
            'lead_id'      => $lead->id,
            'status'       => 'success',
        ]);

        return $lead;
    }

    private function logError(BotActivityLog $log, string $message): void
    {
        $log->update(['status' => 'failed', 'error_message' => $message]);
    }
}
