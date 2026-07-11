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

        // Middleware disabled: Admin wants this user to have access to other pages based on Spatie permissions.
        // if ($user && $user->hasRole('period_viewer')) {
        //     if (
        //         !$request->routeIs('home')
        //         && !$request->is('home')
        //         && !$request->routeIs('logout')
        //         && !$request->is('logout')
        //     ) {
        //         return redirect()->route('home')
        //             ->with('error', 'Aap ke account ko sirf limited access hai. Period archive ab sirf admin ke liye hai.');
        //     }
        // }

        return $next($request);
    }
}
