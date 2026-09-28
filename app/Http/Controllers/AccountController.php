<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountEntry;
use App\Models\Counter;
use App\Services\AccountService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function __construct(protected AccountService $accounts) {}

    protected function ensureAdmin(): void
    {
        if (! Auth::user()?->isAdminUser()) {
            abort(403, 'Accounts are only available to shop admins.');
        }
    }

    protected function bootstrap(): int
    {
        $this->ensureAdmin();

        $shopId = Auth::user()->shop_id;
        $this->accounts->ensureShopAccounts($shopId);

        return $shopId;
    }

    protected function accountView(Request $request, string $activeTab)
    {
        $shopId = $this->bootstrap();
        [$start, $end] = $this->accounts->dateRange($request);
        $counters = Counter::where('shop_id', $shopId)->get();

        $allAccountModels = Account::where('shop_id', $shopId)
            ->with('counter')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $activeAccountModels = $allAccountModels->where('is_active', true)->values();
        $balances = $this->accounts->accountBalances($allAccountModels);

        $accounts = $activeAccountModels->map(fn (Account $a) => [
            'account' => $a,
            'balance' => $balances[$a->id] ?? 0,
        ]);

        $chartAccounts = $allAccountModels->map(fn (Account $a) => [
            'account' => $a,
            'balance' => $balances[$a->id] ?? 0,
        ]);

        $grouped = $chartAccounts->groupBy(fn ($row) => $row['account']->type);
        $accountCount = $chartAccounts->count();

        $accountList = $activeAccountModels->sortBy('name')->values();

        $ledgerAccountId = $activeTab === 'ledger'
            ? $request->get('account_id', $accountList->first()?->id)
            : $accountList->first()?->id;
        $ledgerAccount = $ledgerAccountId
            ? $allAccountModels->firstWhere('id', (int) $ledgerAccountId)
            : null;

        $ledgerEntries = collect();

        if ($ledgerAccount) {
            $opening = (float) $ledgerAccount->opening_balance;
            $priorEntries = AccountEntry::where('account_id', $ledgerAccount->id)
                ->whereHas('transaction', fn ($q) => $q->where('transaction_date', '<', $start->toDateString()))
                ->get();

            foreach ($priorEntries as $entry) {
                $opening += $this->entryDelta($ledgerAccount, $entry);
            }

            $periodEntries = AccountEntry::where('account_id', $ledgerAccount->id)
                ->whereHas('transaction', fn ($q) => $q->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]))
                ->with(['transaction', 'counter'])
                ->get()
                ->sortBy(fn ($entry) => $entry->transaction->transaction_date . $entry->id);

            $runningBalance = $opening;
            $ledgerEntries = $periodEntries->map(function ($entry) use ($ledgerAccount, &$runningBalance) {
                $delta = $this->entryDelta($ledgerAccount, $entry);
                $runningBalance += $delta;

                return [
                    'entry' => $entry,
                    'debit' => $entry->entry_type === 'debit' ? $entry->amount : 0,
                    'credit' => $entry->entry_type === 'credit' ? $entry->amount : 0,
                    'balance' => $runningBalance,
                ];
            });
        }

        $cashAccounts = $this->accounts->cashAccounts($shopId);
        $cashAccountId = $activeTab === 'cash-book' ? $request->get('account_id') : null;
        $counterId = $activeTab === 'cash-book' ? $request->get('counter_id') : null;

        if ($counterId && ! $cashAccountId) {
            $cashAccountId = $cashAccounts->firstWhere('counter_id', (int) $counterId)?->id;
        }

        $cashBookAccount = $cashAccountId
            ? $cashAccounts->firstWhere('id', (int) $cashAccountId)
            : $cashAccounts->first();

        $cashBookEntries = collect();

        if ($cashBookAccount) {
            $cashBookEntries = AccountEntry::where('account_id', $cashBookAccount->id)
                ->whereHas('transaction', fn ($q) => $q->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]))
                ->with(['transaction', 'counter'])
                ->join('account_transactions', 'account_entries.account_transaction_id', '=', 'account_transactions.id')
                ->orderBy('account_transactions.transaction_date')
                ->orderBy('account_entries.id')
                ->select('account_entries.*')
                ->get();
        }

        $cashBookBalance = $cashBookAccount ? ($balances[$cashBookAccount->id] ?? 0) : 0;

        $date = $request->filled('date') ? Carbon::parse($request->date) : now();
        $summaryCounterId = $activeTab === 'daily-summary' ? $request->get('counter_id') : null;
        $summaryRows = $this->accounts->dailySummary($shopId, $date, $summaryCounterId ? (int) $summaryCounterId : null);

        $pettyAccount = $this->accounts->getAccount($shopId, 'PETTY');
        $pettyBalance = $balances[$pettyAccount->id] ?? 0;

        $transferAccounts = $activeAccountModels
            ->where('type', 'asset')
            ->sortBy('name')
            ->values();

        return view('accounts.layout', compact(
            'activeTab',
            'start',
            'end',
            'counters',
            'accounts',
            'chartAccounts',
            'grouped',
            'accountCount',
            'ledgerAccount',
            'accountList',
            'ledgerEntries',
            'cashAccounts',
            'cashBookAccount',
            'cashBookEntries',
            'cashBookBalance',
            'date',
            'summaryRows',
            'summaryCounterId',
            'pettyBalance',
            'transferAccounts',
        ));
    }

    public function openingBalance(Request $request)
    {
        return $this->accountView($request, 'opening-balance');
    }

    public function updateOpeningBalance(Request $request)
    {
        $shopId = $this->bootstrap();

        $request->validate([
            'balances' => 'required|array',
            'balances.*' => 'nullable|numeric|min:0',
        ]);

        foreach ($request->balances as $accountId => $amount) {
            $account = Account::where('shop_id', $shopId)->findOrFail($accountId);
            $account->update(['opening_balance' => $amount ?? 0]);
        }

        return redirect()
            ->route('accounts.opening-balance')
            ->with('success', 'Opening balances updated successfully.');
    }

    public function chart(Request $request)
    {
        return $this->accountView($request, 'chart');
    }

    public function storeAccount(Request $request)
    {
        $shopId = $this->bootstrap();

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
        ]);

        $code = 'CUSTOM-' . strtoupper(substr(md5($request->name . now()), 0, 8));

        Account::create([
            'shop_id' => $shopId,
            'code' => $code,
            'name' => $request->name,
            'type' => $request->type,
            'is_system' => false,
            'is_active' => true,
        ]);

        return redirect()
            ->route('accounts.chart')
            ->with('success', 'Account added to chart.');
    }

    public function ledger(Request $request)
    {
        return $this->accountView($request, 'ledger');
    }

    public function cashBook(Request $request)
    {
        return $this->accountView($request, 'cash-book');
    }

    public function dailySummary(Request $request)
    {
        return $this->accountView($request, 'daily-summary');
    }

    public function pettyCashForm(Request $request)
    {
        return $this->accountView($request, 'petty-cash');
    }

    public function pettyCashStore(Request $request)
    {
        $shopId = $this->bootstrap();

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
        ]);

        $petty = $this->accounts->getAccount($shopId, 'PETTY');
        $balance = $this->accounts->accountBalance($petty);

        if ($request->amount > $balance) {
            return redirect()
                ->route('accounts.petty-cash')
                ->with('error', 'Insufficient petty cash balance.');
        }

        $this->accounts->postPettyCash($shopId, (float) $request->amount, $request->description);

        return redirect()
            ->route('accounts.petty-cash')
            ->with('success', 'Petty cash expense recorded.');
    }

    public function transferForm(Request $request)
    {
        return $this->accountView($request, 'transfer');
    }

    public function transferStore(Request $request)
    {
        $shopId = $this->bootstrap();

        $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|min:3|max:500',
        ]);

        $from = Account::where('shop_id', $shopId)->with('counter')->findOrFail($request->from_account_id);
        $to = Account::where('shop_id', $shopId)->with('counter')->findOrFail($request->to_account_id);

        try {
            // Counter till → counter till: use dedicated transfer so both sessions justify the move
            if ($from->counter_id && $to->counter_id && $from->counter && $to->counter) {
                $txn = $this->accounts->postCounterCashTransfer(
                    $from->counter,
                    $to->counter,
                    (float) $request->amount,
                    $request->description,
                );
            } else {
                $fromBalance = $this->accounts->accountBalance($from);
                if ($request->amount > $fromBalance) {
                    return redirect()
                        ->route('accounts.transfer')
                        ->with('error', 'Insufficient balance in source account.');
                }

                $this->accounts->postTransfer(
                    $shopId,
                    $from,
                    $to,
                    (float) $request->amount,
                    $request->description,
                );
                $txn = null;
            }
        } catch (\Throwable $e) {
            return redirect()
                ->route('accounts.transfer')
                ->with('error', $e->getMessage());
        }

        $msg = 'Transfer completed successfully.';
        if ($txn) {
            $msg .= ' Ref ' . $txn->transaction_no . '. Both counter sessions will show this move.';
        }

        return redirect()
            ->route('accounts.transfer')
            ->with('success', $msg);
    }

    protected function entryDelta(Account $account, AccountEntry $entry): float
    {
        $amount = (float) $entry->amount;

        if (in_array($account->type, ['asset', 'expense'])) {
            return $entry->entry_type === 'debit' ? $amount : -$amount;
        }

        return $entry->entry_type === 'credit' ? $amount : -$amount;
    }
}
