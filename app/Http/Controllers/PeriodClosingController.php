<?php

namespace App\Http\Controllers;

use App\Models\AccountingPeriod;
use App\Models\PeriodClosingSetting;
use App\Models\User;
use App\Services\PeriodClosing\PeriodClosingService;
use App\Services\PeriodClosing\PeriodLock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PeriodClosingController extends Controller
{
    public function __construct(
        private PeriodClosingService $closingService
    ) {}

    public function index()
    {
        $settings = PeriodClosingSetting::instance();
        $summary = $this->closingService->getSummary();
        $hasPassword = $settings->hasClosingPassword();

        return view('admin_panel.period_closing.index', compact('settings', 'summary', 'hasPassword'));
    }

    public function unlockForm()
    {
        $settings = PeriodClosingSetting::instance();

        if (!$settings->hasClosingPassword()) {
            return redirect()->route('period.closing.index')
                ->with('error', 'Pehle Period Closing page se access password set karein.');
        }

        return view('admin_panel.period_closing.unlock');
    }

    public function verifyAccess(Request $request)
    {
        $request->validate([
            'access_password' => 'required|string',
        ]);

        $settings = PeriodClosingSetting::instance();

        if (!$settings->hasClosingPassword()) {
            return redirect()->route('period.closing.index');
        }

        if (!$settings->verifyClosingPassword($request->access_password)) {
            return back()->withErrors(['access_password' => 'Access password galat hai.']);
        }

        $request->session()->put('period_sensitive_unlocked', true);

        $intended = $request->session()->pull('period_access_intended', route('period.closing.index'));

        return redirect()->to($intended)
            ->with('success', 'Access verified. Aap ab Period Closing / Archive pages use kar sakte hain.');
    }

    public function lockAccess(Request $request)
    {
        $request->session()->forget(['period_sensitive_unlocked', 'period_access_intended']);

        return redirect()->route('period.access.unlock')
            ->with('success', 'Period pages lock ho gayi hain. Dobara password chahiye hoga.');
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'closing_password' => 'required|string|min:4|confirmed',
            'viewer_name' => 'required|string|max:255',
            'viewer_email' => 'required|email|max:255',
            'viewer_password' => 'required|string|min:4|confirmed',
        ]);

        $settings = PeriodClosingSetting::instance();
        $settings->closing_password = Hash::make($request->closing_password);
        $settings->updated_by = Auth::id();

        $viewerRole = Role::firstOrCreate(['name' => 'period_viewer']);
        Permission::firstOrCreate(['name' => 'Closed Period Archive']);
        $viewerRole->syncPermissions(['Closed Period Archive']);

        $viewer = $settings->viewer_user_id
            ? User::find($settings->viewer_user_id)
            : null;

        if ($viewer) {
            $viewer->update([
                'name' => $request->viewer_name,
                'email' => $request->viewer_email,
                'password' => Hash::make($request->viewer_password),
                'usertype' => 'user',
            ]);
        } else {
            $viewer = User::create([
                'name' => $request->viewer_name,
                'email' => $request->viewer_email,
                'password' => Hash::make($request->viewer_password),
                'usertype' => 'user',
            ]);
            $settings->viewer_user_id = $viewer->id;
        }

        if (!$viewer->hasRole('period_viewer')) {
            $viewer->syncRoles(['period_viewer']);
        }

        $settings->save();

        $request->session()->put('period_sensitive_unlocked', true);

        return back()->with('success', 'Closing password aur archive user account save ho gaya.');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'closing_date' => 'required|date|before_or_equal:today',
        ]);

        $summary = $this->closingService->getSummary(Carbon::parse($request->closing_date));

        return response()->json($summary);
    }

    public function close(Request $request)
    {
        $request->validate([
            'closing_date' => 'required|date|before_or_equal:today',
            'closing_password' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $settings = PeriodClosingSetting::instance();

        if (!$settings->hasClosingPassword() || !$settings->verifyClosingPassword($request->closing_password)) {
            return back()->withErrors(['closing_password' => 'Closing password galat hai.'])->withInput();
        }

        try {
            $period = $this->closingService->closePeriod(
                Carbon::parse($request->closing_date),
                Auth::id(),
                $request->notes
            );

            return back()->with('success', 'Period successfully close ho gaya: ' . $period->name . '. Aap ka sara data safe hai — koi record delete nahi hua.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['closing_date' => $e->getMessage()])->withInput();
        }
    }
}
