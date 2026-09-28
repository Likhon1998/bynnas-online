<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountEntry;
use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardSummaryService
{
    public function __construct(
        protected AccountService $accounts,
        protected CounterSessionService $sessions,
    ) {}

    /**
     * Business summary for today.
     * $counterId = null means shop-wide (admin "All together").
     *
     * P&L cards (sales / returns / expenses / net) follow the general ledger.
     * Till cards follow counter session expected cash (incl. AR cash collections).
     */
    public function todaySummary(int $shopId, ?int $counterId = null): array
    {
        $this->accounts->ensureShopAccounts($shopId);

        $today = Carbon::today();
        $dayStart = $today->copy()->startOfDay();
        $dayEnd = $today->copy()->endOfDay();

        $posSalesToday = $this->salesForPeriod($shopId, $counterId, $dayStart, $dayEnd, ['sale']);
        $onlineSalesToday = $this->salesForPeriod($shopId, $counterId, $dayStart, $dayEnd, ['web_sale']);
        $posSalesLifetime = $this->salesForPeriod($shopId, $counterId, null, null, ['sale']);
        $onlineSalesLifetime = $this->salesForPeriod($shopId, $counterId, null, null, ['web_sale']);
        $totalSales = $posSalesToday + $onlineSalesToday;
        $lifetimeSales = $posSalesLifetime + $onlineSalesLifetime;
        $returns = $this->periodReturns($shopId, $counterId, $dayStart, $dayEnd);
        $expenses = $this->todayExpenses($shopId, $counterId, $dayStart, $dayEnd);
        $netAmount = $totalSales - $returns - $expenses;

        $cash = $this->cashDrawerSummary($shopId, $counterId, $today);
        $pettyCash = $this->pettyCashBalance($shopId);
        $bakiCollected = $this->todayArCollections($shopId, 'baki_payment', 'BAKI-AR', $counterId, $today);
        $emiCollected = $this->todayArCollections($shopId, 'emi_payment', 'EMI-AR', $counterId, $today);

        return [
            'pos_sales' => round($posSalesToday, 2),
            'online_sales' => round($onlineSalesToday, 2),
            'total_sales' => round($totalSales, 2),
            'pos_lifetime_sales' => round($posSalesLifetime, 2),
            'online_lifetime_sales' => round($onlineSalesLifetime, 2),
            'lifetime_sales' => round($lifetimeSales, 2),
            'returns' => round($returns, 2),
            'expenses' => round($expenses, 2),
            'net_amount' => round($netAmount, 2),
            'petty_cash' => round($pettyCash, 2),
            'baki_collected' => round($bakiCollected, 2),
            'emi_collected' => round($emiCollected, 2),
            'opening_balance' => round($cash['opening'], 2),
            'cash_in' => round($cash['cash_in'], 2),
            'cash_out' => round($cash['cash_out'], 2),
            'closing_balance' => round($cash['closing'], 2),
            'session_status' => $cash['session_status'],
            'orders_count' => $cash['orders_count'],
            'has_session' => $cash['has_session'],
            'stale_open' => $cash['stale_open'] ?? false,
        ];
    }

    /** Empty summary used when staff has no counter assigned. */
    public function emptySummary(): array
    {
        return [
            'pos_sales' => 0.0,
            'online_sales' => 0.0,
            'total_sales' => 0.0,
            'pos_lifetime_sales' => 0.0,
            'online_lifetime_sales' => 0.0,
            'lifetime_sales' => 0.0,
            'returns' => 0.0,
            'expenses' => 0.0,
            'net_amount' => 0.0,
            'petty_cash' => 0.0,
            'baki_collected' => 0.0,
            'emi_collected' => 0.0,
            'opening_balance' => 0.0,
            'cash_in' => 0.0,
            'cash_out' => 0.0,
            'closing_balance' => 0.0,
            'session_status' => 'none',
            'orders_count' => 0,
            'has_session' => false,
            'stale_open' => false,
        ];
    }

    public function countersForShop(int $shopId): Collection
    {
        return Counter::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @deprecated Prefer periodSales() (GL). Kept for callers that still need order filters.
     */
    public function revenueOrdersQuery(int $shopId, ?int $counterId = null): Builder
    {
        return Order::where('shop_id', $shopId)
            ->whereIn('status', ['completed', 'refunded'])
            ->where(function ($q) {
                $q->where('is_exchange_receipt', false)
                    ->orWhereNull('is_exchange_receipt');
            })
            ->when($counterId !== null, fn ($q) => $q->where('counter_id', $counterId));
    }

    /**
     * Recognized sales revenue (Cr REVENUE).
     * Default: POS + online. Pass ['sale'] or ['web_sale'] to split channels.
     * Refunded sales stay in this figure; Returns card reverses them on refund day.
     */
    public function salesForPeriod(
        int $shopId,
        ?int $counterId,
        ?Carbon $dayStart,
        ?Carbon $dayEnd,
        array $txnTypes = ['sale', 'web_sale'],
    ): float {
        return $this->accounts->sumAccountByTypes(
            $shopId,
            'REVENUE',
            'credit',
            $txnTypes,
            $dayStart,
            $dayEnd,
            $counterId,
        );
    }

    /**
     * Revenue reversals (Dr REVENUE on refund / web_refund) — matches postOrderRefund amounts & dates.
     */
    public function returnsForPeriod(int $shopId, ?int $counterId, Carbon $dayStart, Carbon $dayEnd): float
    {
        return $this->accounts->sumAccountByTypes(
            $shopId,
            'REVENUE',
            'debit',
            ['refund', 'web_refund'],
            $dayStart,
            $dayEnd,
            $counterId,
        );
    }

    protected function periodSales(int $shopId, ?int $counterId, ?Carbon $dayStart, ?Carbon $dayEnd): float
    {
        return $this->salesForPeriod($shopId, $counterId, $dayStart, $dayEnd);
    }

    protected function periodReturns(int $shopId, ?int $counterId, Carbon $dayStart, Carbon $dayEnd): float
    {
        return $this->returnsForPeriod($shopId, $counterId, $dayStart, $dayEnd);
    }

    /**
     * Operating expenses today: petty-cash spend (shop-wide) + till shortage (counter-scoped).
     */
    protected function todayExpenses(int $shopId, ?int $counterId, Carbon $dayStart, Carbon $dayEnd): float
    {
        $petty = $this->accounts->sumAccountByTypes(
            $shopId,
            'EXPENSE',
            'debit',
            ['petty_cash'],
            $dayStart,
            $dayEnd,
            null, // shop-wide float — always counts toward net
        );

        $shortage = $this->accounts->sumAccountByTypes(
            $shopId,
            'EXPENSE',
            'debit',
            ['counter_shortage'],
            $dayStart,
            $dayEnd,
            $counterId,
        );

        return $petty + $shortage;
    }

    protected function pettyCashBalance(int $shopId): float
    {
        try {
            $petty = $this->accounts->getAccount($shopId, 'PETTY');

            return $this->accounts->accountBalance($petty);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    protected function cashDrawerSummary(int $shopId, ?int $counterId, Carbon $today): array
    {
        $ledgerRows = $this->accounts->dailySummary($shopId, $today, $counterId);

        $openingLedger = 0.0;
        $salesIn = 0.0;
        $collectionsIn = 0.0;
        $transfersIn = 0.0;
        $transfersOut = 0.0;
        $refundsOut = 0.0;
        $purchasesOut = 0.0;
        $closingLedger = 0.0;

        foreach ($ledgerRows as $row) {
            $openingLedger += (float) $row['opening'];
            $salesIn += (float) $row['sales_in'];
            $collectionsIn += (float) ($row['collections_in'] ?? 0);
            $transfersIn += (float) $row['transfers_in'];
            $transfersOut += (float) $row['transfers_out'];
            $refundsOut += (float) $row['refunds_out'];
            $purchasesOut += (float) ($row['purchases_out'] ?? 0);
            $closingLedger += (float) $row['closing'];
        }

        // Today's sessions + any still-open sessions from prior days (overnight tills)
        $sessions = CounterSession::where('shop_id', $shopId)
            ->when($counterId !== null, fn ($q) => $q->where('counter_id', $counterId))
            ->where(function ($q) use ($today) {
                $q->whereDate('opened_at', $today)
                    ->orWhere('status', 'open');
            })
            ->orderByDesc('opened_at')
            ->get()
            ->unique('counter_id')
            ->values();

        $hasSession = $sessions->isNotEmpty();
        $anyOpen = $sessions->contains(fn ($s) => $s->status === 'open');
        $allClosed = $hasSession && $sessions->every(fn ($s) => $s->status === 'closed');
        $staleOpen = $sessions->contains(
            fn ($s) => $s->status === 'open' && ! $s->opened_at->isSameDay($today)
        );

        $openingSession = (float) $sessions->sum('opening_cash');
        $closingSession = 0.0;
        $ordersCount = 0;
        $sessionCashIn = 0.0;
        $sessionCashOut = 0.0;

        foreach ($sessions as $session) {
            $stats = $session->status === 'closed' && $session->closed_at
                ? $this->sessions->statsAsOf($session, $session->closed_at)
                : $this->sessions->liveStats($session);

            // Always expected drawer (Opening + In − Out), never declared count —
            // variance belongs on the session close screen, not this summary.
            $closingSession += $this->sessions->expectedCash($session, $stats);
            $ordersCount += (int) ($stats['order_count'] ?? 0);
            $sessionCashIn += (float) ($stats['cash_sales'] ?? 0)
                + (float) ($stats['collections_in'] ?? 0)
                + (float) ($stats['transfers_in'] ?? 0);
            $sessionCashOut += (float) ($stats['cash_refunds'] ?? 0)
                + (float) ($stats['transfers_out'] ?? 0)
                + (float) ($stats['cash_purchases'] ?? 0);
        }

        $opening = $hasSession ? $openingSession : $openingLedger;
        $closing = $hasSession ? $closingSession : $closingLedger;

        $cashIn = $hasSession
            ? $sessionCashIn
            : ($salesIn + $collectionsIn + $transfersIn);
        $cashOut = $hasSession
            ? $sessionCashOut
            : ($transfersOut + $refundsOut + $purchasesOut);

        $sessionStatus = 'none';
        if ($staleOpen) {
            $sessionStatus = 'stale';
        } elseif ($anyOpen) {
            $sessionStatus = 'open';
        } elseif ($allClosed) {
            $sessionStatus = 'closed';
        }

        return [
            'opening' => $opening,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'closing' => $closing,
            'session_status' => $sessionStatus,
            'orders_count' => $ordersCount,
            'has_session' => $hasSession,
            'stale_open' => $staleOpen,
        ];
    }

    /**
     * Today's Baki/EMI collections from ledger (AR credit lines).
     * Scoped by account_entries.counter_id when $counterId is set.
     */
    public function todayArCollections(
        int $shopId,
        string $txnType,
        string $arCode,
        ?int $counterId,
        ?Carbon $today = null,
    ): float {
        $today = $today ?? Carbon::today();
        $ar = Account::where('shop_id', $shopId)->where('code', $arCode)->first();
        if (! $ar) {
            return 0.0;
        }

        return (float) AccountEntry::query()
            ->where('account_id', $ar->id)
            ->where('entry_type', 'credit')
            ->when($counterId !== null, fn ($q) => $q->where('counter_id', $counterId))
            ->whereHas('transaction', function ($q) use ($shopId, $txnType, $today) {
                $q->where('shop_id', $shopId)
                    ->where('type', $txnType)
                    ->whereDate('transaction_date', $today);
            })
            ->sum('amount');
    }

    /**
     * Map of counter_id => collected amount for today (null key = no counter).
     *
     * @return Collection<string|int, float>
     */
    public function todayArCollectionsByCounter(
        int $shopId,
        string $txnType,
        string $arCode,
        ?Carbon $today = null,
    ): Collection {
        $today = $today ?? Carbon::today();
        $ar = Account::where('shop_id', $shopId)->where('code', $arCode)->first();
        if (! $ar) {
            return collect();
        }

        return AccountEntry::query()
            ->where('account_id', $ar->id)
            ->where('entry_type', 'credit')
            ->whereHas('transaction', function ($q) use ($shopId, $txnType, $today) {
                $q->where('shop_id', $shopId)
                    ->where('type', $txnType)
                    ->whereDate('transaction_date', $today);
            })
            ->selectRaw('counter_id, COALESCE(SUM(amount), 0) as total')
            ->groupBy('counter_id')
            ->pluck('total', 'counter_id')
            ->map(fn ($v) => round((float) $v, 2));
    }
}
