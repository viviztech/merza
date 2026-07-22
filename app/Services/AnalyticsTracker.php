<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\Log;

/**
 * Server-side funnel event logging (page_view / product_view / add_to_cart /
 * checkout_start / order_placed) — no third-party pixel, no PII beyond the
 * session id. Feeds ConversionFunnelWidget so "where do customers drop off"
 * is answered from real counts instead of guessed.
 */
class AnalyticsTracker
{
    public function track(string $eventType, ?int $productId = null, ?int $orderId = null): void
    {
        try {
            AnalyticsEvent::create([
                'session_id' => session()->getId(),
                'event_type' => $eventType,
                'product_id' => $productId,
                'order_id'   => $orderId,
            ]);
        } catch (\Throwable $e) {
            // Tracking must never break the page it's called from.
            Log::warning('AnalyticsTracker: failed to record event', [
                'event_type' => $eventType,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
