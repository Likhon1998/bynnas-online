<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Lists product families: rows sharing `variant_group` are variants of one product. */
class ProductFamilyController extends Controller
{
    public function index(Request $request)
    {
        $shopId = (int) Auth::user()->shop_id;
        $search = trim((string) $request->query('q', ''));

        $families = Product::query()
            ->where('shop_id', $shopId)
            ->whereNotNull('variant_group')
            ->where('variant_group', '!=', '')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")
                    ->orWhere('variant_group', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"));
            })
            ->groupBy('variant_group')
            ->select([
                'variant_group',
                DB::raw('COUNT(*) as variants_count'),
                DB::raw('SUM(stock_quantity) as total_stock'),
                DB::raw('SUM(COALESCE(reserved_stock, 0)) as total_reserved'),
                DB::raw('MIN(selling_price) as min_price'),
                DB::raw('MAX(selling_price) as max_price'),
                DB::raw('MAX(updated_at) as last_updated'),
            ])
            ->orderByDesc('last_updated')
            ->paginate(20)
            ->withQueryString();

        $variantsByGroup = Product::query()
            ->where('shop_id', $shopId)
            ->whereIn('variant_group', $families->pluck('variant_group'))
            ->with(['variantValues.attribute', 'variantValues.attributeValue', 'category:id,name', 'brand:id,name'])
            ->orderBy('id')
            ->get()
            ->groupBy('variant_group');

        $singleCount = Product::where('shop_id', $shopId)
            ->where(fn ($q) => $q->whereNull('variant_group')->orWhere('variant_group', ''))
            ->count();

        return view('products.variants', compact('families', 'variantsByGroup', 'search', 'singleCount'));
    }
}
