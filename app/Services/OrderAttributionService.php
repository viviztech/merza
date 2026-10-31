<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Cookie;

class OrderAttributionService
{
    public const COOKIE_FIRST_TOUCH = 'merza_attr_first';
    public const COOKIE_LAST_TOUCH = 'merza_attr_last';

    /**
     * Parse the given request into a normalized touch attribution array.
     */
    public function parseRequest(Request $request): array
    {
        $utmSource   = $this->cleanString($request->query('utm_source'));
        $utmMedium   = $this->cleanString($request->query('utm_medium'));
        $utmCampaign = $this->cleanString($request->query('utm_campaign'));
        $utmTerm     = $this->cleanString($request->query('utm_term'));
        $utmContent  = $this->cleanString($request->query('utm_content'));

        $gclid   = $this->cleanString($request->query('gclid') ?: ($request->query('gbraid') ?: $request->query('wbraid')));
        $fbclid  = $this->cleanString($request->query('fbclid'));
        $ttclid  = $this->cleanString($request->query('ttclid'));
        $msclkid = $this->cleanString($request->query('msclkid'));

        $clickId = $gclid ?: ($fbclid ?: ($ttclid ?: $msclkid));

        $referrer = $this->cleanString($request->headers->get('referer'));
        $landingUrl = $request->fullUrl();
        $deviceType = $this->detectDevice($request->userAgent());
        $userAgent  = $this->cleanString($request->userAgent());

        $hasCampaign = filled($utmSource) || filled($clickId);
        $referrerHost = $referrer ? strtolower((string) parse_url($referrer, PHP_URL_HOST)) : null;
        $currentHost  = strtolower((string) parse_url($landingUrl, PHP_URL_HOST));

        $isExternalReferrer = $referrerHost && ! $this->isSameHost($referrerHost, $currentHost);

        // 1. Paid Click IDs
        if (filled($gclid)) {
            $source = 'google';
            $medium = $utmMedium ?: 'cpc';
            $sourceType = 'paid';
            $origin = 'Paid: Google';
        } elseif (filled($fbclid)) {
            $source = $utmSource ?: 'facebook';
            $medium = $utmMedium ?: 'cpc';
            $sourceType = 'paid';
            $origin = 'Paid: ' . $this->formatSourceTitle($source);
        } elseif (filled($ttclid)) {
            $source = 'tiktok';
            $medium = $utmMedium ?: 'cpc';
            $sourceType = 'paid';
            $origin = 'Paid: TikTok';
        } elseif (filled($msclkid)) {
            $source = 'bing';
            $medium = $utmMedium ?: 'cpc';
            $sourceType = 'paid';
            $origin = 'Paid: Bing';
        }
        // 2. UTM Parameters
        elseif (filled($utmSource)) {
            $source = $utmSource;
            $medium = $utmMedium ?: 'none';

            if (in_array(strtolower((string) $utmMedium), ['cpc', 'ppc', 'paid', 'paid_social', 'paidsocial', 'ad', 'ads'], true)) {
                $sourceType = 'paid';
                $origin = 'Paid: ' . $this->formatSourceTitle($source);
            } elseif (in_array(strtolower((string) $utmMedium), ['email', 'newsletter'], true)) {
                $sourceType = 'email';
                $origin = 'Email: ' . $this->formatSourceTitle($source);
            } elseif (in_array(strtolower((string) $utmMedium), ['organic'], true)) {
                $sourceType = 'organic';
                $origin = 'Organic: ' . $this->formatSourceTitle($source);
            } elseif ($this->isSocialSource($source)) {
                $sourceType = 'social';
                $origin = 'Source: ' . $this->formatSourceTitle($source);
            } else {
                $sourceType = 'utm';
                $origin = 'Source: ' . $this->formatSourceTitle($source);
            }
        }
        // 3. Referrer Analysis
        elseif ($isExternalReferrer) {
            $searchEngine = $this->detectSearchEngine($referrerHost);
            $socialNetwork = $this->detectSocialNetwork($referrerHost);

            if ($searchEngine) {
                $source = $searchEngine;
                $medium = 'organic';
                $sourceType = 'organic';
                $origin = 'Organic: ' . $this->formatSourceTitle($searchEngine);
            } elseif ($socialNetwork) {
                $source = $socialNetwork;
                $medium = 'social';
                $sourceType = 'social';
                $origin = 'Source: ' . $this->formatSourceTitle($socialNetwork);
            } else {
                $cleanedDomain = preg_replace('/^www\./i', '', $referrerHost);
                $source = $cleanedDomain;
                $medium = 'referral';
                $sourceType = 'referral';
                $origin = 'Referral: ' . $cleanedDomain;
            }
        }
        // 4. Direct traffic
        else {
            $source = 'direct';
            $medium = 'none';
            $sourceType = 'direct';
            $origin = 'Direct';
        }

        return [
            'origin'        => $origin,
            'source_type'   => $sourceType,
            'source'        => $source,
            'medium'        => $medium,
            'campaign'      => $utmCampaign,
            'term'          => $utmTerm,
            'content'       => $utmContent,
            'click_id'      => $clickId,
            'device_type'   => $deviceType,
            'referrer_url'  => $referrer,
            'landing_url'   => $landingUrl,
            'user_agent'    => $userAgent,
            'has_campaign'  => $hasCampaign || $isExternalReferrer,
            'timestamp'     => now()->toIso8601String(),
        ];
    }

