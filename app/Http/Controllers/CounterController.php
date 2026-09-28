<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CounterController extends Controller
{
    public function __construct(protected AccountService $accounts) {}

    public function index()
    {
        $shop = Auth::user()->shop;
        $counters = Counter::where('shop_id', $shop->id)
            ->with(['users:id,name,counter_id'])
            ->withCount(['users', 'sessions' => fn ($q) => $q->where('status', 'open')])
            ->orderBy('name')
            ->get();

        $staffWithoutCounter = User::where('shop_id', $shop->id)
            ->whereNull('counter_id')
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['Shop Owner', 'Admin']))
            ->whereNotIn('role', ['admin', 'Admin', 'shop_owner', 'Shop Owner', 'superadmin'])
            ->count();

        return view('counters.index', compact('counters', 'staffWithoutCounter'));
    }

    public function store(Request $request)
    {
        $shopId = Auth::user()->shop_id;

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('counters', 'name')->where(fn ($q) => $q->where('shop_id', $shopId)),
            ],
        ]);

        $counter = Counter::create([
            'shop_id' => $shopId,
            'name' => trim($request->name),
            'is_active' => true,
        ]);

        $this->accounts->ensureCounterCashAccount($counter);

        return redirect()->route('counters.index')->with('success', "Terminal \"{$counter->name}\" added. Assign it to staff from Staff Management.");
    }

    public function update(Request $request, Counter $counter)
    {
        if ($counter->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('counters', 'name')
                    ->where(fn ($q) => $q->where('shop_id', $counter->shop_id))
                    ->ignore($counter->id),
            ],
        ]);

        $counter->update([
            'name' => trim($request->name),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->accounts->ensureCounterCashAccount($counter);

        return redirect()->route('counters.index')->with('success', 'Terminal updated successfully.');
    }

    public function destroy(Counter $counter)
    {
        if ($counter->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        if ($counter->sessions()->where('status', 'open')->exists()) {
            return redirect()->route('counters.index')->with('error', 'Cannot delete a counter with an open cash session. Close it first.');
        }

        $assigned = $counter->users()->count();
        $counter->users()->update(['counter_id' => null]);
        $counter->delete();

        $msg = 'Terminal removed.';
        if ($assigned > 0) {
            $msg .= " {$assigned} staff member(s) are now unassigned — assign them a new counter before POS use.";
        }

        return redirect()->route('counters.index')->with('success', $msg);
    }
}
