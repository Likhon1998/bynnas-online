<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerEmiEntry;
use App\Models\CustomerEmiPlan;
use App\Services\EmiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerEmiController extends Controller
{
    public function __construct(
        protected EmiService $emi,
    ) {}

    public function index()
    {
        $shopId = Auth::user()->shop_id;
        $this->emi->markOverdueInstallments($shopId);

        $plans = CustomerEmiPlan::with([
                'customer:id,name,phone',
                'order:id,invoice_no',
                'order.items.product.galleryImages',
                'installments',
            ])
            ->where('shop_id', $shopId)
            ->where('status', 'active')
            ->where('remaining_amount', '>', 0)
            ->orderByDesc('id')
            ->get();

        $totalOutstanding = (float) $plans->sum('remaining_amount');
        $today = now()->toDateString();
        $overdueCount = $plans->sum(function ($p) use ($today) {
            return $p->installments
                ->whereIn('status', ['overdue', 'pending', 'partial'])
                ->filter(fn ($i) => $i->due_date && $i->due_date->toDateString() < $today)
                ->count();
        });

        return view('customers.emi-index', compact('plans', 'totalOutstanding', 'overdueCount'));
    }

    public function show(CustomerEmiPlan $plan)
    {
        $this->authorizePlan($plan);
        $this->emi->markOverdueInstallments($plan->shop_id);

        $plan->load([
            'customer',
            'order:id,invoice_no,total_amount,paid_amount,created_at',
            'order.items.product.galleryImages',
            'installments',
            'entries.user:id,name',
        ]);

        return view('customers.emi-show', compact('plan'));
    }

    public function pay(Request $request, CustomerEmiPlan $plan)
    {
        $this->authorizePlan($plan);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => ['required', Rule::in(['cash', 'card', 'bkash'])],
            'note' => 'nullable|string|max:255',
        ]);

        $amount = round((float) $validated['amount'], 2);
        $remaining = round((float) $plan->remaining_amount, 2);

        if ($amount > $remaining + 0.009) {
            return back()->withInput()->with('error', 'Payment cannot exceed EMI remaining (৳'.format_taka_number($remaining).').');
        }

        try {
            $user = Auth::user();
            $counterId = $user->counter_id ?: ($user->isAdminUser() ? $user->adminOpenPosSession()?->counter_id : null);

            $cash = $card = $mobile = null;
            match ($validated['method']) {
                'cash' => $cash = $amount,
                'card' => $card = $amount,
                'bkash' => $mobile = $amount,
            };

            $isPartial = $amount + 0.009 < $remaining;
            $note = trim((string) ($validated['note'] ?? ''));
            if ($note === '') {
                $note = $isPartial
                    ? 'Partial EMI payment ৳'.format_taka_number($amount)
                    : 'EMI settlement';
            }

            $entry = $this->emi->collectPayment(
                plan: $plan,
                amount: $amount,
                method: $validated['method'],
                note: $note,
                userId: $user->id,
                counterId: $counterId ? (int) $counterId : null,
                cash: $cash,
                card: $card,
                mobile: $mobile,
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $fresh = $plan->fresh();

        return redirect()
            ->route('customers.emi.show', $fresh)
            ->with('success', 'Collected ৳'.format_taka_number($amount).'. Remaining EMI: ৳'.format_taka_number((float) $fresh->remaining_amount).'.')
            ->with('open_slip_url', route('customers.emi.slip', ['entry' => $entry, 'embed' => 1]))
            ->with('print_slip', true);
    }

    public function slip(CustomerEmiEntry $entry)
    {
        if ((int) $entry->shop_id !== (int) Auth::user()->shop_id) {
            abort(403);
        }
        if ($entry->type !== 'payment') {
            abort(404);
        }

        $entry->load(['customer', 'user', 'shop', 'order:id,invoice_no', 'plan.order:id,invoice_no']);

        $plan = $entry->plan;
        $paidUpTo = abs((float) CustomerEmiEntry::where('emi_plan_id', $entry->emi_plan_id)
            ->where('type', 'payment')
            ->where('id', '<=', $entry->id)
            ->sum('amount'));
        $principal = (float) ($plan->principal ?? $plan->total_payable ?? 0);
        $remainingAfter = max(0, round($principal - $paidUpTo, 2));

        $extraRows = [];
        if ($plan) {
            $extraRows[] = ['label' => 'EMI tenure', 'value' => $plan->months.' months'];
            $extraRows[] = ['label' => 'Plan status', 'value' => strtoupper($plan->status)];
        }

        return view('customers.payment-slip', [
            'entry' => $entry,
            'slipTitle' => 'EMI Payment Slip',
            'slipTypeLabel' => 'Customer EMI',
            'slipRef' => 'EMI-'.$entry->id,
            'invoiceNo' => $entry->order->invoice_no ?? ($plan?->order?->invoice_no),
            'remainingAfter' => $remainingAfter,
            'extraRows' => $extraRows,
            'backUrl' => $plan
                ? route('customers.emi.show', $plan)
                : route('customers.emi.index'),
        ]);
    }

    public function customer(Customer $customer)
    {
        $this->authorizeCustomer($customer);
        $this->emi->markOverdueInstallments($customer->shop_id);

        $plans = $customer->emiPlans()
            ->with([
                'order:id,invoice_no',
                'order.items.product.galleryImages',
                'installments',
            ])
            ->latest('id')
            ->get();

        return view('customers.emi-customer', compact('customer', 'plans'));
    }

    private function authorizePlan(CustomerEmiPlan $plan): void
    {
        if ((int) $plan->shop_id !== (int) Auth::user()->shop_id) {
            abort(403);
        }
    }

    private function authorizeCustomer(Customer $customer): void
    {
        if ((int) $customer->shop_id !== (int) Auth::user()->shop_id) {
            abort(403);
        }
    }
}
