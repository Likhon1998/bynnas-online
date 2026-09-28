<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Models\StockLocation;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockLocationController extends Controller
{
    use ShopScoped;

    public function __construct(protected StockService $stock) {}

    public function stores()
    {
        $this->stock->ensureDefaultLocations($this->shopId());
        $locations = StockLocation::where('shop_id', $this->shopId())->where('type', 'store')->latest()->get();

        return view('supply.stores.index', compact('locations'));
    }

    public function storeCreate()
    {
        if ($this->locationLimitReached('store')) {
            return redirect()->route('supply.stores.index')
                ->with('error', 'Only one store is allowed. Edit your existing store instead.');
        }

        return view('supply.stores.create');
    }

    public function storeSave(Request $request)
    {
        if ($this->locationLimitReached('store')) {
            return redirect()->route('supply.stores.index')
                ->with('error', 'Only one store is allowed.');
        }

        return $this->saveLocation($request, 'store', 'supply.stores.index', 'Store');
    }

    public function storeEdit(StockLocation $location)
    {
        $this->authorizeShop($location);
        abort_unless($location->type === 'store', 404);

        return view('supply.stores.edit', compact('location'));
    }

    public function storeUpdate(Request $request, StockLocation $location)
    {
        $this->authorizeShop($location);
        abort_unless($location->type === 'store', 404);

        return $this->updateLocation($request, $location, 'supply.stores.index', 'Store');
    }

    public function warehouses()
    {
        $this->stock->ensureDefaultLocations($this->shopId());
        $locations = StockLocation::where('shop_id', $this->shopId())->where('type', 'warehouse')->with('warehouseStocks.product')->latest()->get();

        return view('supply.warehouses.index', compact('locations'));
    }

    public function warehouseCreate()
    {
        if ($this->locationLimitReached('warehouse')) {
            return redirect()->route('supply.warehouses.index')
                ->with('error', 'Only one warehouse is allowed. Edit your existing warehouse instead.');
        }

        return view('supply.warehouses.create');
    }

    public function warehouseSave(Request $request)
    {
        if ($this->locationLimitReached('warehouse')) {
            return redirect()->route('supply.warehouses.index')
                ->with('error', 'Only one warehouse is allowed.');
        }

        return $this->saveLocation($request, 'warehouse', 'supply.warehouses.index', 'Warehouse');
    }

    public function warehouseEdit(StockLocation $location)
    {
        $this->authorizeShop($location);
        abort_unless($location->type === 'warehouse', 404);

        return view('supply.warehouses.edit', compact('location'));
    }

    public function warehouseUpdate(Request $request, StockLocation $location)
    {
        $this->authorizeShop($location);
        abort_unless($location->type === 'warehouse', 404);

        return $this->updateLocation($request, $location, 'supply.warehouses.index', 'Warehouse');
    }

    protected function locationLimitReached(string $type): bool
    {
        if (! config('store.single_shop_mode', true)) {
            return false;
        }

        $max = (int) config('store.max_locations_per_type', 1);

        return StockLocation::where('shop_id', $this->shopId())
            ->where('type', $type)
            ->count() >= $max;
    }

    protected function saveLocation(Request $request, string $type, string $route, string $label)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        $isSingleStore = config('store.single_shop_mode', true);
        $isDefault = $isSingleStore || ! empty($data['is_default']);

        if ($isDefault && ! $isSingleStore) {
            StockLocation::where('shop_id', $this->shopId())->where('type', $type)->update(['is_default' => false]);
        }

        StockLocation::create([
            'shop_id' => $this->shopId(),
            'name' => $data['name'],
            'type' => $type,
            'address' => $data['address'] ?? null,
            'is_default' => $isDefault,
            'is_active' => true,
        ]);

        return redirect()->route($route)->with('success', "{$label} saved successfully.");
    }

    protected function updateLocation(Request $request, StockLocation $location, string $route, string $label)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $isSingleStore = config('store.single_shop_mode', true);

        if ($isSingleStore) {
            $data['is_default'] = true;
            $data['is_active'] = $data['is_active'] ?? true;
        } elseif (! empty($data['is_default'])) {
            StockLocation::where('shop_id', $this->shopId())
                ->where('type', $location->type)
                ->where('id', '!=', $location->id)
                ->update(['is_default' => false]);
        }

        $location->update($data);

        return redirect()->route($route)->with('success', "{$label} updated.");
    }
}
