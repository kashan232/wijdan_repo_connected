<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\BiometricDevice;
use App\Services\Hr\BiometricDeviceService;
use App\Services\Hr\BiometricSyncService;
use App\Jobs\Hr\SyncBiometricDataJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class BiometricDeviceController extends Controller
{
    protected BiometricDeviceService $deviceService;
    protected BiometricSyncService $syncService;

    public function __construct(BiometricDeviceService $deviceService, BiometricSyncService $syncService)
    {
        $this->deviceService = $deviceService;
        $this->syncService = $syncService;
    }

    /**
     * Display listing of biometric devices
     */
    public function index()
    {
        $devices = BiometricDevice::with('employees')->latest()->get();
        return view('hr.biometric-devices.index', compact('devices'));
    }

    /**
     * Store a new device
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'model' => 'nullable|string',
            'protocol' => 'nullable|string|in:auto,zkteco,hikvision',
            'notes' => 'nullable|string',
        ]);

        // Encrypt password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Crypt::encryptString($validated['password']);
        }

        $device = BiometricDevice::create($validated);

        // Test connection
        $testResult = $this->deviceService->testConnection($device);

        if ($testResult['success']) {
            return response()->json([
                'success' => "📠 <b>Device Added!</b><br>Biometric device <b>'{$device->name}'</b> joined and connection verified.",
                'reload' => true,
            ]);
        } else {
            // Return as 200 but with warning message in success (since device IS created)
            // Or ideally we should probably use a warning type, but for now success with detailed msg
            return response()->json([
                'success' => "⚠️ <b>Warning!</b><br>Device <b>'{$device->name}'</b> was added, but we couldn't connect: " . $testResult['message'],
                'reload' => true,
            ]);
        }
    }

    /**
     * Update device
     */
    public function update(Request $request, BiometricDevice $device)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'model' => 'nullable|string',
            'protocol' => 'nullable|string|in:auto,zkteco,hikvision',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Encrypt password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Crypt::encryptString($validated['password']);
        } else {
            unset($validated['password']); // Don't update if empty
        }

        $device->update($validated);

        return response()->json([
            'success' => "⚙️ <b>Updated!</b><br>Device settings for <b>'{$device->name}'</b> have been saved.",
            'reload' => true,
        ]);
    }

    /**
     * Delete device
     */
    public function destroy(BiometricDevice $device)
    {
        $device->delete();

        return response()->json([
            'success' => "🗑️ <b>Removed!</b><br>Device <b>'{$device->name}'</b> has been deleted.",
            'reload' => true,
        ]);
    }

    /**
     * Test device connection
     */
    public function testConnection(BiometricDevice $device)
    {
        $result = $this->deviceService->testConnection($device);

        return response()->json($result);
    }

    /**
     * Sync Device Time
     */
    public function syncTime(BiometricDevice $device)
    {
        $success = $this->deviceService->syncTime($device);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => "🕒 <b>Time Synced!</b><br>Device <b>'{$device->name}'</b> clock has been synchronized with the server.",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "❌ <b>Sync Failed!</b><br>Could not synchronize time with <b>'{$device->name}'</b>. Ensure the device is online and supports time synchronization.",
        ], 500);
    }

    /**
     * Sync all employees to device
     */
    public function syncEmployees(BiometricDevice $device)
    {
        $result = $this->syncService->syncAllEmployeesToDevice($device);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'synced' => $result['synced'],
            'failed' => $result['failed'],
        ]);
    }

    /**
     * Pull attendance logs from device
     */
    public function pullAttendance(BiometricDevice $device)
    {
        $result = $this->syncService->pullAttendanceFromDevice($device);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'created' => $result['created'] ?? 0,
            'skipped' => $result['skipped'] ?? 0,
        ]);
    }

    /**
     * Pull attendance logs from device in background
     */
    public function pullAttendanceBackground(BiometricDevice $device)
    {
        SyncBiometricDataJob::dispatch($device->id, auth()->id(), 'attendance');

        return response()->json([
            'success' => true,
            'message' => "⏳ <b>Sync Started!</b><br>Attendance data is being pulled from <b>{$device->name}</b> in the background.",
        ]);
    }

    /**
     * Sync all employees to device in background
     */
    public function syncEmployeesBackground(BiometricDevice $device)
    {
        SyncBiometricDataJob::dispatch($device->id, auth()->id(), 'employees');

        return response()->json([
            'success' => true,
            'message' => "⏳ <b>Employee Sync Started!</b><br>Uploading data to <b>{$device->name}</b>. This will complete in the background.",
        ]);
    }
}
