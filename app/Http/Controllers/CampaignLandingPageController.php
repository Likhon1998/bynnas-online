<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CmsReview;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\CampaignAttributionService;
use App\Services\DeliveryChargeService;
use App\Services\OrderCreationException;
use App\Services\OrderCreationService;
use App\Services\WebsiteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CampaignLandingPageController extends Controller
{
    public function __construct(
        private WebsiteService $website,
        private OrderCreationService $orders,
        private CampaignAttributionService $attribution,
        private DeliveryChargeService $delivery,
    ) {}

    public function index()
    {
        $shopId = (int) Auth::user()->shop_id;
        $pages = LandingPage::where('shop_id', $shopId)->with('campaign')->latest('id')->paginate(20);

        $orderStats = Order::whereIn('landing_page_id', $pages->pluck('id'))
            ->groupBy('landing_page_id')
            ->select(
                'landing_page_id',
                DB::raw('COUNT(*) as orders'),
                DB::raw("SUM(CASE WHEN status IN ('".implode("','", Campaign::VOID_ORDER_STATUSES)."') THEN 0 ELSE total_amount END) as revenue")
            )
            ->get()->keyBy('landing_page_id');

        return view('landing-pages.index', compact('pages', 'orderStats'));
    }

    public function create()
    {
        return view('landing-pages.create', array_merge($this->formData(), ['page' => new LandingPage([
            'status' => 'draft', 'show_reviews' => true, 'cta_text' => 'Order now', 'accent_color' => '#4f46e5',
            'benefits' => [
                ['title' => 'Cash on delivery', 'text' => 'Pay when the parcel reaches you.'],
                ['title' => 'Fast delivery', 'text' => 'Delivered to your door across the country.'],
                ['title' => 'Easy returns', 'text' => 'Not right? Tell us and we will sort it out.'],
            ],
        ])]));
    }

    public function store(Request $request)
    {
        $shopId = (int) Auth::user()->shop_id;
        $page = new LandingPage(['shop_id' => $shopId, 'created_by' => Auth::id()]);
        $page->fill($this->validated($request, $shopId, $page))->save();

        return redirect()->route('landing-pages.edit', $page)->with('success', 'Landing page saved.');
    }

    public function edit(LandingPage $landingPage)
    {
        $this->authorizeShop($landingPage);

        return view('landing-pages.edit', array_merge($this->formData(), ['page' => $landingPage]));
    }

    public function update(Request $request, LandingPage $landingPage)
    {
        $this->authorizeShop($landingPage);
        $landingPage->fill($this->validated($request, (int) $landingPage->shop_id, $landingPage))->save();

        return redirect()->route('landing-pages.edit', $landingPage)->with('success', 'Landing page updated.');
    }

    public function destroy(LandingPage $landingPage)
    {
        $this->authorizeShop($landingPage);
        if ($landingPage->hero_image) {
            Storage::disk('public')->delete($landingPage->hero_image);
        }
        $landingPage->delete();

        return redirect()->route('landing-pages.index')->with('success', 'Landing page deleted. Its orders are kept.');
    }

    /** Public page: /campaign/{slug}. Staff can preview drafts. */
    public function show(Request $request, string $slug)
    {
        $page = $this->findPublic($slug, allowDraft: Auth::guard('admin')->check());
        $isPreview = ! $page->isPublished();

        $seenKey = 'bs_lp_viewed.'.$page->id;
        if (! $isPreview && ! $this->attribution->isBot($request) && ! $request->session()->has($seenKey)) {
            $request->session()->put($seenKey, true);
            $page->increment('views');
        }

        $families = $page->productFamilies();
        $productIds = $families->flatten()->pluck('id');
        $reviews = collect();
        if ($page->show_reviews) {
            $reviews = CmsReview::where('shop_id', $page->shop_id)->where('is_published', true)
                ->where(fn ($q) => $q->whereIn('product_id', $productIds)->orWhere('is_featured', true))
                ->orderByRaw('CASE WHEN product_id IS NULL THEN 1 ELSE 0 END')
                ->orderBy('sort_order')->limit(6)->get();
        }

        return view('website.landing', [
            'page' => $page,
            'families' => $families,
            'reviews' => $reviews,
            'isPreview' => $isPreview,
            'settings' => SiteSetting::current(),
            'deliveryConfig' => $this->delivery->publicConfig(null, (int) $page->shop_id),
            'ws' => $this->website,
        ]);
    }

    /** Guest one-page order from a landing page — goes through the shared OrderCreationService. */
    public function order(Request $request, string $slug)
    {
        $page = $this->findPublic($slug);

        $data = $request->validate([
            'product_id' => 'required|integer',
            'qty' => 'required|integer|min:1|max:20',
            'customer_name' => 'required|string|min:2|max:120',
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{8,20}$/'],
            'customer_address' => 'required|string|min:5|max:1000',
            'delivery_zone' => ['nullable', 'string', Rule::in($this->delivery->zoneCodes((int) $page->shop_id))],
            'payment_method' => ['nullable', 'string', Rule::in($this->delivery->allowedPaymentMethods())],
            'note' => 'nullable|string|max:500',
            'website' => 'nullable|size:0',
        ], [
            'customer_phone.regex' => 'Enter a valid phone number.',
            'customer_address.required' => 'Delivery address is required.',
            'website.size' => 'Please try again.',
        ]);

        $allowedIds = $page->productFamilies()->flatten()->pluck('id')->map(fn ($id) => (int) $id);
        if (! $allowedIds->contains((int) $data['product_id'])) {
            return $this->orderFailed($request, 'Please choose a product from this offer.');
        }

        $attribution = $this->attribution->orderAttributes($request, (int) $page->shop_id);
        if ($attribution === [] && $page->campaign && (int) $page->campaign->shop_id === (int) $page->shop_id) {
            $attribution = [
                'campaign_id' => $page->campaign->id,
                'utm_source' => $page->campaign->source,
                'utm_medium' => $page->campaign->medium,
                'utm_campaign' => $page->campaign->utm_campaign,
                'landing_page' => '/campaign/'.$page->slug,
            ];
        }

        $user = $request->user();
        try {
            $result = $this->orders->place((int) $page->shop_id, [
                'name' => $data['customer_name'],
                'phone' => $data['customer_phone'],
                'address' => $data['customer_address'],
            ], [['id' => $data['product_id'], 'qty' => $data['qty']]], $user?->isStorefrontCustomer() ? $user : null, [
                'zone' => $data['delivery_zone'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'note' => $data['note'] ?? null,
                'attribution' => $attribution,
                'landing_page_id' => $page->id,
            ]);
        } catch (OrderCreationException $e) {
            return $this->orderFailed($request, $e->getMessage());
        }

        $order = $result['order'];
        $summary = [
            'invoice' => $order->invoice_no,
            'total' => (float) $order->total_amount,
            'message' => $result['message'],
        ];

        if ($request->expectsJson()) {
            return response()->json(['success' => true] + $summary);
        }

        return redirect()->to(route('website.landing', $page->slug).'#order')->with('landing_order', $summary);
    }

    private function orderFailed(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return redirect()->to(route('website.landing', $request->route('slug')).'#order')
            ->withInput()->withErrors(['order' => $message]);
    }

    private function findPublic(string $slug, bool $allowDraft = false): LandingPage
    {
        $shopId = $this->website->shopId();
        abort_unless($shopId, 404);

        return LandingPage::where('shop_id', $shopId)->where('slug', $slug)
            ->when(! $allowDraft, fn ($q) => $q->where('status', 'published'))
            ->with('campaign')
            ->firstOrFail();
    }

    private function validated(Request $request, int $shopId, LandingPage $page): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:160',
            'slug' => 'nullable|string|max:160|alpha_dash',
            'status' => ['required', Rule::in(array_keys(LandingPage::STATUSES))],
            'campaign_id' => ['nullable', Rule::exists('campaigns', 'id')->where('shop_id', $shopId)],
            'headline' => 'required|string|max:200',
            'subheadline' => 'nullable|string|max:500',
            'hero' => 'nullable|image|max:4096',
            'remove_hero' => 'nullable|boolean',
            'offer_badge' => 'nullable|string|max:60',
            'offer_text' => 'nullable|string|max:500',
            'countdown_ends_at' => 'nullable|date',
            'product_ids' => 'nullable|array|max:12',
            'product_ids.*' => ['integer', Rule::exists('products', 'id')->where('shop_id', $shopId)],
            'benefits' => 'nullable|array|max:8',
            'benefits.*.title' => 'nullable|string|max:80',
            'benefits.*.text' => 'nullable|string|max:200',
            'faqs' => 'nullable|array|max:12',
            'faqs.*.q' => 'nullable|string|max:200',
            'faqs.*.a' => 'nullable|string|max:1000',
            'show_reviews' => 'nullable|boolean',
            'cta_text' => 'nullable|string|max:60',
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'seo_title' => 'nullable|string|max:160',
            'seo_description' => 'nullable|string|max:300',
        ]);

        if ($data['status'] === 'published' && empty($data['product_ids'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['product_ids' => 'Choose at least one product before publishing.']);
        }

        if ($request->hasFile('hero')) {
            if ($page->hero_image) {
                Storage::disk('public')->delete($page->hero_image);
            }
            $data['hero_image'] = $request->file('hero')->store('landing-pages', 'public');
        } elseif ($request->boolean('remove_hero') && $page->hero_image) {
            Storage::disk('public')->delete($page->hero_image);
            $data['hero_image'] = null;
        }

        $data['slug'] = ($data['slug'] ?? null) ?: $data['title'];
        $data['product_ids'] = array_values(array_unique(array_map('intval', $data['product_ids'] ?? [])));
        $data['benefits'] = collect($data['benefits'] ?? [])
            ->map(fn ($b) => ['title' => trim((string) ($b['title'] ?? '')), 'text' => trim((string) ($b['text'] ?? ''))])
            ->filter(fn ($b) => $b['title'] !== '')->values()->all();
        $data['faqs'] = collect($data['faqs'] ?? [])
            ->map(fn ($f) => ['q' => trim((string) ($f['q'] ?? '')), 'a' => trim((string) ($f['a'] ?? ''))])
            ->filter(fn ($f) => $f['q'] !== '' && $f['a'] !== '')->values()->all();
        $data['show_reviews'] = $request->boolean('show_reviews');
        unset($data['hero'], $data['remove_hero']);

        return $data;
    }

    private function formData(): array
    {
        $shopId = (int) Auth::user()->shop_id;

        return [
            'products' => Product::where('shop_id', $shopId)
                ->where(fn ($q) => $q->where('is_published', true)->orWhereNull('is_published'))
                ->orderBy('name')->get(['id', 'name', 'variant_group'])
                ->unique(fn ($p) => $p->variant_group ?: 'id-'.$p->id)->values(),
            'campaigns' => Campaign::where('shop_id', $shopId)->orderByDesc('id')->get(['id', 'name', 'status']),
        ];
    }

    private function authorizeShop(LandingPage $page): void
    {
        abort_unless((int) $page->shop_id === (int) Auth::user()->shop_id, 404);
    }
}
