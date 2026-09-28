<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CmsBlog;
use App\Models\CmsBlogCategory;
use App\Models\CmsFaq;
use App\Models\CmsFaqCategory;
use App\Models\CmsPage;
use App\Models\CmsReview;
use App\Models\HeroSlide;
use App\Models\NavigationLink;
use App\Models\Product;
use App\Models\PromoBanner;
use App\Models\Shop;
use App\Models\SiteFeature;
use App\Models\SiteSetting;

class WebsiteService
{
    public function shop(): ?Shop
    {
        $settings = SiteSetting::query()->first();
        if ($settings?->default_shop_id) {
            $shop = Shop::query()->where('id', $settings->default_shop_id)->where('is_active', true)->first();
            if ($shop) {
                return $shop;
            }
        }

        return Shop::query()->where('is_active', true)->orderBy('id')->first();
    }

    public function shopId(): ?int
    {
        return $this->shop()?->id;
    }

    public function homeCopyDefaults(?array $saved = null): array
    {
        $defaults = [
            'categories_eyebrow' => 'Curated collections',
            'categories_title' => 'Shop by',
            'categories_title_accent' => 'Category',
            'categories_subtitle' => 'Premium gadgets, sorted for how you live — browse the collection.',
            'flash_eyebrow' => 'Limited time',
            'flash_title' => 'Flash',
            'flash_title_accent' => 'Sale',
            'flash_subtitle' => 'Today’s best prices on selected gadgets — ends when the timer hits zero.',
            'new_eyebrow' => 'Just landed',
            'new_title' => 'New',
            'new_title_accent' => 'Arrivals',
            'new_subtitle' => 'Fresh gadgets added to the store — explore what’s new this week.',
            'trending_eyebrow' => 'Most loved',
            'trending_title' => "What's",
            'trending_title_accent' => 'Trending',
            'trending_subtitle' => 'Customer favorites — grab them before they sell out.',
            'brands_eyebrow' => 'Partners',
            'brands_title' => 'Brands We',
            'brands_title_accent' => 'Carry',
            'brands_subtitle' => 'Trusted names in tech — shop your favorites.',
            'reviews_title' => 'What Our Customers Say',
            'reviews_subtitle' => 'Real feedback from shoppers who bought with us.',
            'blog_eyebrow' => 'From the journal',
            'blog_title' => 'Latest from the',
            'blog_title_accent' => 'Blog',
            'blog_subtitle' => 'Guides, reviews, and tips from the Maks Gadget team.',
        ];

        return array_merge($defaults, array_filter($saved ?? [], fn ($v) => $v !== null && $v !== ''));
    }

