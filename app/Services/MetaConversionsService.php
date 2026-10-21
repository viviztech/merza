<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sends validated, consent-gated website events to Meta's Conversions API.
 *
 * Browser events use the same event ID that this service sends, allowing Meta
 * to deduplicate a browser/server pair. This class deliberately receives the
 * CAPI token only from config/services.php; it never returns it to a caller.
 */
class MetaConversionsService
{
    private const SUPPORTED_EVENTS = [
        'ViewContent',
        'AddToCart',
        'InitiateCheckout',
        'Lead',
        'CompleteRegistration',
        'Purchase',
    ];

    public function isConfigured(): bool
    {
        return (bool) config('services.meta.tracking_enabled')
            && (bool) config('services.meta.conversions_api.enabled')
            && ctype_digit((string) config('services.meta.dataset_id'))
            && filled(config('services.meta.conversions_api.access_token'));
    }

    public function consentGranted(?bool $recordedConsent = null): bool
    {
        if (! config('services.meta.require_consent')) {
            return true;
        }

        if ($recordedConsent !== null) {
            return $recordedConsent;
        }

        return request()->cookie('merza_meta_consent') === 'granted';
    }

    public static function eventId(string $eventName): string
    {
        return 'merza-'.Str::kebab($eventName).'-'.Str::uuid();
    }

    public static function purchaseEventId(Order $order): string
    {
        return 'merza-purchase-'.$order->order_number;
    }

    /**
     * Track an event caused by the current storefront request. Personal data
     * is normalised and SHA-256 hashed before it leaves the application.
     */
    public function trackFromRequest(
        string $eventName,
        array $customerData = [],
        array $customData = [],
        ?string $eventId = null,
        ?string $eventSourceUrl = null,
    ): bool {
        if (! $this->consentGranted()) {
            return false;
        }

        return $this->track(
            eventName: $eventName,
            userData: $this->userDataFromRequest($customerData),
            customData: $customData,
            eventId: $eventId ?? self::eventId($eventName),
            eventSourceUrl: $eventSourceUrl,
        );
    }

    /**
     * Purchase events can run later in a payment webhook job, so consent and
     * matching context are captured on the order at checkout.
     */
    public function trackPurchase(Order $order): bool
    {
        if ($order->payment_status !== 'paid' || ! $this->consentGranted($order->meta_tracking_consent)) {
            return false;
        }

        $order->loadMissing('items');
        $context = is_array($order->meta_event_context) ? $order->meta_event_context : [];

        return $this->track(
            eventName: 'Purchase',
            userData: $this->normaliseUserData([
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
                'city' => $order->city,
                'state' => $order->state,
                'country' => 'in',
                'external_id' => $order->order_number,
                'fbp' => $context['fbp'] ?? null,
                'fbc' => $context['fbc'] ?? null,
                'client_ip_address' => $context['client_ip_address'] ?? null,
                'client_user_agent' => $context['client_user_agent'] ?? null,
            ]),
            customData: $this->purchaseCustomData($order),
            eventId: self::purchaseEventId($order),
            eventSourceUrl: rtrim((string) config('app.url'), '/').'/checkout/confirmation',
            eventTime: $order->updated_at?->timestamp,
        );
    }

    /**
     * The non-sensitive fields used by the browser Pixel and CAPI Purchase.
     */
    public function purchaseBrowserEvent(Order $order): array
    {
        return [
            'eventName' => 'Purchase',
            'eventId' => self::purchaseEventId($order),
            'customData' => $this->purchaseCustomData($order),
        ];
    }

