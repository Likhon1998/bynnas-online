<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Product;
use App\Models\User;
use App\Services\DeliveryChargeService;
use App\Services\OrderCreationException;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function __construct(
        private LeadService $leads,
        private DeliveryChargeService $delivery,
    ) {}

    public function index(Request $request)
    {
        $shopId = $this->shopId();
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_merge(array_keys(Lead::STATUSES), ['open', 'due']))],
            'source' => ['nullable', Rule::in(array_keys(Lead::SOURCES))],
            'assigned' => ['nullable', 'string', 'max:20'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Lead::forShop($shopId)->with(['assignee:id,name', 'product:id,name', 'campaign:id,name', 'order:id,invoice_no']);

        match ($filters['status'] ?? null) {
            null => null,
            'open' => $query->open(),
            'due' => $query->followUpDue(),
            default => $query->where('status', $filters['status']),
        };

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        $assigned = $filters['assigned'] ?? null;
        if ($assigned === 'me') {
            $query->where('assigned_to', Auth::id());
        } elseif ($assigned === 'none') {
            $query->whereNull('assigned_to');
        } elseif ($assigned && ctype_digit($assigned)) {
            $query->where('assigned_to', (int) $assigned);
        }

        if (! empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('conversation_ref', 'like', "%{$term}%"));
        }

        $leads = $query
            ->orderByRaw("CASE WHEN status IN ('converted','lost') THEN 1 ELSE 0 END")
            ->orderByRaw('CASE WHEN follow_up_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('follow_up_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $counts = Lead::forShop($shopId)->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $monthStart = now()->startOfMonth();
        $newThisMonth = Lead::forShop($shopId)->where('created_at', '>=', $monthStart)->count();
        $convertedThisMonth = Lead::forShop($shopId)->where('status', 'converted')->where('converted_at', '>=', $monthStart)->count();

        $stats = [
            'open' => (int) $counts->only(['new', 'contacted', 'interested', 'follow_up'])->sum(),
            'new' => (int) ($counts['new'] ?? 0),
            'due' => Lead::forShop($shopId)->followUpDue()->count(),
            'converted_month' => $convertedThisMonth,
            'conversion_rate' => $newThisMonth > 0 ? round($convertedThisMonth / $newThisMonth * 100, 1) : null,
        ];

        return view('leads.index', [
            'leads' => $leads,
            'counts' => $counts,
            'stats' => $stats,
            'filters' => $filters,
            'staff' => $this->staff($shopId),
        ]);
    }

    public function create(Request $request)
    {
        $lead = new Lead([
            'source' => $request->query('source', 'facebook'),
            'status' => 'new',
            'name' => $request->query('name'),
            'phone' => $request->query('phone'),
        ]);

        return view('leads.form', $this->formData($lead));
    }

    public function store(Request $request)
    {
        $shopId = $this->shopId();
        $data = $this->validated($request, $shopId, creating: true);

        $lead = $this->leads->create($shopId, $data, Auth::user());

        return redirect()->route('leads.show', $lead)->with('success', 'Lead added.');
    }

    public function show(Lead $lead)
    {
        $this->authorizeLead($lead);
        $shopId = $lead->shop_id;

        $lead->load([
            'assignee:id,name', 'creator:id,name', 'product', 'campaign:id,name', 'landingPage:id,title,slug',
            'customer', 'order:id,invoice_no,status,total_amount,created_at', 'activities.user:id,name',
        ]);

        $orders = $lead->customer
            ? $lead->customer->orders()->latest('id')->limit(10)->get(['id', 'invoice_no', 'status', 'total_amount', 'created_at', 'lead_id'])
            : collect();

        $settings = \App\Models\SiteSetting::where('shop_id', $shopId)->first();
        $deliveryConfig = $this->delivery->publicConfig($settings, $shopId);

        return view('leads.show', [
            'lead' => $lead,
            'orders' => $orders,
            'staff' => $this->staff($shopId),
            'products' => $this->products($shopId),
            'deliveryConfig' => $deliveryConfig,
            'paymentMethods' => $this->delivery->allowedPaymentMethods(),
        ]);
    }

    public function edit(Lead $lead)
    {
        $this->authorizeLead($lead);

        return view('leads.form', $this->formData($lead));
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);
        $data = $this->validated($request, $lead->shop_id, creating: false);

        $assignee = array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $lead->assigned_to;
        unset($data['assigned_to']);

        $lead->update($data);
        $this->leads->assign($lead, $assignee ? (int) $assignee : null, Auth::user());

        return redirect()->route('leads.show', $lead)->with('success', 'Lead updated.');
    }

    public function destroy(Lead $lead)
    {
        $this->authorizeLead($lead);
        abort_unless(Auth::user()->isAdminUser(), 403, 'Only the shop owner can delete leads.');

        $lead->delete();

        return redirect()->route('leads.index')->with('success', 'Lead deleted.');
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);
        $data = $request->validate([
            // "converted" is set only by linking a real order.
            'status' => ['required', Rule::in(['new', 'contacted', 'interested', 'follow_up', 'lost'])],
            'note' => 'nullable|string|max:1000',
            'lost_reason' => 'nullable|string|max:255',
        ]);

        if ($lead->status === 'converted') {
            return back()->with('error', 'This lead is already converted to an order.');
        }

        $this->leads->changeStatus($lead, $data['status'], $data['note'] ?? null, Auth::user(), $data['lost_reason'] ?? null);

        return back()->with('success', 'Status updated to '.Lead::STATUSES[$data['status']].'.');
    }

    public function assign(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);
        $data = $request->validate([
            'assigned_to' => ['nullable', 'integer', $this->staffRule($lead->shop_id)],
        ]);

        $this->leads->assign($lead, isset($data['assigned_to']) ? (int) $data['assigned_to'] : null, Auth::user());

        return back()->with('success', 'Assignment saved.');
    }

    public function logActivity(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);
        $data = $request->validate([
            'type' => ['required', Rule::in(['note', 'call', 'message'])],
            'body' => 'nullable|string|max:2000|required_without:follow_up_at',
            'follow_up_at' => 'nullable|date|after:now',
        ]);

        $this->leads->logContact($lead, $data['type'], $data['body'] ?? null, $data['follow_up_at'] ?? null, Auth::user());

        return back()->with('success', LeadActivity::TYPES[$data['type']].' saved.');
    }

    public function convertToCustomer(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);
        $data = $request->validate(['address' => 'nullable|string|max:1000']);

        try {
            $customer = $this->leads->convertToCustomer($lead, $data['address'] ?? null, Auth::user());
        } catch (OrderCreationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Linked to customer '.$customer->name.'.');
    }

    public function convertToOrder(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);
        $shopId = $lead->shop_id;

        $data = $request->validate([
            'items' => 'required|array|min:1|max:20',
            'items.*.id' => ['required', 'integer', Rule::exists('products', 'id')->where('shop_id', $shopId)],
            'items.*.qty' => 'required|integer|min:1|max:999',
            'address' => 'required|string|min:5|max:1000',
            'delivery_zone' => ['nullable', 'string', Rule::in($this->delivery->zoneCodes($shopId))],
            'payment_method' => ['nullable', 'string', Rule::in($this->delivery->allowedPaymentMethods())],
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $order = $this->leads->convertToOrder($lead, array_values($data['items']), [
                'address' => $data['address'],
                'zone' => $data['delivery_zone'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'note' => $data['note'] ?? null,
            ], Auth::user());
        } catch (OrderCreationException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('online-orders.show', $order)
            ->with('success', "Order {$order->invoice_no} created from lead {$lead->name}.");
    }

    private function validated(Request $request, int $shopId, bool $creating): array
    {
        $rules = [
            'name' => 'required|string|min:2|max:120',
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-]{8,20}$/'],
            'email' => 'nullable|email|max:160',
            'source' => ['required', Rule::in(array_keys(Lead::SOURCES))],
            'conversation_ref' => 'nullable|string|max:255',
            'campaign_id' => ['nullable', 'integer', Rule::exists('campaigns', 'id')->where('shop_id', $shopId)],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('shop_id', $shopId)],
            'landing_page_id' => ['nullable', 'integer', Rule::exists('landing_pages', 'id')->where('shop_id', $shopId)],
            'notes' => 'nullable|string|max:5000',
            'assigned_to' => ['nullable', 'integer', $this->staffRule($shopId)],
            'follow_up_at' => 'nullable|date',
        ];
        if ($creating) {
            $rules['status'] = ['nullable', Rule::in(['new', 'contacted', 'interested', 'follow_up'])];
        }

        $data = $request->validate($rules, [
            'phone.regex' => 'Enter a valid phone number.',
        ]);

        if ($creating && ! empty($data['follow_up_at']) && ($data['status'] ?? 'new') === 'new') {
            $data['status'] = 'follow_up';
        }

        return $data;
    }

    private function formData(Lead $lead): array
    {
        $shopId = $this->shopId();

        return [
            'lead' => $lead,
            'staff' => $this->staff($shopId),
            'products' => $this->products($shopId),
            'campaigns' => Campaign::where('shop_id', $shopId)->orderByDesc('id')->limit(200)->get(['id', 'name']),
            'landingPages' => LandingPage::where('shop_id', $shopId)->orderByDesc('id')->limit(200)->get(['id', 'title']),
        ];
    }

    private function staff(int $shopId)
    {
        return User::query()->staffMembers()->where('shop_id', $shopId)
            ->where(fn ($q) => $q->whereNull('is_suspended')->orWhere('is_suspended', false))
            ->orderBy('name')->get(['id', 'name']);
    }

    private function staffRule(int $shopId)
    {
        $ids = $this->staff($shopId)->pluck('id')->all();

        return Rule::in($ids);
    }

    private function products(int $shopId)
    {
        return Product::where('shop_id', $shopId)
            ->where(fn ($q) => $q->whereNull('is_published')->orWhere('is_published', true))
            ->orderBy('name')
            ->limit(1000)
            ->get();
    }

    private function authorizeLead(Lead $lead): void
    {
        abort_unless((int) $lead->shop_id === $this->shopId(), 404);
    }

    private function shopId(): int
    {
        return (int) Auth::user()->shop_id;
    }
}
