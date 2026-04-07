<?php

namespace App\Services\Hr;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Hr\Employee;
use App\Models\Hr\Payroll;
use App\Models\PaymentVoucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SalaryVoucherService
 *
 * When a payroll is marked as PAID:
 * 1. Auto-creates (or finds) a Chart of Account HEAD: "Salary Expenses" (Expense type)
 * 2. Auto-creates (or finds) a per-employee sub-account under that head (e.g., "Salary - Ali Hassan")
 * 3. Creates a Payment Voucher recording the salary disbursement
 * 4. Updates the employee salary account balance (current_balance)
 * 5. Links voucher_id and salary_account_id back to the Payroll record
 */
class SalaryVoucherService
{
    // The account head name for all salary expenses
    const SALARY_HEAD_NAME = 'Salary Expenses';
    const SALARY_HEAD_TYPE = 'Expense';

    /**
     * Process salary payment: create voucher + update COA
     *
     * @param Payroll $payroll  The payroll record that was just marked paid
     * @return array ['voucher_id' => int, 'salary_account_id' => int]
     */
    public function processPayment(Payroll $payroll): array
    {
        DB::beginTransaction();

        try {
            $employee = $payroll->employee;
            $amount   = $payroll->net_salary;
            $date     = $payroll->payment_date ?? now()->toDateString();

            // 1. Ensure "Salary Expenses" account head exists
            $salaryHead = $this->getOrCreateSalaryHead();

            // 2. Ensure per-employee salary account exists under that head
            $salaryAccount = $this->getOrCreateEmployeeAccount($employee, $salaryHead);

            // 3. Create Payment Voucher
            $voucher = $this->createPaymentVoucher($payroll, $employee, $salaryAccount, $amount, $date);

            // 4. Update employee salary account balance (Debit = Expense increases)
            $salaryAccount->increment('current_balance', $amount);

            // 5. Link back to payroll
            $payroll->update([
                'voucher_id'        => $voucher->id,
                'salary_account_id' => $salaryAccount->id,
            ]);

            DB::commit();

            return [
                'voucher_id'        => $voucher->id,
                'salary_account_id' => $salaryAccount->id,
                'pvid'              => $voucher->pvid,
                'account_title'     => $salaryAccount->title,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SalaryVoucherService: Failed to create payment voucher - ' . $e->getMessage(), [
                'payroll_id' => $payroll->id,
                'trace'      => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get or create the "Salary Expenses" account head
     */
    protected function getOrCreateSalaryHead(): AccountHead
    {
        $head = AccountHead::where('name', self::SALARY_HEAD_NAME)->first();

        if (! $head) {
            // Find Expenses parent head (level 1)
            $expenseParent = AccountHead::where('type', 'Expense')->where('level', 1)->first();

            $head = AccountHead::create([
                'name'      => self::SALARY_HEAD_NAME,
                'type'      => self::SALARY_HEAD_TYPE,
                'level'     => $expenseParent ? 2 : 1,
                'parent_id' => $expenseParent?->id,
                'code'      => 'SAL-EXP-' . now()->format('ymd'),
                'opening_balance' => 0,
            ]);

            Log::info('SalaryVoucherService: Created account head "' . self::SALARY_HEAD_NAME . '" (ID: ' . $head->id . ')');
        }

        return $head;
    }

    /**
     * Get or create the individual employee salary tracking account
     * Account title: "Salary - {Employee Full Name}"
     */
    protected function getOrCreateEmployeeAccount(Employee $employee, AccountHead $salaryHead): Account
    {
        $accountTitle = 'Salary - ' . $employee->full_name;

        $account = Account::where('head_id', $salaryHead->id)
            ->where('title', $accountTitle)
            ->first();

        if (! $account) {
            // Generate employee account code: SAL-EMP-{employee_id padded}
            $code = 'SAL-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT);

            $account = Account::create([
                'head_id'         => $salaryHead->id,
                'title'           => $accountTitle,
                'account_code'    => $code,
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'type'            => 'Debit', // Expense accounts are Debit-nature
                'status'          => 1,
                'is_active'       => 1,
            ]);

            Log::info('SalaryVoucherService: Created salary account "' . $accountTitle . '" (ID: ' . $account->id . ') for employee ID ' . $employee->id);
        }

        return $account;
    }

    /**
     * Create Payment Voucher entry in payment_vouchers table
     */
    protected function createPaymentVoucher(Payroll $payroll, Employee $employee, Account $salaryAccount, float $amount, string $date): PaymentVoucher
    {
        // Generate next PVID
        $lastVoucher = PaymentVoucher::latest('id')->first();
        $nextNum     = $lastVoucher ? $lastVoucher->id + 1 : 1;
        $pvid        = 'PV-SAL-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        // Build period label
        if ($payroll->payroll_type === 'daily') {
            $period = Carbon::parse($payroll->month)->format('d M Y');
        } else {
            $period = Carbon::parse($payroll->month . '-01')->format('M Y');
        }

        $narration = "Salary Payment - {$employee->full_name} ({$period})";

        $voucher = PaymentVoucher::create([
            'pvid'          => $pvid,
            'receipt_date'  => $date,
            'entry_date'    => $date,
            'type'          => 'employee',
            'party_id'      => $employee->id,
            'tel'           => $employee->phone ?? '',
            'remarks'       => $narration,
            // Row data stored as JSON arrays (single row)
            'narration_id'      => json_encode([$narration]),
            'reference_no'      => json_encode(['Payroll#' . $payroll->id]),
            'row_account_head'  => json_encode([$salaryAccount->head_id]),
            'row_account_id'    => json_encode([$salaryAccount->id]),
            'discount_value'    => json_encode([0]),
            'kg'                => json_encode(['']),
            'rate'              => json_encode([$amount]),
            'amount'            => json_encode([$amount]),
            'total_amount'      => $amount,
        ]);

        Log::info("SalaryVoucherService: Created Payment Voucher {$pvid} for payroll #{$payroll->id}, employee {$employee->full_name}, amount {$amount}");

        return $voucher;
    }

    /**
     * Get total salary paid to an employee across all payrolls (from their salary account)
     */
    public function getEmployeeTotalPaid(Employee $employee): float
    {
        $salaryHead = AccountHead::where('name', self::SALARY_HEAD_NAME)->first();
        if (! $salaryHead) {
            return 0.0;
        }

        $account = Account::where('head_id', $salaryHead->id)
            ->where('title', 'Salary - ' . $employee->full_name)
            ->first();

        return $account ? (float) $account->current_balance : 0.0;
    }
}
