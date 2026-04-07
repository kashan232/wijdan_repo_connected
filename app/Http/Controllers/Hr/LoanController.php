<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\Loan;
use App\Models\Hr\LoanPayment;
use App\Models\Hr\LoanScheduledDeduction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{
    // ──────────────────────────────────────────
    // List
    // ──────────────────────────────────────────

    public function index()
    {
        $loans = Loan::with('employee.department', 'employee.designation')
            ->latest()
            ->paginate(12);

        $employees = Employee::where('status', 'active')
            ->with('department', 'designation')
            ->orderBy('first_name')
            ->get();

        return view('hr.loans.index', compact('loans', 'employees'));
    }

    // ──────────────────────────────────────────
    // Create Loan
    // ──────────────────────────────────────────

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id'  => 'required|exists:hr_employees,id',
            'loan_type'    => 'required|in:salary_deduction,self_paid',
            'amount'       => 'required|numeric|min:1',
            'reason'       => 'nullable|string',
            'notes'        => 'nullable|string',
            // Salary-deduction specific
            'num_months'          => 'nullable|integer|min:1|max:360',
            'installment_amount'  => 'nullable|numeric|min:1',
            'start_month'         => 'nullable|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $amount    = floatval($request->amount);
        $loanType  = $request->loan_type;

        // Calculate installment plan for salary-deduction type
        $installmentAmount = 0;
        $totalInstallments = null;
        $expectedEndMonth  = null;
        $startMonth        = $request->start_month ?? Carbon::now()->addMonth()->format('Y-m');

        if ($loanType === 'salary_deduction') {
            if ($request->num_months && intval($request->num_months) > 0) {
                $totalInstallments = intval($request->num_months);
                $installmentAmount = round($amount / $totalInstallments, 2);
            } elseif ($request->installment_amount && floatval($request->installment_amount) > 0) {
                $installmentAmount = floatval($request->installment_amount);
                $totalInstallments = (int) ceil($amount / $installmentAmount);
            } else {
                return response()->json(['errors' => ['num_months' => ['Please set number of months or monthly amount for salary deduction loans.']]], 422);
            }
            $expectedEndMonth = Carbon::parse($startMonth . '-01')
                ->addMonths($totalInstallments - 1)
                ->format('Y-m');
        }

        Loan::create([
            'employee_id'        => $request->employee_id,
            'loan_type'          => $loanType,
            'amount'             => $amount,
            'installment_amount' => $installmentAmount,
            'total_installments' => $totalInstallments,
            'installments_paid'  => 0,
            'start_month'        => $loanType === 'salary_deduction' ? $startMonth : null,
            'expected_end_month' => $expectedEndMonth,
            'reason'             => $request->reason,
            'notes'              => $request->notes,
            'status'             => 'pending',
            'paid_amount'        => 0,
        ]);

        return response()->json(['success' => 'Loan request submitted successfully.', 'reload' => true]);
    }

    // ──────────────────────────────────────────
    // Update Loan
    // ──────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);
        
        if ($loan->status !== 'pending') {
            return response()->json(['error' => 'Only pending loans can be edited.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'amount'       => 'required|numeric|min:1',
            'reason'       => 'nullable|string',
            'notes'        => 'nullable|string',
            // Salary-deduction specific
            'num_months'          => 'nullable|integer|min:1|max:360',
            'installment_amount'  => 'nullable|numeric|min:1',
            'start_month'         => 'nullable|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $amount    = floatval($request->amount);
        $loanType  = $loan->loan_type;

        $installmentAmount = 0;
        $totalInstallments = null;
        $expectedEndMonth  = null;
        $startMonth        = $request->start_month ?? Carbon::now()->addMonth()->format('Y-m');

        if ($loanType === 'salary_deduction') {
            if ($request->num_months && intval($request->num_months) > 0) {
                $totalInstallments = intval($request->num_months);
                $installmentAmount = round($amount / $totalInstallments, 2);
            } elseif ($request->installment_amount && floatval($request->installment_amount) > 0) {
                $installmentAmount = floatval($request->installment_amount);
                $totalInstallments = (int) ceil($amount / $installmentAmount);
            } else {
                return response()->json(['errors' => ['num_months' => ['Please set number of months or monthly amount for salary deduction loans.']]], 422);
            }
            $expectedEndMonth = Carbon::parse($startMonth . '-01')
                ->addMonths($totalInstallments - 1)
                ->format('Y-m');
        }

        $loan->update([
            'amount'             => $amount,
            'installment_amount' => $installmentAmount,
            'total_installments' => $totalInstallments,
            'start_month'        => $loanType === 'salary_deduction' ? $startMonth : null,
            'expected_end_month' => $expectedEndMonth,
            'reason'             => $request->reason,
            'notes'              => $request->notes,
        ]);

        return response()->json(['success' => 'Loan request updated successfully.', 'reload' => true]);
    }


    public function calculateInstallment(Request $request)
    {
        $amount = floatval($request->amount ?? 0);
        if ($amount <= 0) {
            return response()->json(['error' => 'Invalid amount'], 422);
        }

        if ($request->filled('num_months') && intval($request->num_months) > 0) {
            $months  = intval($request->num_months);
            $monthly = round($amount / $months, 2);
        } elseif ($request->filled('monthly_amount') && floatval($request->monthly_amount) > 0) {
            $monthly = floatval($request->monthly_amount);
            $months  = (int) ceil($amount / $monthly);
        } else {
            return response()->json(['error' => 'Provide either num_months or monthly_amount'], 422);
        }

        $startMonth = $request->start_month
            ? Carbon::parse($request->start_month . '-01')
            : Carbon::now()->addMonth()->startOfMonth();

        $endMonth = (clone $startMonth)->addMonths($months - 1);

        return response()->json([
            'monthly_installment' => $monthly,
            'total_installments'  => $months,
            'total_amount'        => $amount,
            'start_month_label'   => $startMonth->format('F Y'),
            'end_month_label'     => $endMonth->format('F Y'),
            'end_month'           => $endMonth->format('Y-m'),
        ]);
    }

    // ──────────────────────────────────────────
    // Approve / Reject
    // ──────────────────────────────────────────

    public function approve($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return response()->json(['success' => 'Loan approved successfully.', 'reload' => true]);
    }

    public function reject($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->update(['status' => 'rejected']);

        return response()->json(['success' => 'Loan rejected.', 'reload' => true]);
    }

    // ──────────────────────────────────────────
    // Record Manual Payment (Self-paid or manual installment)
    // ──────────────────────────────────────────

    public function recordPayment(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount'       => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'type'         => 'required|in:cash,bank_transfer,salary_deduction',
            'reference'    => 'nullable|string|max:100',
            'notes'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $amount = floatval($request->amount);

        if ($amount > $loan->remaining_amount) {
            $amount = $loan->remaining_amount; // Cap at remaining
        }

        LoanPayment::create([
            'loan_id'      => $loan->id,
            'amount'       => $amount,
            'payment_date' => $request->payment_date,
            'type'         => $request->type,
            'source'       => 'manual',
            'reference'    => $request->reference,
            'notes'        => $request->notes,
        ]);

        $loan->increment('paid_amount', $amount);
        $loan->refresh();

        if ($loan->paid_amount >= $loan->amount) {
            $loan->update(['status' => 'paid']);
        }

        return response()->json(['success' => 'Payment of Rs. ' . number_format($amount, 2) . ' recorded.', 'reload' => true]);
    }

    // ──────────────────────────────────────────
    // Delete
    // ──────────────────────────────────────────

    public function destroy($id)
    {
        Loan::findOrFail($id)->delete();
        return response()->json(['success' => 'Loan deleted successfully.', 'reload' => true]);
    }

    // ──────────────────────────────────────────
    // Schedule One-off Deduction
    // ──────────────────────────────────────────

    public function scheduleDeduction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:hr_loans,id',
            'amount'  => 'required|numeric|min:1',
            'month'   => 'required|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loan = Loan::findOrFail($request->loan_id);

        if ($request->amount > $loan->remaining_amount) {
            return response()->json(['error' => 'Amount exceeds remaining balance (Rs. ' . number_format($loan->remaining_amount, 2) . ')'], 422);
        }

        LoanScheduledDeduction::create([
            'loan_id'         => $loan->id,
            'amount'          => $request->amount,
            'deduction_month' => $request->month,
            'status'          => 'pending',
            'notes'           => $request->notes,
        ]);

        return response()->json(['success' => 'Deduction scheduled for ' . $request->month . '.', 'reload' => true]);
    }

    // ──────────────────────────────────────────
    // Get Full Loan Details (for modal/AJAX)
    // ──────────────────────────────────────────

    public function getHistory($id)
    {
        $loan = Loan::with(['employee.designation', 'payments', 'scheduledDeductions'])
            ->findOrFail($id);

        return response()->json([
            'id'                  => $loan->id,
            'loan_type'           => $loan->loan_type,
            'type_label'          => $loan->type_label,
            'amount'              => $loan->amount,
            'paid_amount'         => $loan->paid_amount,
            'remaining_amount'    => $loan->remaining_amount,
            'installment_amount'  => $loan->monthly_installment,
            'total_installments'  => $loan->total_installments,
            'installments_paid'   => $loan->installments_paid,
            'remaining_installments' => $loan->remaining_installments,
            'start_month'         => $loan->start_month,
            'expected_end_month'  => $loan->expected_end_month,
            'progress_percentage' => $loan->progress_percentage,
            'is_overdue'          => $loan->is_overdue,
            'status'              => $loan->status,
            'reason'              => $loan->reason,
            'notes'               => $loan->notes,
            'approved_at'         => $loan->approved_at?->format('d M Y'),
            'disbursed_at'        => $loan->disbursed_at?->format('d M Y'),
            'employee'            => [
                'id'          => $loan->employee->id,
                'name'        => $loan->employee->full_name,
                'designation' => $loan->employee->designation->name ?? 'N/A',
            ],
            'payments'            => $loan->payments->map(fn($p) => [
                'id'           => $p->id,
                'amount'       => $p->amount,
                'payment_date' => $p->payment_date?->format('d M Y'),
                'type'         => $p->type,
                'type_label'   => $p->getTypeLabel(),
                'source'       => $p->source,
                'reference'    => $p->reference,
                'notes'        => $p->notes,
            ]),
            'scheduled_deductions' => $loan->scheduledDeductions->map(fn($s) => [
                'id'              => $s->id,
                'amount'          => $s->amount,
                'deduction_month' => $s->deduction_month,
                'status'          => $s->status,
                'notes'           => $s->notes,
            ]),
        ]);
    }
}
