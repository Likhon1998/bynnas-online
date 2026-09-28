<?php

namespace App\Http\Middleware;

use App\Support\AuthSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckIfSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user() ?? $request->user('admin');

        if ($user && $user->is_suspended) {
            AuthSession::logout($request, 'admin');

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Your account is suspended. Please contact your shop owner.',
            ]);
        }

        return $next($request);
    }
}
