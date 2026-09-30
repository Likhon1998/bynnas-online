<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignVisit;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Last-touch social attribution: which campaign / source brought the visitor that placed an order.
 * Only real events are stored — tracked-link clicks, UTM landings and orders. Bots and link previews are ignored.
 */
class CampaignAttributionService
{
    public const SESSION_KEY = 'bs_attribution';

    public const COOKIE = 'bs_attr';

    public const COOKIE_DAYS = 30;

    /** Repeat clicks from the same session inside this window count once. */
    private const REPEAT_WINDOW_MINUTES = 30;

    private const SOCIAL_REFERRERS = [
        'facebook.com' => 'facebook',
        'fb.com' => 'facebook',
        'fb.me' => 'facebook',
        'instagram.com' => 'instagram',
        'tiktok.com' => 'tiktok',
        'youtube.com' => 'youtube',
        'youtu.be' => 'youtube',
        'whatsapp.com' => 'whatsapp',
        'wa.me' => 'whatsapp',
        'messenger.com' => 'messenger',
        'm.me' => 'messenger',
        't.co' => 'twitter',
        'x.com' => 'twitter',
        'twitter.com' => 'twitter',
        'linkedin.com' => 'linkedin',
        'pinterest.com' => 'pinterest',
        'google.com' => 'google',
    ];

    /** utm_campaign used by the Share buttons on product pages. */
    public const PRODUCT_SHARE = 'product_share';

    public function __construct(private WebsiteService $website) {}

    /**
     * Shares are tracked through a normal campaign so clicks, orders and revenue
     * show up in Campaigns; the per-network split stays in utm_source.
     */
    public function productShareCampaign(int $shopId): Campaign
    {
        return Campaign::firstOrCreate(
            ['shop_id' => $shopId, 'utm_campaign' => self::PRODUCT_SHARE],
            [
                'name' => 'Product shares',
                'source' => 'other',
                'medium' => 'organic_social',
                'landing_type' => 'shop',
                'status' => 'active',
                'notes' => 'Created automatically for the Share buttons on product pages (Facebook, Messenger, WhatsApp, copied link).',
            ]
        );
    }

