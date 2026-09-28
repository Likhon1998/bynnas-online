<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\CounterSession;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class CounterSessionService
{
    public function __construct(protected AccountService $accounts) {}

    public function openSession(Counter $counter, float $openingCash, ?string $notes = null, ?int $userId = null): CounterSession
    {
        if ($this->currentOpen($counter)) {
            throw new InvalidArgumentException("{$counter->name} already has an open cash session. Close it first.");
        }

        $userId ??= Auth::id();
        $openingCash = round(max(0, $openingCash), 2);

        $session = CounterSession::create([
            'shop_id' => $counter->shop_id,
            'counter_id' => $counter->id,
            'opened_by' => $userId,
            'opened_at' => now(),
            'opening_cash' => $openingCash,
            'status' => 'open',
            'notes' => $notes,
        ]);

        if ($openingCash > 0) {
            $cashAccount = $this->accounts->ensureCounterCashAccount($counter);
            $this->accounts->postCounterFloat(
                $counter->shop_id,
                $cashAccount,
                $openingCash,
                "Opening float — {$counter->name} (session #{$session->id})",
                $userId,
            );
        }

        return $session;
    }

    public function closeSession(
        CounterSession $session,
        float $closingCash,
        ?string $notes = null,
        ?int $userId = null,
        ?Carbon $closedAt = null,
    ): CounterSession {
        if (! $session->isOpen()) {
            throw new InvalidArgumentException('This cash session is already closed.');
        }

        $closedAt ??= now();
        // System/auto close may have no Auth user
        if ($userId === null && Auth::check()) {
            $userId = Auth::id();
        }

        $closingCash = round(max(0, $closingCash), 2);
        $stats = $this->sessionStats($session, $closedAt);
        $expected = $this->expectedCash($session, $stats);
        $variance = round($closingCash - $expected, 2);

        $session->loadMissing('counter');
        if ($session->counter) {
            $this->accounts->settleCounterSessionClose(
                $session->counter,
                $session,
                $closingCash,
                $expected,
                $userId,
            );
        }

        $session->update([
            'closed_by' => $userId,
            'closed_at' => $closedAt,
            'closing_cash' => $closingCash,
            'expected_cash' => $expected,
            'variance' => $variance,
            'order_count' => $stats['order_count'],
            'total_sales' => $stats['total_sales'],
            'cash_sales' => $stats['cash_sales'],
            'card_sales' => $stats['card_sales'],
            'mobile_sales' => $stats['mobile_sales'],
            'cash_refunds' => $stats['cash_refunds'],
            'status' => 'closed',
            'notes' => trim(($session->notes ? $session->notes . "\n" : '') . ($notes ?? '')),
        ]);

        return $session->fresh(['counter', 'opener', 'closer']);
    }

    /**
     * Close every open till using expected drawer cash (midnight auto-close).
     *
     * @return array{closed:int, failed:int, details:list<string>}
     */
    public function autoCloseAllOpenSessions(?Carbon $at = null): array
    {
        $at ??= now();
        $closed = 0;
        $failed = 0;
        $details = [];

        $sessions = CounterSession::query()
            ->open()
            ->with(['counter', 'opener'])
            ->orderBy('id')
            ->get();

        foreach ($sessions as $session) {
            $label = $session->counter->name ?? ('Session #'.$session->id);

            try {
                $stats = $this->sessionStats($session, $at);
                $expected = $this->expectedCash($session, $stats);
                $note = sprintf(
                    'Auto-closed at midnight (%s) with expected drawer cash ৳%s.',
                    $at->timezone(config('app.display_timezone', 'Asia/Dhaka'))->format('d M Y, h:i A'),
                    format_taka_number($expected)
                );

                $this->closeSession(
                    $session,
                    $expected,
                    $note,
                    $session->opened_by,
                    $at,
                );

                $closed++;
                $details[] = "{$label}: closed at ৳".format_taka_number($expected);
            } catch (\Throwable $e) {
                $failed++;
                $details[] = "{$label}: FAILED — ".$e->getMessage();
            }
        }

        return compact('closed', 'failed', 'details');
    }

    public function currentOpen(Counter $counter): ?CounterSession
    {
        return CounterSession::where('counter_id', $counter->id)
            ->open()
            ->latest('opened_at')
            ->first();
    }

    public function liveStats(CounterSession $session): array
    {
        return $this->sessionStats($session, now());
    }

    public function statsAsOf(CounterSession $session, ?Carbon $until = null): array
    {
        return $this->sessionStats($session, $until ?? ($session->closed_at ?? now()));
    }

    public function expectedCash(CounterSession $session, ?array $stats = null): float
    {
        $stats ??= $this->liveStats($session);

        return round(
            (float) $session->opening_cash
            + (float) ($stats['cash_sales'] ?? 0)
            + (float) ($stats['collections_in'] ?? 0)
            + (float) ($stats['transfers_in'] ?? 0)
            - (float) ($stats['cash_refunds'] ?? 0)
            - (float) ($stats['transfers_out'] ?? 0)
            - (float) ($stats['cash_purchases'] ?? 0),
            2
        );
    }

    /**
     * Physical drawer available for transfer (prefer session expected, never above ledger).
     */
    public function transferableCash(CounterSession $session): float
    {
        $session->loadMissing('counter');
        $expected = $this->expectedCash($session);
        if (! $session->counter) {
            return max(0, $expected);
        }

        $ledger = $this->accounts->accountBalance(
            $this->accounts->ensureCounterCashAccount($session->counter)
        );

        return round(max(0, min($expected, $ledger)), 2);
    }

    protected function sessionStats(CounterSession $session, Carbon $until): array
    {
        $from = $session->opened_at;
        $orders = Order::where('shop_id', $session->shop_id)
            ->where('counter_id', $session->counter_id)
            ->whereBetween('created_at', [$from, $until])
            ->get();

        $sold = $orders->whereIn('status', ['completed', 'refunded']);
        $refunded = $orders->whereIn('status', ['refunded', 'returned']);

        $cashSales = 0.0;
        $cardSales = 0.0;
        $mobileSales = 0.0;
        $otherSales = 0.0;

        foreach ($sold as $order) {
            $tender = $order->settledTenderBreakdown();

            if ($tender['has_breakdown']) {
                $cashSales += $tender['cash'];
                $cardSales += $tender['card'];
                $mobileSales += $tender['mobile'];
                continue;
            }

            $amount = $order->netPayable();
            $method = strtolower((string) $order->payment_method);

            if ($this->isPureCash($method)) {
                $cashSales += $amount;
            } elseif (str_contains($method, '+') || (str_contains($method, 'cash') && (
                str_contains($method, 'card') || str_contains($method, 'bkash') || str_contains($method, 'mobile')
            ))) {
                // Legacy mixed tender without breakdown — exclude from cash drawer to avoid fake cash
                $otherSales += $amount;
            } elseif (str_contains($method, 'card')) {
                $cardSales += $amount;
            } elseif (str_contains($method, 'bkash') || str_contains($method, 'nagad') || str_contains($method, 'mobile')) {
                $mobileSales += $amount;
            } elseif (str_contains($method, 'cash')) {
                $cashSales += $amount;
            } else {
                $otherSales += $amount;
            }
        }

        $cashRefunds = 0.0;
        foreach ($refunded as $order) {
            $tender = $order->settledTenderBreakdown();
            if ($tender['has_breakdown']) {
                $cashRefunds += $tender['cash'];
                continue;
            }
            $method = strtolower((string) $order->payment_method);
            if ($this->isPureCash($method) || (str_contains($method, 'cash') && ! str_contains($method, '+'))) {
                $cashRefunds += $order->netPayable();
            }
        }

        $session->loadMissing('counter');
        $movements = $session->counter
            ? $this->accounts->counterCashSessionMovements($session->counter, $from, $until)
            : [
                'transfers_in' => 0.0,
                'transfers_out' => 0.0,
                'cash_purchases' => 0.0,
                'collections_in' => 0.0,
            ];

        return [
            'order_count' => $sold->where('status', 'completed')->count(),
            'total_sales' => round((float) $sold->where('status', 'completed')->sum(fn ($o) => $o->netPayable()), 2),
            'cash_sales' => round($cashSales, 2),
            'card_sales' => round($cardSales, 2),
            'mobile_sales' => round($mobileSales, 2),
            'other_sales' => round($otherSales, 2),
            'cash_refunds' => round((float) $cashRefunds, 2),
            'collections_in' => round((float) ($movements['collections_in'] ?? 0), 2),
            'transfers_in' => round((float) $movements['transfers_in'], 2),
            'transfers_out' => round((float) $movements['transfers_out'], 2),
            'cash_purchases' => round((float) $movements['cash_purchases'], 2),
        ];
    }

    protected function isPureCash(string $method): bool
    {
        $method = trim($method);

        return $method === 'cash' || $method === '';
    }
}
