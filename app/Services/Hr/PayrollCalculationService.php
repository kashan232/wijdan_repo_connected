<?php

namespace App\Services\Hr;

use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\Payroll;
use App\Models\Hr\PayrollDetail;
use App\Models\Hr\SalaryStructure;
use App\Models\Hr\Holiday;
use App\Models\Hr\Leave;
use Carbon\Carbon;

class PayrollCalculationService
{
    /**
     * Get the effective salary structure for an employee
     * Uses the direct salaryStructure relationship
     */
    public function getEffectiveSalaryStructure(Employee $employee): ?SalaryStructure
    {
        // Use the activeSalaryStructure relation defined in Employee model
        $structure = $employee->activeSalaryStructure;
        
        if (!$structure) {
            // Fallback for standalone legacy structures if needed
            $structure = SalaryStructure::where('employee_id', $employee->id)->latest('id')->first();
        }

        return $structure;
    }

    /**
     * Calculate allowances with support for both fixed and percentage types
     */
    private function calculateAllowances(SalaryStructure $structure): array
    {
        $allowances = collect($structure->allowances ?? [])->filter(function ($item) {
            return ! empty($item['is_active']) && $item['is_active'] !== 'false';
        });

        $baseSalary = $structure->base_salary ?? 0;
        $totalAllowances = 0;
        $details = [];

        foreach ($allowances as $allowance) {
            $name = $allowance['name'] ?? 'Allowance';
            $type = $allowance['type'] ?? 'fixed'; // 'fixed' or 'percentage'
            $value = floatval($allowance['amount'] ?? 0);

            if ($type === 'percentage') {
                // Calculate based on base salary
                $amount = ($baseSalary * $value) / 100;
                $description = "{$value}% of base salary";
            } else {
                $amount = $value;
                $description = $allowance['description'] ?? null;
            }

            $totalAllowances += $amount;
            $details[] = [
                'name' => $name,
                'amount' => $amount,
                'calculation_type' => $type,
                'description' => $description,
            ];
        }

        return [
            'total' => $totalAllowances,
            'details' => $details,
        ];
    }

    /**
     * Calculate fixed deductions from salary structure
     */
    private function calculateFixedDeductions(SalaryStructure $structure): array
    {
        $deductions = collect($structure->deductions ?? [])->filter(function ($item) {
            return ! empty($item['is_active']) && $item['is_active'] !== 'false';
        });

        $baseSalary = $structure->base_salary ?? 0;
        $totalDeductions = 0;
        $details = [];

        foreach ($deductions as $deduction) {
            $name = $deduction['name'] ?? 'Deduction';
            $type = $deduction['type'] ?? 'fixed';
            $value = floatval($deduction['amount'] ?? 0);

            if ($type === 'percentage') {
                $amount = ($baseSalary * $value) / 100;
                $description = "{$value}% of base salary";
            } else {
                $amount = $value;
                $description = $deduction['description'] ?? null;
            }

            $totalDeductions += $amount;
            $details[] = [
                'name' => $name,
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
            ];
        }

        return [
            'total' => $totalDeductions,
            'details' => $details,
        ];
    }

