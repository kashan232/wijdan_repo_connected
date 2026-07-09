<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectPeriodViewer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('period_viewer')) {
            if (
                !$request->routeIs('period.archive.*')
                && !$request->routeIs('logout')
                && !$request->is('logout')
            ) {
                return redirect()->route('period.archive.index')
                    ->with('error', 'Aap sirf closed period archive dekh sakte hain.');
            }
        }

        return $next($request);
    }
}
