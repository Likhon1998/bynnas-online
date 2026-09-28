<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $shopId = $user->shop_id;

        $query = Customer::where('shop_id', $shopId)->withCount('orders');

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

        return view('customers.index', compact('customers', 'onlineCount', 'offlineCount'));
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