    /**
     * Process incoming request and manage first-touch / last-touch in session and cookies.
     *
     * @return array{first_touch: array, last_touch: array, cookies: Cookie[]}
     */
    public function capture(Request $request): array
    {
        $currentTouch = $this->parseRequest($request);

        // Retrieve existing touches from session or cookies
        $firstTouch = session('order_attribution_first') ?: $this->decodeCookie($request->cookie(self::COOKIE_FIRST_TOUCH));
        $lastTouch  = session('order_attribution_last') ?: $this->decodeCookie($request->cookie(self::COOKIE_LAST_TOUCH));

        $cookies = [];

        // 1. First Touch: set once on first visit and never overwritten
        if (! is_array($firstTouch) || empty($firstTouch['origin'])) {
            $firstTouch = $currentTouch;
            session(['order_attribution_first' => $firstTouch]);
            $cookies[] = cookie(
                self::COOKIE_FIRST_TOUCH,
                json_encode($firstTouch),
                60 * 24 * 365, // 1 year
                null,
                null,
                false,
                false // Not HttpOnly so JS can access if needed
            );
        }

        // 2. Last Touch: update when user arrives via campaign / ad click / new external referrer
        $isNewAttribution = ($currentTouch['has_campaign'] ?? false) || empty($lastTouch);

        if ($isNewAttribution || ! is_array($lastTouch)) {
            $lastTouch = $currentTouch;
            session(['order_attribution_last' => $lastTouch]);
            $cookies[] = cookie(
                self::COOKIE_LAST_TOUCH,
                json_encode($lastTouch),
                60 * 24 * 30, // 30 days
                null,
                null,
                false,
                false
            );
        }

        return [
            'first_touch' => $firstTouch,
            'last_touch'  => $lastTouch,
            'cookies'     => $cookies,
        ];
    }

    /**
     * Build the attributes to attach to an Order model during creation.
     */
    public function getOrderAttributionData(?Request $request = null, array $fallback = []): array
    {
        $req = $request ?: request();

        $firstTouch = session('order_attribution_first')
            ?: ($req ? $this->decodeCookie($req->cookie(self::COOKIE_FIRST_TOUCH)) : null);

        $lastTouch = session('order_attribution_last')
            ?: ($req ? $this->decodeCookie($req->cookie(self::COOKIE_LAST_TOUCH)) : null);

        if (! is_array($lastTouch) && $req) {
            $captured = $this->capture($req);
            $firstTouch = $captured['first_touch'];
            $lastTouch  = $captured['last_touch'];
        }

        $activeTouch = is_array($lastTouch) && ! empty($lastTouch['origin']) ? $lastTouch : $firstTouch;

        if (! is_array($activeTouch) || empty($activeTouch['origin'])) {
            $activeTouch = array_merge([
                'origin'        => $fallback['origin'] ?? 'Direct',
                'source_type'   => $fallback['source_type'] ?? 'direct',
                'source'        => $fallback['source'] ?? 'direct',
                'medium'        => $fallback['medium'] ?? 'none',
                'campaign'      => null,
                'term'          => null,
                'content'       => null,
                'click_id'      => null,
                'device_type'   => $this->detectDevice($req?->userAgent()),
                'referrer_url'  => $req?->headers->get('referer'),
                'landing_url'   => $req?->fullUrl(),
                'user_agent'    => $req?->userAgent(),
                'timestamp'     => now()->toIso8601String(),
            ], $fallback);
        }

        if (! is_array($firstTouch)) {
            $firstTouch = $activeTouch;
        }

        return [
            'attribution_origin'       => $activeTouch['origin'] ?? 'Direct',
            'attribution_source_type'  => $activeTouch['source_type'] ?? 'direct',
            'attribution_source'       => $activeTouch['source'] ?? null,
            'attribution_medium'       => $activeTouch['medium'] ?? null,
            'attribution_campaign'     => $activeTouch['campaign'] ?? null,
            'attribution_term'         => $activeTouch['term'] ?? null,
            'attribution_content'      => $activeTouch['content'] ?? null,
            'attribution_click_id'     => $activeTouch['click_id'] ?? null,
            'attribution_device_type'  => $activeTouch['device_type'] ?? 'Desktop',
            'attribution_referrer_url' => $activeTouch['referrer_url'] ?? null,
            'attribution_landing_url'  => $activeTouch['landing_url'] ?? null,
            'attribution_user_agent'   => $activeTouch['user_agent'] ?? null,
            'attribution_data'         => [
                'origin'      => $activeTouch['origin'] ?? 'Direct',
                'device_type' => $activeTouch['device_type'] ?? 'Desktop',
                'user_agent'  => $activeTouch['user_agent'] ?? null,
                'first_touch' => $firstTouch,
                'last_touch'  => $activeTouch,
            ],
        ];
    }

