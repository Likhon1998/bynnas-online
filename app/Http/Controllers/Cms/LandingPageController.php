<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Http\Controllers\Controller;
use App\Models\PromoBanner;
use App\Models\SiteFeature;
use App\Models\SiteSetting;
use App\Services\CmsImageStore;
use App\Services\SiteLogoNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LandingPageController extends Controller
{
    use ShopScoped;

    public function edit()
    {
        $settings = SiteSetting::current();
        if (!$settings->exists) {
            $settings->default_shop_id = $this->shopId();
            $settings->save();
            $settings = SiteSetting::current();
        }

        $features = SiteFeature::where('shop_id', $this->shopId())->orderBy('sort_order')->orderBy('id')->get();
        $allBanners = PromoBanner::where('shop_id', $this->shopId())->orderBy('sort_order')->orderBy('id')->get();
        $banners = $allBanners->filter(fn ($b) => ! in_array($b->placement ?? 'deals', ['hero_side', 'mid_promo'], true))->values();
        $heroSide = $allBanners->filter(fn ($b) => ($b->placement ?? '') === 'hero_side')->values();
        $midPromo = $allBanners->filter(fn ($b) => ($b->placement ?? '') === 'mid_promo')->values();

        return view('cms.landing.edit', compact('settings', 'features', 'banners', 'heroSide', 'midPromo'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'store_name' => 'required|string|max:255',
            'currency_code' => 'required|string|max:10',
            'currency_symbol' => [
                'required',
                'string',
                'max:10',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $v = trim((string) $value);
                    if ($v === '' || preg_match('/^\+?\d{2,4}$/', $v) || in_array($v, ['880', '+880', '088', '88'], true)) {
                        $fail('Currency symbol cannot be a phone country code like 880. Use ৳, Tk, or BDT.');
                    }
                },
            ],
            'special_offer_text' => 'nullable|string|max:255',
            'trusted_by_text' => 'nullable|string|max:255',
            'footer_tagline' => 'nullable|string|max:255',
            'home_copy' => 'nullable|array',
            'home_copy.*' => 'nullable|string|max:255',
            'deals_kicker' => 'nullable|string|max:80',
            'deals_title' => 'nullable|string|max:120',
            'deals_title_accent' => 'nullable|string|max:80',
            'deals_subtitle' => 'nullable|string|max:500',
            'logo' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:5120',
            'favicon' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg,ico|max:2048',
            'features' => 'nullable|array',
            'features.*.id' => 'nullable|integer',
            'features.*.icon' => 'nullable|string|max:50',
            'features.*.title' => 'nullable|string|max:255',
            'features.*.subtitle' => 'nullable|string|max:255',
            'features.*.sort_order' => 'nullable|integer|min:0',
            'features.*.is_active' => 'nullable|boolean',
            'banners' => 'nullable|array',
            'banners.*.id' => 'nullable|integer',
            'banners.*.title' => 'nullable|string|max:255',
            'banners.*.subtitle' => 'nullable|string|max:255',
            'banners.*.badge_text' => 'nullable|string|max:60',
            'banners.*.highlight_text' => 'nullable|string|max:60',
            'banners.*.discount_badge' => 'nullable|string|max:20',
            'banners.*.price_from' => 'nullable|numeric|min:0',
            'banners.*.button_text' => 'nullable|string|max:100',
            'banners.*.button_url' => 'nullable|string|max:255',
            'banners.*.theme' => 'nullable|in:dark,light',
            'banners.*.sort_order' => 'nullable|integer|min:0',
            'banners.*.is_active' => 'nullable|boolean',
            'banners.*.image' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif|max:5120',
            'hero_side' => 'nullable|array|max:2',
            'hero_side.*.id' => 'nullable|integer',
            'hero_side.*.title' => 'nullable|string|max:255',
            'hero_side.*.subtitle' => 'nullable|string|max:255',
            'hero_side.*.badge_text' => 'nullable|string|max:60',
            'hero_side.*.discount_badge' => 'nullable|string|max:20',
            'hero_side.*.button_text' => 'nullable|string|max:100',
            'hero_side.*.button_url' => 'nullable|string|max:255',
            'hero_side.*.theme' => 'nullable|in:dark,light',
            'hero_side.*.sort_order' => 'nullable|integer|min:0',
            'hero_side.*.is_active' => 'nullable|boolean',
            'hero_side.*.image' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif|max:5120',
            'mid_promo' => 'nullable|array|max:12',
            'mid_promo.*.id' => 'nullable|integer',
            'mid_promo.*.title' => 'nullable|string|max:255',
            'mid_promo.*.subtitle' => 'nullable|string|max:255',
            'mid_promo.*.badge_text' => 'nullable|string|max:60',
            'mid_promo.*.highlight_text' => 'nullable|string|max:60',
            'mid_promo.*.discount_badge' => 'nullable|string|max:20',
            'mid_promo.*.price_from' => 'nullable|numeric|min:0',
            'mid_promo.*.button_text' => 'nullable|string|max:100',
            'mid_promo.*.button_url' => 'nullable|string|max:255',
            'mid_promo.*.theme' => 'nullable|in:dark,light',
            'mid_promo.*.sort_order' => 'nullable|integer|min:0',
            'mid_promo.*.is_active' => 'nullable|boolean',
            'mid_promo.*.image' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        $website = app(\App\Services\WebsiteService::class);
        $data['currency_symbol'] = $website->normalizeCurrencySymbol(
            $data['currency_symbol'],
            $data['currency_code']
        );

        $settings = SiteSetting::current();
        if (!$settings->exists) {
            $settings = new SiteSetting();
        }

        $settings->fill([
            'default_shop_id' => $this->shopId(),
            'store_name' => $data['store_name'],
            'currency_code' => $data['currency_code'],
            'currency_symbol' => $data['currency_symbol'],
            'special_offer_text' => $data['special_offer_text'] ?? null,
            'trusted_by_text' => $data['trusted_by_text'] ?? null,
            'footer_tagline' => $data['footer_tagline'] ?? null,
            'home_copy' => $website->homeCopyDefaults($data['home_copy'] ?? []),
            'deals_kicker' => $data['deals_kicker'] ?? null,
            'deals_title' => $data['deals_title'] ?? null,
            'deals_title_accent' => $data['deals_title_accent'] ?? null,
            'deals_subtitle' => $data['deals_subtitle'] ?? null,
            // Contact identity is edited only under CMS → Contact (avoid overwrite conflicts)
        ]);

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $settings->logo_path = app(SiteLogoNormalizer::class)
                ->storeProcessed($request->file('logo'), 'logo');
        }

        if ($request->hasFile('favicon')) {
            if ($settings->favicon_path) {
                Storage::disk('public')->delete($settings->favicon_path);
            }
            $settings->favicon_path = app(SiteLogoNormalizer::class)
                ->storeProcessed($request->file('favicon'), 'favicon');
        }

        $settings->save();

        $this->syncFeatures($request->input('features', []));
        $this->syncBanners($request);

        return back()->with('success', 'Landing page settings saved. Changes appear on the public storefront.');
    }

    private function syncFeatures(array $rows): void
    {
        $keep = [];
        foreach ($rows as $row) {
            if (blank($row['title'] ?? null)) {
                continue;
            }
            $payload = [
                'shop_id' => $this->shopId(),
                'icon' => $row['icon'] ?: 'truck',
                'title' => $row['title'],
                'subtitle' => $row['subtitle'] ?? null,
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'is_active' => !empty($row['is_active']),
            ];
            if (!empty($row['id'])) {
                $feature = SiteFeature::where('shop_id', $this->shopId())->find($row['id']);
                if ($feature) {
                    $feature->update($payload);
                    $keep[] = $feature->id;
                    continue;
                }
            }
            $keep[] = SiteFeature::create($payload)->id;
        }

        SiteFeature::where('shop_id', $this->shopId())->whereNotIn('id', $keep ?: [0])->delete();
    }

    private function syncBanners(Request $request): void
    {
        $keep = [];
        $images = app(CmsImageStore::class);

        $keep = array_merge(
            $keep,
            $this->persistBannerRows(
                $request->input('hero_side', []),
                $request->file('hero_side', []),
                'hero_side',
                $images,
                2
            )
        );

        $keep = array_merge(
            $keep,
            $this->persistBannerRows(
                $request->input('mid_promo', []),
                $request->file('mid_promo', []),
                'mid_promo',
                $images,
                12
            )
        );

        $keep = array_merge(
            $keep,
            $this->persistBannerRows(
                $request->input('banners', []),
                $request->file('banners', []),
                'deals',
                $images
            )
        );

        PromoBanner::where('shop_id', $this->shopId())->whereNotIn('id', $keep ?: [0])->delete();
    }

    private function persistBannerRows(array $rows, array $files, string $placement, CmsImageStore $images, ?int $limit = null): array
    {
        $keep = [];
        $count = 0;

        foreach ($rows as $i => $row) {
            if (blank($row['title'] ?? null)) {
                continue;
            }
            if ($limit !== null && $count >= $limit) {
                break;
            }

            $payload = [
                'shop_id' => $this->shopId(),
                'title' => $row['title'],
                'subtitle' => $row['subtitle'] ?? null,
                'badge_text' => $row['badge_text'] ?? null,
                'highlight_text' => $row['highlight_text'] ?? null,
                'discount_badge' => $row['discount_badge'] ?? null,
                'price_from' => $row['price_from'] ?? null,
                'button_text' => $row['button_text'] ?: 'Shop Now',
                'button_url' => $row['button_url'] ?? null,
                'theme' => $row['theme'] ?? 'dark',
                'placement' => $placement,
                'sort_order' => (int) ($row['sort_order'] ?? $count),
                'is_active' => !empty($row['is_active']),
            ];

            $banner = null;
            if (!empty($row['id'])) {
                $banner = PromoBanner::where('shop_id', $this->shopId())->find($row['id']);
            }

            if (!empty($files[$i]['image'])) {
                if ($banner?->image_path) {
                    Storage::disk('public')->delete($banner->image_path);
                }
                $payload['image_path'] = $images->store($files[$i]['image'], 'cms/banners', 1920, 82);
            }

            if ($banner) {
                $banner->update($payload);
                $keep[] = $banner->id;
            } else {
                $keep[] = PromoBanner::create($payload)->id;
            }

            $count++;
        }

        return $keep;
    }
}
