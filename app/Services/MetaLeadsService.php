<?php

namespace App\Services;

use App\Models\BotSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaLeadsService
{
    public function __construct(private readonly BotSetting $settings) {}

    /**
     * Verify the Facebook webhook challenge (GET request).
     */
    public function verifyWebhook(array $params): string|false
    {
        if (
            ($params['hub_mode'] ?? '') === 'subscribe' &&
            ($params['hub_verify_token'] ?? '') === $this->settings->meta_verify_token
        ) {
            return $params['hub_challenge'] ?? false;
        }

        return false;
    }

    /**
     * Fetch full lead field data from the Graph API using the lead gen lead ID.
     *
     * @return array{id: string, field_data: array, created_time: string}|null
     */
    public function fetchLead(string $leadId): ?array
    {
        $token = $this->settings->meta_page_access_token;

        if (empty($token)) {
            Log::warning('MetaLeadsService: No page access token configured');
            return null;
        }

        $response = Http::timeout(10)->get("https://graph.facebook.com/v21.0/{$leadId}", [
            'access_token' => $token,
            'fields'       => 'id,created_time,field_data,ad_id,ad_name,form_id,page_id',
        ]);

        if ($response->failed()) {
            Log::error('MetaLeadsService: Graph API error', [
                'lead_id' => $leadId,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
            return null;
        }

        return $response->json();
    }

    /**
     * Parse field_data array into a clean key => value map.
     *
     * Meta returns: [['name' => 'full_name', 'values' => ['Priya Sharma']], ...]
     */
    public function parseFields(array $fieldData): array
    {
        $fields = [];
        foreach ($fieldData as $field) {
            $key = str_replace([' ', '-'], '_', strtolower($field['name'] ?? ''));
            $fields[$key] = $field['values'][0] ?? null;
        }
        return $fields;
    }

    /**
     * Extract the leadgen entry from a webhook payload.
     * Returns array of ['page_id', 'form_id', 'lead_id'] items.
     */
    public function extractLeadEntries(array $payload): array
    {
        $entries = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'leadgen') {
                    continue;
                }

                $value = $change['value'] ?? [];
                $entries[] = [
                    'page_id' => $value['page_id'] ?? $entry['id'] ?? null,
                    'form_id' => $value['form_id'] ?? null,
                    'lead_id' => $value['leadgen_id'] ?? null,
                ];
            }
        }

        return $entries;
    }
}