    /**
     * Calculate attendance-based deductions for monthly payroll
     */
    public function calculateMonthlyAttendanceDeductions(
        Employee $employee,
        string $month,
        SalaryStructure $structure
    ): array {
        $startDate = Carbon::parse($month.'-01')->startOfMonth();
        $endDate = Carbon::parse($month.'-01')->endOfMonth();

        $policy = $structure->attendance_deduction_policy ?? [];
        
        // Absent deduction calculation logic
        if (($structure->absent_deduction_type ?? 'manual') === 'automatic' && $structure->base_salary > 0) {
            // Calculate working days (excluding weekends/holidays)
            $totalWorkingDays = $this->getWorkingDaysInRange($startDate, $endDate, $employee);
            if ($totalWorkingDays > 0) {
                $perDayDeduction = round($structure->base_salary / $totalWorkingDays, 2);
            } else {
                $perDayDeduction = 0;
            }
        } else {
            $perDayDeduction = $structure->leave_salary_per_day ?? 0;
            $totalWorkingDays = $this->getWorkingDaysInRange($startDate, $endDate, $employee);
        }
        
        $daysInMonth = $startDate->daysInMonth;

        // Fetch attendance records for the period
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        // Attendance stats & Absence detection
        $attendancesByDate = $attendances->keyBy('date');
        $daysPresent = 0;
        $daysAbsent = 0;
        $daysPaidLeave = 0;
        $absentRecords = [];
        $paidLeaveRecords = [];

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateStr = $current->format('Y-m-d');
            $att = $attendancesByDate->get($dateStr);

            // Check day status (supports multiple off days like "Monday,Tuesday")
            $offDays = $employee->weekly_off ? array_map('trim', explode(',', strtolower($employee->weekly_off))) : ['sunday'];
            $isWeeklyOff = in_array(strtolower($current->format('l')), $offDays);

            $isHoliday = Holiday::isHoliday($dateStr, $employee->id);

            // Check for approved leave AND whether it should deduct salary
            $approvedLeave        = Leave::getApprovedLeave($employee->id, $dateStr);
            $isLeave              = !is_null($approvedLeave);
            $leaveDeductsSalary   = $isLeave && $approvedLeave->deduct_salary;

            if ($att) {
                if (in_array(strtolower($att->status), ['present', 'late'])) {
                    $daysPresent++;
                } elseif (strtolower($att->status) === 'absent') {
                    // If the status is 'absent' but it's a weekly off or holiday, don't count as absent
                    if ($isWeeklyOff || $isHoliday) {
                        // Skip
                    } elseif ($isLeave && !$leaveDeductsSalary) {
                        // No deduction — leave with full pay
                        $daysPaidLeave++;
                        $paidLeaveRecords[] = [
                            'date' => $current->format('d M Y'),
                            'day' => $current->format('l'),
                            'type' => $approvedLeave->leave_type,
                        ];
                    } else {
                        $daysAbsent++;
                        $absentRecords[] = [
                            'date'      => $current->format('d M Y'),
                            'day'       => $current->format('l'),
                            'deduction' => $perDayDeduction,
                        ];
                    }
                } elseif (strtolower($att->status) === 'leave') {
                    // Attendance record marked as "leave" — check if should deduct
                    if ($leaveDeductsSalary && !$isWeeklyOff && !$isHoliday) {
                        $daysAbsent++;
                        $absentRecords[] = [
                            'date'      => $current->format('d M Y'),
                            'day'       => $current->format('l'),
                            'deduction' => $perDayDeduction,
                            'note'      => 'Leave (Unpaid)',
                        ];
                    } else {
                        // Leave with no deduction — Paid Leave (or it's an off day)
                        if (!$isWeeklyOff && !$isHoliday) {
                            $daysPaidLeave++;
                            $paidLeaveRecords[] = [
                                'date' => $current->format('d M Y'),
                                'day' => $current->format('l'),
                                'type' => $approvedLeave->leave_type ?? 'Leave',
                            ];
                        }
                    }
                }
            } else {
                // No attendance record for this day
                if ($isLeave && !$leaveDeductsSalary && !$isWeeklyOff && !$isHoliday) {
                    // Employee is on paid leave — no deduction
                    $daysPaidLeave++;
                    $paidLeaveRecords[] = [
                        'date' => $current->format('d M Y'),
                        'day' => $current->format('l'),
                        'type' => $approvedLeave->leave_type,
                    ];
                } elseif ($isLeave && $leaveDeductsSalary && !$isWeeklyOff && !$isHoliday) {
                    // Leave quota exhausted — count as absent (deduction applies)
                    $daysAbsent++;
                    $absentRecords[] = [
                        'date'      => $current->format('d M Y'),
                        'day'       => $current->format('l'),
                        'deduction' => $perDayDeduction,
                        'note'      => 'Leave (Unpaid)',
                    ];
                } elseif (!$isWeeklyOff && !$isHoliday) {
                    // Regular absent (no leave, no holiday)
                    $daysAbsent++;
                    $absentRecords[] = [
                        'date'      => $current->format('d M Y'),
                        'day'       => $current->format('l'),
                        'deduction' => $perDayDeduction,
                    ];
                }
            }
            $current->addDay();
        }

        $lateCheckIns = $attendances->where('is_late', true)->count();
        $earlyCheckOuts = $attendances->where('is_early_leave', true)->count();
        $totalLateMinutes = $attendances->sum('late_minutes');
        $totalEarlyMinutes = $attendances->sum('early_leave_minutes');

        // If no attendance data exists (at least one punch)
        $hasAttendanceData = $attendances->count() > 0;

        // Calculate deductions
        $absenceDeduction = 0;
        $lateDeduction = 0;
        $earlyDeduction = 0;
        $deductionDetails = [];

