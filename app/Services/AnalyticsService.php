<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function dateRange(Request $request): array
    {
        if ($request->boolean('all_time')) {
            $shopId = auth()->user()->shop_id;
            $first = Order::where('shop_id', $shopId)->min('created_at');

            return [
                $first ? Carbon::parse($first)->startOfDay() : now()->startOfMonth(),
                now()->endOfDay(),
            ];
        }

        $start = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->subDays(29)->startOfDay();

        $end = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        return [$start, $end];
    }

    /** Previous period of equal length for comparison. */
    public function previousRange(Carbon $start, Carbon $end): array
    {
        $days = max(1, $start->diffInDays($end) + 1);

        return [
            $start->copy()->subDays($days)->startOfDay(),
            $start->copy()->subDay()->endOfDay(),
        ];
    }

    public function percentChange(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function baseOrderQuery(int $shopId, Carbon $start, Carbon $end)
    {
        return Order::where('shop_id', $shopId)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->where('is_exchange_receipt', false)
                    ->orWhereNull('is_exchange_receipt');
            });
    }

    public function revenue(int $shopId, Carbon $start, Carbon $end): float
    {
        return (float) $this->baseOrderQuery($shopId, $start, $end)
            ->selectRaw('COALESCE(SUM(GREATEST(total_amount - COALESCE(discount_amount, 0) - COALESCE(exchange_credit, 0), 0)), 0) as revenue')
            ->value('revenue');
    }

    public function orderCount(int $shopId, Carbon $start, Carbon $end): int
    {
        return $this->baseOrderQuery($shopId, $start, $end)->count();
    }

    public function averageOrderValue(int $shopId, Carbon $start, Carbon $end): float
    {
        $orders = $this->orderCount($shopId, $start, $end);

        return $orders > 0 ? $this->revenue($shopId, $start, $end) / $orders : 0.0;
    }

    public function totalDiscounts(int $shopId, Carbon $start, Carbon $end): float
    {
        return (float) $this->baseOrderQuery($shopId, $start, $end)->sum('discount_amount');
    }

    public function posOrders(int $shopId, Carbon $start, Carbon $end)
    {
        return $this->baseOrderQuery($shopId, $start, $end)->whereNotNull('counter_id');
    }

    public function webOrders(int $shopId, Carbon $start, Carbon $end)
    {
        return $this->baseOrderQuery($shopId, $start, $end)->onlineOrders();
    }

    public function costOfGoodsSold(int $shopId, Carbon $start, Carbon $end): float
    {
        return (float) OrderItem::query()
            ->whereHas('order', function ($q) use ($shopId, $start, $end) {
                $q->where('shop_id', $shopId)
                    ->whereBetween('created_at', [$start, $end])
                    ->where('status', 'completed')
                    ->where(function ($inner) {
                        $inner->where('is_exchange_receipt', false)
                            ->orWhereNull('is_exchange_receipt');
                    });
            })
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->selectRaw('COALESCE(SUM(order_items.quantity * products.cost_price), 0) as cogs')
            ->value('cogs');
    }

    public function inventorySnapshot(int $shopId): array
    {
        $products = Product::where('shop_id', $shopId);

        return [
            'total_products' => (clone $products)->count(),
            'total_units' => (int) (clone $products)->sum('stock_quantity'),
            'cost_value' => (float) (clone $products)->selectRaw('SUM(cost_price * stock_quantity) as v')->value('v'),
            'retail_value' => (float) (clone $products)->selectRaw('SUM(selling_price * stock_quantity) as v')->value('v'),
            'low_stock' => (clone $products)->whereColumn('stock_quantity', '<=', 'alert_quantity')->count(),
            'out_of_stock' => (clone $products)->where('stock_quantity', '<=', 0)->count(),
        ];
    }

    public function dailyRevenueChart(int $shopId, Carbon $start, Carbon $end)
    {
        $dayExpr = $this->localDateExpression('created_at');

        return $this->baseOrderQuery($shopId, $start, $end)
            ->select(
                DB::raw("{$dayExpr} as day"),
                DB::raw('COUNT(id) as orders'),
                DB::raw('SUM(GREATEST(total_amount - COALESCE(discount_amount, 0) - COALESCE(exchange_credit, 0), 0)) as revenue')
            )
            ->groupBy(DB::raw($dayExpr))
            ->orderBy('day')
            ->get();
    }

    /** Calendar date in app timezone (Asia/Dhaka) for chart buckets. */
    protected function localDateExpression(string $column = 'created_at'): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'pgsql' => "(({$column} AT TIME ZONE 'UTC') AT TIME ZONE 'Asia/Dhaka')::date",
            'mysql', 'mariadb' => "DATE(CONVERT_TZ({$column}, '+00:00', '+06:00'))",
            default => "DATE({$column})",
        };
    }

    public function topSellingProducts(int $shopId, Carbon $start, Carbon $end, int $limit = 5)
    {
        // Allocate order-level discount/exchange credit across lines so product revenue matches net sales.
        $netShare = 'order_items.subtotal * GREATEST(orders.total_amount - COALESCE(orders.discount_amount, 0) - COALESCE(orders.exchange_credit, 0), 0) / NULLIF(orders.total_amount, 0)';

        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.shop_id', $shopId)
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', 'completed')
            ->where(function ($q) {
                $q->where('orders.is_exchange_receipt', false)
                    ->orWhereNull('orders.is_exchange_receipt');
            })
            ->select(
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as sold'),
                DB::raw("SUM({$netShare}) as revenue")
            )
            ->groupBy('order_items.product_id')
            ->orderByDesc('sold')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->product = \App\Models\Product::with('category')->find($row->product_id);

                return $row;
            });
    }

    public function salesByCategory(int $shopId, Carbon $start, Carbon $end, int $limit = 6)
    {
        $netShare = 'order_items.subtotal * GREATEST(orders.total_amount - COALESCE(orders.discount_amount, 0) - COALESCE(orders.exchange_credit, 0), 0) / NULLIF(orders.total_amount, 0)';

        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.shop_id', $shopId)
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', 'completed')
            ->where(function ($q) {
                $q->where('orders.is_exchange_receipt', false)
                    ->orWhereNull('orders.is_exchange_receipt');
            })
            ->select(
                DB::raw("COALESCE(categories.name, 'Uncategorized') as category"),
                DB::raw("SUM({$netShare}) as revenue"),
                DB::raw('SUM(order_items.quantity) as sold')
            )
            ->groupBy(DB::raw("COALESCE(categories.name, 'Uncategorized')"))
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * Brand merchandise sales (POS + website completed orders).
     * Delivery fees are excluded so brand totals reflect product revenue only.
     */
    public function salesByBrand(int $shopId, Carbon $start, Carbon $end, ?int $limit = 12)
    {
        $brandLabel = "COALESCE(NULLIF(brands.name, ''), NULLIF(products.brand_name, ''), 'Unbranded')";
        $merchGross = 'GREATEST(orders.total_amount - COALESCE(orders.delivery_charge, 0), 0)';
        $merchNet = 'GREATEST(orders.total_amount - COALESCE(orders.delivery_charge, 0) - COALESCE(orders.discount_amount, 0) - COALESCE(orders.exchange_credit, 0), 0)';
        $netShare = "COALESCE(order_items.subtotal * ({$merchNet}) / NULLIF({$merchGross}, 0), 0)";
        $isWeb = "(orders.counter_id IS NULL AND orders.invoice_no LIKE 'WEB-%')";
        $isPos = "(NOT {$isWeb})";

        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->where('orders.shop_id', $shopId)
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', 'completed')
            ->where(function ($q) {
                $q->where('orders.is_exchange_receipt', false)
                    ->orWhereNull('orders.is_exchange_receipt');
            })
            ->select(
                DB::raw("{$brandLabel} as brand"),
                DB::raw('SUM(order_items.quantity) as sold'),
                DB::raw("SUM({$netShare}) as revenue"),
                DB::raw('SUM(order_items.quantity * COALESCE(products.cost_price, 0)) as cost'),
                DB::raw("SUM(CASE WHEN {$isPos} THEN order_items.quantity ELSE 0 END) as pos_sold"),
                DB::raw("SUM(CASE WHEN {$isWeb} THEN order_items.quantity ELSE 0 END) as web_sold"),
                DB::raw("SUM(CASE WHEN {$isPos} THEN {$netShare} ELSE 0 END) as pos_revenue"),
                DB::raw("SUM(CASE WHEN {$isWeb} THEN {$netShare} ELSE 0 END) as web_revenue"),
                DB::raw('COUNT(DISTINCT orders.id) as orders_count')
            )
            // Include raw columns so MySQL ONLY_FULL_GROUP_BY accepts the COALESCE label.
            ->groupBy('brands.name', 'products.brand_name')
            ->orderByDesc('revenue')
            ->get()
            ->groupBy(fn ($row) => $row->brand)
            ->map(function ($rows) {
                $first = $rows->first();
                $revenue = (float) $rows->sum('revenue');
                $cost = (float) $rows->sum('cost');

                return (object) [
                    'brand' => $first->brand,
                    'sold' => (int) $rows->sum('sold'),
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'profit' => $revenue - $cost,
                    'pos_sold' => (int) $rows->sum('pos_sold'),
                    'web_sold' => (int) $rows->sum('web_sold'),
                    'pos_revenue' => (float) $rows->sum('pos_revenue'),
                    'web_revenue' => (float) $rows->sum('web_revenue'),
                    'orders_count' => (int) $rows->sum('orders_count'),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        return $limit !== null ? $query->take($limit)->values() : $query;
    }

    /**
     * Per-day brand units + revenue (POS + website).
     */
    public function dailySalesByBrand(int $shopId, Carbon $start, Carbon $end)
    {
        $brandLabel = "COALESCE(NULLIF(brands.name, ''), NULLIF(products.brand_name, ''), 'Unbranded')";
        $merchGross = 'GREATEST(orders.total_amount - COALESCE(orders.delivery_charge, 0), 0)';
        $merchNet = 'GREATEST(orders.total_amount - COALESCE(orders.delivery_charge, 0) - COALESCE(orders.discount_amount, 0) - COALESCE(orders.exchange_credit, 0), 0)';
        $netShare = "COALESCE(order_items.subtotal * ({$merchNet}) / NULLIF({$merchGross}, 0), 0)";
        $isWeb = "(orders.counter_id IS NULL AND orders.invoice_no LIKE 'WEB-%')";
        $isPos = "(NOT {$isWeb})";

        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->where('orders.shop_id', $shopId)
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', 'completed')
            ->where(function ($q) {
                $q->where('orders.is_exchange_receipt', false)
                    ->orWhereNull('orders.is_exchange_receipt');
            })
            ->select(
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw("{$brandLabel} as brand"),
                DB::raw('SUM(order_items.quantity) as sold'),
                DB::raw("SUM({$netShare}) as revenue"),
                DB::raw('SUM(order_items.quantity * COALESCE(products.cost_price, 0)) as cost'),
                DB::raw("SUM(CASE WHEN {$isPos} THEN order_items.quantity ELSE 0 END) as pos_sold"),
                DB::raw("SUM(CASE WHEN {$isWeb} THEN order_items.quantity ELSE 0 END) as web_sold"),
                DB::raw("SUM(CASE WHEN {$isPos} THEN {$netShare} ELSE 0 END) as pos_revenue"),
                DB::raw("SUM(CASE WHEN {$isWeb} THEN {$netShare} ELSE 0 END) as web_revenue")
            )
            ->groupBy(DB::raw('DATE(orders.created_at)'), 'brands.name', 'products.brand_name')
            ->orderByDesc(DB::raw('DATE(orders.created_at)'))
            ->orderByDesc('revenue')
            ->get()
            ->groupBy(fn ($row) => $row->date.'|'.$row->brand)
            ->map(function ($rows) {
                $first = $rows->first();
                $revenue = (float) $rows->sum('revenue');
                $cost = (float) $rows->sum('cost');

                return (object) [
                    'date' => $first->date,
                    'brand' => $first->brand,
                    'sold' => (int) $rows->sum('sold'),
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'profit' => $revenue - $cost,
                    'pos_sold' => (int) $rows->sum('pos_sold'),
                    'web_sold' => (int) $rows->sum('web_sold'),
                    'pos_revenue' => (float) $rows->sum('pos_revenue'),
                    'web_revenue' => (float) $rows->sum('web_revenue'),
                ];
            })
            ->sortByDesc(fn ($row) => $row->date.'-'.str_pad((string) round($row->revenue * 100), 12, '0', STR_PAD_LEFT))
            ->values();
    }

    public function topCustomers(int $shopId, Carbon $start, Carbon $end, int $limit = 15)
    {
        return $this->baseOrderQuery($shopId, $start, $end)
            ->whereNotNull('customer_id')
            ->select(
                'customer_id',
                DB::raw('COUNT(id) as orders'),
                DB::raw('SUM(GREATEST(total_amount - COALESCE(discount_amount, 0) - COALESCE(exchange_credit, 0), 0)) as revenue'),
                DB::raw('SUM(COALESCE(discount_amount, 0)) as discounts')
            )
            ->groupBy('customer_id')
            ->with('customer')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }

    public function discountBreakdown(int $shopId, Carbon $start, Carbon $end)
    {
        return $this->baseOrderQuery($shopId, $start, $end)
            ->where('discount_amount', '>', 0)
            ->with(['customer', 'counter'])
            ->latest()
            ->limit(50)
            ->get();
    }

    public function salesKpis(int $shopId, Carbon $start, Carbon $end): array
    {
        [$prevStart, $prevEnd] = $this->previousRange($start, $end);

        $revenue = $this->revenue($shopId, $start, $end);
        $prevRevenue = $this->revenue($shopId, $prevStart, $prevEnd);
        $orders = $this->orderCount($shopId, $start, $end);
        $prevOrders = $this->orderCount($shopId, $prevStart, $prevEnd);
        $aov = $this->averageOrderValue($shopId, $start, $end);
        $prevAov = $this->averageOrderValue($shopId, $prevStart, $prevEnd);
        $cogs = $this->costOfGoodsSold($shopId, $start, $end);
        $prevCogs = $this->costOfGoodsSold($shopId, $prevStart, $prevEnd);
        $profit = $revenue - $cogs;
        $prevProfit = $prevRevenue - $prevCogs;
        $discounts = $this->totalDiscounts($shopId, $start, $end);
        $prevDiscounts = $this->totalDiscounts($shopId, $prevStart, $prevEnd);

        return [
            'revenue' => $revenue,
            'orders' => $orders,
            'aov' => $aov,
            'profit' => $profit,
            'discounts' => $discounts,
            'cogs' => $cogs,
            'prev' => [
                'revenue' => $prevRevenue,
                'orders' => $prevOrders,
                'aov' => $prevAov,
                'profit' => $prevProfit,
                'discounts' => $prevDiscounts,
                'start' => $prevStart,
                'end' => $prevEnd,
            ],
            'change' => [
                'revenue' => $this->percentChange($revenue, $prevRevenue),
                'orders' => $this->percentChange((float) $orders, (float) $prevOrders),
                'aov' => $this->percentChange($aov, $prevAov),
                'profit' => $this->percentChange($profit, $prevProfit),
                'discounts' => $this->percentChange($discounts, $prevDiscounts),
            ],
        ];
    }
}
