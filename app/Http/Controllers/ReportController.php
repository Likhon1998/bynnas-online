<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected function ensureAdmin(): void
    {
        if (! Auth::user()?->isAdminUser()) {
            abort(403, 'Reports are only available to shop admins.');
        }
    }

    protected function completedOrdersQuery(int $shopId, $startDate, $endDate)
    {
        return Order::where('shop_id', $shopId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->where('is_exchange_receipt', false)
                    ->orWhereNull('is_exchange_receipt');
            });
    }

    /**
     * SQL expressions for Cash / Card·Bank / bKash using tender columns
     * (with legacy payment_method fallback when breakdown is missing).
     */
    protected function tenderSumExpressions(string $netExpr): array
    {
        $hasBreakdown = '(cash_paid IS NOT NULL OR card_paid IS NOT NULL OR mobile_paid IS NOT NULL)';
        $method = "LOWER(COALESCE(payment_method, ''))";

        $cashFallback = "({$method} = 'cash'
            OR (
                {$method} LIKE '%cash%'
                AND {$method} NOT LIKE '%+%'
                AND {$method} NOT LIKE '%card%'
                AND {$method} NOT LIKE '%bank%'
                AND {$method} NOT LIKE '%bkash%'
                AND {$method} NOT LIKE '%nagad%'
                AND {$method} NOT LIKE '%mobile%'
            ))";

        $cardFallback = "({$method} IN ('card', 'bank')
            OR (
                ({$method} LIKE '%card%' OR {$method} LIKE '%bank%')
                AND {$method} NOT LIKE '%+%'
                AND {$method} NOT LIKE '%cash%'
                AND {$method} NOT LIKE '%bkash%'
            ))";

        $bkashFallback = "({$method} IN ('bkash', 'nagad', 'mobile')
            OR (
                ({$method} LIKE '%bkash%' OR {$method} LIKE '%nagad%' OR {$method} LIKE '%mobile%')
                AND {$method} NOT LIKE '%+%'
                AND {$method} NOT LIKE '%cash%'
                AND {$method} NOT LIKE '%card%'
            ))";

        return [
            'cash' => "SUM(CASE WHEN {$hasBreakdown} THEN GREATEST(COALESCE(cash_paid, 0) - COALESCE(change_amount, 0), 0) WHEN {$cashFallback} THEN {$netExpr} ELSE 0 END)",
            'card' => "SUM(CASE WHEN {$hasBreakdown} THEN COALESCE(card_paid, 0) WHEN {$cardFallback} THEN {$netExpr} ELSE 0 END)",
            'bkash' => "SUM(CASE WHEN {$hasBreakdown} THEN COALESCE(mobile_paid, 0) WHEN {$bkashFallback} THEN {$netExpr} ELSE 0 END)",
        ];
    }

    public function dailySales(Request $request)
    {
        $this->ensureAdmin();

        $shopId = Auth::user()->shop_id;

        // Quick presets
        if ($request->boolean('today')) {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        } elseif ($request->has('all_time')) {
            $firstOrder = Order::where('shop_id', $shopId)->min('created_at');
            $startDate = $firstOrder ? Carbon::parse($firstOrder)->startOfDay() : now()->startOfMonth();
            $endDate = now()->endOfDay();
        } else {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::today()->startOfDay();
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::today()->endOfDay();
        }

        $netExpr = 'GREATEST(total_amount - COALESCE(discount_amount, 0) - COALESCE(exchange_credit, 0), 0)';
        $tenders = $this->tenderSumExpressions($netExpr);

        // 1. PERIOD SNAPSHOT (Cash / Card·Bank / bKash for the selected range)
        $summary = $this->completedOrdersQuery($shopId, $startDate, $endDate)
            ->select(
                DB::raw('COUNT(id) as total_orders'),
                DB::raw("SUM({$netExpr}) as total_revenue"),
                DB::raw("{$tenders['cash']} as cash_total"),
                DB::raw("{$tenders['card']} as card_total"),
                DB::raw("{$tenders['bkash']} as bkash_total")
            )->first();

        // 2. SALES BY EMPLOYEE
        $employeeSales = $this->completedOrdersQuery($shopId, $startDate, $endDate)
            ->select('user_id', DB::raw('COUNT(id) as total_orders'), DB::raw("SUM({$netExpr}) as total_revenue"))
            ->groupBy('user_id')
            ->with('user')
            ->orderByDesc('total_revenue')
            ->get();

        // 3. SALES BY COUNTER
        $counterSales = $this->completedOrdersQuery($shopId, $startDate, $endDate)
            ->select('counter_id', DB::raw('COUNT(id) as total_orders'), DB::raw("SUM({$netExpr}) as total_revenue"))
            ->groupBy('counter_id')
            ->with('counter')
            ->orderByDesc('total_revenue')
            ->get();

        // 4. HISTORICAL DAILY LEDGER (per-day cash / card / bkash)
        $historicalSales = $this->completedOrdersQuery($shopId, $startDate, $endDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(id) as total_orders'),
                DB::raw("SUM({$netExpr}) as total_revenue"),
                DB::raw("{$tenders['cash']} as cash_total"),
                DB::raw("{$tenders['card']} as card_total"),
                DB::raw("{$tenders['bkash']} as bkash_total")
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'desc')
            ->get();

        return view('reports.daily', compact('summary', 'employeeSales', 'counterSales', 'historicalSales', 'startDate', 'endDate'));
    }

    /**
     * Daily / period sales by brand (POS + website completed orders).
     */
    public function dailySalesByBrand(Request $request, AnalyticsService $analytics)
    {
        $this->ensureAdmin();

        $shopId = Auth::user()->shop_id;

        if ($request->boolean('today')) {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        } elseif ($request->has('all_time')) {
            $firstOrder = Order::where('shop_id', $shopId)->min('created_at');
            $startDate = $firstOrder ? Carbon::parse($firstOrder)->startOfDay() : now()->startOfMonth();
            $endDate = now()->endOfDay();
        } else {
            $startDate = $request->input('start_date')
                ? Carbon::parse($request->input('start_date'))->startOfDay()
                : Carbon::today()->startOfDay();
            $endDate = $request->input('end_date')
                ? Carbon::parse($request->input('end_date'))->endOfDay()
                : Carbon::today()->endOfDay();
        }

        $brandSales = $analytics->salesByBrand($shopId, $startDate, $endDate, null);
        $dailyByBrand = $analytics->dailySalesByBrand($shopId, $startDate, $endDate);

        $summary = (object) [
            'total_revenue' => (float) $brandSales->sum('revenue'),
            'total_cost' => (float) $brandSales->sum('cost'),
            'total_profit' => (float) $brandSales->sum('profit'),
            'total_units' => (int) $brandSales->sum('sold'),
            'brand_count' => $brandSales->count(),
            'pos_revenue' => (float) $brandSales->sum('pos_revenue'),
            'web_revenue' => (float) $brandSales->sum('web_revenue'),
            'pos_units' => (int) $brandSales->sum('pos_sold'),
            'web_units' => (int) $brandSales->sum('web_sold'),
            'orders_count' => (int) $brandSales->sum('orders_count'),
        ];

        $dailyGrouped = $dailyByBrand->groupBy('date');

        return view('reports.daily-by-brand', compact(
            'summary',
            'brandSales',
            'dailyByBrand',
            'dailyGrouped',
            'startDate',
            'endDate'
        ));
    }

    public function bestSellers(Request $request)
    {
        $this->ensureAdmin();

        $shopId = Auth::user()->shop_id;
        
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::today()->subDays(30)->startOfDay();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::today()->endOfDay();

        $bestSellers = \App\Models\OrderItem::whereHas('order', function ($query) use ($shopId, $startDate, $endDate) {
                $query->where('shop_id', $shopId)
                      ->whereBetween('created_at', [$startDate, $endDate])
                      ->where('status', 'completed')
                      ->where(function ($q) {
                          $q->where('is_exchange_receipt', false)
                              ->orWhereNull('is_exchange_receipt');
                      });
            })
            ->select(
                'product_id', 
                DB::raw('SUM(quantity) as total_sold')
            )
            ->groupBy('product_id')
            ->with('product') 
            ->orderByDesc('total_sold')
            ->paginate(15);

        $bestSellers->appends($request->all());

        return view('reports.best-sellers', compact('bestSellers', 'startDate', 'endDate'));
    }

    public function lowStock(Request $request)
    {
        $shopId = Auth::user()->shop_id;

        $base = \App\Models\Product::where('shop_id', $shopId)
            ->whereColumn('stock_quantity', '<=', 'alert_quantity');

        $outCount = (clone $base)->where('stock_quantity', '<=', 0)->count();
        $lowCount = (clone $base)->where('stock_quantity', '>', 0)->count();

        $query = (clone $base)->with(['category', 'brand'])->orderBy('stock_quantity', 'asc');

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('brand_name', 'like', "%{$q}%");
            });
        }

        if ($request->input('status') === 'out') {
            $query->where('stock_quantity', '<=', 0);
        } elseif ($request->input('status') === 'low') {
            $query->where('stock_quantity', '>', 0);
        }

        $lowStockItems = $query->paginate(20)->withQueryString();

        return view('reports.low-stock', compact('lowStockItems', 'outCount', 'lowCount'));
    }

    public function staffPerformance(Request $request)
    {
        $this->ensureAdmin();

        $shopId = Auth::user()->shop_id;
        $view = in_array($request->input('view'), ['person', 'counter', 'log'], true)
            ? $request->input('view')
            : 'person';

        if ($request->boolean('all_time')) {
            $firstOrder = Order::where('shop_id', $shopId)->min('created_at');
            $startDate = $firstOrder ? Carbon::parse($firstOrder)->startOfDay() : now()->startOfMonth();
            $endDate = now()->endOfDay();
        } else {
            $startDate = $request->input('start_date')
                ? Carbon::parse($request->input('start_date'))->startOfDay()
                : Carbon::today()->startOfMonth();
            $endDate = $request->input('end_date')
                ? Carbon::parse($request->input('end_date'))->endOfDay()
                : Carbon::today()->endOfDay();
        }

        $selectedStaffId = $request->input('staff_id');
        $netExpr = 'GREATEST(total_amount - COALESCE(discount_amount, 0) - COALESCE(exchange_credit, 0), 0)';

        $base = $this->completedOrdersQuery($shopId, $startDate, $endDate);

        $personLeaderboard = (clone $base)
            ->select(
                'user_id',
                DB::raw('COUNT(id) as total_orders'),
                DB::raw("COALESCE(SUM({$netExpr}), 0) as total_revenue"),
                DB::raw("COALESCE(AVG({$netExpr}), 0) as avg_ticket")
            )
            ->groupBy('user_id')
            ->with('user')
            ->orderByDesc('total_revenue')
            ->get()
            ->values()
            ->map(function ($row, $index) {
                $row->rank = $index + 1;
                return $row;
            });

        $counterLeaderboard = (clone $base)
            ->select(
                'counter_id',
                DB::raw('COUNT(id) as total_orders'),
                DB::raw("COALESCE(SUM({$netExpr}), 0) as total_revenue"),
                DB::raw("COALESCE(AVG({$netExpr}), 0) as avg_ticket"),
                DB::raw('COUNT(DISTINCT user_id) as staff_count')
            )
            ->groupBy('counter_id')
            ->with('counter')
            ->orderByDesc('total_revenue')
            ->get()
            ->values()
            ->map(function ($row, $index) {
                $row->rank = $index + 1;
                return $row;
            });

        $periodTotal = (float) $personLeaderboard->sum('total_revenue');
        $periodOrders = (int) $personLeaderboard->sum('total_orders');
        $topPerson = $personLeaderboard->first();
        $topCounter = $counterLeaderboard->first();

        $staffList = \App\Models\User::where('shop_id', $shopId)->orderBy('name')->get();

        $logQuery = $this->completedOrdersQuery($shopId, $startDate, $endDate);
        if ($selectedStaffId) {
            $logQuery->where('user_id', $selectedStaffId);
        }

        $activityLog = $logQuery->select(
                DB::raw('DATE(created_at) as sale_date'),
                'user_id',
                'counter_id',
                DB::raw('COUNT(id) as total_orders'),
                DB::raw("SUM({$netExpr}) as total_revenue")
            )
            ->groupBy(DB::raw('DATE(created_at)'), 'user_id', 'counter_id')
            ->with(['user', 'counter'])
            ->orderByDesc(DB::raw('DATE(created_at)'))
            ->paginate(15)
            ->appends($request->query());

        $payload = compact(
            'view',
            'personLeaderboard',
            'counterLeaderboard',
            'activityLog',
            'periodTotal',
            'periodOrders',
            'topPerson',
            'topCounter',
            'startDate',
            'endDate',
            'staffList',
            'selectedStaffId',
        );

        if ($request->ajax()) {
            return view('reports.partials.staff-performance-live', $payload);
        }

        return view('reports.staff-performance', $payload);
    }

    // Modal API Endpoint
    public function staffDailyDetails(Request $request)
    {
        $this->ensureAdmin();

        $shopId = Auth::user()->shop_id;
        $staffId = $request->input('staff_id');
        $date = $request->input('date');
        $counterId = $request->input('counter_id');

        $parsedDate = \Carbon\Carbon::parse($date);
        $staff = \App\Models\User::where('shop_id', $shopId)->findOrFail($staffId);
        
        // Fetch Counter Name if it exists
        $counterName = 'N/A';
        if ($counterId) {
            $counter = \App\Models\Counter::where('shop_id', $shopId)->find($counterId);
            $counterName = $counter ? $counter->name : 'N/A';
        }

        // Fetch exact orders for this staff, on this date, at this counter
        $orders = \App\Models\Order::with('customer')
            ->where('shop_id', $shopId)
            ->where('user_id', $staffId)
            ->whereDate('created_at', $parsedDate->toDateString())
            ->when($counterId, function($q) use ($counterId) {
                return $q->where('counter_id', $counterId);
            })
            ->where('status', '!=', 'refunded')
            ->latest()
            ->get();

        return response()->json([
            'staff_name' => $staff->name,
            'counter_name' => $counterName,
            'date_formatted' => $parsedDate->format('l, F j, Y'),
            'total_orders' => $orders->count(),
            'total_revenue' => format_taka_number($orders->sum('total_amount')),
            'orders' => $orders->map(function($order) {
                return [
                    'time' => $order->created_at->format('h:i A'),
                    'id' => $order->id,
                    'customer' => $order->customer ? $order->customer->name : 'Walk-in Customer',
                    'amount' => format_taka_number($order->total_amount),
                ];
            })
        ]);
    }
}