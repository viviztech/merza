<?php

namespace App\Jobs;

use App\Models\CampaignContact;
use App\Services\CampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCampaignStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public int $campaignContactId) {}

    public function handle(CampaignService $service): void
    {
        $campaignContact = CampaignContact::find($this->campaignContactId);

        if (!$campaignContact || $campaignContact->status !== 'pending') {
            return;
        }

        $campaign = $campaignContact->campaign;

        if (!$campaign || in_array($campaign->status, ['cancelled', 'paused'])) {
            return;
        }

        $service->sendStep($campaignContact);
    }
}
