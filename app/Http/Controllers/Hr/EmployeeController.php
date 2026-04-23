<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Department;
use App\Models\Hr\Designation;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('hr.employees.view')) {
            abort(403, 'Unauthorized action.');
        }
        $employees = Employee::with(['department', 'designation', 'shift', 'leaves' => function ($q) {
            $q->where('leave_type', 'Casual');
        }])->latest()->paginate(12);
        $departments = Department::all();
        $designations = Designation::all();
        $shifts = \App\Models\Hr\Shift::all();

        return view('hr.employees.index', compact('employees', 'departments', 'designations', 'shifts'));
    }

    public function store(Request $request)
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|max:255|unique:hr_employees,email,'.$request->edit_id,
            'department_id' => 'required|exists:hr_departments,id',
            'designation_id' => 'required|exists:hr_designations,id',
            'joining_date' => 'required|date',
            'weekly_off' => 'nullable|string',
            'password' => $request->filled('edit_id') ? 'nullable|min:6' : 'required|min:6',
            'punch_gap_minutes' => 'nullable|integer|min:1|max:120',
            'document_degree' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
            'document_certificate' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
            'document_hsc_marksheet' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
            'document_ssc_marksheet' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
            'document_cv' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
            'casual_leaves_allocated' => 'nullable|integer|min:0',
            'sick_leaves_allocated' => 'nullable|integer|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['document_degree', 'document_certificate', 'document_hsc_marksheet', 'document_ssc_marksheet', 'document_cv', 'password']);
        $data['is_docs_submitted'] = $request->has('is_docs_submitted') ? 1 : 0;

        // Handle Shift Logic - convert empty strings to null
        if ($request->shift_id === 'custom') {
            $data['shift_id'] = null;
        } else {
            $data['shift_id'] = $request->filled('shift_id') ? $request->shift_id : null;
            // Standard shift assigned, clear custom times
            $data['custom_start_time'] = null;
            $data['custom_end_time'] = null;
        }

        if ($request->filled('edit_id')) {
            if (! auth()->user()->can('hr.employees.edit')) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            $employee = Employee::findOrFail($request->edit_id);

            // Update User email if changed
            if ($employee->user_id) {
                $user = \App\Models\User::find($employee->user_id);
                if ($user) {
                    $user->email = $request->email;
                    $user->name = $request->first_name.' '.$request->last_name;
                    if ($request->filled('password')) {
                        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
                    }
                    $user->save();
                }
            }

            $employee->update($data);
        } else {
            if (! auth()->user()->can('hr.employees.create')) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            // Create User Account
            $user = \App\Models\User::create([
                'name' => $request->first_name.' '.$request->last_name,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            ]);

            $data['user_id'] = $user->id;
            $employee = Employee::create($data);
        }

        // Handle File Uploads (Create/Update in hr_employee_documents)
        $fileFields = ['document_degree', 'document_certificate', 'document_hsc_marksheet', 'document_ssc_marksheet', 'document_cv'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $path = $file->store('employee_docs', 'public');

                $employee->documents()->updateOrCreate(
                    ['type' => str_replace('document_', '', $field)],
                    ['file_path' => $path, 'file_name' => $file->getClientOriginalName()]
                );
            }
        }

        // Clear relevant caches
        \Cache::forget('employee.face_encodings');
        \Cache::forget('hr.employees.active.lite');

        return response()->json([
            'success' => "👤 <b>Success!</b><br>Employee <b>'{$request->first_name} {$request->last_name}'</b> has been ".($request->filled('edit_id') ? 'updated' : 'created')." successfully.",
            'reload' => true
        ]);
    }

    public function destroy(Employee $employee)
    {
        if (! auth()->user()->can('hr.employees.delete')) {
            abort(403, 'Unauthorized action.');
        }
        // Delete User Account
        if ($employee->user_id) {
            \App\Models\User::destroy($employee->user_id);
        }
        // Delete all casual leave records for this employee
        $employee->leaves()->where('leave_type', 'Casual')->delete();
        $employee->delete();

        return response()->json([
            'success' => "🗑️ <b>Deleted!</b><br>Employee <b>'{$employee->full_name}'</b> (ID: #{$employee->id}) and all related records have been removed.",
            'reload' => true
        ]);
    }

    /**
     * Get face encodings for all employees (for Kiosk)
     * Cached for 1 hour to improve performance
     */
    public function getEncodings()
    {
        $data = \Cache::remember('employee.face_encodings', 3600, function () {
            $employees = Employee::whereNotNull('face_encoding')
                ->where('status', 'active')
                ->select('id', 'first_name', 'last_name', 'face_encoding', 'face_photo', 'designation_id', 'department_id')
                ->with(['department:id,name', 'designation:id,name'])
                ->get();

            return $employees->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->full_name,
                    'department' => $emp->department->name ?? 'N/A',
                    'designation' => $emp->designation->name ?? 'N/A',
                    'photo' => $emp->face_photo ? asset($emp->face_photo) : null,
                    'descriptor' => $emp->face_encoding,
                ];
            });
        });

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Expires', now()->addHour()->toRfc7231String());
    }

    /**
     * Store face encoding for an employee
     */
    /**
     * Store face encoding for an employee
     */
    public function storeFace(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:hr_employees,id',
            'descriptor' => 'required|array',
            'descriptor.*' => 'numeric', // Validate each element is numeric
            'image' => 'nullable|string', // Base64 image
            'force_override' => 'nullable|boolean', // Allow admin to override duplicate warning
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate descriptor format
        $faceService = app(\App\Services\Hr\FaceRecognitionService::class);
        if (! $faceService->isValidDescriptor($request->descriptor)) {
            return response()->json([
                'error' => 'Invalid face descriptor format. Expected 128-dimensional array.',
            ], 422);
        }

        $employee = Employee::findOrFail($request->employee_id);

        // Check face uniqueness (exclude current employee if updating)
        $uniquenessCheck = $faceService->checkFaceUniqueness(
            $request->descriptor,
            $employee->id
        );

        // If face is not unique and user hasn't forced override
        if (! $uniquenessCheck['is_unique']) {
            if ($request->force_override) {
                if (! auth()->user()->can('hr.employees.delete')) {
                    return response()->json(['error' => 'You do not have permission to override duplicate face warnings.'], 403);
                }
                // If authorized, proceed to registration
                \Illuminate\Support\Facades\Log::warning("Face override authorized by user ".auth()->id()." for employee ID {$employee->id}");
            } else {
                $similarEmployee = $uniquenessCheck['similar_employee'];

                // Check if user has permission to override
                $canOverride = auth()->user()->can('hr.employees.delete'); // Using delete permission as "admin" level

                return response()->json([
                    'error' => 'duplicate_face',
                    'message' => sprintf(
                        'This face is already registered to %s (ID: %d). Similarity: %s%%',
                        $similarEmployee->full_name,
                        $similarEmployee->id,
                        $uniquenessCheck['similarity_percentage']
                    ),
                    'similar_employee' => [
                        'id' => $similarEmployee->id,
                        'name' => $similarEmployee->full_name,
                        'department' => $similarEmployee->department->name ?? 'N/A',
                        'designation' => $similarEmployee->designation->name ?? 'N/A',
                    ],
                    'similarity_percentage' => $uniquenessCheck['similarity_percentage'],
                    'distance' => $uniquenessCheck['distance'],
                    'can_override' => $canOverride,
                ], 409); // 409 Conflict
            }
        }

        // Proceed with registration
        $employee->face_encoding = $request->descriptor;

        // Save face photo if provided
        if ($request->image) {
            $imageData = explode(',', $request->image);
            if (count($imageData) > 1) {
                $decoded = base64_decode($imageData[1]);
                $fileName = 'face_'.$employee->id.'_'.time().'.jpg';
                $path = 'uploads/faces/';

                if (! file_exists(public_path($path))) {
                    mkdir(public_path($path), 0755, true);
                }

                file_put_contents(public_path($path.$fileName), $decoded);
                $employee->face_photo = $path.$fileName;
            }
        }

        $employee->save();

        // Log the registration
        \Illuminate\Support\Facades\Log::info('Face registered for employee', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'registered_by' => auth()->id(),
            'was_override' => $request->force_override ?? false,
        ]);

        // Clear face encodings cache to ensure fresh data on next request
        \Cache::forget('employee.face_encodings');

        return response()->json([
            'success' => "📸 <b>Face Registered!</b><br>Biometric face profile for <b>{$employee->full_name}</b> has been saved successfully.",
            'was_override' => $request->force_override ?? false,
        ]);
    }

}
