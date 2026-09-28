<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockLocation;
use App\Models\WarehouseStock;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StockTransferController extends Controller
{
    use ShopScoped;

    public function __construct(protected StockService $stock) {}

    public function index()
    {
        $this->stock->ensureDefaultLocations($this->shopId());
        $transfers = StockTransfer::where('shop_id', $this->shopId())
            ->with(['fromLocation', 'toLocation'])
            ->latest()
            ->paginate(15);

        return view('supply.stock-transfers.index', compact('transfers'));
    }

    public function create()
    {
        $this->stock->ensureDefaultLocations($this->shopId());

        $locations = StockLocation::where('shop_id', $this->shopId())
            ->where('is_active', true)
            ->orderByRaw("CASE type WHEN 'store' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        $products = Product::where('shop_id', $this->shopId())->orderBy('name')->get();

        $warehouseQty = WarehouseStock::whereIn(
            'location_id',
            $locations->where('type', 'warehouse')->pluck('id')
        )
            ->get()
            ->groupBy('location_id')
            ->map(fn ($rows) => $rows->pluck('quantity', 'product_id'));

        return view('supply.stock-transfers.create', compact('locations', 'products', 'warehouseQty'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_location_id' => [
                'required',
                Rule::exists('stock_locations', 'id')->where(fn ($q) => $q->where('shop_id', $this->shopId())->where('is_active', true)),
            ],
            'to_location_id' => [
                'required',
                'different:from_location_id',
                Rule::exists('stock_locations', 'id')->where(fn ($q) => $q->where('shop_id', $this->shopId())->where('is_active', true)),
            ],
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('shop_id', $this->shopId())),
            ],
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $this->stock->transaction(function () use ($request) {
                $from = StockLocation::where('shop_id', $this->shopId())->findOrFail($request->from_location_id);
                $to = StockLocation::where('shop_id', $this->shopId())->findOrFail($request->to_location_id);

                $transfer = StockTransfer::create([
                    'shop_id' => $this->shopId(),
                    'from_location_id' => $from->id,
                    'to_location_id' => $to->id,
                    'user_id' => Auth::id(),
                    'transfer_number' => $this->stock->generateNumber($this->shopId(), 'ST'),
                    'status' => 'completed',
                    'notes' => $request->notes,
                ]);

                foreach ($request->items as $item) {
                    $product = Product::where('shop_id', $this->shopId())->findOrFail($item['product_id']);
                    StockTransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                    ]);
                    $this->stock->transferBetweenLocations(
                        $from,
                        $to,
                        $product,
                        (int) $item['quantity'],
                        'Transfer ' . $transfer->transfer_number,
                        Auth::id(),
                        'stock_transfer',
                        $transfer->id,
                    );
                }
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('supply.stock-transfers.index')->with('success', 'Stock transfer completed and sellable stock synced.');
    }
}
