<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffOpeningBalance
{
    /**
     * Staff with a counter must enter opening cash for today before using the app.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! retail_enabled()) {
            return $next($request);
        }

        $user = Auth::guard('admin')->user() ?? $request->user('admin');

        if (! $user || ! $user->requiresDailyOpeningBalance()) {
            return $next($request);
        }

        if ($user->hasTodayOpenSession()) {
            return $next($request);
        }

        if ($request->routeIs([
            'counters.sessions.open-today',
            'counters.sessions.open-today.store',
            'counters.sessions.close',
            'counters.sessions.close-form',
            'logout',
            'profile.edit',
            'profile.update',
            'profile.destroy',
        ])) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Enter today\'s opening cash for your counter before continuing.',
                'redirect' => route('counters.sessions.open-today'),
            ], 403);
        }

        return redirect()
            ->route('counters.sessions.open-today')
            ->with('error', 'Open your counter cash session before using the system.');
    }
}
