<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $table = 'hr_leaves';

    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'deduct_salary',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'deduct_salary' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public static function hasApprovedLeave($employeeId, $date)
    {
        return self::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    public static function getApprovedLeave($employeeId, $date)
    {
        return self::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }

    /**
     * Get how many days of a given leave type have been used (approved) in current year
     */
    public static function getUsedDays(int $employeeId, string $leaveType, $year = null): int
    {
        $year = $year ?? now()->year;

        $leaves = self::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('leave_type', $leaveType)
            ->whereYear('start_date', $year)
            ->get();

        $totalDays = 0;
        foreach ($leaves as $leave) {
            $totalDays += $leave->start_date->diffInDays($leave->end_date) + 1;
        }

        return $totalDays;
    }

    /**
     * Get leave balance (remaining) for an employee for a specific leave type
     */
    public static function getBalance(Employee $employee, string $leaveType): array
    {
        $allocated = match ($leaveType) {
            'Casual' => (int) ($employee->casual_leaves_allocated ?? 0),
            'Sick'   => (int) ($employee->sick_leaves_allocated ?? 0),
            default  => 0,
        };

        $used      = self::getUsedDays($employee->id, $leaveType);
        $remaining = max(0, $allocated - $used);

        return [
            'leave_type' => $leaveType,
            'allocated'  => $allocated,
            'used'       => $used,
            'remaining'  => $remaining,
            'exhausted'  => $remaining <= 0 && $allocated > 0,
        ];
    }

    /**
     * Get full leave summary for an employee (both Casual & Sick)
     */
    public static function getEmployeeLeaveSummary(Employee $employee): array
    {
        $casual = self::getBalance($employee, 'Casual');
        $sick   = self::getBalance($employee, 'Sick');

        return [
            'casual'          => $casual,
            'sick'            => $sick,
            'total_allocated' => $casual['allocated'] + $sick['allocated'],
            'total_used'      => $casual['used'] + $sick['used'],
            'total_remaining' => $casual['remaining'] + $sick['remaining'],
        ];
    }

    /**
     * Should we force salary deduction for this leave request?
     * True when the employee has exhausted their quota.
     */
    public static function shouldForceDeduction(Employee $employee, string $leaveType, int $newDays = 1): bool
    {
        if (! in_array($leaveType, ['Casual', 'Sick'])) {
            return true; // Annual/Other always deducts
        }

        $balance = self::getBalance($employee, $leaveType);

        return $balance['remaining'] < $newDays;
    }

    public static function calculateDeductibleDays($startDate, $endDate, $employeeId)
    {
        $employee = Employee::find($employeeId);
        if (! $employee) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($startDate);
        $end   = \Carbon\Carbon::parse($endDate);
        $days  = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $isWeeklyOff = $employee->weekly_off && strtolower($employee->weekly_off) === strtolower($date->format('l'));
            $isHoliday   = \App\Models\Hr\Holiday::getHoliday($date->format('Y-m-d'), $employeeId);

            if (! $isWeeklyOff && ! $isHoliday) {
                $days++;
            }
        }

        return $days;
    }
}