    public function settings(): object
    {
        $site = SiteSetting::current();
        $shop = $this->shop();

        $currencyCode = $site->currency_code ?: 'BDT';
        $currencySymbol = $this->normalizeCurrencySymbol($site->currency_symbol, $currencyCode);

        // Persist healed symbol if admin accidentally saved a phone code (880 / +880).
        if ($site->exists && trim((string) $site->currency_symbol) !== $currencySymbol) {
            try {
                $site->forceFill([
                    'currency_code' => $currencyCode,
                    'currency_symbol' => $currencySymbol,
                ])->save();
            } catch (\Throwable) {
                // Display still uses the normalized symbol.
            }
        }

        return (object) [
            'store_name' => $site->store_name ?: ($shop?->name ?? config('app.name', 'Maks Gadget')),
            'logo_path' => $site->logo_path,
            'favicon_path' => $site->favicon_path,
            'currency_code' => $currencyCode,
            'currency_symbol' => $currencySymbol,
            'special_offer_text' => $site->special_offer_text ?: 'Special Offer!',
            'trusted_by_text' => $site->trusted_by_text ?: 'Trusted by thousands of customers',
            'footer_tagline' => $site->footer_tagline ?: 'Your one-stop shop for the latest tech gadgets and accessories.',
            'home_copy' => $this->homeCopyDefaults($site->home_copy ?? []),
            'deals_kicker' => $site->deals_kicker ?: 'Special Offers',
            'deals_title' => $site->deals_title ?: "Deals You'll",
            'deals_title_accent' => $site->deals_title_accent ?: 'Love',
            'deals_subtitle' => $site->deals_subtitle ?: 'Grab the best deals on top-quality gadgets and accessories.',
            'contact_email' => $site->contact_email ?: $shop?->email,
            'contact_phone' => $site->contact_phone ?: $shop?->phone,
            'contact_address' => $site->contact_address ?: $shop?->address,
            'social_links' => $site->social_links ?? [],
            'blog_hero_kicker' => $site->blog_hero_kicker,
            'blog_hero_title' => $site->blog_hero_title,
            'blog_hero_subtitle' => $site->blog_hero_subtitle,
            'blog_hero_image' => $site->blog_hero_image,
            'blog_newsletter_title' => $site->blog_newsletter_title,
            'blog_newsletter_text' => $site->blog_newsletter_text,
            'blog_articles_title' => $site->blog_articles_title,
            'blog_feature_1_title' => $site->blog_feature_1_title,
            'blog_feature_1_text' => $site->blog_feature_1_text,
            'blog_feature_2_title' => $site->blog_feature_2_title,
            'blog_feature_2_text' => $site->blog_feature_2_text,
            'blog_feature_3_title' => $site->blog_feature_3_title,
            'blog_feature_3_text' => $site->blog_feature_3_text,
            'faq_hero_title' => $site->faq_hero_title,
            'faq_hero_subtitle' => $site->faq_hero_subtitle,
            'faq_help_title' => $site->faq_help_title,
            'faq_help_text' => $site->faq_help_text,
            'faq_help_button' => $site->faq_help_button,
            'contact_hero_kicker' => $site->contact_hero_kicker,
            'contact_hero_title' => $site->contact_hero_title,
            'contact_hero_subtitle' => $site->contact_hero_subtitle,
            'contact_chat_title' => $site->contact_chat_title,
            'contact_chat_text' => $site->contact_chat_text,
            'contact_chat_status' => $site->contact_chat_status,
            'contact_email_card_title' => $site->contact_email_card_title,
            'contact_email_card_text' => $site->contact_email_card_text,
            'contact_phone_card_title' => $site->contact_phone_card_title,
            'contact_phone_card_text' => $site->contact_phone_card_text,
            'contact_hours_title' => $site->contact_hours_title,
            'contact_hours_weekday' => $site->contact_hours_weekday,
            'contact_hours_weekend' => $site->contact_hours_weekend,
            'contact_form_title' => $site->contact_form_title,
            'contact_form_subtitle' => $site->contact_form_subtitle,
            'contact_map_embed' => normalize_map_embed_url($site->contact_map_embed) ?: $site->contact_map_embed,
            'contact_website_url' => $site->contact_website_url,
            'contact_newsletter_title' => $site->contact_newsletter_title,
            'contact_newsletter_text' => $site->contact_newsletter_text,
        ];
    }

