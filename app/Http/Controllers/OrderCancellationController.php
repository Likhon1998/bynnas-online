<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderWorkflowException;
use App\Services\OrderWorkflowService;
use App\Support\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Edge-case COD lifecycle: cancel / fraud / door reject → release reserved stock
 * (or restock if packing already committed physical inventory).
 */
class OrderCancellationController extends Controller
{
    public function __construct(
        protected OrderWorkflowService $workflow,
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

        if (OrderStatus::isVoid($order->status)) {
            return back()->with('error', 'This order is already closed.');
        }

        if (! OrderStatus::canTransition($order->status, OrderStatus::CANCELLED)) {
            return back()->with('error', 'Delivered orders cannot be cancelled. Use return or refund instead.');
        }

        $request->validate([
            'customer_note' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:80',
        ]);

        try {
            $this->workflow->transition($order, OrderStatus::CANCELLED, [
                'note' => $request->input('customer_note') ?: 'This order was cancelled.',
                'stock_reason' => $request->input('reason', 'order_cancelled'),
            ], $user->id);
        } catch (OrderWorkflowException|\InvalidArgumentException $e) {
            return back()->with('error', 'Cancel failed: '.$e->getMessage());
        }

        return back()->with('success', "Order {$order->invoice_no} cancelled. Reserved stock returned to available inventory.");
    }
}
