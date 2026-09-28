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

class OpeningInventoryController extends Controller
{
    use ShopScoped;

    public function __construct(
        protected StockService $stock,
        protected AccountService $accounts,
    ) {}

    public function index()
    {
        $this->stock->ensureDefaultLocations($this->shopId());

        $openedProductIds = $this->openedProductIds();

        $products = Product::where('shop_id', $this->shopId())
            ->whereNotIn('id', $openedProductIds)
            ->where('stock_quantity', 0)
            ->orderBy('name')
            ->get();

        $openedRecords = StockMovement::where('shop_id', $this->shopId())
            ->where(function ($q) {
                $q->where('document_type', 'opening_inventory')
                    ->orWhere('reason', 'opening_inventory');
            })
            ->with('product:id,name,stock_quantity')
            ->orderByDesc('created_at')
            ->get();

        return view('supply.opening-inventory.index', compact('products', 'openedRecords'));
    }

    public function store(Request $request)
    {
        $shopId = $this->shopId();

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('shop_id', $shopId)),
            ],
            'items.*.quantity' => 'nullable|integer|min:0',
        ]);

        $this->stock->ensureDefaultLocations($shopId);

        $updated = 0;
        try {
            $this->stock->transaction(function () use ($request, $shopId, &$updated) {
                foreach ($request->items as $row) {
                    $quantity = (int) ($row['quantity'] ?? 0);
                    if ($quantity < 1) {
                        continue;
                    }

                    $product = Product::where('shop_id', $shopId)->findOrFail($row['product_id']);

                    if ($this->stock->hasOpeningInventory($product)) {
                        continue;
                    }

                    $movement = $this->stock->setOpeningStock($product, $quantity, Auth::id());
                    $this->accounts->postOpeningInventory($movement);
                    $updated++;
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        if ($updated === 0) {
            return redirect()->route('supply.opening-inventory.index')
                ->with('error', 'No opening stock was saved. Enter a quantity greater than 0 for products that still need opening inventory.');
        }

        return redirect()->route('supply.opening-inventory.index')
            ->with('success', "Opening inventory saved. {$updated} product(s) set. Use Stock Adjustment for later changes.");
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    private function openedProductIds()
    {
        return StockMovement::where('shop_id', $this->shopId())
            ->where(function ($q) {
                $q->where('document_type', 'opening_inventory')
                    ->orWhere('reason', 'opening_inventory');
            })
            ->pluck('product_id')
            ->unique()
            ->values();
    }
}
