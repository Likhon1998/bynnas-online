<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LandingPage extends Model
{
    public const STATUSES = [
        'draft' => 'Draft',
        'published' => 'Published',
    ];

    protected $fillable = [
        'shop_id', 'campaign_id', 'title', 'slug', 'status', 'headline', 'subheadline', 'hero_image',
        'offer_badge', 'offer_text', 'countdown_ends_at', 'product_ids', 'benefits', 'faqs',
        'show_reviews', 'cta_text', 'accent_color', 'seo_title', 'seo_description', 'views', 'created_by',
    ];

    protected $casts = [
        'countdown_ends_at' => 'datetime',
        'product_ids' => 'array',
        'benefits' => 'array',
        'faqs' => 'array',
        'show_reviews' => 'boolean',
        'views' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (LandingPage $page) {
            $page->slug = static::uniqueSlug($page->slug ?: $page->title, $page->id);
        });
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($value) ?: 'offer', 150, '');
        $slug = $base;
        $n = 2;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function publicUrl(): string
    {
        return route('website.landing', $this->slug);
    }

    public function heroImageUrl(): ?string
    {
        return $this->hero_image ? asset('storage/'.$this->hero_image) : null;
    }

    public function accent(): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $this->accent_color) ? $this->accent_color : '#4f46e5';
    }

    public function countdownActive(): bool
    {
        return $this->countdown_ends_at !== null && $this->countdown_ends_at->isFuture();
    }

    /**
     * Sellable rows offered on the page. Each selected product brings its whole variant family
     * so the visitor can pick size / colour.
     *
     * @return Collection<int, Collection<int, Product>> keyed by the selected product id
     */
    public function productFamilies(): Collection
    {
        $ids = collect($this->product_ids ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $selected = Product::where('shop_id', $this->shop_id)->whereIn('id', $ids)
            ->where(fn ($q) => $q->where('is_published', true)->orWhereNull('is_published'))
            ->get()->keyBy('id');
        $groups = $selected->pluck('variant_group')->filter()->unique();
        $siblings = $groups->isEmpty() ? collect() : Product::where('shop_id', $this->shop_id)
            ->whereIn('variant_group', $groups)
            ->where(fn ($q) => $q->where('is_published', true)->orWhereNull('is_published'))
            ->orderBy('id')->get()->groupBy('variant_group');

        return $ids->filter(fn ($id) => $selected->has($id))
            ->mapWithKeys(function ($id) use ($selected, $siblings) {
                $product = $selected[$id];
                $family = $product->variant_group ? ($siblings[$product->variant_group] ?? collect([$product])) : collect([$product]);

                return [$id => $family->values()];
            });
    }
}
