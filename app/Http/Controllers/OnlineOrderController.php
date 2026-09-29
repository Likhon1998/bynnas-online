<?php

namespace App\Http\Controllers;

use App\Models\CourierService;
use App\Models\Order;
use App\Services\OnlineOrderTrackingService;
use App\Services\OrderWorkflowException;
use App\Services\OrderWorkflowService;
use App\Support\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OnlineOrderController extends Controller
{
    public function __construct(
        protected OnlineOrderTrackingService $tracking,
        protected OrderWorkflowService $workflow,
    ) {}

    protected function ensureAdmin(): void
    {
        if (! Auth::user()?->isAdminUser()) {
            abort(403, 'Online orders are only available to shop admins.');
        }
    }

    protected function authorizeOrder(Order $order): void
    {
        $this->ensureAdmin();
        if ($order->shop_id !== Auth::user()->shop_id || ! $order->isOnlineOrder()) {
            abort(403, 'Unauthorized Access');
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

        $statusCounts = (clone $statsQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $countFor = fn (array $statuses) => (int) collect(OrderStatus::expand($statuses))
            ->sum(fn ($status) => (int) ($statusCounts[$status] ?? 0));

        $pendingCount = $countFor([OrderStatus::NEW]);
        $confirmedCount = $countFor([OrderStatus::CONFIRMED]);
        $processingCount = $countFor([OrderStatus::PROCESSING, OrderStatus::PACKED]);
        $shippedCount = $countFor([OrderStatus::SHIPPED]);
        $returnRequestedCount = $countFor([OrderStatus::RETURN_REQUESTED]);

        $settledRevenue = (float) (clone $statsQuery)
            ->where('status', OrderStatus::COMPLETED)
            ->selectRaw('COALESCE(SUM(CASE WHEN total_amount - COALESCE(delivery_charge, 0) - COALESCE(discount_amount, 0) - COALESCE(exchange_credit, 0) > 0 THEN total_amount - COALESCE(delivery_charge, 0) - COALESCE(discount_amount, 0) - COALESCE(exchange_credit, 0) ELSE 0 END), 0) as settled')
            ->value('settled');

        $dueQuery = Order::where('shop_id', $shopId)
            ->onlineOrders()
            ->whereIn('status', [OrderStatus::SHIPPED, OrderStatus::DELIVERED])
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

        $statusLabels = OrderStatus::labels();
        $statusBadges = OrderStatus::badgeClasses();

        return view('online-orders.index', compact(
            'ordersPayload',
            'pendingCount',
            'confirmedCount',
            'returnRequestedCount',
            'processingCount',
            'shippedCount',
            'courierReceivables',
            'dueByCourier',
            'settledRevenue',
            'filterDate',
            'search',
            'statusFilter',
            'statusLabels',
            'statusBadges',
        ));
    }

    private function orderListPayload(Order $order): array
    {
        $productRevenue = (float) $order->total_amount - (float) ($order->delivery_charge ?? 0);
        $dueFromCourier = $order->amountDueFromCourier();

        return [
            'id' => $order->id,
            'invoice' => $order->invoice_no,
            'status' => $order->workflowStatus(),
            'is_verified' => $order->isVerified(),
            'created_at' => asian_datetime($order->created_at, 'd M Y, h:i A'),
            'payment_method' => str_replace('_', ' ', (string) $order->payment_method),
            'product_revenue' => format_taka_number($productRevenue),
            'delivery_charge' => (float) ($order->delivery_charge ?? 0),
            'delivery_charge_fmt' => format_taka_number((float) ($order->delivery_charge ?? 0)),
            'shipping_courier' => $order->courierService?->name ?: $order->shipping_courier,
            'shipping_tracking_no' => $order->shipping_tracking_no,
            'due_from_courier' => $dueFromCourier,
            'due_from_courier_fmt' => format_taka_number($dueFromCourier),
            'is_voided' => OrderStatus::isVoid($order->status),
            'show_url' => route('online-orders.show', $order),
            'receipt_url' => route('orders.invoice', $order->id),
            'customer_name' => $order->delivery_name ?: ($order->customer->name ?? 'Guest'),
            'customer_phone' => $order->delivery_phone ?: ($order->customer->phone ?? 'N/A'),
            'customer_address' => $order->delivery_address ?: ($order->customer->address ?? 'No address provided'),
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
                $order->delivery_phone,
            ]))),
        ];
    }

    public function show(Order $order)
    {
        $this->authorizeOrder($order);

        // Opening an order from the bell should clear the unread badge.
        session(['online_orders_seen_at' => now()->toDateTimeString()]);

        $order->load([
            'customer:id,name,phone,address,email',
            'items:id,order_id,product_id,quantity,unit_price,subtotal',
            'items.product:id,name',
            'courierService:id,name,phone',
            'verifier:id,name',
            'statusLogs' => fn ($q) => $q->latest('id')->limit(20),
        ]);

        $timeline = $this->tracking->customerTimeline($order);
        $statusLabels = OrderStatus::labels();
        $currentStatus = $order->workflowStatus();
        $allowedNextStatuses = array_values(array_unique(array_merge(
            [$currentStatus],
            OrderStatus::nextFor($currentStatus),
        )));
        $verificationMethods = OrderStatus::VERIFICATION_METHODS;

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
            'currentStatus',
            'verificationMethods',
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

        $items = $orders->map(function (Order $order) use ($seenAt) {
            $isNew = $seenAt
                ? $order->created_at->greaterThan($seenAt)
                : $order->created_at->greaterThan(now()->subDay());

            return [
                'id' => $order->id,
                'invoice' => $order->invoice_no,
                'status' => $order->workflowStatus(),
                'status_label' => OrderStatus::label($order->status),
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

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        $shopId = Auth::user()->shop_id;

        $data = $request->validate([
            'status' => ['required', Rule::in(OrderStatus::storedValues())],
            'customer_note' => 'nullable|string|max:500',
            'courier_service_id' => [
                'nullable',
                'integer',
                Rule::exists('courier_services', 'id')->where(fn ($q) => $q->where('shop_id', $shopId)->where('is_active', true)),
            ],
            'tracking_number' => 'nullable|string|max:120',
            'verification_method' => ['nullable', Rule::in(array_keys(OrderStatus::VERIFICATION_METHODS))],
            'verification_notes' => 'nullable|string|max:500',
            'return_reason' => 'nullable|string|max:500',
        ]);

        $from = $order->workflowStatus();
        $to = OrderStatus::normalize($data['status']);

        try {
            $order = $this->workflow->transition($order, $to, [
                'note' => $data['customer_note'] ?? null,
                'courier_service_id' => $data['courier_service_id'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'verification_method' => $data['verification_method'] ?? null,
                'verification_notes' => $data['verification_notes'] ?? null,
                'return_reason' => $data['return_reason'] ?? null,
            ], Auth::id());
        } catch (OrderWorkflowException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($from === $to) {
            return back()->with('success', "Order {$order->invoice_no} details saved.");
        }

        $msg = "Order {$order->invoice_no} updated to ".OrderStatus::label($to).'. Customer can now see this on tracking.';
        if ($to === OrderStatus::COMPLETED && (float) ($order->courier_collected_amount ?? 0) > 0.009) {
            $msg .= ' Collected ৳'.format_taka_number((float) $order->courier_collected_amount).' from courier (products only).';
        }

        return back()->with('success', $msg);
    }

    /** Record how the order was verified with the customer and confirm it. */
    public function verify(Request $request, Order $order)
    {
        $this->authorizeOrder($order);

        $data = $request->validate([
            'verification_method' => ['required', Rule::in(array_keys(OrderStatus::VERIFICATION_METHODS))],
            'verification_notes' => 'nullable|string|max:500',
            'customer_note' => 'nullable|string|max:500',
        ]);

        if ($order->workflowStatus() !== OrderStatus::NEW) {
            return back()->with('error', 'Only new orders can be verified.');
        }

        try {
            $this->workflow->verify(
                $order,
                $data['verification_method'],
                $data['verification_notes'] ?? null,
                Auth::id(),
                $data['customer_note'] ?? null,
            );
        } catch (OrderWorkflowException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Order {$order->invoice_no} verified and confirmed.");
    }

    /**
     * Remit COD held by the courier: settle shop cash and mark order completed.
     */
    public function collectFromCourier(Request $request, Order $order)
    {
        $this->authorizeOrder($order);

        $due = $order->amountDueFromCourier();

        try {
            $order = $this->workflow->collectFromCourier($order, Auth::id());
        } catch (OrderWorkflowException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $service = $order->courierService?->name ?: ($order->shipping_courier ?: 'courier');

        return back()->with(
            'success',
            $due > 0.009
                ? 'Collected ৳'.format_taka_number($due)." from {$service}. Order marked completed."
                : "Order marked completed. No COD was outstanding from {$service}."
        );
    }
}
