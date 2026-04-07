<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ReportLockController extends Controller
{
    public function showLockForm(Request $request)
    {
        $intended = $request->get('intended');
        return view('admin_panel.reporting.lock_form', compact('intended'));
    }

    public function unlock(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        $user = Auth::user();

        if (Hash::check($request->password, $user->password)) {
            $intended = $request->get('intended');
            
            if ($intended) {
                // Extract relative path to match Laravel's $request->path()
                $path = parse_url($intended, PHP_URL_PATH);
                $baseUrl = $request->getBaseUrl();
                
                if ($baseUrl && str_starts_with($path, $baseUrl)) {
                    $path = substr($path, strlen($baseUrl));
                }
                $path = ltrim($path, '/');
                
                // Allow exactly ONE hit for this report view
                session(['just_unlocked' => $path]);
                
                return redirect($intended);
            }
            
            return redirect()->route('home');
        }

        return back()->withErrors(['password' => 'Invalid password. Please try again.']);
    }
}
