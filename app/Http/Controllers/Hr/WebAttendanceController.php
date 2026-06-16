<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Hr\Attendance;

class WebAttendanceController extends Controller
{
    public function checkIn(Request $request)
    {
        $user = auth()->user();
        $employee = $user->employee;

        // IP Restriction
        $allowedIps = ['103.75.244.56', '127.0.0.1', '::1'];
        $clientIp = $request->ip();

        if (!in_array($clientIp, $allowedIps)) {
            return back()->with('sweet_error', 'Aap office ke ilawa aur kahin se bhi attendance place nahi kar sakte.');
        }

        if (!$employee) {
            return back()->with('error', 'You do not have an associated employee profile.');
        }

        $today = Carbon::today();
        $attendance = $employee->getTodayAttendance();

        if ($attendance) {
            return back()->with('error', 'You have already checked in today.');
        }

        $now = Carbon::now();
        $shift = $employee->shift;
        
        $isLate = false;
        $lateMinutes = 0;

        if ($shift) {
            $isLate = $shift->isLate($now);
            $lateMinutes = $shift->getLateMinutes($now);
        }

        Attendance::create([
            'employee_id' => $employee->id,
            'date' => $today->format('Y-m-d'),
            'clock_in' => $now->format('H:i:s'),
            'check_in_time' => $now,
            'is_late' => $isLate,
            'late_minutes' => $lateMinutes,
            'status' => 'present',
        ]);

        return back()->with('success', 'Checked in successfully.' . ($isLate ? " You were late by {$lateMinutes} minutes." : ""));
    }

    public function checkOut(Request $request)
    {
        $user = auth()->user();
        $employee = $user->employee;

        // IP Restriction
        $allowedIps = ['103.75.244.56', '127.0.0.1', '::1'];
        $clientIp = $request->ip();

        if (!in_array($clientIp, $allowedIps)) {
            return back()->with('sweet_error', 'Aap office ke ilawa aur kahin se bhi attendance place nahi kar sakte.');
        }

        if (!$employee) {
            return back()->with('error', 'You do not have an associated employee profile.');
        }

        $attendance = $employee->getTodayAttendance();

        if (!$attendance) {
            return back()->with('error', 'You need to check in first.');
        }

        $now = Carbon::now();
        $checkInTime = Carbon::parse($attendance->clock_in);
        $punchGap = $employee->punch_gap_minutes ?? 0;

        if ($checkInTime->copy()->addMinutes($punchGap)->gt($now)) {
            $remaining = $checkInTime->copy()->addMinutes($punchGap)->diffInMinutes($now);
            return back()->with('error', "You cannot check out yet. Please wait {$remaining} more minute(s).");
        }

        if ($attendance->clock_out) {
            return back()->with('error', 'You have already checked out today.');
        }

        $now = Carbon::now();
        $shift = $employee->shift;
        
        $isEarlyLeave = false;
        $earlyLeaveMinutes = 0;

        if ($shift) {
            $isEarlyLeave = $shift->isEarlyLeave($now);
            if ($isEarlyLeave) {
                $shiftEnd = Carbon::parse($shift->end_time);
                $earlyLeaveMinutes = $now->diffInMinutes($shiftEnd);
            }
        }

        $checkInTime = Carbon::parse($attendance->clock_in);
        $totalHours = $now->diffInHours($checkInTime);

        $attendance->update([
            'clock_out' => $now->format('H:i:s'),
            'check_out_time' => $now,
            'is_early_leave' => $isEarlyLeave,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'total_hours' => $totalHours,
        ]);

        return back()->with('success', 'Checked out successfully. Have a good rest of your day!');
    }
}
