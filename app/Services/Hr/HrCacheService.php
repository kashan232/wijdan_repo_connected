<?php

namespace App\Services\Hr;

use App\Models\Hr\Employee;
use App\Models\Hr\Holiday;
use App\Models\Hr\HrSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class HrCacheService
{
    // Cache Keys
    const KEY_HR_SETTINGS = 'hr.settings.all';

    const KEY_HOLIDAYS_YEAR = 'hr.holidays.year.';

    const KEY_EMPLOYEES_ACTIVE_LITE = 'hr.employees.active.lite';

    const KEY_SALARY_STRUCTURES_ACTIVE = 'hr.salary_structures.active'; // For quick lookup

    // Cache Durations (in seconds)
    const DURATION_LONG = 86400; // 24 hours

    const DURATION_MEDIUM = 3600; // 1 hour

    const DURATION_SHORT = 600;   // 10 minutes

    /**
     * Get all HR settings, cached.
     * Use this instead of querying HrSetting directly.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getSettings()
    {
        return Cache::remember(self::KEY_HR_SETTINGS, self::DURATION_LONG, function () {
            // Fetch all settings and map them key => value
            // Assuming HrSetting model has 'key', 'value', 'type' columns
            return HrSetting::all()->mapWithKeys(function ($setting) {
                return [$setting->key => $this->castValue($setting->value, $setting->type)];
            });
        });
    }

    /**
     * Get a specific setting value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getSetting($key, $default = null)
    {
        $settings = $this->getSettings();

        return $settings->get($key, $default);
    }

    /**
     * Clear settings cache. Call this after updating settings.
     */
    public function clearSettingsCache()
    {
        Cache::forget(self::KEY_HR_SETTINGS);
    }

    /**
     * Get holidays for a specific year, cached.
     *
     * @param  int|null  $year
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHolidays($year = null)
    {
        $year = $year ?? now()->year;

        return Cache::remember(self::KEY_HOLIDAYS_YEAR.$year, self::DURATION_LONG, function () use ($year) {
            return Holiday::with('employees')->where(function($q) use ($year) {
                $q->whereYear('date', $year)
                  ->orWhereYear('end_date', $year);
            })->orderBy('date')->get();
        });
    }

    /**
     * Check if a specific date is a holiday.
     * Uses the cached yearly holidays to avoid DB hits.
     *
     * @param  string|Carbon  $date
     * @return bool
     */
    public function isHoliday($date, $employeeId = null)
    {
        $date = Carbon::parse($date)->startOfDay();
        $holidays = $this->getHolidays($date->year);

        return $holidays->contains(function ($holiday) use ($date, $employeeId) {
            $startDate = Carbon::parse($holiday->date)->startOfDay();
            $endDate = $holiday->end_date ? Carbon::parse($holiday->end_date)->startOfDay() : $startDate;

            if ($date->betweenIncluded($startDate, $endDate)) {
                if ($employeeId && $holiday->employees->isNotEmpty()) {
                    return $holiday->employees->contains('id', $employeeId);
                }
                return true;
            }
            return false;
        });
    }

    /**
     * Clear holidays cache for a specific year or all.
     *
     * @param  int|null  $year
     */
    public function clearHolidaysCache($year = null)
    {
        if ($year) {
            Cache::forget(self::KEY_HOLIDAYS_YEAR.$year);
        } else {
            // If year not specified, we might need a way to clear all or just current/next year
            // For simplicity, let's clear current and next year as they are most likely to change
            Cache::forget(self::KEY_HOLIDAYS_YEAR.now()->year);
            Cache::forget(self::KEY_HOLIDAYS_YEAR.now()->addYear()->year);
        }
    }

    /**
     * Get a lite list of active employees (id, name, designation) for dropdowns.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getActiveEmployeesLite()
    {
        return Cache::remember(self::KEY_EMPLOYEES_ACTIVE_LITE, self::DURATION_MEDIUM, function () {
            return Employee::active()
                ->select('id', 'first_name', 'last_name', 'designation_id', 'department_id', 'face_photo')
                ->with(['designation:id,name', 'department:id,name'])
                ->orderBy('first_name')
                ->get()
                ->map(function ($emp) {
                    return [
                        'id' => $emp->id,
                        'name' => $emp->full_name,
                        'designation' => $emp->designation->name ?? '',
                        'department' => $emp->department->name ?? '',
                        'photo' => $emp->face_photo,
                    ];
                });
        });
    }

    /**
     * Clear active employees cache.
     */
    public function clearEmployeesCache()
    {
        Cache::forget(self::KEY_EMPLOYEES_ACTIVE_LITE);
    }

    // --- Helper Methods ---

    /**
     * Cast value to correct type (copied logic from HrSetting model)
     */
    protected function castValue($value, $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'float' => (float) $value,
            default => $value,
        };
    }
}
