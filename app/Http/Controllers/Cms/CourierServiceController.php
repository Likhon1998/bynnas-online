<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Http\Controllers\Controller;
use App\Models\CourierService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourierServiceController extends Controller
{
    use ShopScoped;

    public function index()
    {
        $this->seedDefaultsIfEmpty();

        $services = CourierService::forShop($this->shopId())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('cms.couriers.index', compact('services'));
    }

    private function seedDefaultsIfEmpty(): void
    {
        if (CourierService::forShop($this->shopId())->exists()) {
            return;
        }

        foreach ([
            ['name' => 'Pathao', 'sort_order' => 1],
            ['name' => 'Steadfast', 'sort_order' => 2],
            ['name' => 'RedX', 'sort_order' => 3],
            ['name' => 'Paperfly', 'sort_order' => 4],
        ] as $row) {
            CourierService::create([
                'shop_id' => $this->shopId(),
                'name' => $row['name'],
                'sort_order' => $row['sort_order'],
                'is_active' => true,
            ]);
        }
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        CourierService::create([
            'shop_id' => $this->shopId(),
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => true,
        ]);

        return back()->with('success', 'Courier service added.');
    }

    public function update(Request $request, CourierService $courier)
    {
        $this->authorizeShop($courier);
        $data = $this->validated($request, $courier->id);

        $courier->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Courier service updated.');
    }

    public function destroy(CourierService $courier)
    {
        $this->authorizeShop($courier);

        if ($courier->orders()->exists()) {
            $courier->update(['is_active' => false]);

            return back()->with('success', 'Courier has past orders — deactivated instead of deleted.');
        }

        $courier->delete();

        return back()->with('success', 'Courier service deleted.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('courier_services', 'name')
                    ->where(fn ($q) => $q->where('shop_id', $this->shopId()))
                    ->ignore($ignoreId),
            ],
            'phone' => 'nullable|string|max:40',
            'notes' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);
    }
}