    private function detectDevice(?string $userAgent): string
    {
        if (blank($userAgent)) {
            return 'Desktop';
        }

        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $userAgent)) {
            return 'Tablet';
        }

        if (preg_match('/(mobile|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop)/i', $userAgent)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    private function detectSearchEngine(?string $host): ?string
    {
        if (! $host) {
            return null;
        }

        if (str_contains($host, 'google.')) return 'google';
        if (str_contains($host, 'bing.com')) return 'bing';
        if (str_contains($host, 'yahoo.com')) return 'yahoo';
        if (str_contains($host, 'duckduckgo.com')) return 'duckduckgo';
        if (str_contains($host, 'ecosia.org')) return 'ecosia';
        if (str_contains($host, 'baidu.com')) return 'baidu';
        if (str_contains($host, 'yandex.')) return 'yandex';

        return null;
    }

    private function detectSocialNetwork(?string $host): ?string
    {
        if (! $host) {
            return null;
        }

        if (str_contains($host, 'instagram.com')) return 'instagram';
        if (str_contains($host, 'facebook.com') || str_contains($host, 'fb.me')) return 'facebook';
        if (str_contains($host, 'whatsapp.com') || str_contains($host, 'wa.me')) return 'whatsapp';
        if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) return 'youtube';
        if (str_contains($host, 'twitter.com') || str_contains($host, 'x.com') || str_contains($host, 't.co')) return 'twitter';
        if (str_contains($host, 'linkedin.com') || str_contains($host, 'lnkd.in')) return 'linkedin';
        if (str_contains($host, 'pinterest.com')) return 'pinterest';
        if (str_contains($host, 'reddit.com')) return 'reddit';
        if (str_contains($host, 't.me') || str_contains($host, 'telegram.org')) return 'telegram';

        return null;
    }

    private function isSocialSource(string $source): bool
    {
        return in_array(strtolower($source), [
            'instagram', 'facebook', 'fb', 'meta', 'whatsapp', 'youtube',
            'twitter', 'x', 'linkedin', 'pinterest', 'reddit', 'threads', 'tiktok',
        ], true);
    }

    private function formatSourceTitle(string $source): string
    {
        return match (strtolower($source)) {
            'google' => 'Google',
            'facebook', 'fb' => 'Facebook',
            'instagram', 'ig' => 'Instagram',
            'whatsapp', 'wa' => 'WhatsApp',
            'youtube', 'yt' => 'YouTube',
            'twitter' => 'Twitter',
            'x' => 'X',
            'linkedin' => 'LinkedIn',
            'tiktok' => 'TikTok',
            'bing' => 'Bing',
            'yahoo' => 'Yahoo',
            'duckduckgo' => 'DuckDuckGo',
            default => ucfirst(strtolower($source)),
        };
    }

    private function isSameHost(string $host1, string $host2): bool
    {
        $h1 = preg_replace('/^www\./i', '', $host1);
        $h2 = preg_replace('/^www\./i', '', $host2);

        return $h1 === $h2;
    }

    private function cleanString(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? mb_substr($trimmed, 0, 500) : null;
    }

    private function decodeCookie(?string $value): ?array
    {
        if (blank($value)) {
            return null;
        }

        $data = json_decode($value, true);

        return is_array($data) ? $data : null;
    }
}

