<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->email === 'superadmin@gmail.com' || $user->hasRole('Super Admin'))) {
            return $next($request);
        }

        abort(403, 'Unauthorized. Sirf admin access kar sakta hai.');
    }
}