    /**
     * Send a prepared event. Failures are isolated from the customer flow and
     * logs intentionally contain only IDs/statuses, never tokens or PII.
     */
    public function track(
        string $eventName,
        array $userData,
        array $customData,
        string $eventId,
        ?string $eventSourceUrl = null,
        ?int $eventTime = null,
    ): bool {
        if (! $this->isConfigured()) {
            return false;
        }

        if (! $this->validEvent($eventName, $userData, $customData, $eventId)) {
            Log::warning('Meta Conversions API event rejected before delivery', [
                'event_name' => $eventName,
                'event_id' => $eventId,
            ]);

            return false;
        }

        $event = [
            'event_name' => $eventName,
            'event_time' => $eventTime ?: now()->timestamp,
            'event_id' => $eventId,
            'action_source' => 'website',
            'event_source_url' => $eventSourceUrl ?: url()->current(),
            'user_data' => $userData,
            'custom_data' => $customData,
        ];

        $payload = ['data' => [$event]];
        if (filled(config('services.meta.conversions_api.test_event_code'))) {
            $payload['test_event_code'] = config('services.meta.conversions_api.test_event_code');
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken((string) config('services.meta.conversions_api.access_token'))
                ->connectTimeout(1)
                ->timeout(3)
                ->post($this->endpoint(), $payload);
        } catch (\Throwable $exception) {
            Log::warning('Meta Conversions API request could not be completed', [
                'event_name' => $eventName,
                'event_id' => $eventId,
                'exception' => $exception::class,
            ]);

            return false;
        }

        if ($response->failed()) {
            Log::warning('Meta Conversions API rejected an event', [
                'event_name' => $eventName,
                'event_id' => $eventId,
                'status' => $response->status(),
                'meta_error_code' => $response->json('error.code'),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Capture just the matching context needed for a later verified Purchase.
     * This data is encrypted on the Order model.
     */
    public function purchaseContextFromRequest(): array
    {
        $request = request();

        return array_filter([
            'fbp' => $this->browserCookie($request, '_fbp'),
            'fbc' => $this->fbc($request),
            'client_ip_address' => $request->ip(),
            'client_user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ], fn ($value) => filled($value));
    }

    private function purchaseCustomData(Order $order): array
    {
        $contents = $order->items->map(fn ($item) => [
            'id' => (string) $item->product_variant_id,
            'quantity' => (int) $item->quantity,
            'item_price' => (float) $item->unit_price,
        ])->values()->all();

        return [
            'currency' => 'INR',
            'value' => (float) $order->total,
            'content_type' => 'product',
            'content_ids' => collect($contents)->pluck('id')->all(),
            'contents' => $contents,
            'num_items' => (int) $order->items->sum('quantity'),
            'order_id' => $order->order_number,
        ];
    }

    private function userDataFromRequest(array $customerData): array
    {
        $request = request();

        return $this->normaliseUserData([
            ...$customerData,
            'fbp' => $customerData['fbp'] ?? $this->browserCookie($request, '_fbp'),
            'fbc' => $customerData['fbc'] ?? $this->fbc($request),
            'client_ip_address' => $customerData['client_ip_address'] ?? $request->ip(),
            'client_user_agent' => $customerData['client_user_agent'] ?? $request->userAgent(),
        ]);
    }

    /** @return array<string, string|array<int, string>> */
    private function normaliseUserData(array $data): array
    {
        $hashed = [];
        $fields = [
            'email' => 'em',
            'phone' => 'ph',
            'first_name' => 'fn',
            'last_name' => 'ln',
            'city' => 'ct',
            'state' => 'st',
            'postcode' => 'zp',
            'country' => 'country',
            'external_id' => 'external_id',
        ];

        foreach ($fields as $field => $metaField) {
            $normalised = $this->normaliseMatchValue($field, $data[$field] ?? null);
            if ($normalised !== null) {
                $hashed[$metaField] = [hash('sha256', $normalised)];
            }
        }

        foreach (['fbp', 'fbc', 'client_ip_address', 'client_user_agent'] as $field) {
            if (filled($data[$field] ?? null)) {
                $hashed[$field] = Str::limit(trim((string) $data[$field]), $field === 'client_user_agent' ? 500 : 200, '');
            }
        }

        return $hashed;
    }

    private function normaliseMatchValue(string $field, mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return null;
        }

        if ($field === 'phone') {
            $value = preg_replace('/\D+/', '', $value) ?? '';
        } elseif (in_array($field, ['first_name', 'last_name', 'city', 'state', 'country'], true)) {
            $value = preg_replace('/[^\pL\pN]+/u', '', $value) ?? '';
        } elseif ($field === 'postcode') {
            $value = preg_replace('/\s+/', '', $value) ?? '';
        }

        return $value === '' ? null : $value;
    }

    private function browserCookie(Request $request, string $name): ?string
    {
        $value = $request->cookie($name);

        return is_string($value) && strlen($value) <= 200 ? $value : null;
    }

    private function fbc(Request $request): ?string
    {
        $fbc = $this->browserCookie($request, '_fbc');
        if ($fbc) {
            return $fbc;
        }

        $fbclid = $request->query('fbclid');

        return is_string($fbclid) && $fbclid !== '' && strlen($fbclid) <= 150
            ? 'fb.1.'.now()->timestamp.'.'.$fbclid
            : null;
    }

    private function validEvent(string $eventName, array $userData, array $customData, string $eventId): bool
    {
        if (! in_array($eventName, self::SUPPORTED_EVENTS, true)
            || ! preg_match('/^[A-Za-z0-9_-]{1,100}$/', $eventId)
            || $userData === []) {
            return false;
        }

        if (isset($customData['value']) && (! is_numeric($customData['value']) || (float) $customData['value'] < 0)) {
            return false;
        }

        return ! isset($customData['currency'])
            || (is_string($customData['currency']) && preg_match('/^[A-Z]{3}$/', $customData['currency']) === 1);
    }

    private function endpoint(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/events',
            trim((string) config('services.meta.conversions_api.graph_api_version'), '/'),
            config('services.meta.dataset_id'),
        );
    }
}
