<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExchangeController extends Controller
{
    public function processExchange(Request $request, Order $order)
    {
        $user = Auth::user();

        if ($order->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized order.');
        }

        if (! $user->isAdminUser() && $user->counter_id && (int) $order->counter_id !== (int) $user->counter_id) {
            abort(403, 'You can only exchange sales from your counter.');
        }

        if ($order->isOnlineOrder() && ! $user->isAdminUser()) {
            abort(403, 'Only admins can exchange online orders.');
        }

        $request->validate([
            'return_product_id' => 'required|exists:products,id',
            'return_qty' => 'required|integer|min:1',
        ]);

        $returnProduct = Product::where('shop_id', $user->shop_id)->findOrFail($request->return_product_id);
        $creditValue = $returnProduct->selling_price * $request->return_qty;

        return redirect()->route('pos.index', [
            'exchange_order' => $order->id,
            'return_product' => $returnProduct->id,
            'return_qty' => $request->return_qty,
            'credit' => $creditValue,
        ]);
    }
}
