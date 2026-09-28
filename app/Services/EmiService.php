<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerEmiEntry;
use App\Models\CustomerEmiInstallment;
use App\Models\CustomerEmiPlan;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmiService
{
    public function __construct(
        protected AccountService $accounts,
    ) {}

    /**
     * Build equal monthly schedule; last installment absorbs rounding remainder.
     *
     * @return array{principal: float, installment_amount: float, schedule: list<array{sequence:int,due_date:string,amount:float}>}
     */
    public function buildSchedule(float $principal, int $months, ?Carbon $start = null): array
    {
        $principal = round($principal, 2);
        $months = max(1, min(36, $months));
        if ($principal <= 0) {
            throw new InvalidArgumentException('EMI principal must be greater than zero.');
        }

        $start = ($start ?? now())->copy()->startOfDay();
        $base = round($principal / $months, 2);
        $schedule = [];
        $allocated = 0.0;

        for ($i = 1; $i <= $months; $i++) {
            $amount = $i === $months
                ? round($principal - $allocated, 2)
                : $base;
            $allocated = round($allocated + $amount, 2);
            $schedule[] = [
                'sequence' => $i,
                'due_date' => $start->copy()->addMonthsNoOverflow($i)->toDateString(),
                'amount' => $amount,
            ];
        }

        return [
            'principal' => $principal,
            'installment_amount' => $base,
            'schedule' => $schedule,
        ];
    }

    public function createPlanFromSale(
        Customer $customer,
        Order $order,
        float $principal,
        float $downPayment,
        int $months,
        ?int $userId = null,
    ): CustomerEmiPlan {
        $principal = round($principal, 2);
        $downPayment = max(0, round($downPayment, 2));
        $months = max(1, min(36, $months));

        if ($principal <= 0) {
            throw new InvalidArgumentException('EMI financed amount must be greater than zero.');
        }

        $built = $this->buildSchedule($principal, $months, $order->created_at ? Carbon::parse($order->created_at) : now());

        return DB::transaction(function () use ($customer, $order, $principal, $downPayment, $months, $userId, $built) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $plan = CustomerEmiPlan::create([
                'shop_id' => $customer->shop_id,
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'user_id' => $userId ?? Auth::id(),
                'principal' => $principal,
                'down_payment' => $downPayment,
                'months' => $months,
                'installment_amount' => $built['installment_amount'],
                'total_payable' => $principal,
                'paid_amount' => 0,
                'remaining_amount' => $principal,
                'status' => 'active',
                'started_at' => now()->toDateString(),
            ]);

            foreach ($built['schedule'] as $row) {
                CustomerEmiInstallment::create([
                    'shop_id' => $customer->shop_id,
                    'emi_plan_id' => $plan->id,
                    'customer_id' => $customer->id,
                    'sequence' => $row['sequence'],
                    'due_date' => $row['due_date'],
                    'amount' => $row['amount'],
                    'paid_amount' => 0,
                    'status' => 'pending',
                ]);
            }

            CustomerEmiEntry::create([
                'shop_id' => $customer->shop_id,
                'customer_id' => $customer->id,
                'emi_plan_id' => $plan->id,
                'order_id' => $order->id,
                'user_id' => $userId ?? Auth::id(),
                'type' => 'plan',
                'amount' => $principal,
                'method' => null,
                'note' => 'EMI plan on sale '.$order->invoice_no.' ('.$months.' months, down ৳'.format_taka_number($downPayment).')',
            ]);

            $customer->increment('emi_balance', $principal);

            return $plan->load('installments');
        });
    }

    /**
     * Collect any amount toward an EMI plan (applies oldest unpaid installments first).
     * Partial installment payments are allowed.
     */
    public function collectPayment(
        CustomerEmiPlan $plan,
        float $amount,
        string $method = 'cash',
        ?string $note = null,
        ?int $userId = null,
        ?int $counterId = null,
        ?float $cash = null,
        ?float $card = null,
        ?float $mobile = null,
    ): CustomerEmiEntry {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use (
            $plan, $amount, $method, $note, $userId, $counterId, $cash, $card, $mobile
        ) {
            $plan = CustomerEmiPlan::whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $customer = Customer::whereKey($plan->customer_id)->lockForUpdate()->firstOrFail();

            if ($plan->status !== 'active') {
                throw new InvalidArgumentException('This EMI plan is not active.');
            }

            $remaining = round((float) $plan->remaining_amount, 2);
            if ($amount > $remaining + 0.009) {
                throw new InvalidArgumentException(
                    'Payment cannot exceed EMI remaining (৳'.format_taka_number($remaining).').'
                );
            }

            $left = $amount;
            $touchedInstallmentId = null;

            $installments = CustomerEmiInstallment::where('emi_plan_id', $plan->id)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->orderBy('sequence')
                ->lockForUpdate()
                ->get();

            foreach ($installments as $inst) {
                if ($left <= 0.009) {
                    break;
                }
                $due = $inst->remaining();
                if ($due <= 0.009) {
                    continue;
                }
                $take = min($due, $left);
                $inst->paid_amount = round((float) $inst->paid_amount + $take, 2);
                if ($inst->remaining() <= 0.009) {
                    $inst->paid_amount = (float) $inst->amount;
                    $inst->status = 'paid';
                    $inst->paid_at = now();
                } else {
                    $inst->status = 'partial';
                    $inst->paid_at = null;
                }
                $inst->save();
                $touchedInstallmentId = $inst->id;
                $left = round($left - $take, 2);
            }

            $plan->paid_amount = round((float) $plan->paid_amount + $amount, 2);
            $plan->remaining_amount = max(0, round((float) $plan->remaining_amount - $amount, 2));
            if ($plan->remaining_amount <= 0.009) {
                $plan->remaining_amount = 0;
                $plan->status = 'completed';
            }
            $plan->save();

            // Keep linked sale invoice in sync for PAID seal / reprints.
            if ($plan->order_id) {
                $order = Order::whereKey($plan->order_id)->lockForUpdate()->first();
                if ($order) {
                    $due = round((float) ($order->credit_amount ?? 0), 2);
                    $take = min($due, $amount);
                    if ($take > 0.009) {
                        $order->credit_amount = round($due - $take, 2);
                        $order->paid_amount = round((float) ($order->paid_amount ?? 0) + $take, 2);
                        $order->save();
                    }
                }
            }

            $customer->decrement('emi_balance', $amount);
            $customer->refresh();
            if ((float) $customer->emi_balance < 0.005) {
                $customer->update(['emi_balance' => 0]);
            }

            $entry = CustomerEmiEntry::create([
                'shop_id' => $customer->shop_id,
                'customer_id' => $customer->id,
                'emi_plan_id' => $plan->id,
                'installment_id' => $touchedInstallmentId,
                'order_id' => $plan->order_id,
                'user_id' => $userId ?? Auth::id(),
                'type' => 'payment',
                'amount' => -1 * $amount,
                'method' => $method,
                'note' => $note ?? 'EMI installment payment',
            ]);

            $this->accounts->postEmiPayment(
                customer: $customer,
                entry: $entry,
                amount: $amount,
                method: $method,
                counterId: $counterId,
                cash: $cash,
                card: $card,
                mobile: $mobile,
                userId: $userId ?? Auth::id(),
            );

            return $entry;
        });
    }

    public function markOverdueInstallments(?int $shopId = null): int
    {
        $q = CustomerEmiInstallment::query()
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', now()->toDateString());

        if ($shopId) {
            $q->where('shop_id', $shopId);
        }

        return $q->update(['status' => 'overdue']);
    }
}
