<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Campaign extends Model
{
    public const SOURCES = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'whatsapp' => 'WhatsApp',
        'messenger' => 'Messenger',
        'google' => 'Google',
        'influencer' => 'Influencer',
        'sms' => 'SMS',
        'email' => 'Email',
        'other' => 'Other',
    ];

    public const MEDIUMS = [
        'paid_social' => 'Paid ad',
        'organic_social' => 'Organic post',
        'story' => 'Story / Reel',
        'live' => 'Live video',
        'message' => 'Direct message',
        'influencer' => 'Influencer post',
        'group' => 'Group / Community',
        'bio_link' => 'Bio link',
        'other' => 'Other',
    ];

    public const LANDING_TYPES = [
        'landing' => 'Campaign landing page',
        'home' => 'Home page',
        'shop' => 'Shop (all products)',
        'category' => 'Category',
        'product' => 'Product',
        'url' => 'Custom page on this site',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'paused' => 'Paused',
        'ended' => 'Ended',
    ];

    /** Orders in these statuses do not count as revenue. */
    public const VOID_ORDER_STATUSES = ['cancelled', 'returned', 'refunded'];

    protected $fillable = [
        'shop_id', 'name', 'code', 'source', 'medium', 'utm_campaign', 'landing_type',
        'product_id', 'category_id', 'landing_url', 'status', 'starts_on', 'ends_on',
        'spend', 'notes', 'created_by',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'spend' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign) {
            $campaign->code = $campaign->code ?: static::newCode();
        });
    }

    public static function newCode(): string
    {
        do {
            $code = Str::lower(Str::random(7));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function visits()
    {
        return $this->hasMany(CampaignVisit::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function landingPages()
    {
        return $this->hasMany(LandingPage::class);
    }

    public function publishedLandingPage(): ?LandingPage
    {
        return $this->landingPages()->where('status', 'published')->latest('id')->first();
    }

    /** Clicks are only recorded while the campaign is active and inside its date window. */
    public function isTracking(): bool
    {
        $today = now()->startOfDay();

        return $this->status === 'active'
            && (! $this->starts_on || $this->starts_on->lte($today))
            && (! $this->ends_on || $this->ends_on->gte($today));
    }

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->source] ?? Str::headline($this->source);
    }

    public function mediumLabel(): string
    {
        return self::MEDIUMS[$this->medium] ?? Str::headline($this->medium);
    }

    /** Short link to share on social media. */
    public function trackingUrl(?string $content = null): string
    {
        $url = route('campaign.go', $this->code);

        return filled($content) ? $url.'?c='.rawurlencode(Str::slug($content)) : $url;
    }

    /** Site-relative path the tracking link sends visitors to. */
    public function landingPath(): string
    {
        return match ($this->landing_type) {
            'landing' => ($page = $this->publishedLandingPage()) ? route('website.landing', $page->slug, false) : '/shop',
            'shop' => '/shop',
            'category' => $this->category ? route('website.category', $this->category->slug ?: $this->category->id, false) : '/shop',
            'product' => $this->product ? route('website.product', $this->product, false) : '/shop',
            'url' => self::normalizePath($this->landing_url),
            default => '/',
        };
    }

    public function landingLabel(): string
    {
        return match ($this->landing_type) {
            'landing' => 'Landing page: '.($this->publishedLandingPage()?->title ?? 'none published yet'),
            'category' => 'Category: '.($this->category->name ?? 'removed'),
            'product' => 'Product: '.($this->product?->storefrontDisplayName() ?? 'removed'),
            'url' => 'Page: '.$this->landingPath(),
            default => self::LANDING_TYPES[$this->landing_type] ?? 'Home page',
        };
    }

    /** Only same-site paths are allowed so tracking links cannot become open redirects. */
    public static function normalizePath(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '/';
        }

        $parts = parse_url($value);
        if ($parts === false) {
            return '/';
        }
        if (isset($parts['host'])) {
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if (! $appHost || strcasecmp($parts['host'], $appHost) !== 0) {
                return '/';
            }
        }

        $path = '/'.ltrim($parts['path'] ?? '', '/');
        if (str_starts_with($path, '//')) {
            return '/';
        }

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }
}
