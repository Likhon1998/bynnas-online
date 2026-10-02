<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\CampaignAttributionService;
use App\Services\OnlineOrderTrackingService;
use App\Services\OrderCreationException;
use App\Services\OrderCreationService;
use App\Services\WebsiteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class WebsiteController extends Controller
{
    public function __construct(
        private WebsiteService $website,
        private OnlineOrderTrackingService $tracking,
        private OrderCreationService $orders,
        private CampaignAttributionService $attribution,
        private \App\Services\DeliveryChargeService $delivery,
        private \App\Services\AbandonedCartService $carts,
    ) {}

    public function home()
    {
        return view('website.home', $this->website->homepageData());
    }

    public function shop(Request $request)
    {
        $shopId = $this->website->shopId();
        abort_unless($shopId, 404);

        $query = $this->website->catalogQuery($shopId)->with(['category', 'brand']);

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->whereSlugOrId($request->category));
        }

        $brandIds = array_values(array_filter(array_map('intval', (array) $request->input('brands', []))));
        if ($brandIds) {
            $query->whereIn('brand_id', $brandIds);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $like = Schema::getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('name', $like, "%{$search}%")
                    ->orWhere('brand_name', $like, "%{$search}%")
                    ->orWhere('sku', $like, "%{$search}%")
                    ->orWhere('barcode', $like, "%{$search}%")
                    ->orWhere('short_description', $like, "%{$search}%")
                    ->orWhereHas('brand', fn ($brand) => $brand->where('name', $like, "%{$search}%"))
                    ->orWhereHas('category', fn ($category) => $category->where('name', $like, "%{$search}%"));
            });
        }

        if ($request->filled('min_price')) {
            $query->where('selling_price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('selling_price', '<=', (float) $request->max_price);
        }

        $this->website->applyAttributeFilters($query, $shopId, $this->website->selectedAttributeFilters($request));

        if ($request->filter === 'deals') {
            $query->onSale();
        } elseif ($request->filter === 'new') {
            $query->newArrivals();
        } elseif ($request->filter === 'combo') {
            $query->combos();
        } elseif (in_array($request->filter, ['bestsellers', 'best'], true)) {
            $query->trending()->orderByDesc('review_count');
        }

        $sort = $request->query('sort', 'featured');
        match ($sort) {
            'price_asc' => $query->orderBy('selling_price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('selling_price')->orderBy('id'),
            'name' => $query->orderBy('name')->orderBy('id'),
            'latest' => $query->latest('id'),
            'bestsellers' => $query->orderByDesc('is_best_seller')->orderByDesc('review_count')->latest('id'),
            default => $request->filter === 'new'
                ? $query->latest('id')
                : $query->orderByDesc('is_best_seller')->orderByDesc('review_count')->latest('id'),
        };

        $this->website->applyVariantGroupListing($query);
        $products = $query->paginate(12)->withQueryString();

        $pageTitle = match ($request->filter) {
            'deals' => 'Deals',
            'new' => 'New Arrivals',
            'combo' => 'Combo Deals',
            'bestsellers', 'best' => 'Best Sellers',
            default => 'Shop',
        };

        if ($request->boolean('ajax') || $request->ajax()) {
            $countFrom = $products->firstItem() ?? 0;
            $countTo = $products->lastItem() ?? 0;
            $countTotal = $products->total();
            $settings = $this->website->settings();

            return response()->json([
                'html' => view('website.partials.shop-results', compact('products', 'settings'))->render(),
                'count_text' => "Showing {$countFrom}–{$countTo} of ".format_taka_number($countTotal).' products',
                'title' => $pageTitle,
                'url' => $request->fullUrlWithoutQuery(['ajax']),
            ]);
        }

        $sidebar = $this->shopSidebarData($shopId);

        return view('website.shop', array_merge($this->website->homepageData(), $sidebar, compact(
            'products',
            'pageTitle',
            'sort',
        )));
    }

    public function searchSuggest(Request $request)
    {
        $shopId = $this->website->shopId();
        if (! $shopId) {
            return response()->json(['products' => []]);
        }

        $q = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $limit = min(10, max(1, (int) $request->query('limit', 8)));
        $like = Schema::getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = $this->website->catalogQuery($shopId)->with(['category', 'brand']);

        if ($category !== '') {
            $query->whereHas('category', fn ($builder) => $builder->whereSlugOrId($category));
        }

        if ($q !== '') {
            $query->where(function ($builder) use ($q, $like) {
                $builder->where('name', $like, "%{$q}%")
                    ->orWhere('brand_name', $like, "%{$q}%")
                    ->orWhere('sku', $like, "%{$q}%")
                    ->orWhere('barcode', $like, "%{$q}%")
                    ->orWhere('short_description', $like, "%{$q}%")
                    ->orWhereHas('brand', fn ($brand) => $brand->where('name', $like, "%{$q}%"))
                    ->orWhereHas('category', fn ($category) => $category->where('name', $like, "%{$q}%"));
            })->orderBy('name');
        } else {
            $query->orderByDesc('is_best_seller')
                ->orderByDesc('review_count')
                ->latest();
        }

        $products = $this->website->dedupeVariantCollection($query->limit($limit * 3)->get(), $limit)
            ->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->storefrontDisplayName(),
            'brand' => $product->brand?->name ?? $product->brand_name,
            'price' => $product->currentPrice(),
            'image' => $this->website->productImageUrl($product),
            'url' => route('website.product', $product),
            'in_stock' => $product->availableStock() > 0,
        ]);

        return response()->json([
            'products' => $products,
            'mode' => $q === '' ? 'best' : 'search',
        ]);
    }

    public function category(string $slug, Request $request)
    {
        $shopId = $this->website->shopId();
        abort_unless($shopId, 404);

        $category = Category::where('shop_id', $shopId)
            ->whereSlugOrId($slug)
            ->firstOrFail();

        if (blank($category->slug)) {
            $category->save();
        }

        $filterConfig = \App\Support\CategoryFilterConfig::for($category);
        $showSidebar = (bool) ($filterConfig['enabled'] ?? false);

        // Category pages can show out-of-stock when filters are on (availability facet)
        $query = Product::query()
            ->where('shop_id', $shopId)
            ->where('category_id', $category->id)
            ->where(function ($q) {
                $q->where('is_published', true)->orWhereNull('is_published');
            })
            ->with(['category', 'brand']);

        if (! $showSidebar) {
            $query->availableForSale();
        }

        $this->applyCategoryFilters($query, $request, $filterConfig, $category);

        $sort = $request->query('sort', 'latest');
        match ($sort) {
            'price_asc' => $query->orderBy('selling_price'),
            'price_desc' => $query->orderByDesc('selling_price'),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        $this->website->applyVariantGroupListing($query);
        $products = $query->paginate(12)->withQueryString();

        $sidebarFacets = $showSidebar
            ? $this->buildSidebarFacets($category, $filterConfig)
            : [];

        $priceBoundsQuery = Product::query()
            ->where('shop_id', $shopId)
            ->where('category_id', $category->id)
            ->where(fn ($q) => $q->where('is_published', true)->orWhereNull('is_published'));

        $sidebar = $this->shopSidebarData($shopId);
        // Keep price slider scoped to this category's range.
        $sidebar['priceBounds'] = [
            'min' => 0,
            'max' => (float) ((clone $priceBoundsQuery)->max('selling_price') ?? 0),
        ];

        return view('website.shop', array_merge($this->website->homepageData(), $sidebar, [
            'products' => $products,
            'activeCategory' => $category,
            'pageTitle' => $category->name,
            'pageSubtitle' => $category->description ?: 'Browse all products in this category.',
            'showSidebar' => $showSidebar,
            'filterConfig' => $filterConfig,
            'sidebarFacets' => $sidebarFacets,
            'sort' => $sort,
        ]));
    }

    /**
     * Shared category / brand / price data for the shop listing sidebar.
     */
    protected function shopSidebarData(int $shopId): array
    {
        $this->website->linkOrphanProductsToBrands($shopId);
        $this->website->mergeDuplicateBrands($shopId);

        $catalog = $this->website->catalogQuery($shopId);

        $categories = Category::where('shop_id', $shopId)
            ->orderBy('name')
            ->withCount(['products as published_count' => function ($q) use ($shopId) {
                $q->where('shop_id', $shopId)
                    ->availableForSale()
                    ->where(function ($qq) {
                        $qq->where('is_published', true)->orWhereNull('is_published');
                    });
            }])
            ->get();

        $visibleBrandProducts = function ($q) use ($shopId) {
            $q->where('shop_id', $shopId)
                ->availableForSale()
                ->where(function ($qq) {
                    $qq->where('is_published', true)->orWhereNull('is_published');
                });
        };

        $brands = Brand::where('shop_id', $shopId)
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->withCount([
                'products as published_count' => $visibleBrandProducts,
                'products as products_count' => $visibleBrandProducts,
            ])
            ->get()
            ->filter(fn (Brand $b) => (int) ($b->products_count ?? $b->published_count ?? 0) > 0)
            ->values();

        return [
            'categories' => $categories,
            'brands' => $brands,
            'attributeFacets' => $this->website->attributeFacets($shopId, $catalog),
            'categoryTotal' => (clone $catalog)->count(),
            'priceBounds' => [
                'min' => 0,
                'max' => (float) ((clone $catalog)->max('selling_price') ?? 0),
            ],
        ];
    }

    protected function applyCategoryFilters($query, Request $request, array $filterConfig, Category $category): void
    {
        if (! empty($filterConfig['price_enabled'])) {
            if ($request->filled('min_price')) {
                $query->where('selling_price', '>=', (float) $request->min_price);
            }
            if ($request->filled('max_price')) {
                $query->where('selling_price', '<=', (float) $request->max_price);
            }
        }

        foreach ($filterConfig['groups'] ?? [] as $group) {
            if (empty($group['enabled'])) {
                continue;
            }

            $key = $group['key'] ?? '';
            $selected = array_filter((array) $request->query($key, []));
            if ($selected === [] || $key === '') {
                continue;
            }

            $type = $group['type'] ?? 'custom';

            if ($type === 'availability') {
                $query->where(function ($q) use ($selected) {
                    foreach ($selected as $value) {
                        $q->orWhere(function ($inner) use ($value) {
                            if ($value === 'in_stock') {
                                $inner->whereRaw('stock_quantity > COALESCE(reserved_stock, 0)')
                                    ->where(function ($a) {
                                        $a->whereNull('availability')
                                            ->orWhere('availability', 'in_stock');
                                    });
                            } elseif ($value === 'out_of_stock') {
                                $inner->whereRaw('stock_quantity <= COALESCE(reserved_stock, 0)')
                                    ->where(function ($a) {
                                        $a->whereNull('availability')
                                            ->orWhere('availability', 'out_of_stock')
                                            ->orWhere('availability', 'in_stock');
                                    });
                            } else {
                                $inner->where('availability', $value);
                            }
                        });
                    }
                });
                continue;
            }

            if ($type === 'brand') {
                $query->where(function ($q) use ($selected, $category) {
                    $brands = Brand::where('shop_id', $category->shop_id)->get();
                    foreach ($selected as $value) {
                        $match = $brands->first(fn ($b) => \Illuminate\Support\Str::slug($b->name, '_') === $value
                            || strtolower($b->name) === str_replace('_', ' ', strtolower($value)));
                        if ($match) {
                            $q->orWhere('brand_id', $match->id)->orWhere('brand_name', $match->name);
                        } else {
                            $label = str_replace('_', ' ', $value);
                            $q->orWhereRaw('LOWER(COALESCE(brand_name, \'\')) = ?', [strtolower($label)]);
                        }
                    }
                });
                continue;
            }

            if ($type === 'attribute') {
                $isMemory = in_array($key, ['storage', 'ram'], true);
                $this->website->applyAttributeFilters($query, (int) $category->shop_id, [
                    $key => collect($selected)
                        ->map(fn ($v) => \Illuminate\Support\Str::slug($isMemory ? memory_size_compact(str_replace('_', ' ', (string) $v)) : (string) $v))
                        ->filter()->values()->all(),
                ]);
                continue;
            }

            // Custom attributes JSON
            $query->where(function ($q) use ($selected, $key) {
                foreach ($selected as $value) {
                    $q->orWhere("filter_attributes->{$key}", $value)
                        ->orWhereJsonContains("filter_attributes->{$key}", $value);
                }
            });
        }
    }

    protected function buildSidebarFacets(Category $category, array $filterConfig): array
    {
        $facets = [];
        foreach ($filterConfig['groups'] ?? [] as $group) {
            if (empty($group['enabled'])) {
                continue;
            }

            $type = $group['type'] ?? 'custom';
            $options = $group['options'] ?? [];

            if ($type === 'attribute') {
                $categoryProducts = Product::query()
                    ->where('shop_id', $category->shop_id)
                    ->where('category_id', $category->id)
                    ->where(fn ($q) => $q->where('is_published', true)->orWhereNull('is_published'));
                $options = $this->website->attributeFacets((int) $category->shop_id, $categoryProducts, [$group['key'] ?? ''])[0]['options'] ?? [];
            } elseif (in_array($type, ['brand', 'custom'], true) && $options === []) {
                $options = \App\Support\CategoryFilterConfig::facetValues($category, $type, $group['key'] ?? '')
                    ->all();
            }

            if ($options === [] && $type !== 'availability') {
                continue;
            }

            $facets[] = [
                'key' => $group['key'],
                'label' => $group['label'],
                'type' => $type,
                'options' => $options,
            ];
        }

        return $facets;
    }

    public function brand(string $slug)
    {
        $shopId = $this->website->shopId();
        abort_unless($shopId, 404);

        $brand = Brand::where('shop_id', $shopId)
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->withCount(['products' => function ($q) use ($shopId) {
                $q->where('shop_id', $shopId)
                    ->availableForSale()
                    ->where(function ($qq) {
                        $qq->where('is_published', true)->orWhereNull('is_published');
                    });
            }])
            ->get()
            ->filter(fn ($b) => \Illuminate\Support\Str::slug($b->name) === $slug)
            // Prefer the brand row that actually has products (handles samsung / Samsung duplicates).
            ->sortByDesc('products_count')
            ->first();

        abort_unless($brand, 404);

        $this->website->linkOrphanProductsToBrands($shopId);
        $this->website->mergeDuplicateBrands($shopId);

        // Re-resolve after merge in case the chosen row was absorbed.
        $brand = Brand::where('shop_id', $shopId)
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->get()
            ->filter(fn ($b) => \Illuminate\Support\Str::slug($b->name) === $slug)
            ->sortByDesc(fn ($b) => Product::where('brand_id', $b->id)->count())
            ->first() ?? $brand;

        $brandQuery = $this->website->catalogQuery($shopId)
            ->where(function ($q) use ($brand) {
                $q->where('brand_id', $brand->id)
                    ->orWhereRaw('LOWER(TRIM(COALESCE(brand_name, \'\'))) = ?', [strtolower(trim($brand->name))]);
            })
            ->with(['category', 'brand'])
            ->latest();
        $this->website->applyVariantGroupListing($brandQuery);
        $products = $brandQuery->paginate(12);

        return view('website.shop', array_merge($this->website->homepageData(), $this->shopSidebarData($shopId), [
            'products' => $products,
            'activeBrand' => $brand,
            'pageTitle' => $brand->name,
            'pageSubtitle' => 'Shop all products from ' . $brand->name . '.',
            'sort' => 'latest',
        ]));
    }

    public function product(Request $request, Product $product)
    {
        $shopId = $this->website->shopId();
        $published = $product->is_published !== false;
        abort_unless($shopId && $product->shop_id === $shopId && $published, 404);

        $product->loadMissing(['category', 'brand']);

        $relatedQuery = $this->website->catalogQuery($shopId)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id);
        if ($product->variant_group) {
            $relatedQuery->where(function ($q) use ($product) {
                $q->whereNull('variant_group')
                    ->orWhere('variant_group', '!=', $product->variant_group);
            });
        }
        $this->website->applyVariantGroupListing($relatedQuery);
        $related = $relatedQuery->take(4)->get();

        $variantOptions = $this->website->productVariantOptions($product);

        $reviews = \App\Models\CmsReview::where('shop_id', $shopId)
            ->where('is_published', true)
            ->where(function ($q) use ($product) {
                $q->where('product_id', $product->id)->orWhereNull('product_id');
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->take(8)
            ->get();

        $home = $this->website->homepageData();
        $settings = $home['settings'] ?? $this->website->settings();

        if ($request->boolean('ajax') || $request->ajax()) {
            $displayName = $product->storefrontDisplayName();

            $storeName = data_get($settings, 'store_name', 'Shop');

            return response()->json([
                'html' => view('website.partials.product-live', compact(
                    'product',
                    'related',
                    'reviews',
                    'variantOptions',
                    'settings'
                ))->render(),
                'url' => route('website.product', $product),
                'title' => $displayName.' | '.$storeName,
            ]);
        }

        return view('website.product', array_merge($home, compact('product', 'related', 'reviews', 'variantOptions')));
    }

    public function page(string $slug)
    {
        $page = $this->website->publishedPage($slug);
        abort_unless($page, 404);

        return view('website.cms-page', array_merge($this->website->homepageData(), compact('page')));
    }

    public function blogs(Request $request)
    {
        $data = $this->website->blogPageData(
            $request->query('q'),
            $request->query('category')
        );

        return view('website.blogs', $data);
    }

    public function blog(string $slug)
    {
        $blog = $this->website->publishedBlog($slug);
        abort_unless($blog, 404);

        $blog->increment('views_count');
        $blog->refresh();

        $related = \App\Models\CmsBlog::where('shop_id', $blog->shop_id)
            ->published()
            ->where('id', '!=', $blog->id)
            ->when($blog->category_id, fn ($q) => $q->where('category_id', $blog->category_id))
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('website.blog-show', array_merge($this->website->homepageData(), [
            'blog' => $blog,
            'relatedPosts' => $related,
            'popularPosts' => $this->website->popularBlogs(3),
            'blogCategories' => $this->website->blogCategories(),
        ]));
    }

    public function subscribeNewsletter(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255']);

        $shopId = $this->website->shopId();
        abort_unless($shopId, 404);

        \App\Models\CmsNewsletterSubscriber::firstOrCreate(
            ['shop_id' => $shopId, 'email' => strtolower(trim($request->email))]
        );

        return back()->with('newsletter_success', 'Thanks for subscribing!');
    }

    public function faqs(Request $request)
    {
        return view('website.faqs', $this->website->faqPageData(
            $request->query('q'),
            $request->query('category')
        ));
    }

    public function contact()
    {
        return view('website.contact', $this->website->contactPageData());
    }

    public function submitContact(Request $request)
    {
        $shopId = $this->website->shopId();
        abort_unless($shopId, 404);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:200',
            'order_number' => 'nullable|string|max:80',
            'message' => 'required|string|max:5000',
        ]);

        \App\Models\CmsContactMessage::create([
            'shop_id' => $shopId,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'order_number' => $data['order_number'] ?? null,
            'message' => $data['message'],
            'is_read' => false,
        ]);

        return back()->with('contact_success', 'Thanks! Your message has been sent. We\'ll get back to you soon.');
    }

    public function trackOrder(Request $request)
    {
        $invoice = trim((string) $request->query('invoice', ''));
        $tracking = null;
        $masked = false;

        if ($invoice !== '' && ($shopId = $this->website->shopId())) {
            $order = $this->findOnlineOrder($shopId, $invoice);
            $access = $order ? $this->trackingAccess($request, $order) : null;

            if ($access) {
                $tracking = $this->tracking->trackingPayload($order);
                $masked = $access === self::TRACK_MASKED;
                if ($masked) {
                    $tracking['customer_name'] = $this->maskName($tracking['customer_name'] ?? '');
                    $tracking['delivery_address'] = $this->maskAddress($tracking['delivery_address'] ?? '');
                }
            }
        }

        return view('website.track-order', array_merge($this->website->homepageData(), [
            'tracking' => $tracking,
            'trackingMasked' => $masked,
            'invoiceNo' => $invoice,
            'phone' => $request->query('phone'),
            'phoneOrders' => session('phone_orders'),
        ]));
    }

    public function trackOrderLookup(Request $request)
    {
        $data = $request->validate([
            'lookup_mode' => ['nullable', 'in:id,phone'],
            'invoice_no' => ['nullable', 'required_if:lookup_mode,id', 'string', 'max:64'],
            'phone' => ['required', 'string', 'max:32'],
        ], [
            'invoice_no.required_if' => 'Enter your Order ID, or switch to “Phone number only”.',
        ]);

        $shopId = $this->website->shopId();
        abort_unless($shopId, 404);

        $phone = Customer::normalizePhone($data['phone']);
        if (strlen($phone) < 8) {
            return back()->withInput()->with('error', 'Enter the full phone number used at checkout.');
        }

        $invoice = strtoupper(trim((string) ($data['invoice_no'] ?? '')));

        // Forgot the Order ID: list every online order placed with this phone number.
        if ($invoice === '') {
            $orders = $this->ordersForPhone($shopId, $phone);
            if ($orders->isEmpty()) {
                return back()->withInput()->with('error', 'No orders found for that phone number.');
            }

            $orders->each(fn (Order $order) => $this->rememberTrackedOrder($request, $order->invoice_no, self::TRACK_MASKED));

            return redirect()->route('website.track')
                ->withInput(['lookup_mode' => 'phone', 'phone' => $data['phone']])
                ->with('phone_orders', $orders->map(fn (Order $order) => $this->phoneOrderSummary($order))->all());
        }

        $order = $this->findOnlineOrder($shopId, $invoice);
        if (! $order || ! $this->orderMatchesPhone($order, $phone)) {
            return back()
                ->withInput()
                ->with('error', 'No order found for that Order ID and phone number.');
        }

        $this->rememberTrackedOrder($request, $order->invoice_no, self::TRACK_MASKED);

        return redirect()->route('website.track', ['invoice' => $order->invoice_no]);
    }

    /** Full: placed in this browser session or owned by the signed-in customer. Masked: verified by phone only. */
    private const TRACK_FULL = 'full';

    private const TRACK_MASKED = 'masked';

    private function trackingAccess(Request $request, Order $order): ?string
    {
        $user = $request->user();
        if ($user?->isStorefrontCustomer() && $order->customer && (int) $order->customer->user_id === (int) $user->id) {
            return self::TRACK_FULL;
        }

        return ((array) $request->session()->get('storefront.tracked_orders', []))[$order->invoice_no] ?? null;
    }

    private function findOnlineOrder(int $shopId, string $invoice): ?Order
    {
        return Order::where('shop_id', $shopId)
            ->onlineOrders()
            ->where('invoice_no', $invoice)
            ->with(['customer', 'items.product', 'statusLogs'])
            ->first();
    }

    private function ordersForPhone(int $shopId, string $phone)
    {
        $tail = '%'.substr($phone, -6);

        return Order::where('shop_id', $shopId)
            ->onlineOrders()
            ->where(fn ($q) => $q->where('delivery_phone', 'like', $tail)
                ->orWhereHas('customer', fn ($c) => $c->where('phone', 'like', $tail)))
            ->with(['customer', 'items.product'])
            ->latest('id')
            ->limit(60)
            ->get()
            ->filter(fn (Order $order) => $this->orderMatchesPhone($order, $phone))
            ->take(20)
            ->values();
    }

    private function orderMatchesPhone(Order $order, string $normalizedPhone): bool
    {
        return in_array($normalizedPhone, array_filter([
            Customer::normalizePhone($order->customer?->phone),
            $order->delivery_phone ? Customer::normalizePhone($order->delivery_phone) : null,
        ]), true);
    }

    private function phoneOrderSummary(Order $order): array
    {
        $names = $order->items->map(fn ($item) => $item->product?->name ?? 'Product')->values();
        $status = \App\Support\OrderStatus::normalize($order->status);

        return [
            'invoice' => $order->invoice_no,
            'date' => asian_datetime($order->created_at, 'd M Y'),
            'status_label' => \App\Support\OrderStatus::customerLabel($order->status),
            'tone' => match (true) {
                \App\Support\OrderStatus::isVoid($status) => 'is-void',
                in_array($status, [\App\Support\OrderStatus::DELIVERED, \App\Support\OrderStatus::COMPLETED], true) => 'is-done',
                default => 'is-live',
            },
            'items' => $names->first().($names->count() > 1 ? ' + '.($names->count() - 1).' more' : ''),
            'total' => number_format((float) $order->total_amount, 2),
            'url' => route('website.track', ['invoice' => $order->invoice_no]),
        ];
    }

    private function rememberTrackedOrder(Request $request, string $invoice, string $level = self::TRACK_FULL): void
    {
        $tracked = (array) $request->session()->get('storefront.tracked_orders', []);
        if (($tracked[$invoice] ?? null) === self::TRACK_FULL) {
            $level = self::TRACK_FULL;
        }
        unset($tracked[$invoice]);
        $tracked[$invoice] = $level;
        $request->session()->put('storefront.tracked_orders', array_slice($tracked, -40, null, true));
    }

    private function maskName(string $name): string
    {
        return collect(preg_split('/\s+/u', trim($name)) ?: [])
            ->filter()
            ->map(fn ($part) => mb_substr($part, 0, 1).str_repeat('•', max(2, min(6, mb_strlen($part) - 1))))
            ->implode(' ') ?: '—';
    }

    private function maskAddress(string $address): string
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $address))));
        if ($parts === []) {
            return '—';
        }

        return count($parts) > 1
            ? '•••••, '.end($parts)
            : mb_substr($parts[0], 0, 3).'•••••';
    }

    public function wishlist()
    {
        return view('website.wishlist', $this->website->homepageData());
    }

    /**
     * Re-price and stock-cap the storefront cart from the live catalog.
     * Client may only send product ids + quantities — never trusted prices.
     */
    public function syncCart(Request $request)
    {
        $shopId = $this->website->shopId();
        if (! $shopId) {
            return response()->json(['items' => [], 'subtotal' => 0, 'warnings' => ['Store unavailable.']], 404);
        }

        $rawItems = collect((array) $request->input('items', $request->input('cart', [])));
        $requested = $rawItems
            ->map(function ($item) {
                return [
                    'id' => (int) (is_array($item) ? ($item['id'] ?? 0) : 0),
                    'qty' => max(1, (int) (is_array($item) ? ($item['qty'] ?? 1) : 1)),
                ];
            })
            ->filter(fn (array $item) => $item['id'] > 0)
            ->values();

        if ($requested->isEmpty()) {
            $this->recordCart($request, $shopId, [], 0);

            return response()->json(['items' => [], 'subtotal' => 0.0, 'warnings' => []]);
        }

        $products = Product::query()
            ->where('shop_id', $shopId)
            ->whereIn('id', $requested->pluck('id')->all())
            ->get()
            ->keyBy('id');

        $lines = [];
        $subtotal = 0.0;
        $warnings = [];

        foreach ($requested as $item) {
            $product = $products->get($item['id']);
            if (! $product || $product->is_published === false) {
                $warnings[] = 'A product was removed because it is no longer available.';
                continue;
            }

            $stock = max(0, (int) $product->availableStock());
            if ($stock < 1) {
                $warnings[] = $product->storefrontDisplayName().' is out of stock and was removed.';
                continue;
            }

            $qty = min($item['qty'], $stock);
            if ($qty < $item['qty']) {
                $warnings[] = $product->storefrontDisplayName().' quantity was limited to '.$stock.' available.';
            }

            $unitPrice = (float) $product->currentPrice();
            $lines[] = [
                'id' => $product->id,
                'name' => $product->storefrontDisplayName(),
                'price' => $unitPrice,
                'image' => $this->website->productImageUrl($product),
                'qty' => $qty,
                'stock' => $stock,
            ];
            $subtotal += $unitPrice * $qty;
        }

        $this->recordCart($request, $shopId, $lines, $subtotal);

        return response()->json([
            'items' => $lines,
            'subtotal' => round($subtotal),
            'warnings' => array_values(array_unique($warnings)),
        ]);
    }

    public function checkout(Request $request)
    {
        $user = $request->user();
        $customerUser = $user?->isStorefrontCustomer() ? $user : null;

        $shopId = $this->website->shopId();
        if (! $shopId || empty($request->cart)) {
            return response()->json(['success' => false, 'message' => 'Cart is empty or store unavailable.']);
        }

        $request->validate([
            'customer_name' => 'required|string|min:2|max:255',
            'customer_phone' => 'required|string|min:8|max:20',
            'customer_address' => 'required|string|max:1000',
            'delivery_zone' => ['nullable', 'string', \Illuminate\Validation\Rule::in($this->delivery->zoneCodes($shopId))],
            'payment_method' => ['nullable', 'string', \Illuminate\Validation\Rule::in($this->delivery->allowedPaymentMethods())],
            'payment_reference' => 'nullable|required_if:payment_method,confirmation_charge|string|min:4|max:100',
        ], [
            'customer_address.required' => 'Delivery address is required to place your order.',
            'payment_reference.required_if' => 'Enter the Transaction ID of your confirmation payment.',
        ]);

        try {
            $result = $this->orders->place($shopId, [
                'name' => $request->customer_name,
                'phone' => $request->customer_phone,
                'address' => $request->customer_address,
            ], (array) $request->cart, $customerUser, [
                'zone' => $request->input('delivery_zone'),
                'payment_method' => $request->input('payment_method'),
                'payment_reference' => $request->input('payment_reference'),
                'attribution' => $this->attribution->orderAttributes($request, $shopId),
                'context' => ['cart_token' => $this->carts->token($request)],
            ]);
        } catch (OrderCreationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Order failed: '.$e->getMessage()]);
        }

        $order = $result['order'];
        $quote = $result['quote'];
        $this->rememberTrackedOrder($request, $order->invoice_no);

        return response()->json([
            'success' => true,
            'guest' => $customerUser === null,
            'order_id' => $order->id,
            'invoice' => $order->invoice_no,
            'track_url' => route('website.track', ['invoice' => $order->invoice_no]),
            'delivery_fee' => $quote['delivery_fee'],
            'grand_total' => $quote['grand_total'],
            'payment_method' => $quote['payment_method'],
            'amount_paid_now' => $quote['amount_paid_now'],
            'amount_due_later' => $quote['amount_due_later'],
            'message' => $result['message'],
        ]);
    }

    /** Cart follow-up tracking must never break the storefront cart. */
    private function recordCart(Request $request, int $shopId, array $lines, float $subtotal): void
    {
        try {
            $this->carts->record($request, $shopId, $lines, $subtotal, (array) $request->input('contact', []));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
