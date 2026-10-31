<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Services\MetaConversionsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMetaContactEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $contactId,
        public readonly array $customData = []
    ) {}

    public function handle(MetaConversionsService $meta): void
    {
        $contact = Contact::find($this->contactId);

        if (! $contact) {
            return;
        }

        try {
            $meta->trackContact($contact, $this->customData);
        } catch (\Throwable $e) {
            Log::warning('SendMetaContactEvent job error', [
                'contact_id' => $this->contactId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

