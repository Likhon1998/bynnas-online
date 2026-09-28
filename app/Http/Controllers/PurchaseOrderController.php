<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Models\AccountTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Services\AccountService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    use ShopScoped;

    public function __construct(
        protected StockService $stock,
        protected AccountService $accounts,
    ) {}

    public function index()
    {
        $orders = PurchaseOrder::where('shop_id', $this->shopId())
            ->with('supplier')
            ->latest()
            ->paginate(15);

        return view('supply.purchase-orders.index', compact('orders'));
    }

    public function create(\Illuminate\Http\Request $request)
    {
        $this->accounts->ensureShopAccounts($this->shopId());
        $this->stock->ensureDefaultLocations($this->shopId());

        $suppliers = Supplier::where('shop_id', $this->shopId())->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('shop_id', $this->shopId())->orderBy('name')->get();

        $suggestedRows = [];
        if ($request->boolean('from_reorder')) {
            $suggestedRows = $products
                ->filter(fn ($p) => $p->stock_quantity <= $p->alert_quantity)
                ->map(function ($p) {
                    $qty = (int) ($p->reorder_quantity ?: max($p->alert_quantity - $p->stock_quantity, 1));

                    return [
                        'key' => 'r'.$p->id,
                        'product_id' => (string) $p->id,
                        'quantity' => max($qty, 1),
                        'unit_cost' => (float) $p->cost_price,
                    ];
                })
                ->values()
                ->all();
        }

        return view('supply.purchase-orders.create', compact('suppliers', 'products', 'suggestedRows'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedPayload($request);

        $po = $this->stock->transaction(function () use ($data) {
            $total = 0;
            $po = PurchaseOrder::create([
                'shop_id' => $this->shopId(),
                'supplier_id' => $data['supplier_id'],
                'user_id' => Auth::id(),
                'po_number' => $this->stock->generateNumber($this->shopId(), 'PO'),
                'status' => 'ordered',
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total_amount' => 0,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::where('shop_id', $this->shopId())->findOrFail($item['product_id']);
                $subtotal = $item['quantity'] * $item['unit_cost'];
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $subtotal,
                ]);
                $total += $subtotal;
            }

            $po->update(['total_amount' => $total]);

            return $po;
        });

        return redirect()->route('supply.purchase-orders.show', $po)->with('success', 'Purchase order created. Receive stock when goods arrive.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeShop($purchaseOrder);
        $this->accounts->ensureShopAccounts($this->shopId());
        $this->stock->ensureDefaultLocations($this->shopId());
        $purchaseOrder->load(['supplier', 'items.product', 'user']);

        $ledgerEntries = AccountTransaction::where('shop_id', $this->shopId())
            ->where('type', 'purchase_receive')
            ->where('description', 'like', '%' . $purchaseOrder->po_number . '%')
            ->latest()
            ->limit(20)
            ->get();

        $cashAccounts = $this->accounts->cashAccounts($this->shopId());
        $apBalance = $this->accounts->accountBalance($this->accounts->getAccount($this->shopId(), 'AP'));
        $receiveLocations = StockLocation::where('shop_id', $this->shopId())
            ->where('is_active', true)
            ->orderByRaw("CASE type WHEN 'store' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        return view('supply.purchase-orders.show', [
            'order' => $purchaseOrder,
            'ledgerEntries' => $ledgerEntries,
            'cashAccounts' => $cashAccounts,
            'apBalance' => $apBalance,
            'receiveLocations' => $receiveLocations,
            'defaultReceiveLocationId' => $this->stock->defaultStore($this->shopId())?->id,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeShop($purchaseOrder);
        $this->assertEditable($purchaseOrder);

        $suppliers = Supplier::where('shop_id', $this->shopId())->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('shop_id', $this->shopId())->orderBy('name')->get();
        $purchaseOrder->load('items');

        return view('supply.purchase-orders.edit', [
            'order' => $purchaseOrder,
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizeShop($purchaseOrder);
        $this->assertEditable($purchaseOrder);

        $data = $this->validatedPayload($request);

        $this->stock->transaction(function () use ($purchaseOrder, $data) {
            $purchaseOrder->update([
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $purchaseOrder->items()->delete();

            $total = 0;
            foreach ($data['items'] as $item) {
                $product = Product::where('shop_id', $this->shopId())->findOrFail($item['product_id']);
                $subtotal = $item['quantity'] * $item['unit_cost'];
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $subtotal,
                ]);
                $total += $subtotal;
            }

            $purchaseOrder->update(['total_amount' => $total]);
        });

        return redirect()->route('supply.purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order updated.');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizeShop($purchaseOrder);

        if (in_array($purchaseOrder->status, ['cancelled', 'received'], true)) {
            return back()->with('error', 'This purchase order cannot receive more stock.');
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:purchase_order_items,id',
            'items.*.receive_qty' => 'required|integer|min:0',
            'receive_location_id' => [
                'required',
                Rule::exists('stock_locations', 'id')->where(fn ($q) => $q->where('shop_id', $this->shopId())->where('is_active', true)),
            ],
        ]);

        $receivedAny = false;

        try {
            $this->stock->transaction(function () use ($request, $purchaseOrder, &$receivedAny) {
                $this->accounts->ensureShopAccounts($this->shopId());
                $this->stock->ensureDefaultLocations($this->shopId());

                $receiveLocation = StockLocation::where('shop_id', $this->shopId())
                    ->where('is_active', true)
                    ->findOrFail($request->receive_location_id);

                foreach ($request->items as $row) {
                    $item = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                        ->with('product')
                        ->findOrFail($row['id']);

                    $receiveQty = (int) $row['receive_qty'];
                    if ($receiveQty < 1) {
                        continue;
                    }

                    $remaining = $item->quantity - $item->received_quantity;
                    if ($receiveQty > $remaining) {
                        throw new \InvalidArgumentException(
                            "Cannot receive more than ordered for {$item->product->name}."
                        );
                    }

                    $product = Product::where('shop_id', $this->shopId())->findOrFail($item->product_id);
                    $product->update(['cost_price' => $item->unit_cost]);

                    $movement = $this->stock->receivePurchaseItem(
                        $product,
                        $receiveQty,
                        $purchaseOrder->po_number,
                        Auth::id(),
                        $purchaseOrder->id,
                        $receiveLocation->id,
                    );

                    $this->accounts->postPurchaseReceive(
                        $movement,
                        (float) $item->unit_cost,
                        $purchaseOrder->po_number,
                    );

                    $item->increment('received_quantity', $receiveQty);
                    $receivedAny = true;
                }

                if (! $receivedAny) {
                    throw new \InvalidArgumentException('Enter at least one quantity to receive.');
                }

                $purchaseOrder->load('items');
                $allReceived = $purchaseOrder->items->every(fn ($i) => $i->received_quantity >= $i->quantity);
                $anyReceived = $purchaseOrder->items->contains(fn ($i) => $i->received_quantity > 0);

                $purchaseOrder->update([
                    'status' => $allReceived ? 'received' : ($anyReceived ? 'partial' : $purchaseOrder->status),
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Stock received. Inventory asset and Accounts Payable updated.');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeShop($purchaseOrder);

        $purchaseOrder->load('items');

        if ($purchaseOrder->status === 'cancelled') {
            return back()->with('error', 'Purchase order is already cancelled.');
        }

        if ($purchaseOrder->items->contains(fn ($i) => $i->received_quantity > 0)) {
            return back()->with('error', 'Cannot cancel — some items were already received. Use Purchase Return instead.');
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return redirect()->route('supply.purchase-orders.index')->with('success', 'Purchase order cancelled.');
    }

    public function pay(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizeShop($purchaseOrder);

        $remaining = $purchaseOrder->remainingPayable();
        if ($remaining <= 0) {
            return back()->with('error', 'This purchase order is already fully paid.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $remaining,
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string|max:255',
        ]);

        $purchaseOrder->loadMissing('supplier');

        $account = \App\Models\Account::where('shop_id', $this->shopId())
            ->where('id', $request->account_id)
            ->firstOrFail();

        try {
            $this->stock->transaction(function () use ($request, $purchaseOrder, $account) {
                $amount = round((float) $request->amount, 2);

                $this->accounts->postSupplierPayment(
                    $this->shopId(),
                    $amount,
                    $account,
                    'Supplier payment for ' . $purchaseOrder->po_number
                        . ($request->notes ? ' — ' . $request->notes : '')
                        . ' (' . ($purchaseOrder->supplier->name ?? 'supplier') . ')',
                    Auth::id(),
                );

                $purchaseOrder->increment('paid_amount', $amount);
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Supplier payment recorded against Accounts Payable.');
    }

    protected function validatedPayload(Request $request): array
    {
        return $request->validate([
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('shop_id', $this->shopId())),
            ],
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('shop_id', $this->shopId())),
            ],
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);
    }

    protected function assertEditable(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status === 'cancelled') {
            abort(403, 'Cancelled purchase orders cannot be edited.');
        }

        $purchaseOrder->loadMissing('items');

        if ($purchaseOrder->items->contains(fn ($i) => $i->received_quantity > 0)) {
            abort(403, 'Cannot edit a PO after stock has been received.');
        }
    }
}