    public function isBot(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        return $agent === ''
            || (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|facebot|meta-externalagent|embedly|preview|headless|lighthouse|curl|wget|python|httpclient|okhttp|go-http/i', $agent);
    }

    /** Handle a click on a tracking link; returns the URL to send the visitor to. */
    public function trackClick(Campaign $campaign, Request $request): string
    {
        $content = Str::limit(Str::slug((string) $request->query('c', '')), 120, '');
        $landing = $campaign->landingPath();

        if ($campaign->isTracking() && ! $this->isBot($request)) {
            $this->recordVisit($campaign, $request, $content ?: null, $landing);
            $this->remember($request, $this->touchFor($campaign, $content ?: null, $landing, $this->referrerHost($request)));
        }

        $utm = array_filter([
            'utm_source' => $campaign->source,
            'utm_medium' => $campaign->medium,
            'utm_campaign' => $campaign->utm_campaign,
            'utm_content' => $content ?: null,
        ]);

        return url($landing).(str_contains($landing, '?') ? '&' : '?').http_build_query($utm);
    }

    /** Capture UTM parameters or a social referrer when a storefront page is opened. */
    public function captureFromRequest(Request $request): void
    {
        if ($this->isBot($request)) {
            return;
        }

        $landing = Str::limit('/'.ltrim($request->path(), '/'), 500, '');
        $referrerHost = $this->referrerHost($request);
        $utmSource = $this->clean($request->query('utm_source'), 60);
        $utmCampaign = $this->clean($request->query('utm_campaign'), 120);

        if ($utmSource || $utmCampaign) {
            $shopId = $this->website->shopId();
            $campaign = ($shopId && $utmCampaign)
                ? Campaign::where('shop_id', $shopId)->where('utm_campaign', $utmCampaign)->first()
                : null;
            if (! $campaign && $shopId && $utmCampaign === self::PRODUCT_SHARE) {
                $campaign = $this->productShareCampaign($shopId);
            }
            $content = $this->clean($request->query('utm_content'), 120);

            if ($campaign && $campaign->isTracking()) {
                $this->recordVisit($campaign, $request, $content, $landing);
            }

            $this->remember($request, [
                'campaign_id' => $campaign && $campaign->isTracking() ? $campaign->id : null,
                'utm_source' => $utmSource ?: ($campaign?->source),
                'utm_medium' => $this->clean($request->query('utm_medium'), 60) ?: ($campaign?->medium),
                'utm_campaign' => $utmCampaign,
                'utm_content' => $content,
                'utm_term' => $this->clean($request->query('utm_term'), 120),
                'landing_page' => $landing,
                'referrer_host' => $referrerHost,
            ]);

            return;
        }

        if ($referrerHost && $this->current($request) === null && ($source = $this->socialSource($referrerHost))) {
            $this->remember($request, [
                'campaign_id' => null,
                'utm_source' => $source,
                'utm_medium' => 'referral',
                'utm_campaign' => null,
                'utm_content' => null,
                'utm_term' => null,
                'landing_page' => $landing,
                'referrer_host' => $referrerHost,
            ]);
        }
    }

    /** Attribution stored for this visitor (session first, then the 30-day cookie). */
    public function current(Request $request): ?array
    {
        $touch = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;
        if (! is_array($touch)) {
            $decoded = json_decode((string) $request->cookie(self::COOKIE), true);
            $touch = is_array($decoded) ? $decoded : null;
        }

        return $touch;
    }

    /** Columns to save on a new online order. */
    public function orderAttributes(Request $request, int $shopId): array
    {
        $touch = $this->current($request);
        if (! $touch) {
            return [];
        }

        $campaignId = isset($touch['campaign_id'])
            ? Campaign::where('shop_id', $shopId)->whereKey((int) $touch['campaign_id'])->value('id')
            : null;

        return [
            'campaign_id' => $campaignId,
            'utm_source' => $this->clean($touch['utm_source'] ?? null, 60),
            'utm_medium' => $this->clean($touch['utm_medium'] ?? null, 60),
            'utm_campaign' => $this->clean($touch['utm_campaign'] ?? null, 120),
            'utm_content' => $this->clean($touch['utm_content'] ?? null, 120),
            'utm_term' => $this->clean($touch['utm_term'] ?? null, 120),
            'landing_page' => $this->clean($touch['landing_page'] ?? null, 500),
            'referrer_host' => $this->clean($touch['referrer_host'] ?? null, 190),
        ];
    }

    /**
     * Real performance numbers per campaign id.
     *
     * @param  Collection<int, int>|array<int, int>  $campaignIds
     * @return array<int, array{clicks: int, visitors: int, orders: int, revenue: float, conversion: ?float}>
     */
    public function stats($campaignIds): array
    {
        $ids = collect($campaignIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $visits = CampaignVisit::query()
            ->whereIn('campaign_id', $ids)
            ->groupBy('campaign_id')
            ->select('campaign_id', DB::raw('COUNT(*) as clicks'), DB::raw('COUNT(DISTINCT visitor_hash) as visitors'))
            ->get()->keyBy('campaign_id');

        $orders = Order::query()
            ->whereIn('campaign_id', $ids)
            ->groupBy('campaign_id')
            ->select(
                'campaign_id',
                DB::raw('COUNT(*) as orders'),
                DB::raw("SUM(CASE WHEN status IN ('".implode("','", Campaign::VOID_ORDER_STATUSES)."') THEN 0 ELSE total_amount END) as revenue"),
                DB::raw("SUM(CASE WHEN status IN ('".implode("','", Campaign::VOID_ORDER_STATUSES)."') THEN 1 ELSE 0 END) as voided")
            )
            ->get()->keyBy('campaign_id');

        $stats = [];
        foreach ($ids as $id) {
            $visitors = (int) ($visits[$id]->visitors ?? 0);
            $orderCount = (int) ($orders[$id]->orders ?? 0);
            $stats[$id] = [
                'clicks' => (int) ($visits[$id]->clicks ?? 0),
                'visitors' => $visitors,
                'orders' => $orderCount,
                'voided' => (int) ($orders[$id]->voided ?? 0),
                'revenue' => round((float) ($orders[$id]->revenue ?? 0), 2),
                'conversion' => $visitors > 0 ? round($orderCount / $visitors * 100, 1) : null,
            ];
        }

        return $stats;
    }

    /**
     * Online orders grouped by where they came from, for a date range.
     *
     * @return Collection<int, object{source: string, medium: string, orders: int, revenue: float}>
     */
    public function sourceBreakdown(int $shopId, \DateTimeInterface $from): Collection
    {
        return Order::query()
            ->where('shop_id', $shopId)
            ->onlineOrders()
            ->where('created_at', '>=', $from)
            ->whereNotIn('status', Campaign::VOID_ORDER_STATUSES)
            ->groupBy('utm_source', 'utm_medium')
            ->select(
                DB::raw("COALESCE(utm_source, '') as source"),
                DB::raw("COALESCE(utm_medium, '') as medium"),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->orderByDesc('orders')
            ->get();
    }

    private function recordVisit(Campaign $campaign, Request $request, ?string $content, string $landing): void
    {
        $key = 'bs_campaign_seen.'.$campaign->id;
        if ($request->hasSession()) {
            $seenAt = (int) $request->session()->get($key, 0);
            if ($seenAt > now()->subMinutes(self::REPEAT_WINDOW_MINUTES)->getTimestamp()) {
                return;
            }
            $request->session()->put($key, now()->getTimestamp());
        }

        CampaignVisit::create([
            'campaign_id' => $campaign->id,
            'visitor_hash' => hash('sha256', $request->ip().'|'.$request->userAgent().'|'.config('app.key')),
            'utm_content' => $content,
            'landing_path' => Str::limit($landing, 500, ''),
            'referrer_host' => $this->referrerHost($request),
        ]);
    }

    private function touchFor(Campaign $campaign, ?string $content, string $landing, ?string $referrerHost): array
    {
        return [
            'campaign_id' => $campaign->id,
            'utm_source' => $campaign->source,
            'utm_medium' => $campaign->medium,
            'utm_campaign' => $campaign->utm_campaign,
            'utm_content' => $content,
            'utm_term' => null,
            'landing_page' => Str::limit($landing, 500, ''),
            'referrer_host' => $referrerHost,
        ];
    }

    private function remember(Request $request, array $touch): void
    {
        $touch['at'] = now()->toIso8601String();
        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $touch);
        }
        Cookie::queue(self::COOKIE, json_encode($touch), self::COOKIE_DAYS * 24 * 60);
    }

    private function referrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);
        if (! $host) {
            return null;
        }
        $host = Str::lower(preg_replace('/^www\./i', '', $host) ?? $host);

        return $host === Str::lower((string) $request->getHost()) ? null : Str::limit($host, 190, '');
    }

    private function socialSource(string $host): ?string
    {
        foreach (self::SOCIAL_REFERRERS as $domain => $source) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return $source;
            }
        }

        return null;
    }

    private function clean($value, int $max): ?string
    {
        $value = trim(strip_tags((string) $value));

        return $value === '' ? null : Str::limit($value, $max, '');
    }
}
