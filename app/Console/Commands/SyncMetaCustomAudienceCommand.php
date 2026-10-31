<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Services\MetaCustomAudienceService;
use Illuminate\Console\Command;

class SyncMetaCustomAudienceCommand extends Command
{
    protected $signature = 'meta:sync-custom-audience 
                            {--audience-id= : Custom Audience ID (defaults to config META_CUSTOM_AUDIENCE_ID)}
                            {--category= : Filter by customer category (e.g. purchased_customer, new_lead)}';

    protected $description = 'Sync CRM customer contacts to Meta Custom Audience for advertising optimization';

    public function handle(MetaCustomAudienceService $service): int
    {
        $this->info('Starting Meta Custom Audience customer-list sync...');

        if (! $service->isConfigured()) {
            $this->error('Meta Custom Audience is not fully configured. Please set META_CONVERSIONS_API_ACCESS_TOKEN and META_CUSTOM_AUDIENCE_ID.');
            return self::FAILURE;
        }

        $audienceId = $this->option('audience-id') ?: config('services.meta.custom_audience_id');

        if (blank($audienceId)) {
            $this->error('Please provide an audience ID via --audience-id or configure META_CUSTOM_AUDIENCE_ID in .env.');
            return self::FAILURE;
        }

        $query = Contact::query()->where('is_blocked', false);

        if ($category = $this->option('category')) {
            $query->where('customer_category', $category);
        }

        $contacts = $query->get();
        $this->info("Found {$contacts->count()} contacts to sync to Audience ID: {$audienceId}.");

        $result = $service->syncCustomerList($contacts, $audienceId);

        if ($result['success']) {
            $this->info("Successfully synced {$result['uploaded_count']} contacts to Meta Custom Audience.");
            return self::SUCCESS;
        }

        $this->error('Failed to sync custom audience: ' . ($result['error'] ?? 'Unknown error'));
        return self::FAILURE;
    }
}

