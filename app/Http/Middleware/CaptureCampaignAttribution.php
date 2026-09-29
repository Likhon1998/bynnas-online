<?php

namespace App\Http\Middleware;

use App\Services\CampaignAttributionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureCampaignAttribution
{
    public function __construct(private CampaignAttributionService $attribution) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')
            && ! $request->ajax()
            && ($request->query->has('utm_source') || $request->query->has('utm_campaign') || $request->headers->has('referer'))
            && $request->routeIs('home', 'website.*')) {
            $this->attribution->captureFromRequest($request);
        }

        return $next($request);
    }
}
