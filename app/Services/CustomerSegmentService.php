<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Customer segments from real order history (thresholds in config/commerce.php).
 * Spend is order value minus delivery fee; cancelled / returned / refunded orders are ignored.
 */
class CustomerSegmentService
{
    public const SEGMENTS = [
        'new' => 'New',
        'returning' => 'Returning',
        'vip' => 'VIP',
        'high_value' => 'High value',
        'frequent' => 'Frequent buyer',
        'inactive' => 'Inactive',
    ];

    public const BADGES = [
        'new' => 'bg-sky-100 text-sky-800',
        'returning' => 'bg-indigo-100 text-indigo-800',
        'vip' => 'bg-amber-100 text-amber-800',
        'high_value' => 'bg-emerald-100 text-emerald-800',
        'frequent' => 'bg-violet-100 text-violet-800',
        'inactive' => 'bg-slate-200 text-slate-600',
    ];

    /** Adds seg_orders, seg_spent, seg_first_order_at, seg_last_order_at to a customer query. */
    public function withStats(Builder $query): Builder
    {
        $valid = fn () => Order::query()
            ->whereColumn('orders.customer_id', 'customers.id')
            ->whereNotIn('orders.status', OrderStatus::VOID);

        if (! $query->getQuery()->columns) {
            $query->select('customers.*');
        }

        return $query->addSelect([
            'seg_orders' => $valid()->selectRaw('COUNT(*)'),
            'seg_spent' => $valid()->selectRaw('COALESCE(SUM(orders.total_amount - COALESCE(orders.delivery_charge, 0)), 0)'),
            'seg_first_order_at' => $valid()->selectRaw('MIN(orders.created_at)'),
            'seg_last_order_at' => $valid()->selectRaw('MAX(orders.created_at)'),
        ]);
    }

    /** @return list<string> segment keys */
    public function segmentsFor(Customer $customer): array
    {
        $stats = $this->stats($customer);
        $rules = config('commerce.segments');
        $orders = $stats['orders'];
        $spent = $stats['spent'];
        $segments = [];

        $newSince = now()->subDays((int) $rules['new_days']);
        if (($orders === 1 && $stats['first_order_at']?->gte($newSince))
            || ($orders === 0 && $customer->created_at?->gte($newSince))) {
            $segments[] = 'new';
        }
        if ($orders >= 2) {
            $segments[] = 'returning';
        }
        if ($spent >= (float) $rules['vip_spend']) {
            $segments[] = 'vip';
        } elseif ($spent >= (float) $rules['high_value_spend']) {
            $segments[] = 'high_value';
        }
        if ($orders >= (int) $rules['frequent_orders']) {
            $segments[] = 'frequent';
        }
        if ($orders > 0 && $stats['last_order_at']?->lt(now()->subDays((int) $rules['inactive_days']))) {
            $segments[] = 'inactive';
        }

        return $segments;
    }

    /** @return array{orders: int, spent: float, first_order_at: ?Carbon, last_order_at: ?Carbon, average: float} */
    public function stats(Customer $customer): array
    {
        if (! array_key_exists('seg_orders', $customer->getAttributes())) {
            $loaded = $this->withStats(Customer::query()->whereKey($customer->id))->first();
            foreach (['seg_orders', 'seg_spent', 'seg_first_order_at', 'seg_last_order_at'] as $key) {
                $customer->setAttribute($key, $loaded?->getAttribute($key));
            }
        }

        $orders = (int) $customer->getAttribute('seg_orders');
        $spent = round((float) $customer->getAttribute('seg_spent'), 2);

        return [
            'orders' => $orders,
            'spent' => $spent,
            'first_order_at' => $this->date($customer->getAttribute('seg_first_order_at')),
            'last_order_at' => $this->date($customer->getAttribute('seg_last_order_at')),
            'average' => $orders > 0 ? round($spent / $orders, 2) : 0.0,
        ];
    }

    private function date($value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }
}
