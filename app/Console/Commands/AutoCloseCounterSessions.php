<?php

namespace App\Console\Commands;

use App\Services\CounterSessionService;
use Illuminate\Console\Command;

class AutoCloseCounterSessions extends Command
{
    protected $signature = 'counters:auto-close-sessions
                            {--dry-run : List open sessions without closing them}';

    protected $description = 'Auto-close all open counter cash sessions at midnight using expected drawer cash';

    public function handle(CounterSessionService $sessions): int
    {
        $tz = config('app.display_timezone', config('app.timezone', 'Asia/Dhaka'));
        $at = now()->timezone($tz);

        if ($this->option('dry-run')) {
            $open = \App\Models\CounterSession::query()->open()->with('counter')->get();
            $this->info('Open sessions: '.$open->count().' ('.$at->toDateTimeString().' '.$tz.')');
            foreach ($open as $session) {
                $expected = $sessions->expectedCash($session);
                $this->line(sprintf(
                    '  - %s opened %s · expected ৳%s',
                    $session->counter->name ?? '#'.$session->id,
                    optional($session->opened_at)->timezone($tz)->format('d M Y H:i'),
                    format_taka_number($expected)
                ));
            }

            return self::SUCCESS;
        }

        $this->info('Auto-closing open counter sessions at '.$at->format('d M Y, h:i A').' ('.$tz.')…');

        $result = $sessions->autoCloseAllOpenSessions($at);

        foreach ($result['details'] as $line) {
            $this->line('  '.$line);
        }

        $this->info("Done. Closed: {$result['closed']} · Failed: {$result['failed']}");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
