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

        // Dispatch individual step jobs for all pending contacts due now
        CampaignContact::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->where('next_send_at', '<=', now())
            ->get()
            ->each(fn ($cc) => ProcessCampaignStepJob::dispatch($cc->id));
    }
}
