<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Holiday;
use App\Services\Hr\HrCacheService;
use App\Models\Hr\Department;
use App\Models\Hr\Designation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HolidayController extends Controller
{
    protected $hrCache;

    public function __construct(HrCacheService $hrCache)
    {
        $this->hrCache = $hrCache;
    }

    public function index()
    {
        if (! auth()->user()->can('hr.holidays.view')) {
            abort(403, 'Unauthorized action.');
        }
        $year = request('year', date('Y'));
        $holidays = Holiday::with('employees')->whereYear('date', $year)->orderBy('date')->paginate(12)->withQueryString();
        $employees = \App\Models\Hr\Employee::with(['department', 'designation'])->active()->get(['id', 'first_name', 'last_name', 'email', 'department_id', 'designation_id']);
        $departments = Department::orderBy('name')->get();
        $designations = Designation::orderBy('name')->get();

        return view('hr.holidays.index', compact('holidays', 'year', 'employees', 'departments', 'designations'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:date',
            'type' => 'required|in:public,company,optional',
            'description' => 'nullable|string',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:hr_employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->filled('edit_id')) {
            if (! auth()->user()->can('hr.holidays.edit')) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            $holiday = Holiday::findOrFail($request->edit_id);
            $oldYear = Carbon::parse($holiday->date)->year;
            $oldEndYear = $holiday->end_date ? Carbon::parse($holiday->end_date)->year : $oldYear;

            $holiday->update($request->except('employee_ids'));

            if ($request->has('employee_ids')) {
                $holiday->employees()->sync($request->employee_ids);
            } else {
                $holiday->employees()->sync([]);
            }

            // Clear cache for old and new year
            $newYear = Carbon::parse($request->date)->year;
            $newEndYear = $request->end_date ? Carbon::parse($request->end_date)->year : $newYear;

            $this->hrCache->clearHolidaysCache($oldYear);
            if ($oldYear !== $oldEndYear) {
                $this->hrCache->clearHolidaysCache($oldEndYear);
            }
            if ($oldYear !== $newYear) {
                $this->hrCache->clearHolidaysCache($newYear);
            }
            if ($newYear !== $newEndYear) {
                $this->hrCache->clearHolidaysCache($newEndYear);
            }

            $message = 'Holiday Updated Successfully';
        } else {
            if (! auth()->user()->can('hr.holidays.create')) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }

            $holiday = Holiday::create($request->except('employee_ids'));

            if ($request->has('employee_ids')) {
                $holiday->employees()->sync($request->employee_ids);
            }

            // Clear cache for the year
            $year = Carbon::parse($request->date)->year;
            $endYear = $request->end_date ? Carbon::parse($request->end_date)->year : $year;
            $this->hrCache->clearHolidaysCache($year);
            if ($year !== $endYear) {
                $this->hrCache->clearHolidaysCache($endYear);
            }

            $message = 'Holiday Created Successfully';
        }

        return response()->json(['success' => $message, 'reload' => true]);
    }

    public function assignEmployees(Request $request, Holiday $holiday)
    {
        if (! auth()->user()->can('hr.holidays.edit')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:hr_employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('employee_ids')) {
            $holiday->employees()->sync($request->employee_ids);
        } else {
            $holiday->employees()->sync([]);
        }

        return response()->json(['success' => 'Employees assigned successfully.', 'reload' => true]);
    }

    public function destroy($id)
    {
        if (! auth()->user()->can('hr.holidays.delete')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }
        $holiday = Holiday::findOrFail($id);

        // Capture year before deleting
        $year = Carbon::parse($holiday->date)->year;

        $holiday->delete();

        // Clear cache
        $this->hrCache->clearHolidaysCache($year);

        return response()->json(['success' => 'Holiday Deleted Successfully', 'reload' => true]);
    }

    /**
     * API to get holidays for calendar
     */
    public function getHolidays(Request $request)
    {
        if (! auth()->user()->can('hr.holidays.view')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }
        $year = $request->get('year', date('Y'));
        $month = $request->get('month');

        // Use cache for logic optimization
        $holidays = $this->hrCache->getHolidays($year);

        if ($month) {
            $holidays = $holidays->filter(function ($holiday) use ($month) {
                return Carbon::parse($holiday->date)->month == $month;
            })->values();
        }

        return response()->json($holidays);
    }
}
