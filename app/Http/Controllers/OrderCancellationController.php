<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\AccountService;
use App\Services\OnlineOrderTrackingService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Edge-case COD lifecycle: cancel / fraud / door reject → release reserved stock
 * (or restock if packing already committed physical inventory).
 */
class OrderCancellationController extends Controller
{
    public function __construct(
        protected StockService $stock,
        protected AccountService $accounts,
        protected OnlineOrderTrackingService $tracking,
    ) {}

    /**
     * Cancel an online order and return units to the available-to-sell pool.
     */
    public function cancel(Request $request, Order $order)
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdminUser()) {
            abort(403, 'Online orders are only available to shop admins.');
        }

        if ($order->shop_id !== $user->shop_id || ! $order->isOnlineOrder()) {
            abort(403, 'Unauthorized Access');
        }

        if (in_array($order->status, ['cancelled', 'returned', 'refunded'], true)) {
            return back()->with('error', 'This order is already closed.');
        }

        if ($order->status === 'completed') {
            return back()->with('error', 'Delivered orders cannot be cancelled. Use refund instead.');
        }

        $request->validate([
            'customer_note' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:80',
        ]);

        $reason = $request->input('reason', 'order_cancelled');
        $note = $request->input('customer_note') ?: 'This order was cancelled.';

        try {
            DB::transaction(function () use ($order, $reason, $note, $user) {
                $oldStatus = $order->status;

                $order->update([
                    'status' => 'cancelled',
                    'paid_amount' => 0,
                ]);

                $this->tracking->upsertLatestLog(
                    $order,
                    'cancelled',
                    $note,
                    $order->shipping_courier,
                    $order->shipping_tracking_no,
                    $user->id,
                );

                $order->load('items.product', 'counter');
                $this->stock->releaseReservedStock($order, $user->id, $reason);

                if (! in_array($oldStatus, ['cancelled', 'returned', 'refunded'], true)) {
                    $this->accounts->postOrderRefund($order);
                }
            });

            return back()->with('success', "Order {$order->invoice_no} cancelled. Reserved stock returned to available inventory.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Cancel failed: '.$e->getMessage());
        }
    }
}
