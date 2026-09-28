<x-supply-layout title="New Purchase Return" subtitle="Return excess or unwanted goods to a supplier. Stock ↓ · Accounts Payable ↓.">
    @php
        $productOptions = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'cost' => (float) $p->cost_price,
            'stock' => (int) $p->stock_quantity,
        ])->values();

        $locationOptions = $locations->map(function ($l) use ($warehouseQty) {
            $qtyMap = [];
            if ($l->type === 'warehouse') {
                $qtyMap = ($warehouseQty[$l->id] ?? collect())->toArray();
            }
            return [
                'id' => $l->id,
                'type' => $l->type,
                'label' => ucfirst($l->type) . ': ' . $l->name,
                'warehouse_qty' => $qtyMap,
            ];
        })->values();

        $poOptions = $purchaseOrders->map(function ($po) {
            return [
                'id' => $po->id,
                'label' => $po->po_number . ' — ' . ($po->supplier->name ?? ''),
                'supplier_id' => (string) $po->supplier_id,
                'url' => route('supply.purchase-returns.create', ['purchase_order_id' => $po->id]),
            ];
        })->values();

        $defaultRows = !empty($prefillRows)
            ? collect($prefillRows)->map(fn ($r, $i) => array_merge($r, ['key' => 'prefill-'.$i]))->values()->all()
            : [['key' => 'new-1', 'product_id' => '', 'quantity' => 1, 'unit_cost' => 0, 'max_qty' => null, 'received' => null, 'returned' => null]];

        $returnableMap = collect($returnableByProduct ?? [])->mapWithKeys(fn ($line, $pid) => [
            (string) $pid => $line,
        ]);
    @endphp

    <div class="mb-5 rounded-xl border border-indigo-100 bg-indigo-50/60 p-4 text-sm text-indigo-900">
        <p class="font-semibold mb-1">When to use this</p>
        <p class="text-xs sm:text-sm text-indigo-800/90">
            Supplier sent <strong>extra / wrong</strong> items after you received a purchase order?
            Link that PO, set the excess qty, and submit — stock leaves your shop and AP is reduced.
        </p>
        @if($selectedPo)
            <p class="mt-2 text-xs font-semibold text-indigo-700">
                Prefilling from PO <a href="{{ route('supply.purchase-orders.show', $selectedPo) }}" class="underline">{{ $selectedPo->po_number }}</a>
                ({{ $selectedPo->supplier->name ?? '—' }}). Qty capped to remaining returnable.
            </p>
        @endif
    </div>

    @if($suppliers->isEmpty())
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Add a <a href="{{ route('supply.suppliers.create') }}" class="font-bold underline">supplier</a> first.
        </div>
    @endif

    @if($selectedPo && empty($prefillRows))
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Nothing left to return on this PO — all received qty has already been returned, or nothing was received yet.
        </div>
    @endif

    <form method="POST"
          action="{{ route('supply.purchase-returns.store') }}"
          class="space-y-5"
          x-data="{
              products: @js($productOptions),
              locations: @js($locationOptions),
              pos: @js($poOptions),
              returnable: @js($returnableMap),
              linkedPo: {{ $selectedPo ? 'true' : 'false' }},
              supplierId: '{{ old('supplier_id', $selectedPo?->supplier_id ?? '') }}',
              poId: '{{ old('purchase_order_id', $selectedPo?->id ?? '') }}',
              locationId: '{{ old('return_location_id', $defaultLocationId) }}',
              rows: @js($defaultRows),
              get location() { return this.locations.find(l => String(l.id) === String(this.locationId)); },
              onPoChange() {
                  const po = this.pos.find(p => String(p.id) === String(this.poId));
                  if (!po) return;
                  if (String(this.poId) !== '{{ $selectedPo?->id }}') {
                      window.location = po.url;
                      return;
                  }
                  this.supplierId = String(po.supplier_id);
              },
              addRow() {
                  this.rows.push({ key: Date.now()+Math.random(), product_id: '', quantity: 1, unit_cost: 0, max_qty: null, received: null, returned: null });
              },
              removeRow(i) {
                  this.rows.length > 1
                      ? this.rows.splice(i,1)
                      : this.rows=[{ key: Date.now(), product_id: '', quantity: 1, unit_cost: 0, max_qty: null, received: null, returned: null }];
              },
              onProduct(row) {
                  const p = this.products.find(x => String(x.id) === String(row.product_id));
                  if (p) row.unit_cost = Number(p.cost) || 0;
                  const cap = this.returnable[String(row.product_id)];
                  if (cap) {
                      row.max_qty = Number(cap.returnable);
                      row.received = Number(cap.received);
                      row.returned = Number(cap.returned);
                      if (Number(row.quantity) > row.max_qty) row.quantity = row.max_qty;
                  } else if (this.linkedPo) {
                      row.max_qty = 0;
                      row.received = 0;
                      row.returned = 0;
                  } else {
                      row.max_qty = null;
                      row.received = null;
                      row.returned = null;
                  }
              },
              available(row) {
                  const p = this.products.find(x => String(x.id) === String(row.product_id));
                  if (!p) return '—';
                  const loc = this.location;
                  if (!loc) return p.stock;
                  if (loc.type === 'warehouse') {
                      return Number(loc.warehouse_qty[p.id] || 0);
                  }
                  return p.stock;
              },
              maxQty(row) {
                  if (row.max_qty != null) return Number(row.max_qty);
                  return null;
              },
              line(row) { return (Number(row.quantity)||0) * (Number(row.unit_cost)||0); },
              total() { return this.rows.reduce((s,r)=>s+this.line(r),0); },
              money(n) { return '৳'+Number(n||0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); }
          }"
          @if($suppliers->isEmpty() || $products->isEmpty() || ($selectedPo && empty($prefillRows))) onsubmit="return false" @endif>
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Linked PO</label>
                <select name="purchase_order_id" x-model="poId" @change="onPoChange()" class="w-full text-sm rounded-lg border-gray-200 py-1.5">
                    <option value="">— None —</option>
                    <template x-for="po in pos" :key="po.id">
                        <option :value="po.id" x-text="po.label" :selected="String(po.id) === String(poId)"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Supplier</label>
                <select name="supplier_id" x-model="supplierId" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required>
                    <option value="">Select…</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected((string) old('supplier_id', $selectedPo?->supplier_id) === (string) $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Return from</label>
                <select name="return_location_id" x-model="locationId" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required>
                    <template x-for="loc in locations" :key="loc.id">
                        <option :value="loc.id" x-text="loc.label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Notes</label>
                <input type="text" name="notes" value="{{ old('notes', $selectedPo ? 'Excess goods from '.$selectedPo->po_number : '') }}" placeholder="e.g. Extra units received" class="w-full text-sm rounded-lg border-gray-200 py-1.5 placeholder:text-gray-400">
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b bg-slate-50">
                <h3 class="text-sm font-bold">Return lines</h3>
                <button type="button" @click="addRow()" class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1.5 rounded-lg" x-show="!linkedPo">+ Add line</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-wider text-gray-400 border-b">
                            <th class="text-left font-semibold px-4 py-2">Product</th>
                            <th class="font-semibold px-2 py-2 w-20">In stock</th>
                            <th class="font-semibold px-2 py-2 w-20" x-show="linkedPo">PO recv</th>
                            <th class="font-semibold px-2 py-2 w-20" x-show="linkedPo">Max</th>
                            <th class="font-semibold px-2 py-2 w-24">Return qty</th>
                            <th class="font-semibold px-2 py-2 w-28">Unit cost</th>
                            <th class="text-right font-semibold px-2 py-2 w-28">Total</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in rows" :key="row.key">
                            <tr class="border-b border-gray-50">
                                <td class="px-4 py-2">
                                    <select :name="'items['+index+'][product_id]'" x-model="row.product_id" @change="onProduct(row)" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required :disabled="linkedPo">
                                        <option value="">Select…</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="p.id" x-text="p.name"></option>
                                        </template>
                                    </select>
                                    <template x-if="linkedPo && row.product_id">
                                        <input type="hidden" :name="'items['+index+'][product_id]'" :value="row.product_id">
                                    </template>
                                </td>
                                <td class="px-2 py-2 text-center text-xs text-gray-500" x-text="available(row)"></td>
                                <td class="px-2 py-2 text-center text-xs text-gray-500" x-show="linkedPo" x-text="row.received ?? '—'"></td>
                                <td class="px-2 py-2 text-center text-xs font-semibold text-indigo-600" x-show="linkedPo" x-text="maxQty(row) ?? '—'"></td>
                                <td class="px-2 py-2">
                                    <input type="number"
                                           :name="'items['+index+'][quantity]'"
                                           x-model.number="row.quantity"
                                           min="1"
                                           :max="maxQty(row) || undefined"
                                           class="w-full text-sm rounded-lg border-gray-200 py-1.5"
                                           required>
                                </td>
                                <td class="px-2 py-2"><input type="number" step="0.01" :name="'items['+index+'][unit_cost]'" x-model.number="row.unit_cost" min="0" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required></td>
                                <td class="px-2 py-2 text-right font-semibold" x-text="money(line(row))"></td>
                                <td class="px-2 py-2 text-center">
                                    <button type="button" @click="removeRow(index)" class="text-gray-400 hover:text-red-500 text-lg" x-show="!linkedPo || rows.length > 1">&times;</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50">
                            <td class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase" :colspan="linkedPo ? 6 : 4">Return total</td>
                            <td class="px-2 py-3 text-right text-sm font-bold" x-text="money(total())"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-5 py-2 rounded-lg font-bold"
                @if($selectedPo && empty($prefillRows)) disabled @endif>
            Submit purchase return
        </button>
    </form>
</x-supply-layout>
