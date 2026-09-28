<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\DashboardSummaryService;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardSummaryService $summaryService)
    {
        $user = Auth::user();
        $shop = $user->shop;
        $shopId = $user->shop_id;
        $isAdmin = $user->isAdminUser();
        $retail = retail_enabled();
        // Without the retail module there are no counters: everyone who can open the dashboard sees shop totals.
        $shopWide = $isAdmin || ! $retail;
        $counters = $retail ? $summaryService->countersForShop($shopId) : collect();

        // Staff: always their counter. Admin: ?counter=all|id
        $filterCounterId = null;
        $filterLabel = $retail ? 'All counters' : 'your store';

        if (! $retail) {
            // Shop-wide, no counter filter.
        } elseif ($isAdmin) {
            $raw = $request->query('counter', 'all');
            if ($raw !== 'all' && $raw !== '' && $raw !== null) {
                $cid = (int) $raw;
                $match = $counters->firstWhere('id', $cid);
                if ($match) {
                    $filterCounterId = $cid;
                    $filterLabel = $match->name;
                }
            }
        } else {
            $filterCounterId = $user->counter_id;
            $filterLabel = $filterCounterId
                ? (Counter::find($filterCounterId)?->name ?? 'Your counter')
                : 'No counter';
        }

        $counter = $filterCounterId ? Counter::find($filterCounterId) : null;

        // Staff without a counter must never see shop-wide totals
        if (! $shopWide && $filterCounterId === null) {
            $businessSummary = $summaryService->emptySummary();
        } else {
            $businessSummary = $summaryService->todaySummary($shopId, $filterCounterId);
        }

        app(StockService::class)->ensureDefaultLocations($shopId);

        // Keep charts / week KPIs on the same revenue definition as Business Summary
        if (! $shopWide && $filterCounterId === null) {
            $queryOrders = $summaryService->revenueOrdersQuery($shopId)->whereRaw('1 = 0');
        } else {
            $queryOrders = $summaryService->revenueOrdersQuery($shopId, $filterCounterId);
        }

        $today = Carbon::today();
        $weekStart = $today->copy()->subDays(6)->startOfDay();
        $prevWeekStart = $today->copy()->subDays(13)->startOfDay();
        $prevWeekEnd = $today->copy()->subDays(7)->endOfDay();

        $canReadSales = $shopWide || $filterCounterId !== null;

        $todaySales = $canReadSales
            ? (float) ($businessSummary['total_sales'] ?? 0)
            : 0.0;
        $todayOrdersCount = $canReadSales
            ? (int) (clone $queryOrders)->whereDate('created_at', $today)->count()
            : 0;

        $weekSales = $canReadSales
            ? $summaryService->salesForPeriod($shopId, $filterCounterId, $weekStart, $today->copy()->endOfDay())
            : 0.0;
        $weekOrders = $canReadSales
            ? (int) (clone $queryOrders)->where('created_at', '>=', $weekStart)->count()
            : 0;
        $prevWeekSales = $canReadSales
            ? $summaryService->salesForPeriod($shopId, $filterCounterId, $prevWeekStart, $prevWeekEnd)
            : 0.0;
        $prevWeekOrders = $canReadSales
            ? (int) (clone $queryOrders)->whereBetween('created_at', [$prevWeekStart, $prevWeekEnd])->count()
            : 0;

        if ($shopWide && $filterCounterId === null) {
            $totalCustomers = Customer::where('shop_id', $shopId)->count();
            $registeredCustomers = $totalCustomers;
        } else {
            $totalCustomers = (int) Order::where('shop_id', $shopId)
                ->when($filterCounterId, fn ($q) => $q->where('counter_id', $filterCounterId), fn ($q) => $q->whereRaw('1 = 0'))
                ->whereNotNull('customer_id')
                ->selectRaw('COUNT(DISTINCT customer_id) as aggregate')
                ->value('aggregate');
            $registeredCustomers = $totalCustomers;
        }

        $lowStockCount = Product::where('shop_id', $shopId)
            ->whereColumn('stock_quantity', '<=', 'alert_quantity')
            ->count();

        $totalProducts = Product::where('shop_id', $shopId)->count();

        $inventoryValue = (float) (Product::where('shop_id', $shopId)
            ->selectRaw('COALESCE(SUM(selling_price * stock_quantity), 0) as total_value')
            ->value('total_value') ?? 0);

        $salesChangePct = $this->pctChange($weekSales, $prevWeekSales);
        $ordersChangePct = $this->pctChange($weekOrders, $prevWeekOrders);

        $salesChartLabels = [];
        $salesChartThisWeek = [];
        $salesChartLastWeek = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $prevDay = $day->copy()->subDays(7);
            $salesChartLabels[] = $day->format('M j');
            $salesChartThisWeek[] = $canReadSales
                ? round($summaryService->salesForPeriod($shopId, $filterCounterId, $day->copy()->startOfDay(), $day->copy()->endOfDay()), 2)
                : 0.0;
            $salesChartLastWeek[] = $canReadSales
                ? round($summaryService->salesForPeriod($shopId, $filterCounterId, $prevDay->copy()->startOfDay(), $prevDay->copy()->endOfDay()), 2)
                : 0.0;
        }

        $recentOrders = (clone $queryOrders)
            ->with(['customer'])
            ->latest()
            ->take(5)
            ->get();

        $scopedOrderFilter = function ($q) use ($shopId, $filterCounterId, $shopWide) {
            $q->where('shop_id', $shopId)
                ->where('status', 'completed')
                ->where(function ($inner) {
                    $inner->where('is_exchange_receipt', false)
                        ->orWhereNull('is_exchange_receipt');
                });
            if ($filterCounterId !== null) {
                $q->where('counter_id', $filterCounterId);
            } elseif (! $shopWide) {
                $q->whereRaw('1 = 0');
            }
        };

        $topProducts = OrderItem::query()
            ->select(
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as sold_qty'),
                DB::raw('SUM(order_items.subtotal) as revenue')
            )
            ->whereHas('order', function ($q) use ($scopedOrderFilter, $weekStart) {
                $scopedOrderFilter($q);
                $q->where('created_at', '>=', $weekStart);
            })
            ->groupBy('order_items.product_id')
            ->orderByDesc('sold_qty')
            ->with('product')
            ->take(5)
            ->get();

        if ($topProducts->isEmpty()) {
            $topProducts = OrderItem::query()
                ->select(
                    'order_items.product_id',
                    DB::raw('SUM(order_items.quantity) as sold_qty'),
                    DB::raw('SUM(order_items.subtotal) as revenue')
                )
                ->whereHas('order', $scopedOrderFilter)
                ->groupBy('order_items.product_id')
                ->orderByDesc('sold_qty')
                ->with('product')
                ->take(5)
                ->get();
        }

        $categorySales = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.shop_id', $shopId)
            ->where('orders.status', 'completed')
            ->where(function ($q) {
                $q->where('orders.is_exchange_receipt', false)
                    ->orWhereNull('orders.is_exchange_receipt');
            })
            ->when($filterCounterId !== null, fn ($q) => $q->where('orders.counter_id', $filterCounterId))
            ->when(! $shopWide && $filterCounterId === null, fn ($q) => $q->whereRaw('1 = 0'))
            ->where('orders.created_at', '>=', $weekStart)
            ->select(
                DB::raw("COALESCE(categories.name, 'Uncategorized') as category_name"),
                DB::raw('SUM(order_items.subtotal) as revenue')
            )
            ->groupBy(DB::raw("COALESCE(categories.name, 'Uncategorized')"))
            ->orderByDesc('revenue')
            ->take(5)
            ->get();

        $categorySalesTotal = (float) $categorySales->sum('revenue');

        $pendingOnlineOrders = 0;
        if ($isAdmin && $filterCounterId === null) {
            $pendingOnlineOrders = (int) Order::where('shop_id', $shopId)
                ->onlineOrders()
                ->whereIn('status', ['pending', 'pending_fulfillment'])
                ->count();
        }

        $counterBreakdown = null;
        $onlineToday = null;

        if ($isAdmin && $retail) {
            $rows = Order::where('shop_id', $shopId)
                ->whereDate('created_at', $today)
                ->where('status', 'completed')
                ->where(function ($q) {
                    $q->where('is_exchange_receipt', false)
                        ->orWhereNull('is_exchange_receipt');
                })
                ->select(
                    'counter_id',
                    DB::raw('COUNT(*) as orders_count'),
                    DB::raw('COALESCE(SUM(GREATEST(total_amount - COALESCE(discount_amount, 0) - COALESCE(exchange_credit, 0), 0)), 0) as sales_total'),
                    DB::raw('COUNT(DISTINCT customer_id) as customers_count')
                )
                ->groupBy('counter_id')
                ->get()
                ->keyBy('counter_id');

            $bakiByCounter = $summaryService->todayArCollectionsByCounter($shopId, 'baki_payment', 'BAKI-AR', $today);
            $emiByCounter = $summaryService->todayArCollectionsByCounter($shopId, 'emi_payment', 'EMI-AR', $today);

            $counterBreakdown = Counter::where('shop_id', $shopId)
                ->orderBy('name')
                ->get()
                ->map(function (Counter $c) use ($rows, $bakiByCounter, $emiByCounter) {
                    $row = $rows->get($c->id);

                    return (object) [
                        'id' => $c->id,
                        'name' => $c->name,
                        'sales_total' => (float) ($row->sales_total ?? 0),
                        'orders_count' => (int) ($row->orders_count ?? 0),
                        'customers_count' => (int) ($row->customers_count ?? 0),
                        'baki_collected' => (float) ($bakiByCounter->get($c->id) ?? 0),
                        'emi_collected' => (float) ($emiByCounter->get($c->id) ?? 0),
                    ];
                });

            $onlineRow = Order::where('shop_id', $shopId)
                ->onlineOrders()
                ->whereDate('created_at', $today)
                ->where('status', 'completed')
                ->where(function ($q) {
                    $q->where('is_exchange_receipt', false)
                        ->orWhereNull('is_exchange_receipt');
                })
                ->selectRaw('COUNT(*) as orders_count, COALESCE(SUM(GREATEST(total_amount - COALESCE(discount_amount, 0) - COALESCE(exchange_credit, 0), 0)), 0) as sales_total, COUNT(DISTINCT customer_id) as customers_count')
                ->first();

            $onlineToday = (object) [
                'name' => 'Online store',
                'sales_total' => (float) ($onlineRow->sales_total ?? 0),
                'orders_count' => (int) ($onlineRow->orders_count ?? 0),
                'customers_count' => (int) ($onlineRow->customers_count ?? 0),
            ];
        }

        $dateRangeLabel = $weekStart->format('M j').' – '.$today->format('M j, Y');
        $selectedCounter = $filterCounterId === null ? 'all' : (string) $filterCounterId;

        return view('dashboard', compact(
            'shop',
            'todaySales',
            'todayOrdersCount',
            'weekSales',
            'weekOrders',
            'salesChangePct',
            'ordersChangePct',
            'totalCustomers',
            'lowStockCount',
            'inventoryValue',
            'totalProducts',
            'isAdmin',
            'counter',
            'counters',
            'selectedCounter',
            'filterLabel',
            'filterCounterId',
            'businessSummary',
            'counterBreakdown',
            'onlineToday',
            'registeredCustomers',
            'salesChartLabels',
            'salesChartThisWeek',
            'salesChartLastWeek',
            'recentOrders',
            'topProducts',
            'categorySales',
            'categorySalesTotal',
            'pendingOnlineOrders',
            'dateRangeLabel',
            'retail',
        ));
    }

    private function pctChange(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
