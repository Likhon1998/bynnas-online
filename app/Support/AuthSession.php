<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Logout one guard without wiping the other (admin + storefront side-by-side).
 */
class AuthSession
{
    public static function logout(Request $request, string $guard): void
    {
        Auth::guard($guard)->logout();

        $otherStillLoggedIn = collect(array_keys(config('auth.guards', [])))
            ->contains(fn (string $name) => $name !== $guard && Auth::guard($name)->check());

        if ($otherStillLoggedIn) {
            $request->session()->regenerateToken();

            return;
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
