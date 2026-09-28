<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    use ShopScoped;

    public function __construct(protected StockService $stock) {}

    public function index()
    {
        $suppliers = Supplier::where('shop_id', $this->shopId())->latest()->get();

        return view('supply.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('supply.suppliers.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $supplier = new Supplier(array_merge($data, [
            'shop_id' => $this->shopId(),
            'is_active' => true,
        ]));
        $supplier->syncLegacyAddress();
        $supplier->save();

        if ($request->boolean('add_another')) {
            return redirect()
                ->route('supply.suppliers.create')
                ->with('success', 'Supplier “'.$supplier->name.'” saved. Add another below.');
        }

        return redirect()
            ->route('supply.suppliers.index')
            ->with('success', 'Supplier added successfully.');
    }

    public function edit(Supplier $supplier)
    {
        $this->authorizeShop($supplier);

        return view('supply.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->authorizeShop($supplier);

        $data = $this->validated($request, editing: true);
        $data['is_active'] = $request->boolean('is_active', true);

        $supplier->fill($data);
        $supplier->syncLegacyAddress();
        $supplier->save();

        return redirect()
            ->route('supply.suppliers.index')
            ->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorizeShop($supplier);

        $hasOrders = PurchaseOrder::where('supplier_id', $supplier->id)->exists();
        $hasReturns = PurchaseReturn::where('supplier_id', $supplier->id)->exists();

        if ($hasOrders || $hasReturns) {
            return back()->with('error', 'Cannot delete this supplier — purchase orders or returns exist. Deactivate instead.');
        }

        $supplier->delete();

        return redirect()
            ->route('supply.suppliers.index')
            ->with('success', 'Supplier removed.');
    }

    protected function validated(Request $request, bool $editing = false): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:50',
            'phone_dial_code' => ['nullable', 'string', 'max:10', Rule::in(Supplier::dialCodeKeys())],
            'alt_phone' => 'nullable|string|max:50',
            'alt_phone_dial_code' => ['nullable', 'string', 'max:10', Rule::in(Supplier::dialCodeKeys())],
            'website' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'business_type' => ['nullable', 'string', Rule::in(array_keys(Supplier::BUSINESS_TYPES))],
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:120',
            'postal_code' => 'nullable|string|max:40',
            'country' => ['required', 'string', Rule::in(Supplier::countryKeys())],
            'city' => [
                'required',
                'string',
                'max:120',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $country = (string) $request->input('country');
                    if ($country === 'Other' || $country === '') {
                        return;
                    }
                    $cities = Supplier::citiesFor($country);
                    if ($cities && ! in_array($value, $cities, true)) {
                        $fail('Please select a valid city for the chosen country.');
                    }
                },
            ],
            'notes' => 'nullable|string|max:5000',
            'is_active' => $editing ? 'nullable|boolean' : 'prohibited',
        ]);

        $data['phone_dial_code'] = $data['phone_dial_code'] ?? '+880';
        $data['alt_phone_dial_code'] = filled($data['alt_phone'] ?? null)
            ? ($data['alt_phone_dial_code'] ?? '+880')
            : null;
        $data['company'] = ($data['company'] ?? null) ?: ($data['contact_person'] ?? null);

        return $data;
    }
}
