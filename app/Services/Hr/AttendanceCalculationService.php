<?php

namespace App\Services\Hr;

use Carbon\Carbon;
use App\Models\Hr\Employee;
use App\Models\Hr\Attendance;

class AttendanceCalculationService
{
    /**
     * Calculate working hours between clock in and clock out
     *
     * @param string $clockIn
     * @param string $clockOut
     * @return float Hours worked
     */
    public function calculateWorkingHours($clockIn, $clockOut)
    {
        if (!$clockIn || !$clockOut) {
            return 0;
        }

        $start = Carbon::parse($clockIn);
        $end = Carbon::parse($clockOut);

        // Handle overnight shifts
        if ($end->lessThan($start)) {
            $end->addDay();
        }

        return $start->diffInHours($end, true); // true for float result
    }

    /**
     * Calculate working minutes between clock in and clock out
     *
     * @param string $clockIn
     * @param string $clockOut
     * @return int Minutes worked
     */
    public function calculateWorkingMinutes($clockIn, $clockOut)
    {
        if (!$clockIn || !$clockOut) {
            return 0;
        }

        $start = Carbon::parse($clockIn);
        $end = Carbon::parse($clockOut);

        // Handle overnight shifts
        if ($end->lessThan($start)) {
            $end->addDay();
        }

        return $start->diffInMinutes($end);
    }

    /**
     * Calculate late minutes based on shift start time
     *
     * @param string $clockIn
     * @param string $shiftStart
     * @param int $graceMinutes
     * @return int Late minutes (0 if not late)
     */
    public function calculateLateMinutes($clockIn, $shiftStart, $graceMinutes = 0)
    {
        if (!$clockIn || !$shiftStart) {
            return 0;
        }

        $clockInTime = Carbon::parse($clockIn);
        $shiftStartTime = Carbon::parse($clockInTime->toDateString() . ' ' . $shiftStart);

        // Add grace period
        $shiftStartTime->addMinutes($graceMinutes);

        // If clocked in after shift start + grace
        if ($clockInTime->greaterThan($shiftStartTime)) {
            return $clockInTime->diffInMinutes($shiftStartTime);
        }

        return 0;
    }

    /**
     * Calculate early departure minutes
     *
     * @param string $clockOut
     * @param string $shiftEnd
     * @return int Early departure minutes (0 if not early)
     */
    public function calculateEarlyDepartureMinutes($clockOut, $shiftEnd)
    {
        if (!$clockOut || !$shiftEnd) {
            return 0;
        }

        $clockOutTime = Carbon::parse($clockOut);
        $shiftEndTime = Carbon::parse($clockOutTime->toDateString() . ' ' . $shiftEnd);

        // Handle overnight shifts
        if ($shiftEndTime->lessThan(Carbon::parse($clockOutTime->toDateString() . ' ' . $shiftEnd))) {
            $shiftEndTime->addDay();
        }

        // If clocked out before shift end
        if ($clockOutTime->lessThan($shiftEndTime)) {
            return $shiftEndTime->diffInMinutes($clockOutTime);
        }

        return 0;
    }

    /**
     * Calculate overtime hours
     *
     * @param float $workingHours
     * @param float $standardHours
     * @return float Overtime hours (0 if no overtime)
     */
    public function calculateOvertime($workingHours, $standardHours = 8.0)
    {
        if ($workingHours > $standardHours) {
            return $workingHours - $standardHours;
        }

        return 0;
    }

    /**
     * Check if shift is overnight (crosses midnight)
     *
     * @param string $clockIn
     * @param string $clockOut
     * @return bool
     */
    public function isOvernightShift($clockIn, $clockOut)
    {
        if (!$clockIn || !$clockOut) {
            return false;
        }

        $start = Carbon::parse($clockIn);
        $end = Carbon::parse($clockOut);

        return $end->lessThan($start);
    }

