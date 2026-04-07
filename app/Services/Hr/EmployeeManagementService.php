<?php

namespace App\Services\Hr;

use App\Models\Hr\Employee;
use App\Models\Hr\Department;
use App\Models\Hr\Designation;
use App\Models\Hr\EmployeeDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class EmployeeManagementService
{
    protected $hrCache;

    public function __construct(HrCacheService $hrCache)
    {
        $this->hrCache = $hrCache;
    }

    /**
     * Create a new employee
     *
     * @param array $data
     * @return Employee
     */
    public function createEmployee(array $data)
    {
        DB::beginTransaction();

        try {
            // Create user account if email is provided
            $user = null;
            if (!empty($data['email'])) {
                $user = $this->createUserAccount($data);
            }

            // Prepare employee data
            $employeeData = $this->prepareEmployeeData($data);
            if ($user) {
                $employeeData['user_id'] = $user->id;
            }

            // Create employee
            $employee = Employee::create($employeeData);

            // Handle documents if provided
            if (!empty($data['documents'])) {
                $this->handleDocuments($employee, $data['documents']);
            }

            // Handle face photo if provided
            if (!empty($data['face_photo'])) {
                $this->handleFacePhoto($employee, $data['face_photo']);
            }

            DB::commit();

            $this->clearCaches();

            return $employee;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing employee
     *
     * @param Employee $employee
     * @param array $data
     * @return Employee
     */
    public function updateEmployee(Employee $employee, array $data)
    {
        DB::beginTransaction();

        try {
            // Update user account if exists and email changed
            if ($employee->user && !empty($data['email']) && $data['email'] !== $employee->email) {
                $this->updateUserAccount($employee->user, $data);
            }

            // Prepare and update employee data
            $employeeData = $this->prepareEmployeeData($data);
            $employee->update($employeeData);

            // Handle documents if provided
            if (!empty($data['documents'])) {
                $this->handleDocuments($employee, $data['documents']);
            }

            // Handle face photo if provided
            if (!empty($data['face_photo'])) {
                $this->handleFacePhoto($employee, $data['face_photo']);
            }

            DB::commit();

            $this->clearCaches();

            return $employee;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete an employee
     *
     * @param Employee $employee
     * @param bool $deleteUser Also delete associated user account
     * @return bool
     */
    public function deleteEmployee(Employee $employee, $deleteUser = false)
    {
        DB::beginTransaction();

        try {
            // Delete documents
            $this->deleteEmployeeDocuments($employee);

            // Delete face photo
            if ($employee->face_photo) {
                Storage::delete($employee->face_photo);
            }

            // Delete user account if requested
            if ($deleteUser && $employee->user) {
                $employee->user->delete();
            }

            // Delete employee
            $employee->delete();

            DB::commit();

            $this->clearCaches();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Change employee status
     *
     * @param Employee $employee
     * @param string $status active|non-active|terminated
     * @return Employee
     */
    public function changeStatus(Employee $employee, $status)
    {
        $validStatuses = ['active', 'non-active', 'terminated'];

        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $employee->update(['status' => $status]);

        // Deactivate user account if terminated
        if ($status === 'terminated' && $employee->user) {
            $employee->user->update(['is_active' => false]);
        }

        $this->clearCaches();

        return $employee;
    }

    /**
     * Transfer employee to different department/designation
     *
     * @param Employee $employee
     * @param int|null $departmentId
     * @param int|null $designationId
     * @return Employee
     */
    public function transferEmployee(Employee $employee, $departmentId = null, $designationId = null)
    {
        $updates = [];

        if ($departmentId) {
            $updates['department_id'] = $departmentId;
        }

        if ($designationId) {
            $updates['designation_id'] = $designationId;
        }

        if (!empty($updates)) {
            $employee->update($updates);
            $this->clearCaches(); // Stats (designation/dept counts?) - Lite list changed
        }

        return $employee;
    }

    /**
     * Assign shift to employee
     *
     * @param Employee $employee
     * @param int|null $shiftId
     * @param string|null $customStartTime
     * @param string|null $customEndTime
     * @return Employee
     */
    public function assignShift(Employee $employee, $shiftId = null, $customStartTime = null, $customEndTime = null)
    {
        $updates = [
            'shift_id' => $shiftId,
            'custom_start_time' => $customStartTime,
            'custom_end_time' => $customEndTime,
        ];

        $employee->update($updates);
        $this->clearCaches(); // Stats change (custom timings)

        return $employee;
    }

    /**
     * Register employee face encoding
     *
     * @param Employee $employee
     * @param array $faceEncoding
     * @param string|null $facePhoto
     * @return Employee
     */
    public function registerFace(Employee $employee, array $faceEncoding, $facePhoto = null)
    {
        $updates = ['face_encoding' => $faceEncoding];

        if ($facePhoto) {
            // Delete old photo if exists
            if ($employee->face_photo) {
                Storage::delete($employee->face_photo);
            }

            $updates['face_photo'] = $facePhoto;
        }

        $employee->update($updates);
        $this->clearCaches(); // Stats change (face registered)

        return $employee;
    }

    /**
     * Enroll employee fingerprint on biometric device
     *
     * @param Employee $employee
     * @param int $deviceId
     * @param string $deviceUserId
     * @return Employee
     */
    public function enrollFingerprint(Employee $employee, $deviceId, $deviceUserId)
    {
        $employee->update([
            'biometric_device_id' => $deviceId,
            'device_user_id' => $deviceUserId,
            'fingerprint_enrolled_at' => now(),
            'last_device_sync_at' => now(),
        ]);

        $this->clearCaches(); // Stats change (fingerprint)

        return $employee;
    }

    /**
     * Prepare employee data from input
     *
     * @param array $data
     * @return array
     */
    protected function prepareEmployeeData(array $data)
    {
        return array_filter([
            'department_id' => $data['department_id'] ?? null,
            'designation_id' => $data['designation_id'] ?? null,
            'shift_id' => $data['shift_id'] ?? null,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'joining_date' => $data['joining_date'] ?? null,
            'status' => $data['status'] ?? 'active',
            'custom_start_time' => $data['custom_start_time'] ?? null,
            'custom_end_time' => $data['custom_end_time'] ?? null,
            'punch_gap_minutes' => $data['punch_gap_minutes'] ?? null,
            'is_docs_submitted' => $data['is_docs_submitted'] ?? false,
        ], function ($value) {
            return $value !== null;
        });
    }

    /**
     * Create user account for employee
     *
     * @param array $data
     * @return User
     */
    protected function createUserAccount(array $data)
    {
        return User::create([
            'name' => ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''),
            'email' => $data['email'],
            'password' => Hash::make($data['password'] ?? 'password123'),
            'is_active' => true,
        ]);
    }

    /**
     * Update user account
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    protected function updateUserAccount(User $user, array $data)
    {
        $updates = [
            'name' => ($data['first_name'] ?? $user->name) . ' ' . ($data['last_name'] ?? ''),
            'email' => $data['email'] ?? $user->email,
        ];

        if (!empty($data['password'])) {
            $updates['password'] = Hash::make($data['password']);
        }

        $user->update($updates);

        return $user;
    }

    /**
     * Handle employee documents upload
     *
     * @param Employee $employee
     * @param array $documents
     * @return void
     */
    protected function handleDocuments(Employee $employee, array $documents)
    {
        foreach ($documents as $type => $file) {
            if ($file) {
                // Delete old document if exists
                $oldDoc = $employee->documents()->where('type', $type)->first();
                if ($oldDoc) {
                    Storage::delete($oldDoc->file_path);
                    $oldDoc->delete();
                }

                // Store new document
                $path = $file->store('employee_documents', 'public');

                EmployeeDocument::create([
                    'employee_id' => $employee->id,
                    'type' => $type,
                    'file_path' => $path,
                ]);
            }
        }
    }

    /**
     * Handle face photo upload
     *
     * @param Employee $employee
     * @param mixed $photo
     * @return void
     */
    protected function handleFacePhoto(Employee $employee, $photo)
    {
        // Delete old photo
        if ($employee->face_photo) {
            Storage::delete($employee->face_photo);
        }

        // Store new photo
        $path = $photo->store('face_photos', 'public');
        $employee->update(['face_photo' => $path]);
    }

    /**
     * Delete all employee documents
     *
     * @param Employee $employee
     * @return void
     */
    protected function deleteEmployeeDocuments(Employee $employee)
    {
        foreach ($employee->documents as $document) {
            Storage::delete($document->file_path);
            $document->delete();
        }
    }

    /**
     * Get employee statistics
     *
     * @return array
     */
    public function getEmployeeStatistics()
    {
        return Cache::remember('hr.employee.stats', 3600, function () {
            return [
                'total' => Employee::count(),
                'active' => Employee::active()->count(),
                'non_active' => Employee::nonActive()->count(),
                'terminated' => Employee::terminated()->count(),
                'with_daily_wages' => Employee::withDailyWages()->count(),
                'with_commission' => Employee::withCommission()->count(),
                'with_face_registered' => Employee::withFaceRegistered()->count(),
                'with_fingerprint' => Employee::withFingerprint()->count(),
                'with_custom_timings' => Employee::withCustomTimings()->count(),
            ];
        });
    }

    /**
     * Helper to clear all employee-related caches
     */
    protected function clearCaches()
    {
        $this->hrCache->clearEmployeesCache();
        Cache::forget('hr.employee.stats');
    }

    /**
     * Validate employee data
     *
     * @param array $data
     * @param bool $isUpdate
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateEmployeeData(array $data, $isUpdate = false)
    {
        $errors = [];

        if (!$isUpdate || isset($data['first_name'])) {
            if (empty($data['first_name'])) {
                $errors[] = 'First name is required';
            }
        }

        if (!$isUpdate || isset($data['last_name'])) {
            if (empty($data['last_name'])) {
                $errors[] = 'Last name is required';
            }
        }

        if (!$isUpdate || isset($data['email'])) {
            if (empty($data['email'])) {
                $errors[] = 'Email is required';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
        }

        if (!$isUpdate || isset($data['department_id'])) {
            if (empty($data['department_id']) || !Department::find($data['department_id'])) {
                $errors[] = 'Valid department is required';
            }
        }

        if (!$isUpdate || isset($data['designation_id'])) {
            if (empty($data['designation_id']) || !Designation::find($data['designation_id'])) {
                $errors[] = 'Valid designation is required';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
