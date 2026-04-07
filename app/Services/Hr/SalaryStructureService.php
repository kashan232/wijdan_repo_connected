<?php

namespace App\Services\Hr;

use App\Models\Hr\Employee;
use App\Models\Hr\SalaryStructure;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SalaryStructureService
{
    /**
     * Create a new salary structure for an employee
     *
     * @param Employee $employee
     * @param array $data
     * @return SalaryStructure
     */
    public function createSalaryStructure(Employee $employee, array $data)
    {
        DB::beginTransaction();

        try {
            // Prepare salary structure data
            $structureData = $this->prepareSalaryStructureData($employee, $data);

            // Create the salary structure
            $salaryStructure = SalaryStructure::create($structureData);

            DB::commit();

            $this->clearActiveStructureCache($employee->id);

            return $salaryStructure;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing salary structure
     *
     * @param SalaryStructure $salaryStructure
     * @param array $data
     * @return SalaryStructure
     */
    public function updateSalaryStructure(SalaryStructure $salaryStructure, array $data)
    {
        DB::beginTransaction();

        try {
            $structureData = $this->prepareSalaryStructureData($salaryStructure->employee, $data);
            $salaryStructure->update($structureData);

            DB::commit();

            $this->clearActiveStructureCache($salaryStructure->employee_id);

            return $salaryStructure;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a new salary structure version (for history tracking)
     *
     * @param SalaryStructure $currentStructure
     * @param array $newData
     * @param string $effectiveDate
     * @return SalaryStructure
     */
    public function createNewVersion(SalaryStructure $currentStructure, array $newData, $effectiveDate = null)
    {
        DB::beginTransaction();

        try {
            // Set the current structure as parent
            $newData['parent_structure_id'] = $currentStructure->id;
            $newData['effective_date'] = $effectiveDate ?? now()->toDateString();

            $newStructure = $this->createSalaryStructure($currentStructure->employee, $newData);

            DB::commit();

            return $newStructure;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign salary structure to employee
     *
     * @param Employee $employee
     * @param array $data
     * @param string|null $effectiveDate
     * @return SalaryStructure
     */
    public function assignToEmployee(Employee $employee, array $data, $effectiveDate = null)
    {
        DB::beginTransaction();

        try {
            // Check if employee already has a salary structure
            $existingStructure = $employee->activeSalaryStructure;

            if ($existingStructure) {
                // Create new version
                $salaryStructure = $this->createNewVersion($existingStructure, $data, $effectiveDate);
            } else {
                // Create first salary structure
                $data['effective_date'] = $effectiveDate ?? now()->toDateString();
                $salaryStructure = $this->createSalaryStructure($employee, $data);
            }

            DB::commit();

            return $salaryStructure;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Prepare salary structure data from input
     *
     * @param Employee $employee
     * @param array $data
     * @return array
     */
    protected function prepareSalaryStructureData(Employee $employee, array $data)
    {
        return [
            'employee_id' => $employee->id,
            'basic_salary' => $data['basic_salary'] ?? 0,
            'house_rent_allowance' => $data['house_rent_allowance'] ?? 0,
            'medical_allowance' => $data['medical_allowance'] ?? 0,
            'transport_allowance' => $data['transport_allowance'] ?? 0,
            'other_allowances' => $data['other_allowances'] ?? 0,
            'provident_fund' => $data['provident_fund'] ?? 0,
            'income_tax' => $data['income_tax'] ?? 0,
            'other_deductions' => $data['other_deductions'] ?? 0,
            'use_daily_wages' => $data['use_daily_wages'] ?? false,
            'daily_wage_rate' => $data['daily_wage_rate'] ?? 0,
            'commission_percentage' => $data['commission_percentage'] ?? null,
            'effective_date' => $data['effective_date'] ?? null,
            'parent_structure_id' => $data['parent_structure_id'] ?? null,
        ];
    }

    /**
     * Calculate total salary components
     *
     * @param SalaryStructure $salaryStructure
     * @return array
     */
    public function calculateSalaryComponents(SalaryStructure $salaryStructure)
    {
        $totalAllowances = $salaryStructure->house_rent_allowance
            + $salaryStructure->medical_allowance
            + $salaryStructure->transport_allowance
            + $salaryStructure->other_allowances;

        $totalDeductions = $salaryStructure->provident_fund
            + $salaryStructure->income_tax
            + $salaryStructure->other_deductions;

        $grossSalary = $salaryStructure->basic_salary + $totalAllowances;
        $netSalary = $grossSalary - $totalDeductions;

        return [
            'basic_salary' => $salaryStructure->basic_salary,
            'total_allowances' => $totalAllowances,
            'gross_salary' => $grossSalary,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'breakdown' => [
                'allowances' => [
                    'house_rent' => $salaryStructure->house_rent_allowance,
                    'medical' => $salaryStructure->medical_allowance,
                    'transport' => $salaryStructure->transport_allowance,
                    'other' => $salaryStructure->other_allowances,
                ],
                'deductions' => [
                    'provident_fund' => $salaryStructure->provident_fund,
                    'income_tax' => $salaryStructure->income_tax,
                    'other' => $salaryStructure->other_deductions,
                ],
            ],
        ];
    }

    /**
     * Calculate daily wage amount
     *
     * @param SalaryStructure $salaryStructure
     * @param int $daysWorked
     * @return float
     */
    public function calculateDailyWages(SalaryStructure $salaryStructure, $daysWorked)
    {
        if (!$salaryStructure->use_daily_wages) {
            return 0;
        }

        return $salaryStructure->daily_wage_rate * $daysWorked;
    }

    /**
     * Calculate commission amount
     *
     * @param SalaryStructure $salaryStructure
     * @param float $salesAmount
     * @return float
     */
    public function calculateCommission(SalaryStructure $salaryStructure, $salesAmount)
    {
        if (!$salaryStructure->commission_percentage || $salaryStructure->commission_percentage <= 0) {
            return 0;
        }

        return ($salesAmount * $salaryStructure->commission_percentage) / 100;
    }

    /**
     * Get salary structure history for an employee
     *
     * @param Employee $employee
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSalaryHistory(Employee $employee)
    {
        return SalaryStructure::where('employee_id', $employee->id)
            ->orderBy('effective_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active salary structure for a specific date (Cached if date is null/today)
     *
     * @param Employee $employee
     * @param string|null $date
     * @return SalaryStructure|null
     */
    public function getSalaryStructureForDate(Employee $employee, $date = null)
    {
        // If query is for today/current active, use cache
        if (empty($date) || $date === now()->toDateString()) {
            return Cache::remember(
                "hr.salary_structure.active.{$employee->id}",
                3600, // 1 hour
                function () use ($employee, $date) {
                    return $this->fetchSalaryStructure($employee, $date);
                }
            );
        }

        return $this->fetchSalaryStructure($employee, $date);
    }

    /**
     * Internal method to fetch structure
     */
    protected function fetchSalaryStructure(Employee $employee, $date)
    {
        $query = SalaryStructure::where('employee_id', $employee->id);

        if ($date) {
            $query->where(function ($q) use ($date) {
                $q->whereNull('effective_date')
                  ->orWhere('effective_date', '<=', $date);
            });
        } else {
             $query->where(function ($q) {
                $q->whereNull('effective_date')
                  ->orWhere('effective_date', '<=', now()->toDateString());
            });
        }

        return $query->latest('effective_date')
            ->latest('id')
            ->first();
    }

    /**
     * Clear active structure cache
     */
    protected function clearActiveStructureCache($employeeId)
    {
        Cache::forget("hr.salary_structure.active.{$employeeId}");
    }

    /**
     * Validate salary structure data
     *
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateSalaryStructure(array $data)
    {
        $errors = [];

        if (empty($data['basic_salary']) || $data['basic_salary'] < 0) {
            $errors[] = 'Basic salary must be a positive number';
        }

        if (isset($data['use_daily_wages']) && $data['use_daily_wages']) {
            if (empty($data['daily_wage_rate']) || $data['daily_wage_rate'] < 0) {
                $errors[] = 'Daily wage rate is required when daily wages are enabled';
            }
        }

        if (isset($data['commission_percentage']) && $data['commission_percentage'] !== null) {
            if ($data['commission_percentage'] < 0 || $data['commission_percentage'] > 100) {
                $errors[] = 'Commission percentage must be between 0 and 100';
            }
        }

        // Validate allowances and deductions are non-negative
        $fields = [
            'house_rent_allowance',
            'medical_allowance',
            'transport_allowance',
            'other_allowances',
            'provident_fund',
            'income_tax',
            'other_deductions',
        ];

        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] < 0) {
                $errors[] = ucwords(str_replace('_', ' ', $field)) . ' cannot be negative';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Clone salary structure to another employee
     *
     * @param SalaryStructure $sourceStructure
     * @param Employee $targetEmployee
     * @param string|null $effectiveDate
     * @return SalaryStructure
     */
    public function cloneSalaryStructure(SalaryStructure $sourceStructure, Employee $targetEmployee, $effectiveDate = null)
    {
        $data = [
            'basic_salary' => $sourceStructure->basic_salary,
            'house_rent_allowance' => $sourceStructure->house_rent_allowance,
            'medical_allowance' => $sourceStructure->medical_allowance,
            'transport_allowance' => $sourceStructure->transport_allowance,
            'other_allowances' => $sourceStructure->other_allowances,
            'provident_fund' => $sourceStructure->provident_fund,
            'income_tax' => $sourceStructure->income_tax,
            'other_deductions' => $sourceStructure->other_deductions,
            'use_daily_wages' => $sourceStructure->use_daily_wages,
            'daily_wage_rate' => $sourceStructure->daily_wage_rate,
            'commission_percentage' => $sourceStructure->commission_percentage,
            'effective_date' => $effectiveDate ?? now()->toDateString(),
        ];

        return $this->createSalaryStructure($targetEmployee, $data);
    }

    /**
     * Bulk assign salary structure to multiple employees
     *
     * @param array $employeeIds
     * @param array $salaryData
     * @param string|null $effectiveDate
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function bulkAssign(array $employeeIds, array $salaryData, $effectiveDate = null)
    {
        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($employeeIds as $employeeId) {
            try {
                $employee = Employee::findOrFail($employeeId);
                $this->assignToEmployee($employee, $salaryData, $effectiveDate);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Employee ID {$employeeId}: " . $e->getMessage();
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Compare two salary structures
     *
     * @param SalaryStructure $structure1
     * @param SalaryStructure $structure2
     * @return array Differences
     */
    public function compareSalaryStructures(SalaryStructure $structure1, SalaryStructure $structure2)
    {
        $fields = [
            'basic_salary',
            'house_rent_allowance',
            'medical_allowance',
            'transport_allowance',
            'other_allowances',
            'provident_fund',
            'income_tax',
            'other_deductions',
            'daily_wage_rate',
            'commission_percentage',
        ];

        $differences = [];

        foreach ($fields as $field) {
            if ($structure1->$field != $structure2->$field) {
                $differences[$field] = [
                    'old' => $structure1->$field,
                    'new' => $structure2->$field,
                    'change' => $structure2->$field - $structure1->$field,
                    'percentage_change' => $structure1->$field > 0
                        ? round((($structure2->$field - $structure1->$field) / $structure1->$field) * 100, 2)
                        : 0,
                ];
            }
        }

        return $differences;
    }
}
