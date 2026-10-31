<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\MetaConversionsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMetaLeadEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $leadId,
        public readonly array $customData = []
    ) {}

    public function handle(MetaConversionsService $meta): void
    {
        $lead = Lead::with('contact')->find($this->leadId);

        if (! $lead) {
            return;
        }

        try {
            $meta->trackLead($lead, $this->customData);
        } catch (\Throwable $e) {
            Log::warning('SendMetaLeadEvent job error', [
                'lead_id' => $this->leadId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

