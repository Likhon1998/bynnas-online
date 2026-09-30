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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            'logo_tagline' => '',
            'hero_badge' => '',
            'hero_title' => 'Welcome to {store}',
            'hero_subtitle' => 'Browse the collection and order online with delivery to your door.',
            'hero_trust_1_title' => '',
            'hero_trust_1_sub' => '',
            'hero_trust_2_title' => '',
            'hero_trust_2_sub' => '',
            'hero_trust_3_title' => '',
            'hero_trust_3_sub' => '',
            'categories_title' => 'Shop by',
            'categories_title_accent' => 'Category',
            'categories_subtitle' => 'Everything you love, sorted by category — browse the collection.',
            'flash_title' => 'Flash',
            'flash_title_accent' => 'Sale',
            'flash_subtitle' => 'Today’s best prices on selected products — ends when the timer hits zero.',
            'featured_title' => 'Featured Products',
            'featured_subtitle' => 'Hand-picked products from our collection.',
            'combo_title' => 'Combo Deals',
            'combo_subtitle' => 'Bundles that save you more.',
            'why_title' => 'Why Shop with {store}',
            'why_subtitle' => '',
            'why_badge' => '',
            'new_title' => 'New',
            'new_title_accent' => 'Arrivals',
            'new_subtitle' => 'Fresh arrivals added to the store — explore what’s new this week.',
            'brands_title' => 'Brands We',
            'brands_title_accent' => 'Carry',
            'brands_subtitle' => 'Trusted brands — shop your favorites.',
            'reviews_title' => 'What Our Customers Say',
            'reviews_subtitle' => 'Feedback from our customers.',
            'blog_title' => 'Latest from the',
            'blog_title_accent' => 'Blog',
            'blog_subtitle' => 'Guides, tips and stories from the {store} team.',
        ];

        // Old default that hard-coded a store name; treat it as unset so {store} applies.
        $legacy = ['blog_subtitle' => 'Guides, reviews, and tips from the Bynnas Social team.'];

        return array_merge($defaults, array_filter(
            $saved ?? [],
            fn ($v, $k) => $v !== null && $v !== '' && ($legacy[$k] ?? null) !== $v,
            ARRAY_FILTER_USE_BOTH
        ));
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
            'store_name' => $site->store_name ?: ($shop?->name ?? config('app.name', 'Bynnas Social')),
            'logo_path' => $site->logo_path,
            'favicon_path' => $site->favicon_path,
            'currency_code' => $currencyCode,
            'currency_symbol' => $currencySymbol,
            'special_offer_text' => $site->special_offer_text,
            'trusted_by_text' => $site->trusted_by_text,
            'footer_tagline' => $site->footer_tagline,
            'home_copy' => $this->homeCopyDefaults($site->home_copy ?? []),
            'deals_kicker' => $site->deals_kicker ?: 'Special Offers',
            'deals_title' => $site->deals_title ?: "Deals You'll",
            'deals_title_accent' => $site->deals_title_accent ?: 'Love',
            'deals_subtitle' => $site->deals_subtitle ?: 'Grab the best deals on top-quality products.',
            // No fallback to the shop record: its email is the admin login.
            'contact_email' => $site->contact_email,
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
                ->take(48)
                ->get(),
            16
        );

        $featuredProducts = $this->dedupeVariantCollection(
            $this->catalogQuery($shopId)
                ->with(['category', 'brand'])
                ->where('is_featured', true)
                ->latest('id')
                ->take(48)
                ->get(),
            16
        );
        if ($featuredProducts->isEmpty()) {
            $featuredProducts = $bestSellers;
        }

        $flashSaleProducts = $this->dedupeVariantCollection(
            $this->catalogQuery($shopId)
                ->with(['category', 'brand'])
                ->onSale()
                ->orderByRaw('(selling_price - sale_price) / NULLIF(selling_price, 0) DESC')
                ->take(48)
                ->get(),
            16
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
            8
        );

        $comboProducts = $this->dedupeVariantCollection(
            $this->catalogQuery($shopId)
                ->with(['category', 'brand'])
                ->combos()
                ->latest('id')
                ->take(24)
                ->get(),
            8
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

        // Prefer brands with a logo + products first (brands strip).
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
            'featuredProducts' => $featuredProducts,
            'flashSaleProducts' => $flashSaleProducts,
            'flashSaleEndsAt' => $flashSaleEndsAt,
            'newArrivals' => $newArrivals,
            'comboProducts' => $comboProducts,
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
            'featuredProducts' => collect(),
            'flashSaleProducts' => collect(),
            'flashSaleEndsAt' => null,
            'newArrivals' => collect(),
            'comboProducts' => collect(),
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

        return $urls[0] ?? $this->placeholderImageUrl();
    }

    public function placeholderImageUrl(): string
    {
        return asset('images/placeholder-product.svg');
    }

    /** All product gallery URLs (uploaded images, then a local placeholder). */
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
            $urls[] = $this->placeholderImageUrl();
        }

        return $urls;
    }

    /**
     * Attribute pickers (Color, Size, …) for a product detail page.
     *
     * @return array{groups: list<array<string, mixed>>, specs: list<array{label: string, value: string}>}
     */
    public function productVariantOptions(Product $product): array
    {
        return app(ProductVariantService::class)->storefrontOptions($product);
    }

    /**
     * Attribute filters chosen on the storefront: attr[color][]=red&attr[size][]=m.
     * Legacy storage[] / ram[] query params map onto the Storage / RAM attributes.
     *
     * @return array<string, list<string>> attribute slug => value slugs
     */
    public function selectedAttributeFilters(Request $request): array
    {
        $selected = [];
        foreach ((array) $request->input('attr', []) as $slug => $values) {
            $slug = Str::slug((string) $slug);
            $values = collect((array) $values)->map(fn ($v) => Str::slug((string) $v))->filter()->unique()->values()->all();
            if ($slug !== '' && $values !== []) {
                $selected[$slug] = $values;
            }
        }

        foreach (['storage', 'ram'] as $legacy) {
            $values = collect((array) $request->input($legacy, []))
                ->map(fn ($v) => Str::slug(memory_size_compact((string) $v)))
                ->filter()->all();
            if ($values !== []) {
                $selected[$legacy] = array_values(array_unique(array_merge($selected[$legacy] ?? [], $values)));
            }
        }

        return $selected;
    }

    /**
     * Keep products whose attribute values match: OR within one attribute, AND across attributes.
     *
     * @param  array<string, list<string>>  $selected
     */
    public function applyAttributeFilters($query, int $shopId, array $selected): void
    {
        $table = $query->getModel()->getTable();

        foreach ($selected as $attributeSlug => $valueSlugs) {
            if ($valueSlugs === []) {
                continue;
            }
            $query->whereIn($table.'.id', function ($sub) use ($shopId, $attributeSlug, $valueSlugs) {
                $sub->select('pvv.product_id')
                    ->from('product_variant_values as pvv')
                    ->join('product_attributes as pa', 'pa.id', '=', 'pvv.product_attribute_id')
                    ->join('product_attribute_values as pav', 'pav.id', '=', 'pvv.product_attribute_value_id')
                    ->where('pa.shop_id', $shopId)
                    ->where('pa.slug', $attributeSlug)
                    ->whereIn('pav.slug', $valueSlugs);
            });
        }
    }

    /**
     * Attribute facets for the products matched by $productQuery (only values actually in use).
     *
     * @param  list<string>|null  $onlySlugs  restrict to these attribute slugs (category filter groups)
     * @return list<array{slug: string, name: string, type: string, options: list<array{value: string, label: string, hex: ?string}>}>
     */
    public function attributeFacets(int $shopId, $productQuery, ?array $onlySlugs = null): array
    {
        $table = $productQuery->getModel()->getTable();
        $productIds = (clone $productQuery)->reorder()->select($table.'.id')->toBase();

        $rows = DB::table('product_variant_values as pvv')
            ->join('product_attributes as pa', 'pa.id', '=', 'pvv.product_attribute_id')
            ->join('product_attribute_values as pav', 'pav.id', '=', 'pvv.product_attribute_value_id')
            ->where('pa.shop_id', $shopId)
            ->when($onlySlugs === null, fn ($q) => $q->where('pa.is_filterable', true))
            ->when($onlySlugs !== null, fn ($q) => $q->whereIn('pa.slug', $onlySlugs))
            ->whereIn('pvv.product_id', $productIds)
            ->select([
                'pa.slug as attribute_slug', 'pa.name as attribute_name', 'pa.type', 'pa.sort_order as attribute_sort',
                'pav.slug as value_slug', 'pav.value', 'pav.color_hex', 'pav.sort_order as value_sort',
            ])
            ->distinct()
            ->get();

        return $rows->groupBy('attribute_slug')
            ->map(function ($values) {
                $first = $values->first();
                $isColor = $first->type === \App\Models\ProductAttribute::TYPE_COLOR;

                return [
                    'slug' => (string) $first->attribute_slug,
                    'name' => (string) $first->attribute_name,
                    'type' => (string) $first->type,
                    'sort' => (int) $first->attribute_sort,
                    'options' => $values
                        ->sortBy(fn ($v) => [(int) $v->value_sort, (string) $v->value])
                        ->map(fn ($v) => [
                            'value' => (string) $v->value_slug,
                            'label' => (string) $v->value,
                            'hex' => $isColor ? ($v->color_hex ?: color_name_to_hex($v->value)) : null,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy(fn ($facet) => [$facet['sort'], $facet['name']])
            ->values()
            ->all();
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

            $best = $group->sortBy(fn (Product $p) => [$p->currentPrice(), $p->id])->first();

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
