<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;

class ReorderLevelController extends Controller
{
    use ShopScoped;

    public function index()
    {
        $products = Product::where('shop_id', $this->shopId())
            ->orderBy('name')
            ->get();

        $lowStock = $products->filter(fn ($p) => $p->stock_quantity <= $p->alert_quantity);

        return view('supply.reorder-levels.index', compact('products', 'lowStock'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.alert_quantity' => 'required|integer|min:0',
            'products.*.reorder_quantity' => 'required|integer|min:0',
        ]);

        foreach ($request->products as $row) {
            Product::where('shop_id', $this->shopId())
                ->where('id', $row['id'])
                ->update([
                    'alert_quantity' => $row['alert_quantity'],
                    'reorder_quantity' => $row['reorder_quantity'],
                ]);
        }

        return redirect()->route('supply.reorder-levels.index')->with('success', 'Reorder levels updated.');
    }
}
