<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\Leave;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('hr.leaves.view')) {
            abort(403, 'Unauthorized action.');
        }
        $leaves    = Leave::with('employee')->latest()->paginate(12);
        $employees = Employee::where('status', 'active')->get();

        return view('hr.leaves.index', compact('leaves', 'employees'));
    }

    /**
     * GET /hr/leaves/balance?employee_id=5&leave_type=Casual&days=3
     * Returns leave balance summary + whether salary deduction should be forced
     */
    public function getLeaveBalance(Request $request)
    {
        $employee = Employee::find($request->employee_id);

        if (! $employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $leaveType = $request->leave_type ?? 'Casual';
        $newDays   = (int) ($request->days ?? 1);

        $summary     = Leave::getEmployeeLeaveSummary($employee);
        $forceDeduct = Leave::shouldForceDeduction($employee, $leaveType, $newDays);

        return response()->json([
            'summary'        => $summary,
            'force_deduct'   => $forceDeduct,
            'leave_type'     => $leaveType,
            'requested_days' => $newDays,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id'   => 'required|exists:hr_employees,id',
            'leave_type'    => 'required|in:Sick,Casual,Annual',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'nullable|string',
            'deduct_salary' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (! auth()->user()->can('hr.leaves.create')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $employee  = Employee::findOrFail($request->employee_id);
        $startDate = Carbon::parse($request->start_date);
        $endDate   = Carbon::parse($request->end_date);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Determine if salary deduction should be forced based on quota
        $forceDeduct = Leave::shouldForceDeduction($employee, $request->leave_type, $totalDays);

        // HR can request deduction manually; it's always forced if quota exhausted
        $deductSalary = $forceDeduct || ($request->boolean('deduct_salary') === true);

        Leave::create([
            'employee_id'   => $request->employee_id,
            'leave_type'    => $request->leave_type,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'reason'        => $request->reason,
            'status'        => 'pending',
            'deduct_salary' => $deductSalary,
        ]);

        $balanceMsg = '';
        if (in_array($request->leave_type, ['Casual', 'Sick'])) {
            $balance    = Leave::getBalance($employee, $request->leave_type);
            $remaining  = max(0, $balance['remaining'] - $totalDays);
            $balanceMsg = " (Remaining {$request->leave_type} leaves after this: {$remaining})";
        }

        return response()->json([
            'success'      => 'Leave request submitted successfully.' . $balanceMsg,
            'force_deduct' => $forceDeduct,
            'reload'       => true,
        ]);
    }

    public function updateStatus(Request $request, Leave $leave)
    {
        if (! auth()->user()->can('hr.leaves.approve')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $leave->update(['status' => $request->status]);

        return response()->json([
            'success' => 'Leave status updated.',
            'reload'  => true,
        ]);
    }

    /**
     * Toggle deduct_salary on an existing leave (HR can override)
     * Prevented if quota is exhausted and deduction is currently off
     */
    public function toggleDeduction(Request $request, Leave $leave)
    {
        if (! auth()->user()->can('hr.leaves.approve')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $employee    = $leave->employee;
        $forceDeduct = Leave::shouldForceDeduction($employee, $leave->leave_type, 0);

        // Cannot un-tick if quota is exhausted
        if ($forceDeduct && ! $leave->deduct_salary) {
            return response()->json([
                'error' => "Cannot un-tick deduction: {$employee->full_name}'s {$leave->leave_type} leave quota is exhausted.",
            ], 422);
        }

        $leave->update(['deduct_salary' => ! $leave->deduct_salary]);

        return response()->json([
            'success'       => 'Leave deduction updated.',
            'deduct_salary' => $leave->fresh()->deduct_salary,
        ]);
    }
}
