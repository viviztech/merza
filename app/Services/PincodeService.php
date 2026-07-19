<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PincodeService
{
    /**
     * Look up the district and state for an Indian PIN code via the free
     * India Post public API. Returns null if the pincode is invalid/unknown
     * or the lookup fails — callers should fall back to manual entry.
     */
    public function lookup(string $pincode): ?array
    {
        if (! preg_match('/^\d{6}$/', $pincode)) {
            return null;
        }

        try {
            $response = Http::timeout(5)->get("https://api.postalpincode.in/pincode/{$pincode}");
        } catch (\Throwable $e) {
            Log::warning('Pincode lookup failed', ['pincode' => $pincode, 'error' => $e->getMessage()]);
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $result = $response->json()[0] ?? null;

        if (($result['Status'] ?? null) !== 'Success' || empty($result['PostOffice'])) {
            return null;
        }

        $postOffice = $result['PostOffice'][0];

        return [
            'district' => $postOffice['District'] ?? null,
            'state'    => $postOffice['State'] ?? null,
        ];
    }
}