        // Absence deduction (absent days × per-day deduction)
        if ($daysAbsent > 0 && $perDayDeduction > 0) {
            $absenceDeduction = $daysAbsent * $perDayDeduction;
            $deductionDetails[] = [
                'name' => "Absence Deduction ({$daysAbsent} days)",
                'amount' => $absenceDeduction,
                'description' => "{$daysAbsent} absent days × Rs. {$perDayDeduction}",
            ];
        }

        // Late check-in penalty
        if ($lateCheckIns > 0) {
            $latePenalty = $policy['late_penalty_per_instance'] ?? 0;
            if ($latePenalty > 0) {
                $lateDeduction = $lateCheckIns * $latePenalty;
                $deductionDetails[] = [
                    'name' => "Late Check-in Penalty ({$lateCheckIns} times)",
                    'amount' => $lateDeduction,
                    'description' => "{$lateCheckIns} late check-ins × Rs. {$latePenalty}",
                ];
            }

            // Alternative: Use late rules if defined
            if (empty($latePenalty) && ! empty($policy['late_rules'])) {
                foreach ($attendances->where('is_late', true) as $att) {
                    $deduction = $this->calculateLateDeduction(
                        $att->late_minutes ?? 0,
                        $policy['late_rules'],
                        $structure->base_salary / $daysInMonth // Per-day rate based on actual month days
                    );
                    $lateDeduction += $deduction;
                }
                if ($lateDeduction > 0) {
                    $deductionDetails[] = [
                        'name' => "Late Check-in Penalties ({$lateCheckIns} times, {$totalLateMinutes} min)",
                        'amount' => $lateDeduction,
                        'description' => 'Calculated based on late rules',
                    ];
                }
            }
        }

        // Early check-out penalty
        if ($earlyCheckOuts > 0) {
            $earlyPenalty = $policy['early_penalty_per_instance'] ?? 0;
            if ($earlyPenalty > 0) {
                $earlyDeduction = $earlyCheckOuts * $earlyPenalty;
                $deductionDetails[] = [
                    'name' => "Early Check-out Penalty ({$earlyCheckOuts} times)",
                    'amount' => $earlyDeduction,
                    'description' => "{$earlyCheckOuts} early check-outs × Rs. {$earlyPenalty}",
                ];
            }

            // Alternative: Use early rules if defined
            if (empty($earlyPenalty) && ! empty($policy['early_rules'])) {
                foreach ($attendances->where('is_early_leave', true) as $att) { // Fixed: is_early_leave
                    $deduction = $this->calculateEarlyDeduction(
                        $att->early_leave_minutes ?? 0, // Fixed: early_leave_minutes
                        $policy['early_rules'],
                        $structure->base_salary / $daysInMonth
                    );
                    $earlyDeduction += $deduction;
                }
                if ($earlyDeduction > 0) {
                    $deductionDetails[] = [
                        'name' => "Early Check-out Penalties ({$earlyCheckOuts} times, {$totalEarlyMinutes} min)",
                        'amount' => $earlyDeduction,
                        'description' => 'Calculated based on early rules',
                    ];
                }
            }
        }

        $totalDeduction = $absenceDeduction + $lateDeduction + $earlyDeduction;

        // Build detailed records for late and early
        $latePenalty = $policy['late_penalty_per_instance'] ?? 0;
        $lateRecords = $attendances->where('is_late', true)->map(function ($att) use ($latePenalty) {
            return [
                'date' => Carbon::parse($att->date)->format('d M Y'),
                'day' => Carbon::parse($att->date)->format('l'),
                'check_in' => $att->clock_in ? Carbon::parse($att->clock_in)->format('h:i A') : 'N/A',
                'late_minutes' => $att->late_minutes ?? 0,
                'deduction' => $latePenalty,
            ];
        })->values()->toArray();

        $earlyPenalty = $policy['early_penalty_per_instance'] ?? 0;
        $earlyRecords = $attendances->where('is_early_leave', true)->map(function ($att) use ($earlyPenalty) {
            return [
                'date' => Carbon::parse($att->date)->format('d M Y'),
                'day' => Carbon::parse($att->date)->format('l'),
                'check_out' => $att->clock_out ? Carbon::parse($att->clock_out)->format('h:i A') : 'N/A',
                'early_minutes' => $att->early_leave_minutes ?? 0,
                'deduction' => $earlyPenalty,
            ];
        })->values()->toArray();

