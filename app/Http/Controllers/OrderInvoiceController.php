<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class OrderInvoiceController extends Controller
{
    /**
     * Printable invoice for any order (online or retail).
     */
    public function show(Order $order)
    {
        if ($order->shop_id !== Auth::user()->shop_id) {
            abort(403, 'Unauthorized Access');
        }

        $order->load([
            'items.product.brand',
            'items.product.category',
            'items.soldImeis',
            'user',
            'customer',
            'shop',
            'counter',
        ]);

        $returnProduct = null;
        if ($order->is_exchange_receipt && $order->return_product_id) {
            $returnProduct = Product::with(['brand', 'category'])->find($order->return_product_id);
        }

        return view('pos.receipt', compact('order', 'returnProduct'));
    }
}
