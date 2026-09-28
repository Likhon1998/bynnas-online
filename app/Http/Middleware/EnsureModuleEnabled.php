<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    /**
     * Usage: ->middleware('module:retail')
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (! module_enabled($module)) {
            abort(404);
        }

        return $next($request);
    }
}
