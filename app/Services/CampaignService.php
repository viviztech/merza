<?php

namespace App\Services;

use App\Models\BotSetting;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use App\Models\Conversation;

class CampaignService
{
    /**
     * Enroll all contacts matching campaign filters and return enrolled count.
     */
    public function enrollContacts(Campaign $campaign): int
    {
        $query = Contact::query()->where('is_blocked', false);

        if (!empty($campaign->filter_tags)) {
            foreach ($campaign->filter_tags as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        if ($campaign->filter_source) {
            $query->where('source', $campaign->filter_source);
        }

        if ($campaign->filter_city) {
            $query->where('city', 'like', "%{$campaign->filter_city}%");
        }

        if (!empty($campaign->filter_lead_stage)) {
            $query->whereHas('leads', fn ($q) => $q->whereIn('stage', $campaign->filter_lead_stage));
        }

        $enrolled = 0;
        $firstStepDelay = 0;

        if ($campaign->type === 'drip') {
            $firstStep = $campaign->steps()->first();
            $firstStepDelay = $firstStep?->delay_days ?? 0;
        }

        $query->get()->each(function (Contact $contact) use ($campaign, $firstStepDelay, &$enrolled) {
            // Skip contacts already enrolled
            if ($campaign->contacts()->where('contact_id', $contact->id)->exists()) {
                return;
            }

            $nextSendAt = match ($campaign->type) {
                'broadcast'  => now(),
                'drip'       => now()->addDays($firstStepDelay),
                'follow_up'  => now(),
            };

            CampaignContact::create([
                'campaign_id'  => $campaign->id,
                'contact_id'   => $contact->id,
                'status'       => 'pending',
                'current_step' => 0,
                'next_send_at' => $nextSendAt,
            ]);

            $enrolled++;
        });

        $campaign->update([
            'total_contacts' => $campaign->contacts()->count(),
            'status'         => 'active',
            'started_at'     => $campaign->started_at ?? now(),
        ]);

        return $enrolled;
    }

    /**
     * Send the current step message to a campaign contact via WhatsApp.
     * Returns true on success, false on failure.
     */
    public function sendStep(CampaignContact $campaignContact): bool
    {
        $campaign = $campaignContact->campaign;
        $contact  = $campaignContact->contact;

        if (!$contact->phone) {
            $campaignContact->update(['status' => 'failed']);
            $campaign->increment('failed_count');
            return false;
        }

        // Resolve the message for this step
        if ($campaign->type === 'drip') {
            $stepNumber = $campaignContact->current_step + 1;
            $step = $campaign->steps()->where('step_number', $stepNumber)->first();

            if (!$step) {
                // All drip steps completed — mark done
                $campaignContact->update(['status' => 'sent', 'next_send_at' => null]);
                $this->checkCampaignCompletion($campaign);
                return true;
            }

            $rawMessage = $step->message;
        } else {
            $rawMessage = $campaign->message ?? '';
        }

        $message = $this->replacePlaceholders($rawMessage, $contact);

        // Send via WhatsApp
        $settings = BotSetting::current();

        if (!$settings->whatsapp_phone_number_id || !$settings->whatsapp_access_token) {
            $campaignContact->update(['status' => 'failed']);
            $campaign->increment('failed_count');
            return false;
        }

        $waService   = new WhatsAppService($settings);
        $waMessageId = $waService->sendTextMessage($contact->phone, $message);

        if (!$waMessageId) {
            $campaignContact->update(['status' => 'failed']);
            $campaign->increment('failed_count');
            return false;
        }

        // Store outbound conversation record
        $conversation = Conversation::create([
            'contact_id'    => $contact->id,
            'channel'       => 'whatsapp',
            'direction'     => 'outbound',
            'message'       => $message,
            'is_bot'        => true,
            'sent_at'       => now(),
            'status'        => 'sent',
            'wa_message_id' => $waMessageId,
        ]);

        $contact->update(['last_contacted_at' => now()]);
        $campaign->increment('sent_count');

        // Advance drip or mark complete
        if ($campaign->type === 'drip') {
            $nextStepNumber = $campaignContact->current_step + 2;
            $nextStep       = $campaign->steps()->where('step_number', $nextStepNumber)->first();

            $campaignContact->update([
                'current_step'    => $campaignContact->current_step + 1,
                'status'          => $nextStep ? 'pending' : 'sent',
                'last_sent_at'    => now(),
                'next_send_at'    => $nextStep ? now()->addDays($nextStep->delay_days) : null,
                'conversation_id' => $conversation->id,
            ]);

            if (!$nextStep) {
                $this->checkCampaignCompletion($campaign);
            }
        } else {
            $campaignContact->update([
                'status'          => 'sent',
                'last_sent_at'    => now(),
                'next_send_at'    => null,
                'conversation_id' => $conversation->id,
            ]);
            $this->checkCampaignCompletion($campaign);
        }

        return true;
    }

    /**
     * Mark campaign completed when no pending contacts remain.
     */
    public function checkCampaignCompletion(Campaign $campaign): void
    {
        $pending = $campaign->contacts()->where('status', 'pending')->count();

        if ($pending === 0 && $campaign->status === 'active') {
            $campaign->update(['status' => 'completed', 'completed_at' => now()]);
        }
    }

    private function replacePlaceholders(string $message, Contact $contact): string
    {
        $productInterest = $contact->active_lead?->product_interest ?? 'our fresh fruits';

        return str_replace(
            ['{{customer_name}}', '{{city}}', '{{phone}}', '{{name}}', '{{product_interest}}'],
            [$contact->name, $contact->city ?? '', $contact->phone, $contact->name, $productInterest],
            $message
        );
    }
}
