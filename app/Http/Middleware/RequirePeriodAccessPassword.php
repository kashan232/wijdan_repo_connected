<?php

namespace App\Http\Middleware;

use App\Models\PeriodClosingSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePeriodAccessPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = PeriodClosingSetting::instance();

        if (!$settings->hasClosingPassword()) {
            return $next($request);
        }

        if ($request->session()->get('period_sensitive_unlocked') === true) {
            return $next($request);
        }

        if (!$request->session()->has('period_access_intended')) {
            $request->session()->put('period_access_intended', $request->fullUrl());
        }

        return redirect()->route('period.access.unlock');
    }
}
