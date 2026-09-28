<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Models\WarehouseStock;
use App\Services\AccountService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseReturnController extends Controller
{
    use ShopScoped;

    public function __construct(
        protected StockService $stock,
        protected AccountService $accounts,
    ) {}

    public function index()
    {
        $returns = PurchaseReturn::where('shop_id', $this->shopId())
            ->with(['supplier', 'purchaseOrder'])
            ->latest()
            ->paginate(15);

        return view('supply.purchase-returns.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $this->stock->ensureDefaultLocations($this->shopId());

        $suppliers = Supplier::where('shop_id', $this->shopId())->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('shop_id', $this->shopId())->orderBy('name')->get();
        $purchaseOrders = PurchaseOrder::where('shop_id', $this->shopId())
            ->whereIn('status', ['partial', 'received'])
            ->with('supplier')
            ->latest()
            ->get();
        $locations = StockLocation::where('shop_id', $this->shopId())
            ->where('is_active', true)
            ->orderByRaw("CASE type WHEN 'store' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        $warehouseQty = WarehouseStock::whereIn('location_id', $locations->where('type', 'warehouse')->pluck('id'))
            ->get()
            ->groupBy('location_id')
            ->map(fn ($rows) => $rows->pluck('quantity', 'product_id'));

        $selectedPo = null;
        $prefillRows = [];
        $returnableByProduct = [];

        if ($request->filled('purchase_order_id')) {
            $selectedPo = PurchaseOrder::where('shop_id', $this->shopId())
                ->whereIn('status', ['partial', 'received'])
                ->with(['supplier', 'items.product'])
                ->find($request->purchase_order_id);

            if ($selectedPo) {
                $returnableByProduct = $selectedPo->returnableLines();
                foreach ($returnableByProduct as $line) {
                    $prefillRows[] = [
                        'product_id' => (string) $line['product_id'],
                        'quantity' => (int) $line['returnable'],
                        'unit_cost' => (float) $line['unit_cost'],
                        'max_qty' => (int) $line['returnable'],
                        'received' => (int) $line['received'],
                        'returned' => (int) $line['returned'],
                    ];
                }
            }
        }

        return view('supply.purchase-returns.create', [
            'suppliers' => $suppliers,
            'products' => $products,
            'purchaseOrders' => $purchaseOrders,
            'locations' => $locations,
            'warehouseQty' => $warehouseQty,
            'defaultLocationId' => $this->stock->defaultStore($this->shopId())?->id,
            'selectedPo' => $selectedPo,
            'prefillRows' => $prefillRows,
            'returnableByProduct' => $returnableByProduct,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('shop_id', $this->shopId())),
            ],
            'purchase_order_id' => [
                'nullable',
                Rule::exists('purchase_orders', 'id')->where(fn ($q) => $q->where('shop_id', $this->shopId())),
            ],
            'return_location_id' => [
                'required',
                Rule::exists('stock_locations', 'id')->where(fn ($q) => $q->where('shop_id', $this->shopId())->where('is_active', true)),
            ],
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('shop_id', $this->shopId())),
            ],
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        try {
            $this->stock->transaction(function () use ($request) {
                $this->accounts->ensureShopAccounts($this->shopId());

                $location = StockLocation::where('shop_id', $this->shopId())
                    ->where('is_active', true)
                    ->findOrFail($request->return_location_id);

                $po = null;
                $returnableByProduct = [];

                if ($request->filled('purchase_order_id')) {
                    $po = PurchaseOrder::where('shop_id', $this->shopId())
                        ->whereIn('status', ['partial', 'received'])
                        ->findOrFail($request->purchase_order_id);

                    if ((int) $po->supplier_id !== (int) $request->supplier_id) {
                        throw ValidationException::withMessages([
                            'purchase_order_id' => 'Linked PO belongs to a different supplier.',
                        ]);
                    }

                    $returnableByProduct = $po->returnableLines();
                }

                // Cap linked-PO returns so you can’t return more than was received (minus prior returns)
                if ($po) {
                    $requestedByProduct = [];
                    foreach ($request->items as $item) {
                        $pid = (int) $item['product_id'];
                        $requestedByProduct[$pid] = ($requestedByProduct[$pid] ?? 0) + (int) $item['quantity'];
                    }

                    foreach ($requestedByProduct as $pid => $qty) {
                        $max = (int) ($returnableByProduct[$pid]['returnable'] ?? 0);
                        if ($qty > $max) {
                            $name = $returnableByProduct[$pid]['name'] ?? ('Product #'.$pid);
                            throw ValidationException::withMessages([
                                'items' => "Cannot return {$qty} of {$name}. Only {$max} still returnable on this PO (received minus prior returns).",
                            ]);
                        }
                    }
                }

                $total = 0;
                $return = PurchaseReturn::create([
                    'shop_id' => $this->shopId(),
                    'supplier_id' => $request->supplier_id,
                    'purchase_order_id' => $request->purchase_order_id,
                    'user_id' => Auth::id(),
                    'return_number' => $this->stock->generateNumber($this->shopId(), 'PR'),
                    'status' => 'completed',
                    'notes' => $request->notes,
                    'total_amount' => 0,
                ]);

                foreach ($request->items as $item) {
                    $subtotal = $item['quantity'] * $item['unit_cost'];
                    PurchaseReturnItem::create([
                        'purchase_return_id' => $return->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'subtotal' => $subtotal,
                    ]);

                    $product = Product::where('shop_id', $this->shopId())->findOrFail($item['product_id']);
                    $movement = $this->stock->returnPurchaseItem(
                        $product,
                        (int) $item['quantity'],
                        'Purchase return '.$return->return_number,
                        Auth::id(),
                        $return->id,
                        $location->id,
                    );

                    $this->accounts->postPurchaseReturn(
                        $movement,
                        (float) $item['unit_cost'],
                        $return->return_number,
                    );

                    $total += $subtotal;
                }

                $return->update(['total_amount' => $total]);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('supply.purchase-returns.index')
            ->with('success', 'Purchase return recorded. Stock decreased and Accounts Payable reduced.');
    }
}
