<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LockReport
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        // 1. AJAX data fetch routes
        if (str_ends_with($path, '/fetch')) {
            $parentPath = substr($path, 0, -6);
            if (session("active_report_session") !== $parentPath) {
                return response()->json(['error' => 'Report is locked.'], 403);
            }
            return $next($request);
        }

        // 2. Main Report Views
        // Check if this specific request was JUST unlocked via password form
        if (session("just_unlocked") === $path) {
            // Set this path as the 'active' one for its internal AJAX calls
            session(["active_report_session" => $path]);
            
            // CRITICAL: Remove the trigger so the NEXT visit to this URL asks for password again
            session()->forget("just_unlocked");
            
            return $next($request);
        }

        // 3. Fallback: Prompt for password
        if ($request->ajax()) {
            return response()->json(['error' => 'Report is locked.'], 403);
        }

        return redirect()->route('report.lock.form', ['intended' => $request->fullUrl()]);
    }
}
