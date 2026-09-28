<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    protected function ensureAdmin(): void
    {
        if (! Auth::user()?->isAdminUser()) {
            abort(403, 'Reports are only available to shop admins.');
        }
    }

    protected function reportView(Request $request, string $activeTab)
    {
        $this->ensureAdmin();

        $shopId = Auth::user()->shop_id;
        [$start, $end] = $this->analytics->dateRange($request);
        [$prevStart, $prevEnd] = $this->analytics->previousRange($start, $end);

        $kpis = $this->analytics->salesKpis($shopId, $start, $end);
        $chart = $this->analytics->dailyRevenueChart($shopId, $start, $end);
        $prevChart = $this->analytics->dailyRevenueChart($shopId, $prevStart, $prevEnd);
        $categorySales = $this->analytics->salesByCategory($shopId, $start, $end);
        $brandSales = $this->analytics->salesByBrand($shopId, $start, $end, 8);
        $topProducts = $this->analytics->topSellingProducts($shopId, $start, $end, 8);

        $orderSummary = [
            'total' => $this->analytics->orderCount($shopId, $start, $end),
            'pos' => $this->analytics->posOrders($shopId, $start, $end)->count(),
            'web' => $this->analytics->webOrders($shopId, $start, $end)->count(),
            'pending' => $this->analytics->baseOrderQuery($shopId, $start, $end)->whereIn('status', ['pending', 'pending_fulfillment'])->count(),
            'completed' => $this->analytics->baseOrderQuery($shopId, $start, $end)->where('status', 'completed')->count(),
        ];

        $recentOrders = Order::where('shop_id', $shopId)
            ->whereBetween('created_at', [$start, $end])
            ->with(['customer', 'counter'])
            ->latest()
            ->limit(25)
            ->get();

        $productRows = $this->analytics->topSellingProducts($shopId, $start, $end, 50);
        $customers = $this->analytics->topCustomers($shopId, $start, $end, 25);
        $inventory = $this->analytics->inventorySnapshot($shopId);

        $lowStock = Product::where('shop_id', $shopId)
            ->whereColumn('stock_quantity', '<=', 'alert_quantity')
            ->with('category')
            ->orderBy('stock_quantity')
            ->limit(25)
            ->get();

        $stockCategories = \App\Models\Category::where('shop_id', $shopId)
            ->withCount('products')
            ->get()
            ->map(function ($category) use ($shopId) {
                $stats = Product::where('shop_id', $shopId)
                    ->where('category_id', $category->id)
                    ->selectRaw('COUNT(*) as products, COALESCE(SUM(stock_quantity),0) as units, COALESCE(SUM(cost_price * stock_quantity),0) as cost_value')
                    ->first();

                return (object) [
                    'name' => $category->name,
                    'products' => $stats->products ?? 0,
                    'units' => $stats->units ?? 0,
                    'cost_value' => $stats->cost_value ?? 0,
                ];
            })
            ->sortByDesc('cost_value')
            ->values();

        $discounts = $this->analytics->discountBreakdown($shopId, $start, $end);
        $discountTotal = $kpis['discounts'];

        // Tax is not stored on orders yet — report stays exportable with clear zero state.
        $taxRows = collect();
        $taxTotal = 0.0;

        return view('analytics.layout', compact(
            'activeTab',
            'start',
            'end',
            'prevStart',
            'prevEnd',
            'kpis',
            'chart',
            'prevChart',
            'categorySales',
            'brandSales',
            'topProducts',
            'orderSummary',
            'recentOrders',
            'productRows',
            'customers',
            'inventory',
            'lowStock',
            'stockCategories',
            'discounts',
            'discountTotal',
            'taxRows',
            'taxTotal',
        ));
    }

    public function overview(Request $request)
    {
        return $this->reportView($request, 'sales');
    }

    public function orders(Request $request)
    {
        return $this->reportView($request, 'orders');
    }

    public function revenue(Request $request)
    {
        return $this->reportView($request, 'sales');
    }

    public function expense(Request $request)
    {
        return $this->reportView($request, 'discount');
    }

    public function inventory(Request $request)
    {
        return $this->reportView($request, 'stock');
    }

    public function balance(Request $request)
    {
        return $this->reportView($request, 'sales');
    }

    public function products(Request $request)
    {
        return $this->reportView($request, 'products');
    }

    public function customers(Request $request)
    {
        return $this->reportView($request, 'customers');
    }

    public function tax(Request $request)
    {
        return $this->reportView($request, 'tax');
    }

    public function discount(Request $request)
    {
        return $this->reportView($request, 'discount');
    }

    public function preview(Request $request)
    {
        $this->ensureAdmin();

        $shopId = Auth::user()->shop_id;
        [$start, $end] = $this->analytics->dateRange($request);
        $tab = $request->get('tab', 'sales');

        $titles = [
            'sales' => 'Sales Report',
            'orders' => 'Orders Report',
            'products' => 'Products Report',
            'customers' => 'Customers Report',
            'stock' => 'Stock Report',
            'tax' => 'Tax Report',
            'discount' => 'Discount Report',
        ];

        $title = $titles[$tab] ?? 'Sales Report';
        $rows = $this->exportRows($shopId, $start, $end, $tab);
        $shopName = Auth::user()->shop->name ?? config('app.name', 'Store');
        $csvUrl = route('analytics.export', array_merge(
            $request->only(['start_date', 'end_date', 'all_time']),
            ['tab' => $tab]
        ));

        if ($request->boolean('modal') || $request->ajax()) {
            return view('analytics.partials.preview-body', compact(
                'tab', 'title', 'rows', 'start', 'end', 'shopName', 'csvUrl'
            ));
        }

        $backUrl = match ($tab) {
            'orders' => route('analytics.orders', $request->only(['start_date', 'end_date', 'all_time'])),
            'products' => route('analytics.products', $request->only(['start_date', 'end_date', 'all_time'])),
            'customers' => route('analytics.customers', $request->only(['start_date', 'end_date', 'all_time'])),
            'stock' => route('analytics.stock', $request->only(['start_date', 'end_date', 'all_time'])),
            'tax' => route('analytics.tax', $request->only(['start_date', 'end_date', 'all_time'])),
            'discount' => route('analytics.discount', $request->only(['start_date', 'end_date', 'all_time'])),
            default => route('analytics.overview', $request->only(['start_date', 'end_date', 'all_time'])),
        };

        return view('analytics.preview', compact(
            'tab', 'title', 'rows', 'start', 'end', 'shopName', 'csvUrl', 'backUrl'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureAdmin();

        $shopId = Auth::user()->shop_id;
        [$start, $end] = $this->analytics->dateRange($request);
        $tab = $request->get('tab', 'sales');
        $filename = 'report-'.$tab.'-'.$start->format('Ymd').'-'.$end->format('Ymd').'.csv';
        $rows = $this->exportRows($shopId, $start, $end, $tab);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function exportRows(int $shopId, $start, $end, string $tab): array
    {
        return match ($tab) {
            'orders' => $this->exportOrders($shopId, $start, $end),
            'products' => $this->exportProducts($shopId, $start, $end),
            'customers' => $this->exportCustomers($shopId, $start, $end),
            'stock' => $this->exportStock($shopId),
            'tax' => $this->exportTax($shopId, $start, $end),
            'discount' => $this->exportDiscounts($shopId, $start, $end),
            default => $this->exportSales($shopId, $start, $end),
        };
    }

    protected function exportSales(int $shopId, $start, $end): array
    {
        $kpis = $this->analytics->salesKpis($shopId, $start, $end);
        $rows = [
            ['Metric', 'This Period', 'Last Period', 'Change %'],
            ['Total Sales', number_format($kpis['revenue'], 2, '.', ''), number_format($kpis['prev']['revenue'], 2, '.', ''), $kpis['change']['revenue']],
            ['Total Orders', $kpis['orders'], $kpis['prev']['orders'], $kpis['change']['orders']],
            ['Average Order Value', number_format($kpis['aov'], 2, '.', ''), number_format($kpis['prev']['aov'], 2, '.', ''), $kpis['change']['aov']],
            ['Total Profit', number_format($kpis['profit'], 2, '.', ''), number_format($kpis['prev']['profit'], 2, '.', ''), $kpis['change']['profit']],
            ['Total Discounts', number_format($kpis['discounts'], 2, '.', ''), number_format($kpis['prev']['discounts'], 2, '.', ''), $kpis['change']['discounts']],
            [],
            ['Date', 'Orders', 'Revenue'],
        ];

        foreach ($this->analytics->dailyRevenueChart($shopId, $start, $end) as $day) {
            $rows[] = [$day->day, $day->orders, number_format((float) $day->revenue, 2, '.', '')];
        }

        $rows[] = [];
        $rows[] = ['Category', 'Revenue', 'Units Sold'];
        foreach ($this->analytics->salesByCategory($shopId, $start, $end, 20) as $cat) {
            $rows[] = [$cat->category, number_format((float) $cat->revenue, 2, '.', ''), $cat->sold];
        }

        $rows[] = [];
        $rows[] = ['Brand', 'Units', 'Purchase Cost', 'Selling Amount', 'Profit', 'POS Revenue', 'Web Revenue'];
        foreach ($this->analytics->salesByBrand($shopId, $start, $end, 50) as $brand) {
            $rows[] = [
                $brand->brand,
                $brand->sold,
                number_format((float) $brand->cost, 2, '.', ''),
                number_format((float) $brand->revenue, 2, '.', ''),
                number_format((float) $brand->profit, 2, '.', ''),
                number_format((float) $brand->pos_revenue, 2, '.', ''),
                number_format((float) $brand->web_revenue, 2, '.', ''),
            ];
        }

        return $rows;
    }

    protected function exportOrders(int $shopId, $start, $end): array
    {
        $rows = [['Invoice', 'Date', 'Customer', 'Channel', 'Payment', 'Status', 'Amount']];

        Order::where('shop_id', $shopId)
            ->whereBetween('created_at', [$start, $end])
            ->with(['customer', 'counter'])
            ->latest()
            ->limit(2000)
            ->get()
            ->each(function ($order) use (&$rows) {
                $rows[] = [
                    $order->invoice_no,
                    $order->created_at->format('Y-m-d H:i'),
                    $order->customer?->name ?? 'Walk-in',
                    $order->counter_id ? 'POS' : 'Online',
                    $order->payment_method,
                    $order->status,
                    number_format((float) $order->total_amount, 2, '.', ''),
                ];
            });

        return $rows;
    }

    protected function exportProducts(int $shopId, $start, $end): array
    {
        $rows = [['Product', 'Category', 'Units Sold', 'Revenue']];

        foreach ($this->analytics->topSellingProducts($shopId, $start, $end, 500) as $row) {
            $rows[] = [
                $row->product?->name ?? 'Unknown',
                $row->product?->category?->name ?? '—',
                $row->sold,
                number_format((float) ($row->revenue ?? 0), 2, '.', ''),
            ];
        }

        return $rows;
    }

    protected function exportCustomers(int $shopId, $start, $end): array
    {
        $rows = [['Customer', 'Phone', 'Orders', 'Revenue', 'Discounts']];

        foreach ($this->analytics->topCustomers($shopId, $start, $end, 500) as $row) {
            $rows[] = [
                $row->customer?->name ?? 'Unknown',
                $row->customer?->phone ?? '—',
                $row->orders,
                number_format((float) $row->revenue, 2, '.', ''),
                number_format((float) $row->discounts, 2, '.', ''),
            ];
        }

        return $rows;
    }

    protected function exportStock(int $shopId): array
    {
        $rows = [['SKU / Name', 'Category', 'Stock', 'Alert Qty', 'Cost Value', 'Retail Value', 'Status']];

        Product::where('shop_id', $shopId)
            ->with('category')
            ->orderBy('name')
            ->get()
            ->each(function ($product) use (&$rows) {
                $status = $product->stock_quantity <= 0
                    ? 'Out of Stock'
                    : ($product->stock_quantity <= $product->alert_quantity ? 'Low Stock' : 'In Stock');

                $rows[] = [
                    $product->name,
                    $product->category?->name ?? '—',
                    $product->stock_quantity,
                    $product->alert_quantity,
                    number_format((float) $product->cost_price * $product->stock_quantity, 2, '.', ''),
                    number_format((float) $product->selling_price * $product->stock_quantity, 2, '.', ''),
                    $status,
                ];
            });

        return $rows;
    }

    protected function exportTax(int $shopId, $start, $end): array
    {
        return [
            ['Note', 'Tax / VAT is not currently tracked on orders in this system.'],
            ['Period Start', $start->format('Y-m-d')],
            ['Period End', $end->format('Y-m-d')],
            ['Taxable Sales', number_format($this->analytics->revenue($shopId, $start, $end), 2, '.', '')],
            ['Tax Collected', '0.00'],
        ];
    }

    protected function exportDiscounts(int $shopId, $start, $end): array
    {
        $rows = [['Invoice', 'Date', 'Customer', 'Gross', 'Discount', 'Net']];

        $this->analytics->baseOrderQuery($shopId, $start, $end)
            ->where('discount_amount', '>', 0)
            ->with('customer')
            ->latest()
            ->limit(2000)
            ->get()
            ->each(function ($order) use (&$rows) {
                $rows[] = [
                    $order->invoice_no,
                    $order->created_at->format('Y-m-d H:i'),
                    $order->customer?->name ?? 'Walk-in',
                    number_format((float) $order->total_amount, 2, '.', ''),
                    number_format((float) $order->discount_amount, 2, '.', ''),
                    number_format($order->netPayable(), 2, '.', ''),
                ];
            });

        return $rows;
    }
}
