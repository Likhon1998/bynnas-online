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
        $user = Auth::user();
        if ((int) $order->shop_id !== (int) $user->shop_id) {
            abort(403, 'Unauthorized Access');
        }

        $allowed = $order->isOnlineOrder()
            ? $user->can('manage orders')
            : retail_enabled() && ($user->can('process pos sales') || $user->can('view sales ledger'));
        abort_unless($allowed || $user->can('view reports'), 403, 'You do not have permission to view this invoice.');

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
            $returnProduct = Product::with(['brand', 'category'])->where('shop_id', $order->shop_id)->find($order->return_product_id);
        }

        return view('pos.receipt', compact('order', 'returnProduct'));
    }
}