        return [
            'total' => $totalDeduction,
            'details' => $deductionDetails,
            'breakdown' => [
                'has_data' => $hasAttendanceData,
                'total_working_days' => $totalWorkingDays,
                'days_present' => $daysPresent,
                'days_absent' => $daysAbsent,
                'days_paid_leave' => $daysPaidLeave,
                'late_check_ins' => $lateCheckIns,
                'early_check_outs' => $earlyCheckOuts,
                'total_late_minutes' => $totalLateMinutes,
                'total_early_minutes' => $totalEarlyMinutes,
                'absence_deduction' => $absenceDeduction,
                'late_deduction' => $lateDeduction,
                'early_deduction' => $earlyDeduction,
                'absent_records' => $absentRecords,
                'paid_leave_records' => $paidLeaveRecords,
                'late_records' => $lateRecords,
                'early_records' => $earlyRecords,
            ],
        ];
    }

    /**
     * Calculate working days in a date range (based on employee's off days)
     */
    private function getWorkingDaysInRange(Carbon $startDate, Carbon $endDate, Employee $employee = null): int
    {
        $workingDays = 0;
        $current = $startDate->copy();

        // Get employee off days (default to Sunday if not set)
        $offDays = ['sunday'];
        if ($employee && $employee->weekly_off) {
            $offDays = array_map('trim', explode(',', strtolower($employee->weekly_off)));
        }

        while ($current->lte($endDate)) {
            $dayName = strtolower($current->format('l'));
            $dateStr = $current->format('Y-m-d');
            
            $isWeeklyOff = in_array($dayName, $offDays);
            $isHoliday = Holiday::isHoliday($dateStr, $employee ? $employee->id : null);
            
            if (!$isWeeklyOff && !$isHoliday) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Calculate monthly payroll for a salaried employee
     */
    public function calculateMonthlyPayroll(Employee $employee, string $month): array
    {
        // Get effective salary structure (handles custom assignments)
        $structure = $this->getEffectiveSalaryStructure($employee);

        if (! $structure) {
            throw new \Exception('Employee has no salary structure assigned.');
        }

        $baseSalary = $structure->base_salary ?? 0;

        // Calculate allowances (supports fixed and percentage types)
        $allowanceData = $this->calculateAllowances($structure);
        $activeAllowances = $allowanceData['total'];
        $allowanceDetails = $allowanceData['details'];

        // Calculate fixed deductions
        $fixedDeductionData = $this->calculateFixedDeductions($structure);
        $activeFixedDeductions = $fixedDeductionData['total'];
        $fixedDeductionDetails = $fixedDeductionData['details'];

        // Calculate attendance-based deductions
        $attendanceDeductionData = $this->calculateMonthlyAttendanceDeductions($employee, $month, $structure);
        $attendanceDeductions = $attendanceDeductionData['total'];
        $attendanceBreakdown = $attendanceDeductionData['breakdown'];

        // Add attendance deduction details to deduction details
        $allDeductionDetails = array_merge($fixedDeductionDetails, $attendanceDeductionData['details']);

        // Calculate commission if applicable
        $commissionData = ['total' => 0, 'description' => 'Not applicable', 'commission_details' => []];
        if (in_array($structure->salary_type, ['commission', 'both'])) {
            $commissionData = $this->calculateSalesCommission($employee, $month, $structure);
        }
        $commission = $commissionData['total'];

        // Map commission details to satisfy savePayrollDetails format
        $mappedCommissionDetails = collect($commissionData['commission_details'] ?? [])->map(function($c) {
            return [
                'name' => "Commission (Sale #{$c['invoice_no']})",
                'amount' => $c['commission'],
                'description' => isset($c['json_meta']) ? $c['json_meta'] : $c['description'],
                'type' => 'commission'
            ];
        })->toArray();

        // Calculate gross salary (includes commission)
        $grossSalary = $baseSalary + $activeAllowances + $commission;

        // Calculate total deductions
        $totalDeductions = $activeFixedDeductions + $attendanceDeductions;

        // Calculate net salary (prevent negative)
        $netSalary = max(0, $grossSalary - $totalDeductions);

        return [
            'payroll_type' => 'monthly',
            'month' => $month,
            'basic_salary' => $baseSalary,
            'gross_salary' => $grossSalary,
            'allowances' => $activeAllowances,
            'deductions' => $activeFixedDeductions,
            'attendance_deductions' => $attendanceDeductions,
            'manual_deductions' => 0,
            'manual_allowances' => 0,
            'carried_forward_deduction' => 0,
            'bonuses' => 0,
            'commission' => $commission,
            'net_salary' => $netSalary,
            'auto_generated' => true,
            'status' => 'generated',
            'allowance_details' => array_merge($allowanceDetails, $mappedCommissionDetails),
            'deduction_details' => $allDeductionDetails,
            'attendance_breakdown' => $attendanceBreakdown,
            'commission_details' => $commissionData['commission_details'] ?? [],
        ];
    }

    /**
     * Calculate daily payroll for a daily wage employee
     */
    public function calculateDailyPayroll(Employee $employee, Attendance $attendance): array
    {
        // Get effective salary structure
        $structure = $this->getEffectiveSalaryStructure($employee);

        if (! $structure || ! $structure->use_daily_wages) {
            throw new \Exception('Employee is not configured for daily wages.');
        }

        $dailyRate = $structure->daily_wages;
        $policy = $structure->attendance_deduction_policy ?? [];
        $carryForward = $structure->carry_forward_deductions ?? false;

        // Start with the daily rate
        $dayEarning = $dailyRate;
        $dayDeduction = 0;
        $deductionDetails = [];

        // Apply late check-in deductions
        if (($attendance->late_minutes ?? 0) > 0) {
            $lateDeduction = $this->calculateLateDeduction(
                $attendance->late_minutes,
                $policy['late_rules'] ?? [],
                $dailyRate
            );

            if ($lateDeduction > 0) {
                $dayDeduction += $lateDeduction;
                $deductionDetails[] = [
                    'name' => 'Late Check-in ('.$attendance->late_minutes.' min)',
                    'amount' => $lateDeduction,
                    'description' => 'Late arrival deduction for '.Carbon::parse($attendance->date)->format('M d, Y'),
                ];
            }
        }

        // Apply early check-out deductions
        if (($attendance->early_leave_minutes ?? 0) > 0) {
            $earlyDeduction = $this->calculateEarlyDeduction(
                $attendance->early_leave_minutes,
                $policy['early_rules'] ?? [],
                $dailyRate
            );

            if ($earlyDeduction > 0) {
                $dayDeduction += $earlyDeduction;
                $deductionDetails[] = [
                    'name' => 'Early Leave ('.$attendance->early_leave_minutes.' min)',
                    'amount' => $earlyDeduction,
                    'description' => 'Early departure deduction for '.Carbon::parse($attendance->date)->format('M d, Y'),
                ];
            }
        }

        // Handle carried forward deductions from previous day
        $carriedForwardDeduction = $employee->pending_deductions ?? 0;
        $totalDeductions = $dayDeduction + $carriedForwardDeduction;

        // Determine net payable and remaining carry-forward
        $netSalary = 0;
        $newPendingDeductions = 0;

        if ($totalDeductions <= $dayEarning) {
            // Can pay full amount after deductions
            $netSalary = $dayEarning - $totalDeductions;
            $newPendingDeductions = 0;
        } else {
            // Deductions exceed daily earning
            if ($carryForward) {
                // Carry forward is allowed
                $netSalary = 0;
                $newPendingDeductions = $totalDeductions - $dayEarning;
            } else {
                // Carry forward not allowed - cap deductions at daily earning
                $netSalary = 0;
                $newPendingDeductions = 0;
                $totalDeductions = $dayEarning;
            }
        }

        // Prevent negative salary
        $netSalary = max(0, $netSalary);

        return [
            'payroll_type' => 'daily',
            'month' => Carbon::parse($attendance->date)->format('Y-m-d'), // Store full date for daily
            'basic_salary' => $dailyRate,
            'gross_salary' => $dailyRate,
            'allowances' => 0,
            'deductions' => 0, // Fixed deductions don't apply to daily
            'attendance_deductions' => $dayDeduction,
            'manual_deductions' => 0,
            'manual_allowances' => 0,
            'carried_forward_deduction' => $carriedForwardDeduction,
            'bonuses' => 0,
            'net_salary' => $netSalary,
            'auto_generated' => true,
            'status' => 'generated',
            'carried_forward_to_next' => $newPendingDeductions,
            'new_pending_deductions' => $newPendingDeductions,
            'deduction_details' => $deductionDetails,
            'allowance_details' => [],
            'attendance_breakdown' => [
                'has_data' => true,
                'date' => $attendance->date,
                'status' => $attendance->status,
                'check_in' => $attendance->clock_in,
                'check_out' => $attendance->clock_out,
                'is_late' => $attendance->is_late ?? false,
                'is_early_out' => $attendance->is_early_leave ?? false,
                'late_minutes' => $attendance->late_minutes ?? 0,
                'early_checkout_minutes' => $attendance->early_leave_minutes ?? 0,
                'total_deduction' => $dayDeduction,
            ],
        ];
    }

    /**
     * Calculate late check-in deduction
     */
    private function calculateLateDeduction(int $lateMinutes, array $rules, float $dailyRate): float
    {
        if (empty($rules)) {
            return 0;
        }

        foreach ($rules as $rule) {
            $min = $rule['min_minutes'] ?? 0;
            $max = $rule['max_minutes'] ?? null;

            if ($lateMinutes >= $min && (is_null($max) || $lateMinutes <= $max)) {
                $amount = $rule['amount'] ?? 0;
                $type = $rule['type'] ?? 'fixed';

                if ($type === 'percentage') {
                    return ($dailyRate * $amount) / 100;
                } else {
                    return floatval($amount);
                }
            }
        }

        return 0;
    }

    /**
     * Calculate early check-out deduction
     */
    private function calculateEarlyDeduction(int $earlyMinutes, array $rules, float $dailyRate): float
    {
        if (empty($rules)) {
            return 0;
        }

        foreach ($rules as $rule) {
            $min = $rule['min_minutes'] ?? 0;
            $max = $rule['max_minutes'] ?? null;

            if ($earlyMinutes >= $min && (is_null($max) || $earlyMinutes <= $max)) {
                $amount = $rule['amount'] ?? 0;
                $type = $rule['type'] ?? 'fixed';

                if ($type === 'percentage') {
                    return ($dailyRate * $amount) / 100;
                } else {
                    return floatval($amount);
                }
            }
        }

        return 0;
    }

    /**
     * Save payroll details (allowances and deductions breakdown)
     */
    public function savePayrollDetails(Payroll $payroll, array $allowanceDetails, array $deductionDetails): void
    {
        // Save allowances
        foreach ($allowanceDetails as $detail) {
            PayrollDetail::create([
                'payroll_id' => $payroll->id,
                'type' => $detail['type'] ?? 'allowance',
                'name' => $detail['name'],
                'amount' => $detail['amount'],
                'description' => $detail['description'] ?? null,
            ]);
        }

        // Save deductions
        foreach ($deductionDetails as $detail) {
            PayrollDetail::create([
                'payroll_id' => $payroll->id,
                'type' => 'deduction',
                'name' => $detail['name'],
                'amount' => $detail['amount'],
                'description' => $detail['description'] ?? null,
            ]);
        }
    }

    /**
     * Update employee's pending deductions
     */
    public function updatePendingDeductions(Employee $employee, float $newPendingDeductions): void
    {
        $employee->update([
            'pending_deductions' => $newPendingDeductions,
        ]);
    }

    /**
     * Calculate sales commission for an employee for a given month.
     *
     * Commission Logic:
     * - Commission is ONLY earned when the customer makes a payment against a sale.
     * - If the customer pays 40% of the total sale → employee earns 40% × commission_rate × share_ratio (50%).
     * - This continues until the full commission is earned.
     * - Double-counting is prevented by tracking commission_paid on the sale record.
     *
     * @param Employee        $employee
     * @param string          $month    Format: 'Y-m'
     * @param SalaryStructure $structure
     * @return array
     */
    public function calculateSalesCommission(Employee $employee, string $month, SalaryStructure $structure): array
    {
        // Get all sales assigned to this employee (all time, not just this month)
        // because payments for old sales may arrive later.
        $sales = \App\Models\Sale::where('employee_id', $employee->id)
            ->where('total_net', '>', 0)
            ->get();

        if ($sales->isEmpty()) {
            return [
                'total' => 0,
                'description' => 'No sales assigned to this employee',
                'commission_details' => [],
            ];
        }

        $totalNewCommission = 0;
        $commissionDetails  = [];
        $salesUpdates       = []; // Store updates to apply after payroll save

        foreach ($sales as $sale) {
            $saleTotal            = floatval($sale->total_net);
            $maxCommission        = floatval($sale->total_commission);
            $alreadyPaid          = floatval($sale->commission_paid);

            // If no commission is set on sale, calculate it from the salary structure
            if ($maxCommission <= 0) {
                if ($structure->commission_tiers && count($structure->commission_tiers) > 0) {
                    $maxCommission = $structure->calculateTieredCommission($saleTotal);
                } elseif ($structure->commission_percentage > 0) {
                    $maxCommission = ($saleTotal * $structure->commission_percentage) / 100;
                }
            }

            // If still no commission or fully paid — skip
            if ($maxCommission <= 0 || $alreadyPaid >= $maxCommission) {
                continue;
            }

            // Sum of all payments linked to this specific sale (made up to end of the given month)
            $endOfMonth = \Carbon\Carbon::parse($month . '-01')->endOfMonth()->format('Y-m-d');
            
            $posPaid = max(0, floatval($sale->cash) + floatval($sale->card) - max(0, floatval($sale->change)));
            
            $totalPaymentsOnSale = $posPaid + \App\Models\CustomerPayment::where('sale_id', $sale->id)
                ->where('payment_date', '<=', $endOfMonth)
                ->sum('amount');

            if ($totalPaymentsOnSale <= 0) {
                continue; // No payment yet — no commission
            }

            // Earned commission = (total payments / sale total) × max commission
            $paymentRatio    = min(1, $totalPaymentsOnSale / $saleTotal); // cap at 100%
            $earnedSoFar     = round($paymentRatio * $maxCommission, 2);

            // New commission = earned so far minus what was already paid
            $newCommission = max(0, $earnedSoFar - $alreadyPaid);

            if ($newCommission > 0) {
                $totalNewCommission += $newCommission;
                $commissionDetails[] = [
                    'sale_id'        => $sale->id,
                    'invoice_no'     => $sale->invoice_no,
                    'sale_total'     => $saleTotal,
                    'payment_ratio'  => round($paymentRatio * 100, 1) . '%',
                    'commission'     => $newCommission,
                    'description'    => "Sale #{$sale->invoice_no}: {$saleTotal} total, " . round($paymentRatio * 100, 1) . "% paid → Rs. " . number_format($newCommission, 2) . " commission",
                    'json_meta'      => json_encode([
                        'sale_total' => $saleTotal,
                        'max_commission' => $maxCommission,
                        'customer_paid_total' => $totalPaymentsOnSale,
                        'paid_so_far' => $alreadyPaid,
                        'current_commission' => $newCommission,
                        'remaining_commission' => max(0, $maxCommission - ($alreadyPaid + $newCommission)),
                        'text_desc' => "Sale #{$sale->invoice_no}: {$saleTotal} total, " . round($paymentRatio * 100, 1) . "% paid → Rs. " . number_format($newCommission, 2) . " commission",
                    ]),
                ];
                $salesUpdates[] = [
                    'sale'        => $sale,
                    'new_paid'    => $alreadyPaid + $newCommission,
                ];
            }
        }

        // IMPORTANT: Update commission_paid on sales ONLY after payroll is confirmed to be saved.
        // Store in a static cache so PayrollController can call commitCommission() after saving.
        static::$pendingCommissionUpdates[$employee->id] = $salesUpdates;

        $description = count($commissionDetails) > 0
            ? 'Commission from ' . count($commissionDetails) . ' sale(s): ' . implode('; ', array_column($commissionDetails, 'description'))
            : 'No eligible commission payments this period';

        return [
            'total'              => $totalNewCommission,
            'description'        => $description,
            'commission_details' => $commissionDetails,
        ];
    }

    /**
     * Pending commission updates per employee (keyed by employee ID).
     * These are committed after payroll is successfully saved.
     */
    public static array $pendingCommissionUpdates = [];

    /**
     * Commit commission_paid updates for an employee after payroll is saved.
     */
    public function commitCommissionUpdates(int $employeeId): void
    {
        $updates = static::$pendingCommissionUpdates[$employeeId] ?? [];
        foreach ($updates as $update) {
            $update['sale']->update(['commission_paid' => $update['new_paid']]);
        }
        unset(static::$pendingCommissionUpdates[$employeeId]);
    }
    /**
     * Generate an instant commission payroll record triggered by a customer payment.
     */
    public function generateInstantCommission(\App\Models\CustomerPayment $payment): void
    {
        if (!$payment->sale_id) return;
        
        $sale = \App\Models\Sale::find($payment->sale_id);
        if (!$sale || !$sale->employee_id) return;

        $employee = \App\Models\Hr\Employee::find($sale->employee_id);
        if (!$employee || strtolower($employee->status) !== 'active') return;

        $structure = $this->getEffectiveSalaryStructure($employee);
        if (!$structure) return;
        
        // Ensure this employee is set up to receive commission
        if (!in_array($structure->salary_type, ['commission', 'both'])) {
            return;
        }

        $saleTotal = floatval($sale->total_net);
        if ($saleTotal <= 0) return; // Avoid division by zero

        $maxCommission = floatval($sale->total_commission);
        $alreadyPaid   = floatval($sale->commission_paid);

        // If no commission set on the sale, calculate it based on structure
        if ($maxCommission <= 0) {
            if ($structure->commission_tiers && count($structure->commission_tiers) > 0) {
                $maxCommission = $structure->calculateTieredCommission($saleTotal);
            } elseif ($structure->commission_percentage > 0) {
                $maxCommission = ($saleTotal * $structure->commission_percentage) / 100;
            }
            
            // Save it on sale so it doesn't calculate differently later
            $sale->total_commission = $maxCommission;
        }

        // If the max commission is zero or already fully paid, skip
        if ($maxCommission <= 0 || $alreadyPaid >= $maxCommission) {
            return;
        }

        // Calculate earned value based on total payments
        $totalPaymentsOnSale = \App\Models\CustomerPayment::where('sale_id', $sale->id)->sum('amount');
        
        $paymentRatio = min(1, $totalPaymentsOnSale / $saleTotal);
        $earnedSoFar  = round($paymentRatio * $maxCommission, 2);
        
        // The new commission is whatever was earned minus what's already paid
        $newCommission = max(0, $earnedSoFar - $alreadyPaid);

        if ($newCommission > 0) {
            $month = \Carbon\Carbon::parse($payment->payment_date)->format('Y-m');
            
            // 🔍 Find existing UNPAID payroll for this specific sale/employee
            $payroll = \App\Models\Hr\Payroll::where('employee_id', $employee->id)
                ->where('sale_id', $sale->id)
                ->where('payroll_type', 'commission')
                ->where('status', '!=', 'paid')
                ->first();

            if ($payroll) {
                // 🛠️ Update existing unpaid payroll
                $payroll->gross_salary += $newCommission;
                $payroll->commission   += $newCommission;
                $payroll->net_salary   += $newCommission;
                $payroll->notes        .= " | Add. Payment {$payment->amount} -> {$newCommission} Comm.";
                $payroll->save();
            } else {
                // ✨ Generate a NEW payroll record
                $payroll = \App\Models\Hr\Payroll::create([
                    'employee_id' => $employee->id,
                    'sale_id' => $sale->id,
                    'payroll_type' => 'commission', 
                    'month' => $month,
                    'basic_salary' => 0,
                    'gross_salary' => $newCommission,
                    'allowances' => 0,
                    'deductions' => 0,
                    'attendance_deductions' => 0,
                    'manual_deductions' => 0,
                    'manual_allowances' => 0,
                    'carried_forward_deduction' => 0,
                    'bonuses' => 0,
                    'commission' => $newCommission,
                    'net_salary' => $newCommission,
                    'status' => 'generated',
                    'auto_generated' => true,
                    'notes' => "Instant Commission for Sale #{$sale->invoice_no} (Triggered by Payment of {$payment->amount})",
                    'payment_date' => null,
                ]);
            }

            // Always create a Detail record to maintain full audit history of payments
            \App\Models\Hr\PayrollDetail::create([
                'payroll_id' => $payroll->id,
                'type' => 'commission',
                'name' => "Sales Commission (Sale #{$sale->invoice_no})",
                'amount' => $newCommission,
                'description' => "Invoice #{$sale->invoice_no}: Payment {$payment->amount} -> {$newCommission} Commission",
            ]);

            // Track paid commission on the sale
            // Force save using DB to prevent any stale eloquent model overwriting the values
            \Illuminate\Support\Facades\DB::table('sales')
                ->where('id', $sale->id)
                ->update([
                    'total_commission' => $maxCommission,
                    'commission_paid' => $alreadyPaid + $newCommission
                ]);
        }

    }

    /**
     * Generate (Calculate and Save) monthly payroll for an employee
     */
    public function generateMonthlyPayrollForEmployee(Employee $employee, string $month): ?Payroll
    {
        // 1. Check if already exists
        $exists = Payroll::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('payroll_type', 'monthly') // Assuming 'monthly' is the type or check existing logic
            ->exists();

        if ($exists) {
            return null; // Already generated
        }

        // 2. Calculate
        $payrollData = $this->calculateMonthlyPayroll($employee, $month);

        // 3. Create Payroll Record
        $payroll = Payroll::create(array_merge(
            ['employee_id' => $employee->id],
            \Illuminate\Support\Arr::except($payrollData, ['allowance_details', 'deduction_details', 'breakdown', 'attendance_breakdown'])
        ));
        // Note: allowance_details, deduction_details are stripped. breakdown/attendance_breakdown might also need stripping if not in fillable

        // 4. Save Details
        $this->savePayrollDetails(
            $payroll,
            $payrollData['allowance_details'] ?? [],
            $payrollData['deduction_details'] ?? []
        );

        return $payroll;
    }
}
