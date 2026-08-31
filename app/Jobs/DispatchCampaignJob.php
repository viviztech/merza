<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Services\CampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $campaignId) {}

    public function handle(CampaignService $service): void
    {
        $campaign = Campaign::find($this->campaignId);

        if (!$campaign || in_array($campaign->status, ['cancelled', 'paused', 'completed'])) {
            return;
        }

        // Enroll contacts matching filters
        $service->enrollContacts($campaign);

        // Dispatch individual step jobs for all pending contacts due now, staggered
        // so a large audience doesn't fire hundreds of WhatsApp sends in the same
        // instant (protects the business's messaging quality rating).
        CampaignContact::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->where('next_send_at', '<=', now())
            ->get()
            ->values()
            ->each(fn ($cc, $index) => ProcessCampaignStepJob::dispatch($cc->id)
                ->delay(now()->addSeconds($index * CampaignService::SEND_STAGGER_SECONDS)));
    }
}
