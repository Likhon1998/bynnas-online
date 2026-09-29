<?php

namespace App\Http\Controllers;

use App\Models\AbandonedCart;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AbandonedCartController extends Controller
{
    public function __construct(private LeadService $leads) {}

    public function index(Request $request)
    {
        $shopId = $this->shopId();
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['abandoned', 'contacted', 'recovered', 'converted', 'lost', 'all'])],
            'contact' => ['nullable', Rule::in(['with', 'without'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $filters['status'] ?? 'abandoned';

        $query = AbandonedCart::forShop($shopId)
            ->where('item_count', '>', 0)
            ->with(['customer:id,name,phone', 'campaign:id,name', 'order:id,invoice_no', 'contactedBy:id,name']);

        match ($status) {
            'abandoned' => $query->abandoned()->where('status', 'active'),
            'all' => null,
            default => $query->where('status', $status),
        };

        if (($filters['contact'] ?? null) === 'with') {
            $query->where(fn ($q) => $q->whereNotNull('phone')->orWhereNotNull('customer_id'));
        } elseif (($filters['contact'] ?? null) === 'without') {
            $query->whereNull('phone')->whereNull('customer_id');
        }

        if (! empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
        }

        $carts = $query->latest('last_activity_at')->paginate(25)->withQueryString();

        $abandoned = AbandonedCart::forShop($shopId)->abandoned();
        $since = now()->subDays(30);
        $stats = [
            'abandoned' => (clone $abandoned)->where('status', 'active')->count(),
            'abandoned_value' => (float) (clone $abandoned)->sum('subtotal'),
            'contacted' => AbandonedCart::forShop($shopId)->where('status', 'contacted')->count(),
            'recovered_30d' => AbandonedCart::forShop($shopId)->where('status', 'recovered')->where('recovered_at', '>=', $since)->count(),
            'recovered_value_30d' => (float) AbandonedCart::forShop($shopId)->where('status', 'recovered')->where('recovered_at', '>=', $since)->sum('subtotal'),
        ];

        return view('abandoned-carts.index', [
            'carts' => $carts,
            'stats' => $stats,
            'status' => $status,
            'filters' => $filters,
            'idleMinutes' => AbandonedCart::idleMinutes(),
        ]);
    }

    public function show(AbandonedCart $abandonedCart)
    {
        $this->authorizeCart($abandonedCart);
        $abandonedCart->load(['customer', 'campaign:id,name', 'order:id,invoice_no,status,total_amount', 'contactedBy:id,name', 'user:id,name,email']);

        $lead = $abandonedCart->phone
            ? $this->leads->findOpenByPhone($abandonedCart->shop_id, $abandonedCart->phone)
            : null;

        return view('abandoned-carts.show', ['cart' => $abandonedCart, 'openLead' => $lead]);
    }

    public function updateStatus(Request $request, AbandonedCart $abandonedCart)
    {
        $this->authorizeCart($abandonedCart);
        $data = $request->validate([
            'status' => ['required', Rule::in(['contacted', 'lost', 'active'])],
            'notes' => 'nullable|string|max:2000',
        ]);

        if (in_array($abandonedCart->status, ['recovered', 'converted'], true)) {
            return back()->with('error', 'This cart already became an order.');
        }

        $updates = ['status' => $data['status']];
        if ($data['status'] === 'contacted') {
            $updates['contacted_at'] = now();
            $updates['contacted_by'] = Auth::id();
        }
        if (filled($data['notes'] ?? null)) {
            $stamp = now()->format('d M H:i').' · '.Auth::user()->name.': ';
            $updates['notes'] = trim(($abandonedCart->notes ? $abandonedCart->notes."\n" : '').$stamp.trim($data['notes']));
        }
        $abandonedCart->update($updates);

        return back()->with('success', 'Cart marked as '.strtolower(AbandonedCart::STATUSES[$data['status']]).'.');
    }

    /** Start a CRM lead from a cart that has a phone number (or reuse the open lead for that phone). */
    public function createLead(AbandonedCart $abandonedCart)
    {
        $this->authorizeCart($abandonedCart);
        $phone = $abandonedCart->phone ?: $abandonedCart->customer?->phone;
        if (! $phone) {
            return back()->with('error', 'This cart has no phone number to follow up.');
        }

        $existing = $this->leads->findOpenByPhone($abandonedCart->shop_id, $phone);
        if ($existing) {
            return redirect()->route('leads.show', $existing)->with('success', 'An open lead already exists for this phone.');
        }

        $items = collect($abandonedCart->items ?? []);
        $summary = $items->map(fn ($i) => ($i['qty'] ?? 1).' × '.($i['name'] ?? 'Item'))->implode(', ');

        $lead = $this->leads->create($abandonedCart->shop_id, [
            'name' => $abandonedCart->name ?: ($abandonedCart->customer?->name ?: 'Website shopper'),
            'phone' => $phone,
            'source' => 'website',
            'campaign_id' => $abandonedCart->campaign_id,
            'product_id' => (int) ($items->first()['id'] ?? 0) ?: null,
            'customer_id' => $abandonedCart->customer_id,
            'notes' => 'Abandoned cart (Tk '.number_format((float) $abandonedCart->subtotal, 0).'): '.$summary,
            'assigned_to' => Auth::id(),
        ], Auth::user());

        return redirect()->route('leads.show', $lead)->with('success', 'Lead created from abandoned cart.');
    }

    private function authorizeCart(AbandonedCart $cart): void
    {
        abort_unless((int) $cart->shop_id === $this->shopId(), 404);
    }

    private function shopId(): int
    {
        return (int) Auth::user()->shop_id;
    }
}
