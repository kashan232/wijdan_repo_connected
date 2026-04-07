<?php

namespace App\Services\Hr;

use App\Models\Hr\Employee;
use App\Models\Hr\Payroll;
use App\Models\Hr\Attendance;
use Carbon\Carbon;

class PayrollValidationService
{
    /**
     * Validate monthly payroll generation request
     *
     * @param string $month Format: Y-m
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateMonthlyPayrollRequest($month)
    {
        $errors = [];

        // Validate month format
        if (!$this->isValidMonthFormat($month)) {
            $errors[] = 'Invalid month format. Expected format: YYYY-MM';
        }

        // Check if month is not in future
        if ($this->isF utureMonth($month)) {
            $errors[] = 'Cannot generate payroll for future months';
        }

        // Check if payroll already exists for this month
        if ($this->monthlyPayrollExists($month)) {
            $errors[] = "Payroll for {$month} has already been generated";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate daily payroll generation request
     *
     * @param string $date Format: Y-m-d
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateDailyPayrollRequest($date)
    {
        $errors = [];

        // Validate date format
        if (!$this->isValidDateFormat($date)) {
            $errors[] = 'Invalid date format. Expected format: YYYY-MM-DD';
        }

        // Check if date is not in future
        if ($this->isFutureDate($date)) {
            $errors[] = 'Cannot generate payroll for future dates';
        }

        // Check if date is too old (e.g., more than 6 months ago)
        if ($this->isTooOld($date, 180)) {
            $errors[] = 'Cannot generate payroll for dates older than 6 months';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate employee eligibility for monthly payroll
     *
     * @param Employee $employee
     * @param string $month
     * @return array ['eligible' => bool, 'reason' => string|null]
     */
    public function validateEmployeeForMonthlyPayroll(Employee $employee, $month)
    {
        // Check if employee is active
        if ($employee->status !== 'active') {
            return [
                'eligible' => false,
                'reason' => "Employee is {$employee->status}",
            ];
        }

        // Check if employee has salary structure
        if (!$employee->activeSalaryStructure) {
            return [
                'eligible' => false,
                'reason' => 'No active salary structure found',
            ];
        }

        $salaryStructure = $employee->activeSalaryStructure;

        // Check if employee is eligible for monthly payroll
        $isMonthlyEligible = !$salaryStructure->use_daily_wages
            || ($salaryStructure->commission_percentage && $salaryStructure->commission_percentage > 0);

        if (!$isMonthlyEligible) {
            return [
                'eligible' => false,
                'reason' => 'Employee is configured for daily payroll only',
            ];
        }

        // Check if payroll already exists
        if ($this->employeeMonthlyPayrollExists($employee, $month)) {
            return [
                'eligible' => false,
                'reason' => 'Payroll already generated for this month',
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
        ];
    }

    /**
     * Validate employee eligibility for daily payroll
     *
     * @param Employee $employee
     * @param string $date
     * @return array ['eligible' => bool, 'reason' => string|null]
     */
    public function validateEmployeeForDailyPayroll(Employee $employee, $date)
    {
        // Check if employee is active
        if ($employee->status !== 'active') {
            return [
                'eligible' => false,
                'reason' => "Employee is {$employee->status}",
            ];
        }

        // Check if employee has salary structure
        if (!$employee->activeSalaryStructure) {
            return [
                'eligible' => false,
                'reason' => 'No active salary structure found',
            ];
        }

        $salaryStructure = $employee->activeSalaryStructure;

        // Check if employee is eligible for daily payroll
        $isDailyEligible = $salaryStructure->use_daily_wages
            && (!$salaryStructure->commission_percentage || $salaryStructure->commission_percentage == 0);

        if (!$isDailyEligible) {
            return [
                'eligible' => false,
                'reason' => 'Employee is not configured for daily payroll',
            ];
        }

        // Check if attendance exists for the date
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->first();

        if (!$attendance) {
            return [
                'eligible' => false,
                'reason' => 'No attendance record found for this date',
            ];
        }

        if (!$attendance->clock_out) {
            return [
                'eligible' => false,
                'reason' => 'Attendance is incomplete (no clock out)',
            ];
        }

        // Check if payroll already exists
        if ($this->employeeDailyPayrollExists($employee, $date)) {
            return [
                'eligible' => false,
                'reason' => 'Payroll already generated for this date',
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
        ];
    }

    /**
     * Validate payroll update request
     *
     * @param Payroll $payroll
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validatePayrollUpdate(Payroll $payroll, array $data)
    {
        $errors = [];

        // Check if payroll can be edited
        if (!$payroll->canEdit()) {
            $errors[] = 'Cannot edit paid payroll';
        }

        // Validate manual allowances
        if (isset($data['manual_allowances']) && $data['manual_allowances'] < 0) {
            $errors[] = 'Manual allowances cannot be negative';
        }

        // Validate manual deductions
        if (isset($data['manual_deductions']) && $data['manual_deductions'] < 0) {
            $errors[] = 'Manual deductions cannot be negative';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate payroll status change
     *
     * @param Payroll $payroll
     * @param string $newStatus
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateStatusChange(Payroll $payroll, $newStatus)
    {
        $errors = [];

        $validStatuses = ['pending', 'reviewed', 'paid'];

        if (!in_array($newStatus, $validStatuses)) {
            $errors[] = "Invalid status: {$newStatus}";
        }

        // Cannot change status of already paid payroll
        if ($payroll->status === 'paid' && $newStatus !== 'paid') {
            $errors[] = 'Cannot change status of paid payroll';
        }

        // Cannot skip reviewed status
        if ($payroll->status === 'pending' && $newStatus === 'paid') {
            $errors[] = 'Payroll must be reviewed before marking as paid';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check if month format is valid
     *
     * @param string $month
     * @return bool
     */
    protected function isValidMonthFormat($month)
    {
        return preg_match('/^\d{4}-\d{2}$/', $month) && Carbon::hasFormat($month . '-01', 'Y-m-d');
    }

    /**
     * Check if date format is valid
     *
     * @param string $date
     * @return bool
     */
    protected function isValidDateFormat($date)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && Carbon::hasFormat($date, 'Y-m-d');
    }

    /**
     * Check if month is in future
     *
     * @param string $month
     * @return bool
     */
    protected function isFutureMonth($month)
    {
        return Carbon::parse($month . '-01')->greaterThan(now()->startOfMonth());
    }

    /**
     * Check if date is in future
     *
     * @param string $date
     * @return bool
     */
    protected function isFutureDate($date)
    {
        return Carbon::parse($date)->greaterThan(now());
    }

    /**
     * Check if date is too old
     *
     * @param string $date
     * @param int $days
     * @return bool
     */
    protected function isTooOld($date, $days)
    {
        return Carbon::parse($date)->lessThan(now()->subDays($days));
    }

    /**
     * Check if monthly payroll exists for month
     *
     * @param string $month
     * @return bool
     */
    protected function monthlyPayrollExists($month)
    {
        return Payroll::where('payroll_type', 'monthly')
            ->where('month', $month)
            ->exists();
    }

    /**
     * Check if employee monthly payroll exists
     *
     * @param Employee $employee
     * @param string $month
     * @return bool
     */
    protected function employeeMonthlyPayrollExists(Employee $employee, $month)
    {
        return Payroll::where('employee_id', $employee->id)
            ->where('payroll_type', 'monthly')
            ->where('month', $month)
            ->exists();
    }

    /**
     * Check if employee daily payroll exists
     *
     * @param Employee $employee
     * @param string $date
     * @return bool
     */
    protected function employeeDailyPayrollExists(Employee $employee, $date)
    {
        return Payroll::where('employee_id', $employee->id)
            ->where('payroll_type', 'daily')
            ->where('month', $date)
            ->exists();
    }

    /**
     * Get eligible employees count for monthly payroll
     *
     * @param string $month
     * @return int
     */
    public function getEligibleEmployeesCountForMonthly($month)
    {
        return Employee::forMonthlyPayroll()
            ->whereDoesntHave('payrolls', function ($q) use ($month) {
                $q->where('payroll_type', 'monthly')
                  ->where('month', $month);
            })
            ->count();
    }

    /**
     * Get eligible employees count for daily payroll
     *
     * @param string $date
     * @return int
     */
    public function getEligibleEmployeesCountForDaily($date)
    {
        return Employee::forDailyPayroll()
            ->whereHas('attendances', function ($q) use ($date) {
                $q->whereDate('date', $date)
                  ->whereNotNull('clock_out');
            })
            ->whereDoesntHave('payrolls', function ($q) use ($date) {
                $q->where('payroll_type', 'daily')
                  ->where('month', $date);
            })
            ->count();
    }

    /**
     * Validate bulk payroll generation
     *
     * @param array $employeeIds
     * @param string $month
     * @param string $type monthly|daily
     * @return array ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public function validateBulkGeneration(array $employeeIds, $month, $type = 'monthly')
    {
        $errors = [];
        $warnings = [];

        if (empty($employeeIds)) {
            $errors[] = 'No employees selected';
        }

        if (count($employeeIds) > 1000) {
            $warnings[] = 'Large batch detected. Consider processing in smaller batches for better performance';
        }

        // Validate each employee
        $invalidCount = 0;
        foreach ($employeeIds as $employeeId) {
            $employee = Employee::find($employeeId);

            if (!$employee) {
                $invalidCount++;
                continue;
            }

            if ($type === 'monthly') {
                $validation = $this->validateEmployeeForMonthlyPayroll($employee, $month);
            } else {
                $validation = $this->validateEmployeeForDailyPayroll($employee, $month);
            }

            if (!$validation['eligible']) {
                $invalidCount++;
            }
        }

        if ($invalidCount > 0) {
            $warnings[] = "{$invalidCount} employees are not eligible for payroll generation";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
