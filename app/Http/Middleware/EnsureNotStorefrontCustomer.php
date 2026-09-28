<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Website shoppers (role=customer) must not enter the admin/POS panel.
 */
class EnsureNotStorefrontCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user() ?? $request->user('admin');

        if ($user && $user->isStorefrontCustomer()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'This area is for store staff only. Please use your customer account page.',
                    'redirect' => route('website.account'),
                ], 403);
            }

            return redirect()
                ->route('website.account')
                ->with('error', 'Staff panel is for employees only. You are signed in as a customer.');
        }

        return $next($request);
    }
}