    /**
     * Calculate attendance status based on hours worked
     *
     * @param float $workingHours
     * @param float $minimumHours
     * @return string present|half_day|absent
     */
    public function calculateAttendanceStatus($workingHours, $minimumHours = 4.0)
    {
        if ($workingHours >= 8.0) {
            return 'present';
        } elseif ($workingHours >= $minimumHours) {
            return 'half_day';
        } else {
            return 'absent';
        }
    }

    /**
     * Get shift timings for employee
     *
     * @param Employee $employee
     * @return array ['start' => '09:00:00', 'end' => '18:00:00', 'grace' => 15]
     */
    public function getEmployeeShiftTimings(Employee $employee)
    {
        return [
            'start' => $employee->getStartTime(),
            'end' => $employee->getEndTime(),
            'grace' => $employee->getGraceMinutes(),
        ];
    }

    /**
     * Format hours to human-readable format
     *
     * @param float $hours
     * @return string "8h 30m"
     */
    public function formatHours($hours)
    {
        $h = floor($hours);
        $m = round(($hours - $h) * 60);

        return "{$h}h {$m}m";
    }

    /**
     * Calculate break time if applicable
     *
     * @param string $clockIn
     * @param string $clockOut
     * @param int $breakMinutes Default break time
     * @return int Break minutes
     */
    public function calculateBreakTime($clockIn, $clockOut, $breakMinutes = 60)
    {
        $workingHours = $this->calculateWorkingHours($clockIn, $clockOut);

        // If worked more than 6 hours, deduct break time
        if ($workingHours > 6) {
            return $breakMinutes;
        }

        return 0;
    }

    /**
     * Calculate net working hours (after break deduction)
     *
     * @param string $clockIn
     * @param string $clockOut
     * @param int $breakMinutes
     * @return float Net working hours
     */
    public function calculateNetWorkingHours($clockIn, $clockOut, $breakMinutes = 60)
    {
        $totalMinutes = $this->calculateWorkingMinutes($clockIn, $clockOut);
        $breakTime = $this->calculateBreakTime($clockIn, $clockOut, $breakMinutes);

        $netMinutes = $totalMinutes - $breakTime;

        return round($netMinutes / 60, 2);
    }

    /**
     * Validate attendance data
     *
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateAttendanceData($data)
    {
        $errors = [];

        if (empty($data['employee_id'])) {
            $errors[] = 'Employee ID is required';
        }

        if (empty($data['date'])) {
            $errors[] = 'Date is required';
        }

        if (empty($data['clock_in'])) {
            $errors[] = 'Clock in time is required';
        }

        if (!empty($data['clock_out'])) {
            $clockIn = Carbon::parse($data['clock_in']);
            $clockOut = Carbon::parse($data['clock_out']);

            // Check if clock out is before clock in (and not overnight)
            if ($clockOut->lessThan($clockIn) && !$this->isOvernightShift($data['clock_in'], $data['clock_out'])) {
                $errors[] = 'Clock out time cannot be before clock in time';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Calculate attendance summary for a period
     *
     * @param Employee $employee
     * @param string $startDate
     * @param string $endDate
     * @return array Summary statistics
     */
    public function calculateAttendanceSummary(Employee $employee, $startDate, $endDate)
    {
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalDays = $attendances->count();
        $presentDays = $attendances->where('status', 'present')->count();
        $halfDays = $attendances->where('status', 'half_day')->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $lateDays = $attendances->where('late_minutes', '>', 0)->count();

        $totalHours = $attendances->sum('working_hours');
        $totalOvertime = $attendances->sum('overtime_hours');
        $totalLateMinutes = $attendances->sum('late_minutes');

        return [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'half_days' => $halfDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'total_hours' => round($totalHours, 2),
            'total_overtime' => round($totalOvertime, 2),
            'total_late_minutes' => $totalLateMinutes,
            'average_hours_per_day' => $totalDays > 0 ? round($totalHours / $totalDays, 2) : 0,
            'attendance_percentage' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0,
        ];
    }
}
