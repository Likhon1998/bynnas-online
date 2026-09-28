<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\AccountService;
use App\Services\BakiService;
use App\Services\OnlineOrderTrackingService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesLedgerController extends Controller
{
    public function __construct(
        protected AccountService $accounts,
        protected StockService $stock,
        protected OnlineOrderTrackingService $tracking,
        protected BakiService $baki,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $shopId = $user->shop_id;
        $isAdmin = $user->isAdminUser();
        $canViewOnline = $isAdmin;

        // Counter staff only see their own till. Admins see all POS counters.
        $channel = ($canViewOnline && $request->input('channel') === 'online')
            ? 'online'
            : 'physical';

        $physicalQuery = Order::where('shop_id', $shopId)
            ->whereNotNull('counter_id')
            ->with(['customer', 'user', 'counter', 'items.product']);

        if (! $isAdmin && $user->counter_id) {
            $physicalQuery->where('counter_id', $user->counter_id);
        }

        $physicalOrders = $physicalQuery->latest()->limit(250)->get();
        $physicalPayload = $physicalOrders->map(fn (Order $order) => $this->physicalRow($order))->values();

        $onlineOrders = collect();
        $onlinePayload = collect();

        if ($canViewOnline) {
            $onlineQuery = Order::where('shop_id', $shopId)
                ->onlineOrders()
                ->with(['customer', 'user', 'items.product']);

            $onlineOrders = $onlineQuery->latest()->limit(250)->get();
            $onlinePayload = $onlineOrders->map(fn (Order $order) => $this->onlineRow($order))->values();
        }

        $physicalStats = [
            'cancelled' => $physicalOrders->where('status', 'cancelled')->count(),
            'returned' => $physicalOrders->where('status', 'returned')->count(),
            'refunded' => $physicalOrders->where('status', 'refunded')->count(),
        ];

        $onlineStats = [
            'pending' => $onlineOrders->whereIn('status', ['pending', 'pending_fulfillment'])->count(),
            'processing' => $onlineOrders->where('status', 'processing')->count(),
            'shipped' => $onlineOrders->where('status', 'shipped')->count(),
            'completed' => $onlineOrders->where('status', 'completed')->count(),
            'cod_outstanding' => (float) $onlineOrders
                ->where('status', 'shipped')
                ->filter(fn (Order $o) => $o->payment_method === 'cash_on_delivery')
                ->sum(fn (Order $o) => max(
                    0,
                    (float) $o->total_amount
                        - (float) ($o->discount_amount ?? 0)
                        - (float) ($o->delivery_charge ?? 0)
                )),
        ];

        return view('sales.index', [
            'channel' => $channel,
            'canViewOnline' => $canViewOnline,
            'physicalPayload' => $physicalPayload,
            'onlinePayload' => $onlinePayload,
            'physicalStats' => $physicalStats,
            'onlineStats' => $onlineStats,
            'physicalCount' => $physicalOrders->count(),
            'onlineCount' => $onlineOrders->count(),
            'csrfToken' => csrf_token(),
        ]);
    }

    private function physicalRow(Order $order): array
    {
        $isVoided = in_array($order->status, ['refunded', 'cancelled', 'returned'], true);
        $withinWindow = $order->created_at >= now()->subDays(7);
        $gross = (float) $order->total_amount;
        $discount = (float) ($order->discount_amount ?? 0);
        $credit = (float) ($order->credit_amount ?? 0);
        $exchangeCredit = (float) ($order->exchange_credit ?? 0);
        $netRevenue = $order->netPayable();
        $wasExchangedFrom = Order::where('shop_id', $order->shop_id)
            ->where('exchange_for_order_id', $order->id)
            ->exists();

        return [
            'id' => $order->id,
            'invoice' => $order->invoice_no,
            'created_at' => asian_datetime($order->created_at, 'd M y, h:i A'),
            'status' => $order->status,
            'payment_method' => (string) $order->payment_method,
            'gross_amount' => format_taka_number($gross),
            'discount_amount' => $discount,
            'discount_amount_fmt' => format_taka_number($discount),
            'credit_amount' => $credit,
            'credit_amount_fmt' => format_taka_number($credit),
            'is_baki' => (bool) ($order->is_baki ?? false),
            'exchange_credit' => $exchangeCredit,
            'product_revenue' => format_taka_number($netRevenue),
            'delivery_charge' => (float) ($order->delivery_charge ?? 0),
            'delivery_charge_fmt' => format_taka_number((float) ($order->delivery_charge ?? 0)),
            'is_voided' => $isVoided,
            'is_exchange_receipt' => (bool) $order->is_exchange_receipt,
            'was_exchanged_from' => $wasExchangedFrom,
            'can_return' => ! $isVoided && ! $order->is_exchange_receipt && ! $wasExchangedFrom && $withinWindow,
            'return_expired' => ! $isVoided && ! $order->is_exchange_receipt && ! $wasExchangedFrom && ! $withinWindow,
            'cashier' => $order->user->name ?? 'Unknown',
            'customer_name' => $order->customer->name ?? null,
            'customer_phone' => $order->customer->phone ?? null,
            'receipt_url' => route('pos.receipt', $order->id),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->product_id,
                'name' => $item->product->name ?? 'Unknown Item',
                'price' => (float) $item->unit_price,
                'qty' => (int) $item->quantity,
            ])->values()->all(),
            'search_blob' => mb_strtolower(implode(' ', array_filter([
                $order->invoice_no,
                $order->customer?->name,
                $order->customer?->phone,
                $order->user?->name,
            ]))),
        ];
    }

    private function onlineRow(Order $order): array
    {
        $isVoided = in_array($order->status, ['refunded', 'cancelled', 'returned'], true);
        $withinWindow = $order->created_at >= now()->subDays(7);
        // Net product total (exclude delivery so the UI can show delivery on its own line).
        $netRevenue = max(
            0,
            (float) $order->total_amount
                - (float) ($order->discount_amount ?? 0)
                - (float) ($order->delivery_charge ?? 0)
        );
        $isCod = $this->isCashOnDelivery($order);
        $moneyCollected = $this->moneyWasCollected($order);

        // Refund only after delivery (money can actually be returned to the customer).
        $canRefund = ! $isVoided && $withinWindow && $moneyCollected
            && $order->status === 'completed';

        // Returned for undelivered orders (pending / packing / shipped) — no Refund button.
        $canMarkReturned = ! $isVoided && $withinWindow
            && in_array($order->status, ['pending', 'pending_fulfillment', 'processing', 'shipped'], true);

        return [
            'id' => $order->id,
            'invoice' => $order->invoice_no,
            'created_at' => asian_datetime($order->created_at, 'd M y, h:i A'),
            'status' => $order->status,
            'payment_method' => str_replace('_', ' ', (string) $order->payment_method),
            'is_cod' => $isCod,
            'money_collected' => $moneyCollected,
            'product_revenue' => format_taka_number($netRevenue),
            'discount_amount' => (float) ($order->discount_amount ?? 0),
            'discount_amount_fmt' => format_taka_number((float) ($order->discount_amount ?? 0)),
            'delivery_charge' => (float) ($order->delivery_charge ?? 0),
            'delivery_charge_fmt' => format_taka_number((float) ($order->delivery_charge ?? 0)),
            'is_voided' => $isVoided,
            'can_refund' => $canRefund,
            'can_mark_returned' => $canMarkReturned,
            'shipping_courier' => $order->shipping_courier,
            'shipping_tracking_no' => $order->shipping_tracking_no,
            'customer_name' => $order->customer->name ?? 'Guest',
            'customer_phone' => $order->customer->phone ?? '—',
            'customer_address' => $order->customer->address ?? '',
            'show_url' => route('online-orders.show', $order),
            'receipt_url' => route('pos.receipt', $order->id),
            'refund_url' => route('sales.refund', $order),
            'return_url' => route('sales.return', $order),
            'items' => $order->items->map(fn ($item) => [
                'qty' => (int) $item->quantity,
                'name' => $item->product->name ?? 'Product',
            ])->values()->all(),
            'search_blob' => mb_strtolower(implode(' ', array_filter([
                $order->invoice_no,
                $order->shipping_tracking_no,
                $order->customer?->name,
                $order->customer?->phone,
            ]))),
        ];
    }

    private function isCashOnDelivery(Order $order): bool
    {
        return in_array(strtolower((string) $order->payment_method), [
            'cash_on_delivery',
            'cod',
            'cash on delivery',
        ], true);
    }

    /** True when customer money was actually received (not just COD receivable). */
    private function moneyWasCollected(Order $order): bool
    {
        if (! $this->isCashOnDelivery($order)) {
            // Prepaid / online pay — treat as collected at order time.
            return (float) $order->paid_amount > 0 || $order->status === 'completed';
        }

        // COD: money only after delivery / settlement.
        return $order->status === 'completed' || (float) $order->paid_amount > 0;
    }

    public function refund(Order $order)
    {
        $user = Auth::user();

        if ($order->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized action.');
        }

        $isOnline = $order->isOnlineOrder();

        if ($isOnline && ! $user->isAdminUser()) {
            abort(403, 'Only admins can refund online orders.');
        }

        if (! $isOnline && ! $user->isAdminUser() && $user->counter_id && (int) $order->counter_id !== (int) $user->counter_id) {
            abort(403, 'You can only refund sales from your counter.');
        }

        if (in_array($order->status, ['refunded', 'cancelled', 'returned'])) {
            return back()->with('error', 'This order has already been voided or refunded.');
        }

        if ($order->is_exchange_receipt) {
            return back()->with('error', 'Exchange receipts cannot be refunded. Adjust from the original sale if needed.');
        }

        if (Order::where('shop_id', $order->shop_id)->where('exchange_for_order_id', $order->id)->exists()) {
            return back()->with('error', 'This order was already exchanged. Refunding it would double-restock and cash-out incorrectly.');
        }

        if ($order->created_at < now()->subDays(7)) {
            return back()->with('error', 'The 7-day refund window has expired for this order.');
        }

        if ($isOnline) {
            if ($order->status !== 'completed') {
                return back()->with('error', 'Refund is only available after the order is delivered. Use Returned if it is not delivered yet.');
            }

            if (! $this->moneyWasCollected($order)) {
                return back()->with('error', 'No payment was collected yet. Use Returned if the package came back unpaid (COD).');
            }
        }

        try {
            DB::beginTransaction();

            $order->update([
                'status' => 'refunded',
                'paid_amount' => 0,
            ]);

            foreach ($order->items as $item) {
                $product = $item->product;

                if ($product) {
                    $this->stock->restockForDocument(
                        $product,
                        $item->quantity,
                        'Refund - '.$order->invoice_no,
                        'order_refund',
                        $order->id,
                        'order_refund',
                        Auth::id(),
                    );
                }
            }

            $order->load('items.product', 'counter');
            $this->baki->reverseSaleCredit($order, Auth::id());
            $this->accounts->postOrderRefund($order);

            if ($isOnline) {
                $this->tracking->upsertLatestLog(
                    $order,
                    'refunded',
                    'Order refunded after payment collection.',
                    $order->shipping_courier,
                    $order->shipping_tracking_no,
                    Auth::id(),
                );
            }

            DB::commit();

            $channel = $isOnline ? 'online' : 'physical';

            return redirect()
                ->route('sales.index', ['channel' => $channel])
                ->with('success', "Order {$order->invoice_no} has been refunded, stock restored, and money reversed.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to process refund. '.$e->getMessage());
        }
    }

    /**
     * Mark online order as returned (goods back, typically COD not collected — no cash refund to customer).
     */
    public function markReturned(Order $order)
    {
        $user = Auth::user();

        if (! $user->isAdminUser()) {
            abort(403, 'Only admins can process online order returns.');
        }

        if ($order->shop_id !== $user->shop_id || ! $order->isOnlineOrder()) {
            abort(403, 'Unauthorized action.');
        }

        if (in_array($order->status, ['refunded', 'cancelled', 'returned'], true)) {
            return back()->with('error', 'This order is already closed.');
        }

        if ($this->moneyWasCollected($order) && $order->status === 'completed') {
            return back()->with('error', 'This order is already delivered and paid. Use Refund instead of Returned.');
        }

        if (! in_array($order->status, ['pending', 'pending_fulfillment', 'processing', 'shipped'], true)) {
            return back()->with('error', 'Only undelivered orders can be marked as Returned.');
        }

        if ($order->created_at < now()->subDays(7)) {
            return back()->with('error', 'The 7-day return window has expired for this order.');
        }

        try {
            DB::beginTransaction();

            $order->update([
                'status' => 'returned',
                'paid_amount' => 0,
            ]);

            foreach ($order->items as $item) {
                $product = $item->product;

                if ($product) {
                    $this->stock->restockForDocument(
                        $product,
                        $item->quantity,
                        'Returned - '.$order->invoice_no,
                        'order_refund',
                        $order->id,
                        'order_returned',
                        Auth::id(),
                    );
                }
            }

            $order->load('items.product', 'counter');
            // Reverse web sale / receivable (no cash paid out to customer for unpaid COD).
            $this->accounts->postOrderRefund($order);

            $this->tracking->upsertLatestLog(
                $order,
                'returned',
                $this->isCashOnDelivery($order)
                    ? 'Order returned. COD was not collected — no customer refund.'
                    : 'Order returned to store.',
                $order->shipping_courier,
                $order->shipping_tracking_no,
                Auth::id(),
            );

            DB::commit();

            return redirect()
                ->route('sales.index', ['channel' => 'online'])
                ->with('success', "Order {$order->invoice_no} marked as Returned. Stock restored.".($this->isCashOnDelivery($order) ? ' No COD cash was refunded.' : ''));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to mark returned. '.$e->getMessage());
        }
    }
}
