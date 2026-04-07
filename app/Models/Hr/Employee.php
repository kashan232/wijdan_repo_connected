<?php

namespace App\Models\Hr;

use App\Models\User;
use App\Models\Hr\EmployeeSalaryStructure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'hr_employees';

    protected $fillable = [
        'user_id',
        'department_id',
        'designation_id',
        'shift_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'is_docs_submitted',
        'date_of_birth',
        'joining_date',
        'status',
        'custom_start_time',
        'custom_end_time',
        'face_encoding',
        'face_photo',
        'biometric_device_id',
        'device_user_id',
        'fingerprint_enrolled_at',
        'last_device_sync_at',
        'punch_gap_minutes',
        'pending_deductions',
        'weekly_off',
        'casual_leaves_allocated',
        'sick_leaves_allocated',
    ];

    protected $casts = [
        'face_encoding' => 'array',
        'fingerprint_enrolled_at' => 'datetime',
        'last_device_sync_at' => 'datetime',
    ];

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function getDocument($type)
    {
        return $this->documents()->where('type', $type)->first()->file_path ?? null;
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function holidays()
    {
        return $this->belongsToMany(Holiday::class, 'hr_employee_holiday', 'employee_id', 'holiday_id')->withTimestamps();
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * Legacy relationship (for backward compatibility)
     *
     * @deprecated Use salaryStructures() or activeSalaryStructure() instead
     */
    public function salaryStructure()
    {
        return $this->hasOne(SalaryStructure::class);
    }

    /**
     * Many-to-many: All salary structure assignments (history)
     */
    // public function salaryStructures()
    // {
    //     // Pivot table does not exist
    // }

    /**
     * Get active salary structure assignment
     */
    /**
     * Get active salary structure assignment (Direct relationship)
     */
    public function activeSalaryStructure()
    {
        return $this->hasOne(SalaryStructure::class)->latest('id');
    }

    /**
     * Get the actual salary structure related to the active assignment.
     */
    public function getSalaryStructureAttribute()
    {
        return $this->activeSalaryStructure;
    }

    /**
     * Get all salary structure assignment records
     */
    // public function salaryStructureAssignments()
    // {
    //     // Pivot does not exist
    // }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the effective start time (custom or shift)
     */
    public function getStartTime()
    {
        if ($this->custom_start_time) {
            return $this->custom_start_time;
        }

        return $this->shift ? $this->shift->start_time : '09:00:00';
    }

    /**
     * Get the effective end time (custom or shift)
     */
    public function getEndTime()
    {
        if ($this->custom_end_time) {
            return $this->custom_end_time;
        }

        return $this->shift ? $this->shift->end_time : '18:00:00';
    }

    /**
     * Get grace minutes from shift or default
     */
    public function getGraceMinutes()
    {
        return $this->shift ? $this->shift->grace_minutes : 15;
    }

    /**
     * Check if employee has registered face
     */
    public function hasFaceRegistered()
    {
        return ! empty($this->face_encoding);
    }

    /**
     * Get biometric device relationship
     */
    public function biometricDevice()
    {
        return $this->belongsTo(\App\Models\BiometricDevice::class, 'biometric_device_id');
    }

    /**
     * Check if employee has fingerprint enrolled on device
     */
    public function hasFingerprint()
    {
        return ! empty($this->device_user_id) && ! empty($this->fingerprint_enrolled_at);
    }

    // ==================== QUERY SCOPES ====================

    /**
     * Scope: Active employees only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Non-active employees
     */
    public function scopeNonActive($query)
    {
        return $query->where('status', 'non-active');
    }

    /**
     * Scope: Terminated employees
     */
    public function scopeTerminated($query)
    {
        return $query->where('status', 'terminated');
    }

    /**
     * Scope: Employees with daily wages enabled
     */
    public function scopeWithDailyWages($query)
    {
        return $query->whereHas('activeSalaryStructure', function ($q) {
            $q->where('use_daily_wages', true);
        });
    }

    /**
     * Scope: Employees with commission enabled
     */
    public function scopeWithCommission($query)
    {
        return $query->whereHas('activeSalaryStructure', function ($q) {
            $q->whereNotNull('commission_percentage')
              ->where('commission_percentage', '>', 0);
        });
    }

    /**
     * Scope: Employees eligible for monthly payroll
     * Includes: (1) employees without daily wages, OR (2) employees with commission
     */
    public function scopeForMonthlyPayroll($query)
    {
        return $query->active()
            ->whereHas('activeSalaryStructure', function ($q) {
                $q->where(function ($sq) {
                    // Pure monthly employees (no daily wages)
                    $sq->where(function ($ssq) {
                        $ssq->where('use_daily_wages', false)
                           ->orWhereNull('use_daily_wages');
                    })
                    // OR employees with commission (regardless of daily wages)
                    ->orWhere(function ($ssq) {
                        $ssq->whereNotNull('commission_percentage')
                           ->where('commission_percentage', '>', 0);
                    });
                });
            });
    }

    /**
     * Scope: Employees eligible for daily payroll
     * Includes: employees with daily wages AND no commission
     */
    public function scopeForDailyPayroll($query)
    {
        return $query->active()
            ->whereHas('activeSalaryStructure', function ($q) {
                $q->where('use_daily_wages', true)
                  ->where(function ($sq) {
                      // No commission OR commission is 0
                      $sq->whereNull('commission_percentage')
                         ->orWhere('commission_percentage', '=', 0);
                  });
            });
    }

    /**
     * Scope: Employees by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope: Employees by designation
     */
    public function scopeByDesignation($query, $designationId)
    {
        return $query->where('designation_id', $designationId);
    }

    /**
     * Scope: Employees with custom timings
     */
    public function scopeWithCustomTimings($query)
    {
        return $query->whereNotNull('custom_start_time')
                     ->whereNotNull('custom_end_time');
    }

    /**
     * Scope: Employees with default shift
     */
    public function scopeWithDefaultShift($query)
    {
        return $query->whereNull('custom_start_time')
                     ->whereNull('custom_end_time');
    }

    /**
     * Scope: Employees with face registered
     */
    public function scopeWithFaceRegistered($query)
    {
        return $query->whereNotNull('face_encoding');
    }

    /**
     * Scope: Employees with fingerprint enrolled
     */
    public function scopeWithFingerprint($query)
    {
        return $query->whereNotNull('device_user_id')
                     ->whereNotNull('fingerprint_enrolled_at');
    }

    /**
     * Scope: Search employees by name or email
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
}
