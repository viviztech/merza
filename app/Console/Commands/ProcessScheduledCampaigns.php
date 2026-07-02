<?php

namespace App\Console\Commands;

use App\Jobs\DispatchCampaignJob;
use App\Jobs\ProcessCampaignStepJob;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use Illuminate\Console\Command;

class ProcessScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:process {--dry-run : Show what would be dispatched without actually dispatching}';
    protected $description = 'Process scheduled broadcasts, active drip steps, and follow-up triggers';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // 1. Activate broadcast campaigns scheduled for now or in the past
        $broadcasts = Campaign::where('type', 'broadcast')
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($broadcasts as $campaign) {
            $this->info("[Broadcast] Activating: {$campaign->name}");
            if (!$dryRun) {
                DispatchCampaignJob::dispatch($campaign->id);
            }
        }

        // 2. Active drip campaigns — dispatch pending contacts whose next_send_at is due
        $dripDue = CampaignContact::whereHas(
            'campaign',
            fn ($q) => $q->where('type', 'drip')->where('status', 'active')
        )
            ->where('status', 'pending')
            ->where('next_send_at', '<=', now())
            ->with('campaign', 'contact')
            ->get();

        foreach ($dripDue as $cc) {
            $this->info("[Drip] Step {$cc->current_step} for contact #{$cc->contact_id} in '{$cc->campaign->name}'");
            if (!$dryRun) {
                ProcessCampaignStepJob::dispatch($cc->id);
            }
        }

        // 3. Follow-up campaigns — enroll contacts who haven't been contacted in X days
        $followUps = Campaign::where('type', 'follow_up')
            ->where('status', 'active')
            ->get();

        foreach ($followUps as $campaign) {
            $days   = $campaign->follow_up_after_days ?? 3;
            $cutoff = now()->subDays($days);

            $query = Contact::query()
                ->where('is_blocked', false)
                ->where(
                    fn ($q) => $q->whereNull('last_contacted_at')
                        ->orWhere('last_contacted_at', '<=', $cutoff)
                )
                ->whereDoesntHave(
                    'campaignContacts',
                    fn ($q) => $q->where('campaign_id', $campaign->id)
                );

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

            $contacts = $query->get();
            $this->info("[Follow-up] '{$campaign->name}': {$contacts->count()} contacts eligible");

            if (!$dryRun) {
                foreach ($contacts as $contact) {
                    $cc = CampaignContact::create([
                        'campaign_id'  => $campaign->id,
                        'contact_id'   => $contact->id,
                        'status'       => 'pending',
                        'current_step' => 0,
                        'next_send_at' => now(),
                    ]);
                    $campaign->increment('total_contacts');
                    ProcessCampaignStepJob::dispatch($cc->id);
                }
            }
        }

        if ($dryRun) {
            $this->warn('Dry run — no jobs dispatched.');
        } else {
            $this->info('Done.');
        }

        return self::SUCCESS;
    }
}
