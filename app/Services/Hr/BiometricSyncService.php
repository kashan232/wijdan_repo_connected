<?php

namespace App\Services\Hr;

use App\Models\BiometricDevice;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\HrSetting;
use Carbon\Carbon;

class BiometricSyncService
{
    protected BiometricDeviceService $deviceService;

    public function __construct(BiometricDeviceService $deviceService)
    {
        $this->deviceService = $deviceService;
    }

    /**
     * Sync single employee to device
     */
    public function syncEmployeeToDevice(Employee $employee, BiometricDevice $device): array
    {
        // Generate device user ID if not exists
        if (! $employee->device_user_id) {
            $employee->device_user_id = $this->generateDeviceUserId($device);
        } else {
            // Check for potential duplicate ID conflict on this device
            $conflict = Employee::where('biometric_device_id', $device->id)
                ->where('device_user_id', $employee->device_user_id)
                ->where('id', '!=', $employee->id)
                ->exists();

            if ($conflict) {
                // ID conflict detected! Regenerate ID for this user
                $employee->device_user_id = $this->generateDeviceUserId($device);
            }
        }

        // Ensure strictly linked to this device
        if ($employee->biometric_device_id !== $device->id) {
            $employee->biometric_device_id = $device->id;
            $employee->save();
        }

        // Add user to device
        $success = $this->deviceService->addUserToDevice(
            $device,
            $employee->device_user_id,
            $employee->full_name
        );

        if ($success) {
            $employee->last_device_sync_at = now();
            $employee->save();

            return [
                'success' => true,
                'message' => 'Employee synced successfully. Device User ID: '.$employee->device_user_id,
                'device_user_id' => $employee->device_user_id,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to sync employee to device.',
        ];
    }

    /**
     * Sync all employees to device
     */
    public function syncAllEmployeesToDevice(BiometricDevice $device): array
    {
        $employees = Employee::where('status', 'active')->get();
        $synced = 0;
        $failed = 0;

        foreach ($employees as $employee) {
            $result = $this->syncEmployeeToDevice($employee, $device);
            if ($result['success']) {
                $synced++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => true,
            'synced' => $synced,
            'failed' => $failed,
            'message' => "Synced {$synced} employees. Failed: {$failed}",
        ];
    }

    /**
     * Get punch gap minutes from global settings
     */
    protected function getPunchGapMinutes(): int
    {
        return HrSetting::getPunchGapMinutes();
    }

    /**
     * Pull attendance logs from device and create attendance records
     */
    public function pullAttendanceFromDevice(BiometricDevice $device): array
    {
        $logs = $this->deviceService->getAttendanceLogs($device);

        if (empty($logs)) {
            return [
                'success' => false,
                'message' => 'No attendance logs found on device.',
                'created' => 0,
                'skipped' => 0,
                'duplicates' => 0,
                'failed' => 0,
            ];

        }

        // Sort logs by timestamp to ensure we process them in order
        usort($logs, function ($a, $b) {
            return strtotime($a['timestamp']) <=> strtotime($b['timestamp']);
        });

        $created = 0;
        $skipped = 0;
        $duplicates = 0;

        foreach ($logs as $log) {
            // Find employee by device_user_id
            $employee = Employee::where('device_user_id', $log['id'])->first();

            if (! $employee) {
                $skipped++;
                continue;
            }

            // Parse timestamp
            $timestamp = Carbon::parse($log['timestamp']);
            $gap = $this->getPunchGapMinutes();

            // 1. Check for OPEN Session
            $openSession = Attendance::where('employee_id', $employee->id)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->latest('check_in_time')
                ->first();

            // Safety check: If open session is too old (e.g., > 20 hours), assume it's a forgotten check-out
            // and treat this new punch as a NEW Check-In for the current day.
            if ($openSession) {
                $checkInTime = Carbon::parse($openSession->check_in_time);
                if ($checkInTime->diffInHours($timestamp) > 20) {
                    $openSession = null; // Ignore old session, force new check-in
                }
            }

            if ($openSession) {
                // We have an open session. Check if this punch updates it (OUT) or is duplicate (IN)
                $checkInTime = Carbon::parse($openSession->check_in_time);
                
                if ($timestamp->lte($checkInTime)) {
                     $duplicates++; // Out of order
                     continue;
                }

                $diff = $checkInTime->diffInMinutes($timestamp);
                
                if ($diff < $gap) {
                    $duplicates++; // Duplicate IN within gap
                    continue;
                }

                // Treat as Check-Out
                $openSession->check_out_time = $timestamp->toDateTimeString();
                $openSession->check_out_location = 'Biometric Device';
                
                // Early Leave Calculation
                $shift = $employee->shift ?? \App\Models\Hr\Shift::where('is_default', true)->first();
                if ($shift) {
                    $endTimeStr = $employee->custom_end_time ?: ($shift->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('H:i:s') : null);
                     if ($endTimeStr) {
                         // Shift End Logic handling Overnight
                         // Use attendance date
                         $attDate = Carbon::parse($openSession->date);
                         $shiftEnd = Carbon::parse($attDate->format('Y-m-d').' '.$endTimeStr);
                         
                         $sStart = $employee->getStartTime();
                         if ($endTimeStr < $sStart) { // Simple AM/PM heuristic or comparing strings
                              $shiftEnd->addDay();
                         }
                         
                         if ($timestamp->lt($shiftEnd)) {
                             $openSession->is_early_leave = true;
                             $openSession->early_leave_minutes = $timestamp->diffInMinutes($shiftEnd);
                         } else {
                             $openSession->is_early_leave = false;
                             $openSession->early_leave_minutes = 0;
                         }
                     }
                }

                $this->calculateTotalHours($openSession);
                $openSession->save();
                $created++; // Updated effectively
                \Log::info("Biometric: Check-Out for {$employee->full_name} at {$timestamp}.");

            } else {
                // No Open Session. Check if it's a Duplicate OUT (Start of new session too soon after last out)
                $lastClosedSession = Attendance::where('employee_id', $employee->id)
                    ->whereNotNull('check_out_time')
                    ->latest('check_out_time')
                    ->first();

                if ($lastClosedSession) {
                     $lastOut = Carbon::parse($lastClosedSession->check_out_time);
                     if ($timestamp->lte($lastOut)) { $duplicates++; continue; }
                     if ($timestamp->diffInMinutes($lastOut) < $gap) {
                         $duplicates++; // Duplicate OUT
                         continue;
                     }
                }

                // Create NEW Session (Check-In)
                $date = $timestamp->toDateString();
                
                // Late Logic
                $shift = $employee->shift ?? \App\Models\Hr\Shift::where('is_default', true)->first();
                $isLate = false;
                $lateMinutes = 0;

                if ($shift) {
                    $timeStr = null;
                    if ($employee->custom_start_time) {
                        $timeStr = $employee->custom_start_time;
                    } elseif ($shift->start_time) {
                        $timeStr = \Carbon\Carbon::parse($shift->start_time)->format('H:i:s');
                    }

                    if ($timeStr) {
                        $shiftStart = Carbon::parse($date.' '.$timeStr);
                        $lateThreshold = $shiftStart->copy()->addMinutes($shift->grace_minutes ?? 0);

                        if ($timestamp->gt($lateThreshold)) {
                            $isLate = true;
                            $lateMinutes = $shiftStart->diffInMinutes($timestamp);
                        }
                    }
                }

                // Check if we already have a record for this date (Re-open session)
                $existingAttendance = Attendance::where('employee_id', $employee->id)
                    ->where('date', $date)
                    ->first();

                if ($existingAttendance) {
                    // Re-open or Update the existing session
                    if (empty($existingAttendance->check_in_time)) {
                        // This might be an absent or leave record. Overwrite it with the actual punch.
                        $existingAttendance->status = $isLate ? 'late' : 'present';
                        $existingAttendance->check_in_time = $timestamp->toDateTimeString();
                        $existingAttendance->check_in_location = 'Biometric Device';
                        $existingAttendance->is_late = $isLate;
                        $existingAttendance->late_minutes = $lateMinutes;
                    } else {
                        // Keep the original check_in_time (First In) and clear the check_out_time to re-open
                        $existingAttendance->check_out_time = null;
                        $existingAttendance->check_out_location = null;
                    }
                    $existingAttendance->save();
                    
                    $created++; // meaningful update
                    \Log::info("Biometric: Updated/Re-opened session for {$employee->full_name} at {$timestamp}.");
                } else {
                    // Create NEW Session (Check-In)
                    $attendance = Attendance::create([
                        'employee_id' => $employee->id,
                        'date' => $date,
                        'status' => $isLate ? 'late' : 'present',
                        'check_in_time' => $timestamp->toDateTimeString(),
                        'check_in_location' => 'Biometric Device',
                        'is_late' => $isLate,
                        'late_minutes' => $lateMinutes,
                    ]);
                    
                    $created++;
                    \Log::info("Biometric: Check-In for {$employee->full_name} at {$timestamp}.");
                }
            }
        }

        // Update device last sync time
        $device->last_sync_at = now();
        $device->save();

        return [
            'success' => true,
            'created' => $created,
            'skipped' => $skipped,
            'duplicates' => $duplicates,
            'failed' => 0,
            'failed' => 0,
            'last_log_date' => $lastLogDate ?? null,
            'message' => "Processed attendance. Synced: {$created}, Skipped (no employee): {$skipped}, Duplicates ignored: {$duplicates}" . (isset($lastLogDate) ? ". Latest Date: $lastLogDate" : ""),
        ];
    }

    /**
     * Calculate and set total hours worked
     */
    protected function calculateTotalHours(Attendance $attendance): void
    {
        if ($attendance->check_in_time && $attendance->check_out_time) {
            $checkIn  = Carbon::parse($attendance->check_in_time);
            $checkOut = Carbon::parse($attendance->check_out_time);
            // Handle overnight shift: if checkout is before checkin, add 1 day
            if ($checkOut->lt($checkIn)) {
                $checkOut->addDay();
            }
            $attendance->total_hours = round($checkOut->diffInMinutes($checkIn) / 60, 2);
        }
    }

    /**
     * Generate unique device user ID
     */
    protected function generateDeviceUserId(BiometricDevice $device): string
    {
        // Get the highest device_user_id for this device
        $lastEmployee = Employee::where('biometric_device_id', $device->id)
            ->whereNotNull('device_user_id')
            ->orderByRaw('CAST(device_user_id AS UNSIGNED) DESC')
            ->first();

        $nextId = $lastEmployee ? (int) $lastEmployee->device_user_id + 1 : 1;

        return (string) $nextId;
    }
}
