<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerSegmentService;
use App\Support\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function __construct(private CustomerSegmentService $segments) {}

    public function index()
    {
        $user = Auth::user();
        $shopId = $user->shop_id;

        $query = $this->segments->withStats(Customer::where('shop_id', $shopId))->withCount('orders');

        // Cashiers: only customers who purchased at their counter
        if (! $user->isAdminUser() && $user->counter_id) {
            $query->whereHas('orders', function ($q) use ($user) {
                $q->where('shop_id', $user->shop_id)
                    ->where('counter_id', $user->counter_id);
            });
        }

        $customers = $query->latest()->get();

        $onlineCount = $customers->whereNotNull('user_id')->count();
        $offlineCount = $customers->whereNull('user_id')->count();

        $segmentMap = $customers->mapWithKeys(fn (Customer $c) => [$c->id => $this->segments->segmentsFor($c)]);
        $segmentCounts = collect(CustomerSegmentService::SEGMENTS)
            ->map(fn ($label, $key) => $segmentMap->filter(fn ($keys) => in_array($key, $keys, true))->count());

        return view('customers.index', compact('customers', 'onlineCount', 'offlineCount', 'segmentMap', 'segmentCounts'));
    }

    public function show(Customer $customer)
    {
        $user = Auth::user();
        abort_unless((int) $customer->shop_id === (int) $user->shop_id, 404);
        if (! $user->isAdminUser() && $user->counter_id) {
            abort_unless($customer->orders()->where('counter_id', $user->counter_id)->exists(), 404);
        }

        $stats = $this->segments->stats($customer);
        $segments = $this->segments->segmentsFor($customer);

        $orders = $customer->orders()
            ->when(! $user->isAdminUser() && $user->counter_id, fn ($q) => $q->where('counter_id', $user->counter_id))
            ->latest('id')
            ->limit(50)
            ->get(['id', 'invoice_no', 'status', 'total_amount', 'delivery_charge', 'counter_id', 'created_at', 'campaign_id', 'utm_source', 'lead_id']);

        $leads = $user->can('manage leads')
            ? $customer->leads()->latest('id')->limit(20)->get()
            : collect();

        return view('customers.show', [
            'customer' => $customer,
            'stats' => $stats,
            'segments' => $segments,
            'orders' => $orders,
            'leads' => $leads,
            'statusLabels' => OrderStatus::labels(),
        ]);
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
        ]);

        Customer::create([
            'shop_id' => Auth::user()->shop_id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone ? Customer::normalizePhone($request->phone) : null,
            'address' => $request->address,
            'reward_points' => 0, // Starts at zero
        ]);

        return redirect()->route('customers.index')->with('success', 'Customer added successfully!');
    }

    public function edit(Customer $customer)
    {
        if ($customer->shop_id !== Auth::user()->shop_id) abort(403);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        if ($customer->shop_id !== Auth::user()->shop_id) abort(403);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'reward_points' => 'required|integer|min:0',
        ]);

        $customer->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone ? Customer::normalizePhone($request->phone) : null,
            'address' => $request->address,
            'reward_points' => $request->reward_points,
        ]);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully!');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->shop_id !== Auth::user()->shop_id) abort(403);
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully!');
    }
}