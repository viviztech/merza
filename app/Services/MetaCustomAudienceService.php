<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaCustomAudienceService
{
    public function isConfigured(): bool
    {
        return filled(config('services.meta.conversions_api.access_token'))
            && (filled(config('services.meta.custom_audience_id')) || filled(config('services.meta.ad_account_id')));
    }

    /**
     * Create a new Custom Audience on Meta Ad Account.
     */
    public function createAudience(string $name, string $description = 'Merza Customer Audience'): ?string
    {
        $adAccountId = config('services.meta.ad_account_id');
        if (blank($adAccountId)) {
            Log::warning('Meta Custom Audience creation failed: META_AD_ACCOUNT_ID is not configured');
            return null;
        }

        // Clean account ID (strip act_ prefix if present, then re-add per Meta standard)
        $cleanId = preg_replace('/^act_/', '', trim((string) $adAccountId));
        $endpoint = sprintf(
            'https://graph.facebook.com/%s/act_%s/customaudiences',
            trim((string) config('services.meta.conversions_api.graph_api_version', 'v26.0'), '/'),
            $cleanId
        );

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken((string) config('services.meta.conversions_api.access_token'))
                ->timeout(10)
                ->post($endpoint, [
                    'name' => $name,
                    'subtype' => 'CUSTOM',
                    'description' => $description,
                    'customer_file_source' => 'USER_PROVIDED_ONLY',
                ]);

            if ($response->successful() && $response->json('id')) {
                return (string) $response->json('id');
            }

            Log::warning('Meta Custom Audience creation rejected', [
                'status' => $response->status(),
                'error' => $response->json('error.message'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Meta Custom Audience API connection exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Sync a list of contacts to a Meta Custom Audience.
     * Normalises and SHA-256 hashes each supported customer attribute.
     *
     * @param iterable<Contact>|Collection<int, Contact> $contacts
     * @return array{success: bool, uploaded_count: int, error?: string}
     */
    public function syncCustomerList(iterable $contacts, ?string $audienceId = null): array
    {
        $targetAudienceId = $audienceId ?: config('services.meta.custom_audience_id');

        if (blank($targetAudienceId)) {
            return [
                'success' => false,
                'uploaded_count' => 0,
                'error' => 'No Custom Audience ID configured.',
            ];
        }

        $schema = ['EMAIL', 'PHONE', 'FN', 'CT', 'ST', 'COUNTRY'];
        $dataRows = [];

        foreach ($contacts as $contact) {
            $email = $this->hashField('email', $contact->email);
            $phone = $this->hashField('phone', $contact->phone);

            // Must have at least email or phone for Meta audience matching
            if (! $email && ! $phone) {
                continue;
            }

            $firstName = $this->hashField('first_name', $contact->name);
            $city = $this->hashField('city', $contact->city);
            $state = $this->hashField('state', $contact->state);
            $country = hash('sha256', 'in');

            $dataRows[] = [
                $email ?: '',
                $phone ?: '',
                $firstName ?: '',
                $city ?: '',
                $state ?: '',
                $country,
            ];
        }

        if (empty($dataRows)) {
            return [
                'success' => true,
                'uploaded_count' => 0,
            ];
        }

        $endpoint = sprintf(
            'https://graph.facebook.com/%s/%s/users',
            trim((string) config('services.meta.conversions_api.graph_api_version', 'v26.0'), '/'),
            $targetAudienceId
        );

        $totalUploaded = 0;
        // Chunk rows into batches of 2,000 (Meta maximum per request is 10,000)
        $chunks = array_chunk($dataRows, 2000);

        foreach ($chunks as $chunk) {
            try {
                $response = Http::acceptJson()
                    ->asJson()
                    ->withToken((string) config('services.meta.conversions_api.access_token'))
                    ->timeout(15)
                    ->post($endpoint, [
                        'payload' => [
                            'schema' => $schema,
                            'data' => $chunk,
                        ],
                    ]);

                if ($response->successful()) {
                    $totalUploaded += count($chunk);
                } else {
                    Log::warning('Meta Custom Audience user sync batch rejected', [
                        'audience_id' => $targetAudienceId,
                        'status' => $response->status(),
                        'error' => $response->json('error.message'),
                    ]);

                    return [
                        'success' => false,
                        'uploaded_count' => $totalUploaded,
                        'error' => $response->json('error.message') ?? 'API request rejected',
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Meta Custom Audience user sync exception', [
                    'audience_id' => $targetAudienceId,
                    'message' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'uploaded_count' => $totalUploaded,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'uploaded_count' => $totalUploaded,
        ];
    }

    private function hashField(string $field, ?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $clean = strtolower(trim($value));

        if ($field === 'phone') {
            $clean = preg_replace('/\D+/', '', $clean) ?? '';
            // If Indian 10 digits without country code, prepend 91
            if (strlen($clean) === 10) {
                $clean = '91'.$clean;
            }
        } elseif ($field === 'first_name' || $field === 'city' || $field === 'state') {
            $clean = preg_replace('/[^\pL\pN]+/u', '', $clean) ?? '';
        }

        return $clean !== '' ? hash('sha256', $clean) : null;
    }
}

