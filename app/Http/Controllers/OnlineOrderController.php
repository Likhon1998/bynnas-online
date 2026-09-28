<?php

namespace App\Http\Controllers;

use App\Models\CourierService;
use App\Models\Order;
use App\Services\AccountService;
use App\Services\OnlineOrderTrackingService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OnlineOrderController extends Controller
{
    public function __construct(
        protected AccountService $accounts,
        protected StockService $stock,
        protected OnlineOrderTrackingService $tracking,
    ) {}

    protected function ensureAdmin(): void
    {
        if (! Auth::user()?->isAdminUser()) {
            abort(403, 'Online orders are only available to shop admins.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();
        $shopId = Auth::user()->shop_id;

        $filterDate = $request->input('date');
        $search = $request->input('search', '');
        $statusFilter = $request->input('status', 'all') ?: 'all';

        session(['online_orders_seen_at' => now()->toDateTimeString()]);

        $statsQuery = Order::where('shop_id', $shopId)->onlineOrders();
        if ($filterDate) {
            $statsQuery->whereDate('created_at', $filterDate);
        }

        $stats = $statsQuery->selectRaw("
            COALESCE(SUM(CASE WHEN status IN ('pending', 'pending_fulfillment') THEN 1 ELSE 0 END), 0) as pending_count,
            COALESCE(SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END), 0) as processing_count,
            COALESCE(SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END), 0) as shipped_count,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN GREATEST(0, total_amount - COALESCE(delivery_charge, 0) - COALESCE(discount_amount, 0) - COALESCE(exchange_credit, 0)) ELSE 0 END), 0) as settled_revenue
        ")->first();

        $pendingCount = (int) ($stats->pending_count ?? 0);
        $processingCount = (int) ($stats->processing_count ?? 0);
        $shippedCount = (int) ($stats->shipped_count ?? 0);
        $settledRevenue = (float) ($stats->settled_revenue ?? 0);

        $dueQuery = Order::where('shop_id', $shopId)
            ->onlineOrders()
            ->where('status', 'shipped')
            ->whereNull('courier_collected_at');

        if ($filterDate) {
            $dueQuery->whereDate('created_at', $filterDate);
        }

        $dueOrders = $dueQuery->with('courierService:id,name')->get([
            'id', 'courier_service_id', 'shipping_courier', 'total_amount', 'delivery_charge',
            'discount_amount', 'exchange_credit', 'confirmation_charge', 'paid_amount',
            'status', 'courier_collected_at', 'counter_id', 'invoice_no', 'payment_method',
        ]);

        $courierReceivables = 0.0;
        $dueByCourier = [];
        foreach ($dueOrders as $order) {
            $due = $order->amountDueFromCourier();
            if ($due <= 0.009) {
                continue;
            }
            $courierReceivables += $due;
            $key = $order->courier_service_id ?: 0;
            $label = $order->courierService?->name ?: ($order->shipping_courier ?: 'Unassigned courier');
            if (! isset($dueByCourier[$key])) {
                $dueByCourier[$key] = [
                    'name' => $label,
                    'amount' => 0.0,
                    'orders' => 0,
                ];
            }
            $dueByCourier[$key]['amount'] += $due;
            $dueByCourier[$key]['orders']++;
        }
        $dueByCourier = collect($dueByCourier)
            ->sortByDesc('amount')
            ->values()
            ->map(fn ($row) => [
                'name' => $row['name'],
                'amount' => round($row['amount'], 2),
                'amount_fmt' => format_taka_number($row['amount']),
                'orders' => $row['orders'],
            ])
            ->all();

        // Preload recent orders for instant client-side filtering (no page reload).
        $liveQuery = Order::where('shop_id', $shopId)
            ->onlineOrders()
            ->with([
                'customer:id,name,phone,address',
                'items:id,order_id,product_id,quantity,subtotal',
                'items.product:id,name',
                'courierService:id,name',
            ]);

        if ($filterDate) {
            $liveQuery->whereDate('created_at', $filterDate);
        }

        $ordersPayload = $liveQuery->latest('created_at')
            ->limit(250)
            ->get()
            ->map(fn (Order $order) => $this->orderListPayload($order))
            ->values();

        return view('online-orders.index', compact(
            'ordersPayload',
            'pendingCount',
            'processingCount',
            'shippedCount',
            'courierReceivables',
            'dueByCourier',
            'settledRevenue',
            'filterDate',
            'search',
            'statusFilter',
        ));
    }

    private function orderListPayload(Order $order): array
    {
        $productRevenue = (float) $order->total_amount - (float) ($order->delivery_charge ?? 0);
        $isVoided = in_array($order->status, ['refunded', 'cancelled', 'returned'], true);
        $dueFromCourier = $order->amountDueFromCourier();

        return [
            'id' => $order->id,
            'invoice' => $order->invoice_no,
            'status' => $order->status,
            'created_at' => asian_datetime($order->created_at, 'd M Y, h:i A'),
            'payment_method' => str_replace('_', ' ', (string) $order->payment_method),
            'product_revenue' => format_taka_number($productRevenue),
            'delivery_charge' => (float) ($order->delivery_charge ?? 0),
            'delivery_charge_fmt' => format_taka_number((float) ($order->delivery_charge ?? 0)),
            'shipping_courier' => $order->courierService?->name ?: $order->shipping_courier,
            'shipping_tracking_no' => $order->shipping_tracking_no,
            'due_from_courier' => $dueFromCourier,
            'due_from_courier_fmt' => format_taka_number($dueFromCourier),
            'is_voided' => $isVoided,
            'show_url' => route('online-orders.show', $order),
            'receipt_url' => route('orders.invoice', $order->id),
            'customer_name' => $order->customer->name ?? 'Guest',
            'customer_phone' => $order->customer->phone ?? 'N/A',
            'customer_address' => $order->customer->address ?? 'No address provided',
            'items' => $order->items->map(fn ($item) => [
                'qty' => (int) $item->quantity,
                'name' => $item->product->name ?? 'Unknown Product',
            ])->values()->all(),
            'search_blob' => mb_strtolower(implode(' ', array_filter([
                $order->invoice_no,
                $order->shipping_tracking_no,
                $order->courierService?->name,
                $order->shipping_courier,
                $order->customer?->name,
                $order->customer?->phone,
            ]))),
        ];
    }

    public function show(Order $order)
    {
        $this->ensureAdmin();
        abort_unless($order->shop_id === Auth::user()->shop_id && $order->isOnlineOrder(), 403);

        // Opening an order from the bell should clear the unread badge.
        session(['online_orders_seen_at' => now()->toDateTimeString()]);

        $order->load([
            'customer:id,name,phone,address,email',
            'items:id,order_id,product_id,quantity,unit_price,subtotal',
            'items.product:id,name',
            'courierService:id,name,phone',
            'statusLogs' => fn ($q) => $q->latest('id')->limit(20),
        ]);

        $timeline = $this->tracking->customerTimeline($order);
        $statusLabels = $this->tracking->statusLabels();
        $allowedNextStatuses = array_values(array_unique(array_merge(
            [$order->status],
            self::STATUS_TRANSITIONS[$order->status] ?? [],
        )));

        $courierServices = CourierService::forShop(Auth::user()->shop_id)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $dueFromCourier = $order->amountDueFromCourier();

        return view('online-orders.show', compact(
            'order',
            'timeline',
            'statusLabels',
            'allowedNextStatuses',
            'courierServices',
            'dueFromCourier',
        ));
    }

    public function notifications()
    {
        $this->ensureAdmin();
        $shopId = Auth::user()->shop_id;
        $seenAt = session('online_orders_seen_at');

        $unreadQuery = Order::where('shop_id', $shopId)->onlineOrders();
        if ($seenAt) {
            $unreadQuery->where('created_at', '>', $seenAt);
        } else {
            // First visit: only treat the last 24 hours as unread.
            $unreadQuery->where('created_at', '>', now()->subDay());
        }
        $unread = (int) $unreadQuery->count();

        $orders = Order::where('shop_id', $shopId)
            ->onlineOrders()
            ->with('customer:id,name,phone')
            ->latest('created_at')
            ->limit(10)
            ->get(['id', 'invoice_no', 'status', 'total_amount', 'customer_id', 'created_at']);

        $labels = $this->tracking->statusLabels();

        $items = $orders->map(function (Order $order) use ($seenAt, $labels) {
            $isNew = $seenAt
                ? $order->created_at->greaterThan($seenAt)
                : $order->created_at->greaterThan(now()->subDay());

            return [
                'id' => $order->id,
                'invoice' => $order->invoice_no,
                'status' => $order->status,
                'status_label' => $labels[$order->status] ?? ucfirst($order->status),
                'customer' => $order->customer?->name ?? 'Guest',
                'phone' => $order->customer?->phone,
                'total' => format_taka_number((float) $order->total_amount),
                'at' => $order->created_at->diffForHumans(),
                'url' => route('online-orders.show', $order),
                'is_new' => $isNew,
            ];
        });

        return response()->json([
            'unread' => $unread,
            'items' => $items->values(),
        ]);
    }

    public function markNotificationsSeen()
    {
        $this->ensureAdmin();
        session(['online_orders_seen_at' => now()->toDateTimeString()]);

        return response()->json(['ok' => true]);
    }

    /** Allowed forward status changes for online orders (terminal states cannot reopen). */
    private const STATUS_TRANSITIONS = [
        'pending' => ['processing', 'cancelled'],
        'pending_fulfillment' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['completed', 'returned', 'cancelled'],
        'completed' => ['refunded'],
        'cancelled' => [],
        'returned' => [],
        'refunded' => [],
    ];

    private const AWAITING_FULFILLMENT = ['pending', 'pending_fulfillment'];

    public function updateStatus(Request $request, Order $order)
    {
        $this->ensureAdmin();
        if ($order->shop_id !== Auth::user()->shop_id || ! $order->isOnlineOrder()) {
            abort(403, 'Unauthorized Access');
        }

        $shopId = Auth::user()->shop_id;

        $request->validate([
            'status' => 'required|in:pending,pending_fulfillment,processing,shipped,completed,cancelled,returned,refunded',
            'customer_note' => 'nullable|string|max:500',
            'courier_service_id' => [
                'nullable',
                'integer',
                Rule::exists('courier_services', 'id')->where(fn ($q) => $q->where('shop_id', $shopId)->where('is_active', true)),
            ],
            'tracking_number' => 'nullable|string|max:120',
        ]);

        $oldStatus = $order->status;
        $newStatus = $request->status;

        if (
            $oldStatus === $newStatus
            && ! $request->filled('customer_note')
            && ! $request->filled('tracking_number')
            && ! $request->filled('courier_service_id')
        ) {
            return back();
        }

        if ($oldStatus !== $newStatus) {
            $allowed = self::STATUS_TRANSITIONS[$oldStatus] ?? [];
            if (! in_array($newStatus, $allowed, true)) {
                return back()->with('error', "Cannot change status from {$oldStatus} to {$newStatus}.");
            }
        }

        $courierServiceId = $request->filled('courier_service_id')
            ? (int) $request->courier_service_id
            : $order->courier_service_id;

        if ($newStatus === 'shipped' && ! $courierServiceId) {
            return back()->with('error', 'Select a courier service when marking as shipped. Add services under CMS → Courier Services.');
        }

        $courierService = $courierServiceId
            ? CourierService::forShop($shopId)->find($courierServiceId)
            : null;

        $isCod = $order->isCashOnDelivery();
        $moneyCollected = $isCod
            ? ($oldStatus === 'completed' || (float) $order->paid_amount > 0 || $order->courier_collected_at)
            : ((float) $order->paid_amount > 0 || $oldStatus === 'completed');

        if ($newStatus === 'refunded' && $oldStatus !== 'completed') {
            return back()->with('error', 'Refund is only available after delivery. Use Returned if the order is not delivered yet.');
        }

        if ($newStatus === 'returned' && $oldStatus === 'completed') {
            return back()->with('error', 'This order is already delivered. Use Refund instead of Returned.');
        }

        try {
            DB::beginTransaction();

            $paidAmount = $order->paid_amount;
            $courierCollectedAt = $order->courier_collected_at;
            $courierCollectedAmount = $order->courier_collected_amount;

            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $dueFromCourier = max(0, round($order->shopCollectableAmount() - $order->shopAdvancePaid(), 2));
                $paidAmount = $order->netPayable();
                if (! $courierCollectedAt && $dueFromCourier > 0.009) {
                    $courierCollectedAt = now();
                    $courierCollectedAmount = $dueFromCourier;
                } elseif (! $courierCollectedAt) {
                    $courierCollectedAt = now();
                    $courierCollectedAmount = 0;
                }
            }

            if (in_array($newStatus, ['cancelled', 'returned', 'refunded'])) {
                $paidAmount = 0;
            }

            $courierName = $courierService?->name ?: $order->shipping_courier;
            $trackingNumber = $request->filled('tracking_number')
                ? $request->tracking_number
                : $order->shipping_tracking_no;

            $order->update([
                'status' => $newStatus,
                'paid_amount' => $paidAmount,
                'courier_service_id' => $courierServiceId,
                'shipping_courier' => $courierName,
                'shipping_tracking_no' => $trackingNumber,
                'courier_collected_at' => $courierCollectedAt,
                'courier_collected_amount' => $courierCollectedAmount,
            ]);

            $defaultNotes = [
                'processing' => 'We are packing your items now.',
                'shipped' => 'Your package is on the way to your delivery address.',
                'completed' => 'Order delivered successfully.',
                'cancelled' => 'This order was cancelled.',
                'returned' => $isCod && ! $moneyCollected
                    ? 'Order returned. COD was not collected — no customer refund.'
                    : 'This order was returned to our store.',
                'refunded' => 'This order was refunded.',
            ];

            $this->tracking->upsertLatestLog(
                $order,
                $newStatus,
                $request->customer_note ?: ($defaultNotes[$newStatus] ?? null),
                $courierName,
                $trackingNumber,
                Auth::id(),
            );

            if ($newStatus === 'processing' && in_array($oldStatus, self::AWAITING_FULFILLMENT, true)) {
                $order->load('items.product');
                $this->stock->commitWebOrderStock($order, Auth::id());
            }

            // If an order skips packing and goes awaiting → shipped, still commit stock.
            if ($newStatus === 'shipped' && in_array($oldStatus, [...self::AWAITING_FULFILLMENT, 'processing'], true)) {
                $order->load('items.product');
                $this->stock->commitWebOrderStock($order, Auth::id());
            }

            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $order->load('items.product');
                $this->stock->commitWebOrderStock($order, Auth::id());
                $this->accounts->postWebSettlement($order);
            }

            if (in_array($newStatus, ['cancelled', 'returned', 'refunded']) && ! in_array($oldStatus, ['cancelled', 'returned', 'refunded'])) {
                $order->load('items.product', 'counter');
                // Drop reservation (or restock physical if already packed).
                $this->stock->releaseReservedStock($order, Auth::id(), 'order_'.$newStatus);
                $this->accounts->postOrderRefund($order);
            }

            DB::commit();

            $msg = "Order {$order->invoice_no} updated to ".ucfirst($newStatus).'. Customer can now see this on tracking.';
            if ($newStatus === 'completed' && (float) ($courierCollectedAmount ?? 0) > 0.009) {
                $msg .= ' Collected ৳'.format_taka_number((float) $courierCollectedAmount).' from courier (products only).';
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    /**
     * Remit COD held by the courier: settle shop cash and mark order completed.
     */
    public function collectFromCourier(Request $request, Order $order)
    {
        $this->ensureAdmin();
        if ($order->shop_id !== Auth::user()->shop_id || ! $order->isOnlineOrder()) {
            abort(403, 'Unauthorized Access');
        }

        if ($order->status !== 'shipped') {
            return back()->with('error', 'Only shipped orders can be collected from the courier.');
        }

        if ($order->courier_collected_at) {
            return back()->with('error', 'Cash from this courier was already recorded.');
        }

        $due = $order->amountDueFromCourier();

        try {
            DB::beginTransaction();

            $order->update([
                'status' => 'completed',
                'paid_amount' => $order->netPayable(),
                'courier_collected_at' => now(),
                'courier_collected_amount' => $due,
            ]);

            $this->tracking->upsertLatestLog(
                $order,
                'completed',
                $due > 0.009
                    ? 'Delivered. Collected ৳'.format_taka_number($due).' product COD from courier (delivery fee stays with courier).'
                    : 'Order delivered successfully.',
                $order->shipping_courier,
                $order->shipping_tracking_no,
                Auth::id(),
            );

            $order->load('items.product');
            $this->stock->commitWebOrderStock($order, Auth::id());
            $this->accounts->postWebSettlement($order);

            DB::commit();

            $service = $order->courierService?->name ?: ($order->shipping_courier ?: 'courier');

            return back()->with(
                'success',
                $due > 0.009
                    ? "Collected ৳".format_taka_number($due)." from {$service}. Order marked completed."
                    : "Order marked completed. No COD was outstanding from {$service}."
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }
}
