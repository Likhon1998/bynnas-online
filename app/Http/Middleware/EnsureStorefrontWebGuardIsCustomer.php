<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep the storefront "web" guard customer-only.
 * Staff must use the "admin" guard so browsing the shop while logged into admin stays guest.
 */
class EnsureStorefrontWebGuardIsCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user && ! $user->isStorefrontCustomer()) {
            Auth::guard('web')->logout();
        }

        return $next($request);
    }
}
