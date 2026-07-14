<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class UpiQrService
{
    /**
     * Build a UPI deep-link payment URI.
     */
    public function buildUpiUri(string $upiId, string $payeeName, float $amount, string $note): string
    {
        $params = [
            'pa' => $upiId,
            'pn' => $payeeName,
            'am' => number_format($amount, 2, '.', ''),
            'tn' => $note,
            'cu' => 'INR',
        ];

        $query = collect($params)
            ->map(fn ($value, $key) => $key . '=' . rawurlencode((string) $value))
            ->implode('&');

        return 'upi://pay?' . $query;
    }

    /**
     * Render a UPI URI to a PNG QR code image and return the raw binary content.
     */
    public function generatePng(string $uri): string
    {
        $qrCode = new QrCode(data: $uri, size: 400, margin: 10);
        $writer = new PngWriter();

        return $writer->write($qrCode)->getString();
    }
}
