<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\AccountService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StockAdjustmentController extends Controller
{
    use ShopScoped;

    public function __construct(
        protected StockService $stock,
        protected AccountService $accounts,
    ) {}

    public function index(Request $request)
    {
        $products = Product::where('shop_id', $this->shopId())->orderBy('name')->get();

        $query = StockMovement::where('shop_id', $this->shopId())
            ->with(['product', 'user', 'location']);

        // Optional focus filters — default shows EVERYTHING including sales & transfers
        $direction = $request->input('direction'); // in|out|all
        if ($direction === 'in') {
            $query->where('type', 'in');
        } elseif ($direction === 'out') {
            $query->whereIn('type', ['out', 'sale']);
        }

        $source = $request->input('source'); // sale|transfer|adjustment|purchase|damage|other
        if ($source === 'sale') {
            $query->where(function ($q) {
                $q->where('type', 'sale')->orWhere('reason', 'sale');
            });
        } elseif ($source === 'transfer') {
            $query->where('reason', 'stock_transfer');
        } elseif ($source === 'adjustment') {
            $query->where('reason', 'adjustment');
        } elseif ($source === 'purchase') {
            $query->whereIn('reason', ['purchase_receive', 'purchase_return']);
        } elseif ($source === 'damage') {
            $query->where('reason', 'damage');
        } elseif ($source === 'opening') {
            $query->where('reason', 'opening_inventory');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $movements = $query->latest('id')->paginate(20)->appends($request->query());

        if ($request->ajax()) {
            return view('supply.adjustments.partials.results', compact('movements'));
        }

        return view('supply.adjustments.index', compact('products', 'movements'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('shop_id', $this->shopId())),
            ],
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reference' => 'required|string|max:255',
        ]);

        $product = Product::where('shop_id', $this->shopId())->findOrFail($request->product_id);

        try {
            $this->stock->transaction(function () use ($request, $product) {
                $this->accounts->ensureShopAccounts($this->shopId());

                $movement = $this->stock->apply(
                    $product,
                    $request->type,
                    (int) $request->quantity,
                    $request->reference,
                    'adjustment',
                    Auth::id(),
                    'stock_adjustment',
                    null,
                    $this->stock->defaultStore($this->shopId())?->id,
                );

                $this->accounts->postInventoryAdjustment($movement);
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('supply.adjustments.index')->with('success', 'Stock adjustment saved and synced to POS, web store, and accounts.');
    }
}
