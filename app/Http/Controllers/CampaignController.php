<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignVisit;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\CampaignAttributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    public function __construct(private CampaignAttributionService $attribution) {}

    public function index(Request $request)
    {
        $shopId = (int) Auth::user()->shop_id;

        $campaigns = Campaign::where('shop_id', $shopId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->source))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($w) use ($request) {
                $w->where('name', 'like', '%'.$request->q.'%')->orWhere('utm_campaign', 'like', '%'.$request->q.'%');
            }))
            ->with(['product', 'category'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stats = $this->attribution->stats($campaigns->pluck('id'));
        $totals = [
            'active' => Campaign::where('shop_id', $shopId)->where('status', 'active')->count(),
            'clicks' => (int) CampaignVisit::whereHas('campaign', fn ($q) => $q->where('shop_id', $shopId))->where('created_at', '>=', now()->subDays(30))->count(),
            'orders' => (int) Order::where('shop_id', $shopId)->whereNotNull('campaign_id')->where('created_at', '>=', now()->subDays(30))->count(),
            'revenue' => (float) Order::where('shop_id', $shopId)->whereNotNull('campaign_id')->where('created_at', '>=', now()->subDays(30))
                ->whereNotIn('status', Campaign::VOID_ORDER_STATUSES)->sum('total_amount'),
        ];
        $sources = $this->attribution->sourceBreakdown($shopId, now()->subDays(30));

        return view('campaigns.index', compact('campaigns', 'stats', 'totals', 'sources'));
    }

    public function create()
    {
        return view('campaigns.create', array_merge($this->formData(), ['campaign' => new Campaign([
            'status' => 'active', 'landing_type' => 'home', 'source' => 'facebook', 'medium' => 'paid_social',
        ])]));
    }

    public function store(Request $request)
    {
        $shopId = (int) Auth::user()->shop_id;
        $data = $this->validated($request, $shopId);
        $campaign = Campaign::create(array_merge($data, ['shop_id' => $shopId, 'created_by' => Auth::id()]));

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign created. Share the tracking link below.');
    }

    public function show(Campaign $campaign)
    {
        $this->authorizeShop($campaign);
        $campaign->load(['product', 'category', 'creator']);

        $stats = $this->attribution->stats([$campaign->id])[$campaign->id];
        $from = now()->subDays(13)->startOfDay();

        $clicksByDay = CampaignVisit::where('campaign_id', $campaign->id)->where('created_at', '>=', $from)
            ->groupBy(DB::raw('DATE(created_at)'))->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->pluck('total', 'day');
        $ordersByDay = Order::where('campaign_id', $campaign->id)->where('created_at', '>=', $from)
            ->groupBy(DB::raw('DATE(created_at)'))->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->pluck('total', 'day');
        $days = collect(range(13, 0))->map(function ($ago) use ($clicksByDay, $ordersByDay) {
            $day = now()->subDays($ago)->toDateString();

            return ['day' => $day, 'clicks' => (int) ($clicksByDay[$day] ?? 0), 'orders' => (int) ($ordersByDay[$day] ?? 0)];
        });

        $placementClicks = CampaignVisit::where('campaign_id', $campaign->id)
            ->groupBy('utm_content')->select('utm_content', DB::raw('COUNT(*) as clicks'))->pluck('clicks', 'utm_content');
        $placementOrders = Order::where('campaign_id', $campaign->id)
            ->groupBy('utm_content')->select('utm_content', DB::raw('COUNT(*) as orders'))->pluck('orders', 'utm_content');
        $placements = $placementClicks->keys()->merge($placementOrders->keys())->unique()
            ->map(fn ($key) => ['label' => $key ?: '(main link)', 'clicks' => (int) ($placementClicks[$key] ?? 0), 'orders' => (int) ($placementOrders[$key] ?? 0)])
            ->sortByDesc('clicks')->values();

        $orders = Order::where('campaign_id', $campaign->id)->with('customer')->latest('id')->take(20)->get();

        return view('campaigns.show', compact('campaign', 'stats', 'days', 'placements', 'orders'));
    }

    public function edit(Campaign $campaign)
    {
        $this->authorizeShop($campaign);

        return view('campaigns.edit', array_merge($this->formData(), compact('campaign')));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $this->authorizeShop($campaign);
        $campaign->update($this->validated($request, (int) $campaign->shop_id, $campaign));

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign updated.');
    }

    public function destroy(Campaign $campaign)
    {
        $this->authorizeShop($campaign);
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted. Its orders keep their source details.');
    }

    /** Public tracking link: /go/{code}. */
    public function go(Request $request, string $code)
    {
        $campaign = Campaign::where('code', Str::lower($code))->first();
        if (! $campaign) {
            return redirect()->route('home');
        }

        return redirect()->away($this->attribution->trackClick($campaign, $request), 302)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('X-Robots-Tag', 'noindex');
    }

    private function validated(Request $request, int $shopId, ?Campaign $campaign = null): array
    {
        $request->merge([
            'utm_campaign' => Str::slug((string) ($request->input('utm_campaign') ?: $request->input('name')), '_'),
        ]);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'source' => ['required', Rule::in(array_keys(Campaign::SOURCES))],
            'medium' => ['required', Rule::in(array_keys(Campaign::MEDIUMS))],
            'utm_campaign' => [
                'required', 'string', 'max:120',
                Rule::unique('campaigns', 'utm_campaign')->where('shop_id', $shopId)->ignore($campaign?->id),
            ],
            'landing_type' => ['required', Rule::in(array_keys(Campaign::LANDING_TYPES))],
            'product_id' => ['nullable', 'required_if:landing_type,product', Rule::exists('products', 'id')->where('shop_id', $shopId)],
            'category_id' => ['nullable', 'required_if:landing_type,category', Rule::exists('categories', 'id')->where('shop_id', $shopId)],
            'landing_url' => 'nullable|required_if:landing_type,url|string|max:500',
            'status' => ['required', Rule::in(array_keys(Campaign::STATUSES))],
            'starts_on' => 'nullable|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
            'spend' => 'nullable|numeric|min:0|max:99999999',
            'notes' => 'nullable|string|max:2000',
        ], [
            'utm_campaign.unique' => 'Another campaign already uses this campaign tag.',
            'product_id.required_if' => 'Choose the product this campaign links to.',
            'category_id.required_if' => 'Choose the category this campaign links to.',
            'landing_url.required_if' => 'Enter the page path, e.g. /shop?filter=deals.',
        ]);

        if ($data['landing_type'] === 'url') {
            $path = Campaign::normalizePath($data['landing_url']);
            if ($path === '/' && ! in_array(trim((string) $data['landing_url']), ['/', ''], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['landing_url' => 'Use a page on this website, e.g. /shop?filter=deals.']);
            }
            $data['landing_url'] = $path;
        } else {
            $data['landing_url'] = null;
        }
        if ($data['landing_type'] !== 'product') {
            $data['product_id'] = null;
        }
        if ($data['landing_type'] !== 'category') {
            $data['category_id'] = null;
        }

        return $data;
    }

    private function formData(): array
    {
        $shopId = (int) Auth::user()->shop_id;

        $products = Product::where('shop_id', $shopId)
            ->where(fn ($q) => $q->where('is_published', true)->orWhereNull('is_published'))
            ->orderBy('name')
            ->get(['id', 'name', 'variant_group', 'slug'])
            ->unique(fn ($p) => $p->variant_group ?: 'id-'.$p->id)
            ->values();

        return [
            'products' => $products,
            'categories' => Category::where('shop_id', $shopId)->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function authorizeShop(Campaign $campaign): void
    {
        abort_unless((int) $campaign->shop_id === (int) Auth::user()->shop_id, 404);
    }
}