    public function homepageData(): array
    {
        $shopId = $this->shopId();
        $settings = $this->settings();

        if (!$shopId) {
            return $this->emptyHomepage($settings);
        }

        $visibleProducts = fn ($q) => $q->availableForSale()
            ->where(fn ($inner) => $inner->where('is_published', true)->orWhereNull('is_published'));

        $categories = Category::where('shop_id', $shopId)
            ->whereHas('products', $visibleProducts)
            ->withCount(['products' => $visibleProducts])
            ->orderBy('name')
            ->take(12)
            ->get();

        $bestSellers = $this->dedupeVariantCollection(
            $this->catalogQuery($shopId)
                ->with(['category', 'brand'])
                ->orderByDesc('is_best_seller')
                ->orderByDesc('review_count')
                ->latest()
                ->take(24)
                ->get(),
            10
        );

        $flashSaleProducts = $this->dedupeVariantCollection(
            $this->catalogQuery($shopId)
                ->with(['category', 'brand'])
                ->onSale()
                ->orderByRaw('(selling_price - sale_price) / NULLIF(selling_price, 0) DESC')
                ->take(24)
                ->get(),
            6
        );

        $flashSaleEndsAt = $flashSaleProducts
            ->map(fn (Product $p) => $p->sale_ends_at)
            ->filter()
            ->sort()
            ->first();

        $newArrivals = $this->dedupeVariantCollection(
            $this->catalogQuery($shopId)
                ->with(['category', 'brand'])
                ->newArrivals()
                ->latest('id')
                ->take(24)
                ->get(),
            6
        );

        // Trending = best sellers (same CMS product flags) — keep one source of truth
        $trendingProducts = $bestSellers->take(5)->values();

        $this->linkOrphanProductsToBrands($shopId);
        $this->mergeDuplicateBrands($shopId);

        $brands = Brand::where('shop_id', $shopId)
            ->where('is_active', true)
            ->withCount(['products' => $visibleProducts])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->values();

        // Prefer brands with a logo + products first (Gadget Lovers strip).
        $brands = $brands->sortBy([
            fn (Brand $b) => filled($b->logo_path) ? 0 : 1,
            fn (Brand $b) => ((int) $b->products_count > 0) ? 0 : 1,
            fn (Brand $b) => (int) $b->sort_order,
            fn (Brand $b) => mb_strtolower($b->name),
        ])->values();

        return [
            'settings' => $settings,
            'shop' => $this->shop(),
            'heroSlides' => $this->resolveHeroSlides($shopId),
            'features' => SiteFeature::where('shop_id', $shopId)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->take(4)->get(),
            'categories' => $categories,
            'allCategories' => Category::where('shop_id', $shopId)
                ->withCount(['products' => fn ($q) => $q->availableForSale()])
                ->orderBy('name')
                ->get(),
            'promoBanners' => PromoBanner::where('shop_id', $shopId)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->where('placement', 'deals')->orWhereNull('placement')->orWhere('placement', '');
                })
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'heroSideCards' => PromoBanner::where('shop_id', $shopId)
                ->where('is_active', true)
                ->where('placement', 'hero_side')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->take(2)
                ->get(),
            'midPromoBanners' => PromoBanner::where('shop_id', $shopId)
                ->where('is_active', true)
                ->where('placement', 'mid_promo')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->take(12)
                ->get(),
            'bestSellers' => $bestSellers,
            'flashSaleProducts' => $flashSaleProducts,
            'flashSaleEndsAt' => $flashSaleEndsAt,
            'newArrivals' => $newArrivals,
            'trendingProducts' => $trendingProducts,
            'brands' => $brands,
            'mainNav' => NavigationLink::where('shop_id', $shopId)->where('location', 'main_nav')->where('is_active', true)->orderBy('sort_order')->get(),
            'topBarNav' => NavigationLink::where('shop_id', $shopId)->where('location', 'top_bar')->where('is_active', true)->orderBy('sort_order')->get(),
            'featuredReviews' => CmsReview::where('shop_id', $shopId)->where('is_published', true)->where('is_featured', true)->orderBy('sort_order')->take(6)->get(),
            'footerPages' => CmsPage::where('shop_id', $shopId)->where('is_published', true)->where('show_in_footer', true)->orderBy('sort_order')->get(),
            'latestBlogs' => CmsBlog::where('shop_id', $shopId)->published()->with('category')->latest('published_at')->take(4)->get(),
            'deliveryConfig' => app(\App\Services\DeliveryChargeService::class)->publicConfig(),
        ];
    }

    private function emptyHomepage(object $settings): array
    {
        return [
            'settings' => $settings,
            'shop' => null,
            'heroSlides' => collect(),
            'features' => collect(),
            'categories' => collect(),
            'allCategories' => collect(),
            'promoBanners' => collect(),
            'heroSideCards' => collect(),
            'midPromoBanners' => collect(),
            'bestSellers' => collect(),
            'flashSaleProducts' => collect(),
            'flashSaleEndsAt' => null,
            'newArrivals' => collect(),
            'trendingProducts' => collect(),
            'brands' => collect(),
            'mainNav' => collect(),
            'topBarNav' => collect(),
            'featuredReviews' => collect(),
            'footerPages' => collect(),
            'latestBlogs' => collect(),
            'deliveryConfig' => app(\App\Services\DeliveryChargeService::class)->publicConfig(),
        ];
    }

    public function publishedPage(string $slug): ?CmsPage
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return null;
        }

        return CmsPage::where('shop_id', $shopId)->where('slug', $slug)->where('is_published', true)->first();
    }

    public function publishedBlog(string $slug): ?CmsBlog
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return null;
        }

        return CmsBlog::where('shop_id', $shopId)->published()->with('category')->where('slug', $slug)->first();
    }

    public function publishedFaqs(?string $search = null, ?string $categorySlug = null)
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return collect();
        }

        $this->ensureFaqDefaults($shopId);

        $query = CmsFaq::where('shop_id', $shopId)
            ->published()
            ->with('faqCategory')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        if ($categorySlug) {
            $query->whereHas('faqCategory', fn ($q) => $q->where('slug', $categorySlug)->where('is_active', true));
        }

        return $query->get();
    }

    public function faqCategories()
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return collect();
        }

        $this->ensureFaqDefaults($shopId);

        return CmsFaqCategory::where('shop_id', $shopId)
            ->where('is_active', true)
            ->withCount(['faqs' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function ensureFaqDefaults(int $shopId): void
    {
        if (CmsFaqCategory::where('shop_id', $shopId)->exists()) {
            return;
        }

        $created = [];
        foreach ([
            ['name' => 'Orders & Payments', 'slug' => 'orders-payments', 'icon' => 'cart', 'sort_order' => 1],
            ['name' => 'Shipping & Delivery', 'slug' => 'shipping-delivery', 'icon' => 'truck', 'sort_order' => 2],
            ['name' => 'Returns & Refunds', 'slug' => 'returns-refunds', 'icon' => 'refresh', 'sort_order' => 3],
            ['name' => 'Products & Warranty', 'slug' => 'products-warranty', 'icon' => 'shield', 'sort_order' => 4],
            ['name' => 'Account & Security', 'slug' => 'account-security', 'icon' => 'lock', 'sort_order' => 5],
            ['name' => 'Promotions & Discounts', 'slug' => 'promotions-discounts', 'icon' => 'tag', 'sort_order' => 6],
            ['name' => 'Others', 'slug' => 'others', 'icon' => 'help', 'sort_order' => 7],
        ] as $row) {
            $created[$row['slug']] = CmsFaqCategory::create(array_merge($row, [
                'shop_id' => $shopId,
                'is_active' => true,
            ]));
        }

        if (CmsFaq::where('shop_id', $shopId)->exists()) {
            return;
        }

        // Categories only — sample Q&A is managed in CMS, not auto-seeded on the storefront.
    }

    public function faqPageData(?string $search = null, ?string $categorySlug = null): array
    {
        return array_merge($this->homepageData(), [
            'faqs' => $this->publishedFaqs($search, $categorySlug),
            'faqCategories' => $this->faqCategories(),
            'faqSearch' => $search,
            'activeFaqCategory' => $categorySlug,
        ]);
    }

    public function contactPageData(): array
    {
        return $this->homepageData();
    }

    public function publishedBlogs(int $perPage = 6, ?string $search = null, ?string $categorySlug = null)
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        $query = CmsBlog::where('shop_id', $shopId)
            ->published()
            ->with('category')
            ->latest('published_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if ($categorySlug) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug)->where('is_active', true));
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function featuredBlog(): ?CmsBlog
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return null;
        }

        return CmsBlog::where('shop_id', $shopId)
            ->published()
            ->with('category')
            ->where('is_featured', true)
            ->latest('published_at')
            ->first()
            ?? CmsBlog::where('shop_id', $shopId)->published()->with('category')->latest('published_at')->first();
    }

    public function popularBlogs(int $limit = 4)
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return collect();
        }

        return CmsBlog::where('shop_id', $shopId)
            ->published()
            ->with('category')
            ->orderByDesc('views_count')
            ->orderByDesc('published_at')
            ->take($limit)
            ->get();
    }

    public function blogCategories()
    {
        $shopId = $this->shopId();
        if (!$shopId) {
            return collect();
        }

        return CmsBlogCategory::where('shop_id', $shopId)
            ->where('is_active', true)
            ->withCount(['blogs' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function blogPageData(?string $search = null, ?string $categorySlug = null): array
    {
        $blogs = $this->publishedBlogs(9, $search, $categorySlug);

        return array_merge($this->homepageData(), [
            'blogs' => $blogs,
            'blogCategories' => $this->blogCategories(),
            'popularPosts' => $this->popularBlogs(3),
            'blogSearch' => $search,
            'activeBlogCategory' => $categorySlug,
        ]);
    }

    /** Homepage posters from CMS only (no product auto-link). */
    public function resolveHeroSlides(?int $shopId = null)
    {
        $shopId ??= $this->shopId();
        if (! $shopId) {
            return collect();
        }

        return HeroSlide::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function formatPrice(?float $amount, ?object $settings = null): string
    {
        $settings ??= $this->settings();
        $symbol = $this->normalizeCurrencySymbol(
            $settings->currency_symbol ?? null,
            $settings->currency_code ?? 'BDT'
        );

        return format_taka($amount ?? 0, $symbol);
    }

    /**
     * Prevent phone country codes (e.g. 880 / +880 / 088) from being used as currency symbols.
     */
    public function normalizeCurrencySymbol(?string $symbol, ?string $code = null): string
    {
        $symbol = trim((string) $symbol);
        $code = strtoupper(trim((string) $code));

        $badExact = ['', '880', '+880', '088', '00880', '88', '0', '00', '000'];
        $looksLikePhoneCode = (bool) preg_match('/^\+?\d{2,4}$/', $symbol);

        // Heal common CMS mistakes: BDT amounts saved with a $ symbol.
        if ($code === 'BDT' && in_array($symbol, ['$', 'USD', 'US$', 'dollar', 'Dollar'], true)) {
            return '৳';
        }

        if (in_array($symbol, $badExact, true) || $looksLikePhoneCode) {
            return match ($code) {
                'USD' => '$',
                'EUR' => '€',
                'GBP' => '£',
                'INR' => '₹',
                default => '৳',
            };
        }

        return $symbol;
    }

    /** Products visible on the public storefront. */
    public function catalogQuery(?int $shopId = null)
    {
        $shopId ??= $this->shopId();

        return Product::query()
            ->with('galleryImages')
            ->where('shop_id', $shopId)
            ->availableForSale()
            ->where(function ($q) {
                $q->where('is_published', true)->orWhereNull('is_published');
            });
    }

    public function productImageUrl($product): string
    {
        $urls = $this->productImageUrls($product);

        return $urls[0] ?? 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&q=80';
    }

    /** All product gallery URLs (uploaded images, then config/fallback). */
    public function productImageUrls($product): array
    {
        $urls = [];
        foreach ($product->imagePaths() as $path) {
            $url = public_storage_url($path);
            if ($url) {
                $urls[] = $url;
            }
        }

        if ($urls === []) {
            $fallback = config('website_assets.products.' . $product->barcode)
                ?? config('website_assets.products.' . \Illuminate\Support\Str::slug($product->name));

            // Prefer a stable per-product placeholder so New Arrivals never all look identical.
            if (! $fallback) {
                $seed = abs(crc32((string) ($product->barcode ?: $product->sku ?: $product->id ?: $product->name)));
                $fallback = 'https://picsum.photos/seed/gadget'.$seed.'/500/500';
            }

            $urls[] = $fallback;
        }

        return $urls;
    }

    /**
     * Color + memory pickers for a product detail page.
     *
     * Flow (typical phone store):
     * 1) Show every color in the variant_group
     * 2) After a color is selected, show that color's available RAM / storage (ROM) combinations
     *
     * Returns:
     * - colors: swatches linking to the best match for that color
     * - combos: "4 GB / 64 GB" chips for the active color (preferred UI)
     * - storages / rams: separate chips when only one dimension is used
     */
    public function productVariantOptions(Product $product): array
    {
        $empty = ['colors' => [], 'combos' => [], 'storages' => [], 'rams' => []];

        if (! $product->variant_group) {
            return $empty;
        }

        $family = Product::query()
            ->where('shop_id', $product->shop_id)
            ->where('variant_group', $product->variant_group)
            ->where(function ($q) {
                $q->where('is_published', true)->orWhereNull('is_published');
            })
            ->get()
            ->unique('id')
            ->values();

        if ($family->isEmpty()) {
            $family = collect([$product]);
        } elseif (! $family->contains(fn (Product $p) => (int) $p->id === (int) $product->id)) {
            $family->push($product);
        }

        $inStock = fn (Product $p) => (int) $p->availableStock() > 0;
        $colorKey = fn (?string $color) => strtolower(trim((string) $color));
        $ramKey = fn (?string $ram) => memory_size_compact($ram);
        $storageKey = fn (?string $storage) => memory_size_compact($storage);

        $pickBest = function ($candidates) use ($product, $inStock, $ramKey, $storageKey) {
            $candidates = collect($candidates)->values();
            if ($candidates->isEmpty()) {
                return null;
            }

            $prefer = $candidates->filter($inStock);
            $pool = $prefer->isNotEmpty() ? $prefer : $candidates;

            return $pool->sortBy([
                fn (Product $p) => $ramKey($p->ram) === $ramKey($product->ram) ? 0 : 1,
                fn (Product $p) => $storageKey($p->storage) === $storageKey($product->storage) ? 0 : 1,
                fn (Product $p) => $p->currentPrice(),
                fn (Product $p) => $p->id,
            ])->first();
        };

        // ── Colors (always show every color in the family) ───────────────
        $colors = [];
        $seenColors = [];
        foreach ($family as $row) {
            $label = trim((string) ($row->color ?? ''));
            if ($label === '') {
                continue;
            }
            $key = $colorKey($label);
            if (isset($seenColors[$key])) {
                continue;
            }
            $seenColors[$key] = true;

            $sameColor = $family->filter(fn (Product $p) => $colorKey($p->color) === $key);
            $match = $pickBest($sameColor);
            if (! $match) {
                continue;
            }

            $available = $sameColor->contains($inStock);
            $colors[] = [
                'label' => $label,
                'hex' => $match->swatchHex(),
                'url' => route('website.product', $match),
                'image' => $this->productImageUrl($match),
                'active' => $colorKey($product->color) === $key,
                'product_id' => $match->id,
                'available' => $available,
                'barcode' => $match->barcode,
                'price' => $match->currentPrice(),
            ];
        }

        $currentColor = $colorKey($product->color);
        $forColor = $currentColor !== ''
            ? $family->filter(fn (Product $p) => $colorKey($p->color) === $currentColor)
            : $family;

        // ── Combined RAM + storage chips for the active color ────────────
        $combos = [];
        $seenCombos = [];
        foreach ($forColor->sortBy([
            fn (Product $p) => memory_size_sort_key($p->ram),
            fn (Product $p) => memory_size_sort_key($p->storage),
            fn (Product $p) => $p->id,
        ]) as $row) {
            $hasRam = filled($row->ram);
            $hasStorage = filled($row->storage);
            if (! $hasRam && ! $hasStorage) {
                continue;
            }

            $comboKey = ($hasRam ? $ramKey($row->ram) : '-').'|'.($hasStorage ? $storageKey($row->storage) : '-');
            if (isset($seenCombos[$comboKey])) {
                continue;
            }
            $seenCombos[$comboKey] = true;

            $twins = $forColor->filter(function (Product $p) use ($row, $hasRam, $hasStorage, $ramKey, $storageKey) {
                $ramOk = ! $hasRam || $ramKey($p->ram) === $ramKey($row->ram);
                $storageOk = ! $hasStorage || $storageKey($p->storage) === $storageKey($row->storage);

                return $ramOk && $storageOk;
            });
            $match = $pickBest($twins) ?? $row;
            $available = $twins->contains($inStock);

            $parts = [];
            if ($hasRam) {
                $parts[] = str_replace(' ', '', normalize_memory_size($row->ram) ?? (string) $row->ram);
            }
            if ($hasStorage) {
                $parts[] = str_replace(' ', '', normalize_memory_size($row->storage) ?? (string) $row->storage);
            }

            $combos[] = [
                'label' => implode('/', $parts),
                'ram' => $hasRam ? (normalize_memory_size($row->ram) ?? $row->ram) : null,
                'storage' => $hasStorage ? (normalize_memory_size($row->storage) ?? $row->storage) : null,
                'url' => route('website.product', $match),
                'active' => $ramKey($product->ram) === $ramKey($row->ram)
                    && $storageKey($product->storage) === $storageKey($row->storage),
                'product_id' => $match->id,
                'available' => $available,
                'price' => $match->currentPrice(),
                'barcode' => $match->barcode,
            ];
        }

        // Prefer combined chips when both dimensions exist for this color.
        $useCombos = collect($combos)->contains(fn (array $c) => $c['ram'] && $c['storage']);

        $storages = [];
        $rams = [];

        if (! $useCombos) {
            $seenStorage = [];
            foreach ($forColor->whereNotNull('storage')->sortBy(fn (Product $p) => memory_size_sort_key($p->storage)) as $row) {
                $compact = $storageKey($row->storage);
                if ($compact === '' || isset($seenStorage[$compact])) {
                    continue;
                }
                $seenStorage[$compact] = true;

                $twins = $forColor->filter(fn (Product $p) => $storageKey($p->storage) === $compact);
                $match = $pickBest($twins) ?? $row;
                $available = $twins->contains($inStock);

                $storages[] = [
                    'label' => str_replace(' ', '', normalize_memory_size($row->storage) ?? (string) $row->storage),
                    'url' => route('website.product', $match),
                    'active' => $storageKey($product->storage) === $compact,
                    'product_id' => $match->id,
                    'available' => $available,
                    'price' => $match->currentPrice(),
                ];
            }

            $seenRam = [];
            $storageFiltered = filled($product->storage)
                ? $forColor->filter(fn (Product $p) => $storageKey($p->storage) === $storageKey($product->storage))
                : $forColor;

            foreach ($storageFiltered->whereNotNull('ram')->sortBy(fn (Product $p) => memory_size_sort_key($p->ram)) as $row) {
                $compact = $ramKey($row->ram);
                if ($compact === '' || isset($seenRam[$compact])) {
                    continue;
                }
                $seenRam[$compact] = true;

                $twins = $storageFiltered->filter(fn (Product $p) => $ramKey($p->ram) === $compact);
                $match = $pickBest($twins) ?? $row;
                $available = $twins->contains($inStock);

                $rams[] = [
                    'label' => str_replace(' ', '', normalize_memory_size($row->ram) ?? (string) $row->ram),
                    'url' => route('website.product', $match),
                    'active' => $ramKey($product->ram) === $compact,
                    'product_id' => $match->id,
                    'available' => $available,
                    'price' => $match->currentPrice(),
                ];
            }
        }

        return [
            'colors' => array_values($colors),
            'combos' => $useCombos ? array_values($combos) : [],
            'storages' => array_values($storages),
            'rams' => array_values($rams),
        ];
    }

    /**
     * Keep one catalog card per variant family (plus ungrouped products).
     * Picks the cheapest in-stock row in each variant_group among the current query filters.
     */
    public function applyVariantGroupListing($query)
    {
        $rows = (clone $query)->reorder()->get();
        if ($rows->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        $keep = [];
        foreach ($rows->groupBy(fn (Product $p) => $p->variant_group ?: ('__solo_'.$p->id)) as $key => $group) {
            if (str_starts_with((string) $key, '__solo_')) {
                $keep[] = (int) $group->first()->id;
                continue;
            }

            $best = $group->sortBy([
                fn (Product $p) => $p->currentPrice(),
                fn (Product $p) => $p->id,
            ])->first();

            $keep[] = (int) $best->id;
        }

        $table = $query->getModel()->getTable();

        return $query->whereIn($table.'.id', array_values(array_unique($keep)));
    }

    /** Deduplicate an already-loaded product collection by variant_group. */
    public function dedupeVariantCollection($products, ?int $limit = null)
    {
        $kept = collect();
        $seenGroups = [];

        foreach ($products as $product) {
            $group = $product->variant_group;
            if ($group) {
                if (isset($seenGroups[$group])) {
                    continue;
                }
                $seenGroups[$group] = true;
            }
            $kept->push($product);
            if ($limit !== null && $kept->count() >= $limit) {
                break;
            }
        }

        return $kept->values();
    }

    public function categoryImageUrl($category): ?string
    {
        if ($category->image_path ?? null) {
            return public_storage_url($category->image_path);
        }

        $product = Product::query()
            ->where('shop_id', $category->shop_id)
            ->where('category_id', $category->id)
            ->availableForSale()
            ->where(fn ($q) => $q->where('is_published', true)->orWhereNull('is_published'))
            ->whereNotNull('image')
            ->latest()
            ->first();

        if ($product) {
            return $this->productImageUrl($product);
        }

        return null;
    }

    /**
     * Attach products missing brand_id when brand_name or product title matches an active brand.
     */
    public function linkOrphanProductsToBrands(int $shopId): void
    {
        $brands = Brand::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderByDesc(\Illuminate\Support\Facades\DB::raw('LENGTH(name)'))
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($brands->isEmpty()) {
            return;
        }

        $orphans = Product::where('shop_id', $shopId)
            ->where(function ($q) {
                $q->whereNull('brand_id')->orWhere('brand_id', 0);
            })
            ->get(['id', 'name', 'brand_name', 'brand_id']);

        foreach ($orphans as $product) {
            $match = null;
            $explicit = trim((string) ($product->brand_name ?? ''));
            if ($explicit !== '') {
                $match = $brands->first(fn (Brand $b) => strcasecmp($b->name, $explicit) === 0);
            }

            if (! $match) {
                $productName = trim((string) $product->name);
                foreach ($brands as $brand) {
                    $brandName = trim($brand->name);
                    if ($brandName === '') {
                        continue;
                    }
                    if (preg_match('/^'.preg_quote($brandName, '/').'(?:\b|[\s\-_])/iu', $productName)) {
                        $match = $brand;
                        break;
                    }
                }
            }

            if ($match) {
                Product::where('id', $product->id)->update([
                    'brand_id' => $match->id,
                    'brand_name' => $match->name,
                ]);
            }
        }
    }

    /**
     * Merge brands that share the same slug (e.g. "samsung" + "Samsung") into one row.
     */
    public function mergeDuplicateBrands(int $shopId): void
    {
        $brands = Brand::where('shop_id', $shopId)->orderBy('id')->get();
        if ($brands->count() < 2) {
            return;
        }

        $groups = $brands->groupBy(fn (Brand $b) => \Illuminate\Support\Str::slug($b->name));

        foreach ($groups as $group) {
            if ($group->count() < 2) {
                continue;
            }

            $counted = $group->map(function (Brand $b) {
                $b->setAttribute('_product_count', Product::where('brand_id', $b->id)->count());

                return $b;
            });

            $maxCount = (int) $counted->max('_product_count');
            $keeper = $counted
                ->filter(fn (Brand $b) => (int) $b->getAttribute('_product_count') === $maxCount)
                ->sortByDesc(fn (Brand $b) => (int) preg_match('/[A-Z]/', $b->name))
                ->sortByDesc(fn (Brand $b) => strlen($b->name))
                ->first() ?? $group->first();

            foreach ($group as $dup) {
                if ($dup->id === $keeper->id) {
                    continue;
                }

                Product::where('shop_id', $shopId)
                    ->where(function ($q) use ($dup) {
                        $q->where('brand_id', $dup->id)
                            ->orWhereRaw('LOWER(TRIM(COALESCE(brand_name, \'\'))) = ?', [strtolower(trim($dup->name))]);
                    })
                    ->update([
                        'brand_id' => $keeper->id,
                        'brand_name' => $keeper->name,
                    ]);

                if (! $keeper->logo_path && $dup->logo_path) {
                    $keeper->update(['logo_path' => $dup->logo_path]);
                }

                $dup->delete();
            }

            Product::where('shop_id', $shopId)
                ->where('brand_id', $keeper->id)
                ->update(['brand_name' => $keeper->name]);
        }
    }
}
