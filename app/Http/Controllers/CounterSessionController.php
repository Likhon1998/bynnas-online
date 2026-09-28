<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\CounterSession;
use App\Services\AccountService;
use App\Services\CounterSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CounterSessionController extends Controller
{
    public function __construct(
        protected CounterSessionService $sessions,
        protected AccountService $accounts,
    ) {}

    public function index()
    {
        $user = Auth::user();
        $shopId = $user->shop_id;
        $counterScope = $this->staffCounterId($user);

        $counters = Counter::where('shop_id', $shopId)
            ->where('is_active', true)
            ->when($counterScope !== null, fn ($q) => $q->where('id', $counterScope))
            ->orderBy('name')
            ->get();

        // Destinations must already be open so the transfer appears on both session reports
        $transferTargets = Counter::where('shop_id', $shopId)
            ->where('is_active', true)
            ->whereIn('id', CounterSession::where('shop_id', $shopId)->open()->pluck('counter_id'))
            ->when($counterScope !== null, fn ($q) => $q->where('id', '!=', $counterScope))
            ->orderBy('name')
            ->get();

        $openSessions = CounterSession::where('shop_id', $shopId)
            ->open()
            ->when($counterScope !== null, fn ($q) => $q->where('counter_id', $counterScope))
            ->with(['counter', 'opener'])
            ->get()
            ->keyBy('counter_id');

        $recent = CounterSession::where('shop_id', $shopId)
            ->when($counterScope !== null, fn ($q) => $q->where('counter_id', $counterScope))
            ->with(['counter', 'opener', 'closer'])
            ->latest('opened_at')
            ->paginate(15);

        $live = [];
        foreach ($openSessions as $counterId => $session) {
            $stats = $this->sessions->liveStats($session);
            $live[$counterId] = [
                'stats' => $stats,
                'expected' => $this->sessions->expectedCash($session, $stats),
            ];
        }

        $isAdmin = $user->isAdminUser();
        $canTransfer = $openSessions->isNotEmpty() && $transferTargets->isNotEmpty();

        return view('counters.sessions', compact(
            'counters',
            'openSessions',
            'recent',
            'live',
            'isAdmin',
            'transferTargets',
            'canTransfer',
        ));
    }

    public function transfer(Request $request)
    {
        $user = Auth::user();
        $shopId = $user->shop_id;

        $request->validate([
            'from_counter_id' => 'required|exists:counters,id',
            'to_counter_id' => 'required|exists:counters,id|different:from_counter_id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:3|max:500',
        ]);

        $from = Counter::where('shop_id', $shopId)->findOrFail($request->from_counter_id);
        $to = Counter::where('shop_id', $shopId)->findOrFail($request->to_counter_id);

        // Staff may only send from their assigned counter
        if (! $user->isAdminUser()) {
            if (! $user->counter_id || (int) $user->counter_id !== (int) $from->id) {
                abort(403, 'You can only transfer cash from your assigned counter.');
            }
        }

        $openFrom = $this->sessions->currentOpen($from);
        if (! $openFrom) {
            return back()->with('error', "{$from->name} has no open cash session. Open the till before transferring.");
        }

        $openTo = $this->sessions->currentOpen($to);
        if (! $openTo) {
            return back()->with('error', "{$to->name} must also have an open cash session before receiving a transfer.");
        }

        $available = $this->sessions->transferableCash($openFrom);
        if ((float) $request->amount > $available + 0.0001) {
            return back()
                ->with('error', "Only " . format_taka($available) . " is transferable from {$from->name} right now (drawer / ledger).")
                ->withInput();
        }

        try {
            $txn = $this->accounts->postCounterCashTransfer(
                $from,
                $to,
                (float) $request->amount,
                $request->reason,
                $user->id,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $amount = format_taka_number((float) $request->amount);

        return back()->with(
            'success',
            "Transferred ৳{$amount} from {$from->name} → {$to->name}. Ref {$txn->transaction_no}. Both tills will show this on close."
        );
    }

    public function openTodayForm()
    {
        $user = Auth::user();

        if ($user->isAdminUser()) {
            $adminSession = $user->adminOpenPosSession();
            if ($adminSession) {
                return redirect()->route('pos.index');
            }

            $freeCounters = Counter::where('shop_id', $user->shop_id)
                ->where('is_active', true)
                ->unassigned()
                ->whereDoesntHave('openSession')
                ->orderBy('name')
                ->get();

            return view('counters.open-today', [
                'counter' => $freeCounters->first(),
                'freeCounters' => $freeCounters,
                'isAdminOpen' => true,
                'staleSession' => null,
                'stats' => null,
                'expected' => null,
                'transferLog' => [],
            ]);
        }

        if (! $user->counter_id) {
            return redirect()->route('dashboard')
                ->with('error', 'No counter assigned. Ask your admin to assign you a counter.');
        }

        if ($user->hasCalendarDayOpenSession()) {
            return redirect()->route('pos.index');
        }

        $counter = Counter::where('shop_id', $user->shop_id)->findOrFail($user->counter_id);
        $staleSession = $this->sessions->currentOpen($counter);

        if ($staleSession && ! $staleSession->opened_at->isToday()) {
            $stats = $this->sessions->liveStats($staleSession);
            $expected = $this->sessions->expectedCash($staleSession, $stats);
            $transferLog = $this->transferLogForSession($staleSession);
            $staleSession->load(['counter', 'opener']);

            return view('counters.open-today', compact('counter', 'staleSession', 'stats', 'expected', 'transferLog') + [
                'freeCounters' => collect(),
                'isAdminOpen' => false,
            ]);
        }

        if ($staleSession && $staleSession->opened_at->isToday()) {
            return redirect()->route('pos.index');
        }

        return view('counters.open-today', [
            'counter' => $counter,
            'freeCounters' => collect(),
            'isAdminOpen' => false,
            'staleSession' => null,
            'stats' => null,
            'expected' => null,
            'transferLog' => [],
        ]);
    }

    public function openTodayStore(Request $request)
    {
        $user = Auth::user();

        if ($user->isAdminUser()) {
            if ($user->adminOpenPosSession()) {
                return redirect()->route('pos.index');
            }

            $request->validate([
                'counter_id' => 'required|exists:counters,id',
                'opening_cash' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:500',
            ]);

            $counter = Counter::where('shop_id', $user->shop_id)
                ->where('is_active', true)
                ->findOrFail($request->counter_id);

            if (! $counter->isUnassigned()) {
                return back()->with('error', 'That counter is assigned to staff. They can open it later — pick an unassigned counter instead.');
            }

            if ($this->sessions->currentOpen($counter)) {
                return back()->with('error', 'That counter already has an open cash session. Close it first or choose another unassigned counter.');
            }

            try {
                $this->sessions->openSession($counter, (float) $request->opening_cash, $request->notes);
            } catch (\Throwable $e) {
                return back()->with('error', $e->getMessage());
            }

            return redirect()
                ->route('pos.index')
                ->with('success', "{$counter->name} opened with ৳" . format_taka_number((float) $request->opening_cash) . ' starting cash.');
        }

        if (! $user->counter_id) {
            abort(403);
        }

        if ($user->hasCalendarDayOpenSession()) {
            return redirect()->route('pos.index');
        }

        $request->validate([
            'opening_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $counter = Counter::where('shop_id', $user->shop_id)->findOrFail($user->counter_id);

        if ($this->sessions->currentOpen($counter)) {
            return back()->with('error', 'Close the previous cash session before entering today\'s opening balance.');
        }

        try {
            $this->sessions->openSession($counter, (float) $request->opening_cash, $request->notes);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('pos.index')
            ->with('success', "{$counter->name} opened with ৳" . format_taka_number((float) $request->opening_cash) . ' starting cash.');
    }

    public function open(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'counter_id' => 'required|exists:counters,id',
            'opening_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        if (! $user->isAdminUser()) {
            if (! $user->counter_id || (int) $request->counter_id !== (int) $user->counter_id) {
                abort(403, 'You can only open your assigned counter.');
            }
        } else {
            if ($user->adminOpenPosSession()) {
                return back()->with('error', 'Close your current POS till before opening another unassigned counter.');
            }
        }

        $counter = Counter::where('shop_id', $user->shop_id)->findOrFail($request->counter_id);

        if ($user->isAdminUser()) {
            if (! $counter->isUnassigned()) {
                return back()->with('error', 'That counter is assigned to staff. Pick an unassigned counter.');
            }
            if ($this->sessions->currentOpen($counter)) {
                return back()->with('error', 'That counter already has an open cash session. Choose another unassigned counter.');
            }
        }

        try {
            $this->sessions->openSession($counter, (float) $request->opening_cash, $request->notes);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route($user->isAdminUser() ? 'pos.index' : 'counters.sessions.index')
            ->with('success', "{$counter->name} opened with ৳" . format_taka_number((float) $request->opening_cash) . '.');
    }

    public function closeForm(CounterSession $session)
    {
        $this->authorizeSession($session);

        if (! $session->isOpen()) {
            return redirect()->route('counters.sessions.index')->with('error', 'Session already closed.');
        }

        $stats = $this->sessions->liveStats($session);
        $expected = $this->sessions->expectedCash($session, $stats);
        $transferLog = $this->transferLogForSession($session);
        $session->load(['counter', 'opener']);

        return view('counters.close-session', compact('session', 'stats', 'expected', 'transferLog'));
    }

    public function close(Request $request, CounterSession $session)
    {
        $this->authorizeSession($session);

        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'counted_confirm' => 'accepted',
        ]);

        $stats = $this->sessions->liveStats($session);
        $expected = $this->sessions->expectedCash($session, $stats);
        $closingCash = (float) $request->closing_cash;
        if (abs($closingCash - $expected) > 0.009 && blank($request->notes)) {
            return back()
                ->withInput()
                ->with('error', 'Expected ' . format_taka($expected) . '. Enter a note explaining the variance before closing.');
        }

        try {
            $closed = $this->sessions->closeSession($session, $closingCash, $request->notes);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $user = Auth::user();
        if ($user->requiresDailyOpeningBalance() && ! $user->hasTodayOpenSession()) {
            return redirect()
                ->route('counters.sessions.open-today')
                ->with('success', 'Previous session closed. Now enter today\'s opening cash.');
        }

        return redirect()
            ->route('counters.sessions.show', $closed)
            ->with('success', 'Counter closed. Variance: ৳' . format_taka_number((float) $closed->variance));
    }

    public function show(CounterSession $session)
    {
        $this->authorizeSession($session);
        $session->load(['counter', 'opener', 'closer']);

        $stats = $session->isOpen()
            ? $this->sessions->liveStats($session)
            : $this->sessions->statsAsOf($session, $session->closed_at);

        $transferLog = $this->transferLogForSession($session);

        return view('counters.session-show', compact('session', 'stats', 'transferLog'));
    }

    protected function transferLogForSession(CounterSession $session): array
    {
        $session->loadMissing('counter');
        if (! $session->counter) {
            return [];
        }

        $until = $session->isOpen() ? now() : ($session->closed_at ?? now());

        return $this->accounts->counterCashTransferLog($session->counter, $session->opened_at, $until);
    }

    protected function authorizeSession(CounterSession $session): void
    {
        $user = Auth::user();

        if ($session->shop_id !== $user->shop_id) {
            abort(403);
        }

        $counterScope = $this->staffCounterId($user);
        if ($counterScope !== null && (int) $session->counter_id !== $counterScope) {
            abort(403, 'You can only view sessions for your assigned counter.');
        }
    }

    /**
     * Non-admin staff are locked to their assigned counter. Admins see all.
     */
    protected function staffCounterId($user): ?int
    {
        if ($user->isAdminUser()) {
            return null;
        }

        return $user->counter_id ? (int) $user->counter_id : -1;
    }
}
