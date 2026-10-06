<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class OrderTrackingService
{
    public function parseQrValue(string $value): array
    {
        $value = trim($value);

        if ($value === '' || mb_strlen($value) > 2048) {
            throw ValidationException::withMessages(['tracking_qr' => 'The QR code does not contain a usable tracking value.']);
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $url = $this->validateUrl($value);
            $parts = parse_url($url);
            $query = [];
            parse_str($parts['query'] ?? '', $query);
            $lookup = array_change_key_case($query, CASE_LOWER);

            foreach (['tracking_id', 'trackingid', 'tracking_no', 'trackingno', 'awb', 'waybill', 'consignment', 'cn', 'track'] as $key) {
                $candidate = $lookup[$key] ?? null;
                if (is_string($candidate) && $this->isValidNumber($candidate)) {
                    return ['tracking_number' => $candidate, 'tracking_url' => $url];
                }
            }

            $lastSegment = rawurldecode(basename(rtrim($parts['path'] ?? '', '/')));
            if ($this->isValidNumber($lastSegment) && preg_match('/\d/', $lastSegment)) {
                return ['tracking_number' => $lastSegment, 'tracking_url' => $url];
            }

            return ['tracking_number' => null, 'tracking_url' => $url];
        }

        if (preg_match('/^(?:AWB|TRACKING(?:\s*(?:ID|NO|NUMBER))?|CONSIGNMENT(?:\s*(?:ID|NO|NUMBER))?)\s*[:#-]?\s*(.+)$/i', $value, $matches)) {
            $value = trim($matches[1]);
        }

        if (! $this->isValidNumber($value)) {
            throw ValidationException::withMessages(['tracking_qr' => 'Scan a QR code containing a tracking ID or an HTTPS courier tracking link.']);
        }

        return ['tracking_number' => $value, 'tracking_url' => null];
    }

    public function completeUrl(string $url, string $trackingNumber): string
    {
        if (! $this->isValidNumber($trackingNumber)) {
            throw ValidationException::withMessages(['tracking_number' => 'Enter a valid courier tracking ID.']);
        }

        $url = trim(str_replace('{tracking_id}', rawurlencode($trackingNumber), $url));
        $url = $this->validateUrl($url);

        if (! str_contains(rawurldecode($url), $trackingNumber)) {
            throw ValidationException::withMessages([
                'tracking_url' => 'The courier link must contain the tracking ID. You can use {tracking_id} in the URL.',
            ]);
        }

        return $url;
    }

    public function validateUrl(string $url): string
    {
        $parts = parse_url($url);
        $host = is_array($parts) ? ($parts['host'] ?? '') : '';

        if (! filter_var($url, FILTER_VALIDATE_URL)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || $host === ''
            || isset($parts['user'])
            || isset($parts['pass'])
            || strcasecmp($host, 'localhost') === 0
            || str_ends_with(strtolower($host), '.local')
            || (filter_var($host, FILTER_VALIDATE_IP) && ! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))) {
            throw ValidationException::withMessages(['tracking_url' => 'Enter a public HTTPS courier tracking link.']);
        }

        return $url;
    }

    private function isValidNumber(string $value): bool
    {
        return (bool) preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{3,79}\z/D', $value);
    }
}
