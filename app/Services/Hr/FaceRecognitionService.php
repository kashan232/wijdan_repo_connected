<?php

namespace App\Services\Hr;

use App\Models\Hr\Employee;
use Illuminate\Support\Facades\Log;

class FaceRecognitionService
{
    /**
     * Default similarity threshold for face matching
     * Lower values = stricter matching (more similar required)
     * Higher values = looser matching (less similar required)
     */
    const DEFAULT_THRESHOLD = 0.45;

    /**
     * Check if a face descriptor is unique (not already registered)
     *
     * @param  array  $descriptor  Face descriptor array (128 dimensions)
     * @param  int|null  $excludeEmployeeId  Employee ID to exclude from check (for updates)
     * @return array ['is_unique' => bool, 'similar_employee' => Employee|null, 'distance' => float|null]
     */
    public function checkFaceUniqueness(array $descriptor, ?int $excludeEmployeeId = null): array
    {
        $threshold = config('hr.face_similarity_threshold', self::DEFAULT_THRESHOLD);

        $query = Employee::whereNotNull('face_encoding')
            ->where('status', 'active');

        if ($excludeEmployeeId) {
            $query->where('id', '!=', $excludeEmployeeId);
        }

        $employees = $query->get();

        foreach ($employees as $employee) {
            if (empty($employee->face_encoding)) {
                continue;
            }

            $distance = $this->calculateEuclideanDistance($descriptor, $employee->face_encoding);

            // If distance is below threshold, faces are too similar (likely same person)
            if ($distance < $threshold) {
                return [
                    'is_unique' => false,
                    'similar_employee' => $employee,
                    'distance' => $distance,
                    'similarity_percentage' => max(0, round((1 - $distance) * 100, 2)),
                ];
            }
        }

        return [
            'is_unique' => true,
            'similar_employee' => null,
            'distance' => null,
            'similarity_percentage' => 0,
        ];
    }

    /**
     * Verify that a face descriptor matches a specific employee's registered face
     *
     * @param  array  $descriptor  Face descriptor to verify
     * @param  int  $employeeId  Employee ID to verify against
     * @param  float|null  $customThreshold  Optional custom threshold
     * @return array ['verified' => bool, 'distance' => float, 'similarity_percentage' => float]
     */
    public function verifyFaceForEmployee(array $descriptor, int $employeeId, ?float $customThreshold = null): array
    {
        $employee = Employee::find($employeeId);

        if (! $employee || empty($employee->face_encoding)) {
            return [
                'verified' => false,
                'distance' => 999,
                'similarity_percentage' => 0,
                'error' => 'Employee not found or no face registered',
            ];
        }

        $threshold = $customThreshold ?? config('hr.face_similarity_threshold', self::DEFAULT_THRESHOLD);
        $distance = $this->calculateEuclideanDistance($descriptor, $employee->face_encoding);
        $similarityPercentage = round((1 - $distance) * 100, 2);

        return [
            'verified' => $distance < $threshold,
            'distance' => $distance,
            'similarity_percentage' => $similarityPercentage,
            'threshold_used' => $threshold,
        ];
    }

    /**
     * Find all employees with similar faces to the given descriptor
     *
     * @param  array  $descriptor  Face descriptor to search for
     * @param  float|null  $threshold  Custom threshold (default: 0.45)
     * @return array Array of ['employee' => Employee, 'distance' => float, 'similarity' => float]
     */
    public function findSimilarFaces(array $descriptor, ?float $threshold = null): array
    {
        $threshold = $threshold ?? config('hr.face_similarity_threshold', self::DEFAULT_THRESHOLD);
        $similarFaces = [];

        $employees = Employee::whereNotNull('face_encoding')
            ->where('status', 'active')
            ->get();

        foreach ($employees as $employee) {
            if (empty($employee->face_encoding)) {
                continue;
            }

            $distance = $this->calculateEuclideanDistance($descriptor, $employee->face_encoding);

            if ($distance < $threshold) {
                $similarFaces[] = [
                    'employee' => $employee,
                    'distance' => $distance,
                    'similarity_percentage' => round((1 - $distance) * 100, 2),
                ];
            }
        }

        // Sort by distance (most similar first)
        usort($similarFaces, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        return $similarFaces;
    }

    /**
     * Calculate Euclidean distance between two face descriptors
     * Lower distance = more similar faces
     *
     * @param  array  $descriptor1  First face descriptor (128 dimensions)
     * @param  array  $descriptor2  Second face descriptor (128 dimensions)
     * @return float Euclidean distance
     */
    public function calculateEuclideanDistance($descriptor1, $descriptor2): float
    {
        // Ensure inputs are arrays
        if (! is_array($descriptor1)) {
            $descriptor1 = (array) $descriptor1;
        }
        if (! is_array($descriptor2)) {
            $descriptor2 = (array) $descriptor2;
        }

        if (count($descriptor1) !== count($descriptor2)) {
            Log::warning('Face descriptor dimension mismatch', [
                'desc1_count' => count($descriptor1),
                'desc2_count' => count($descriptor2),
            ]);

            return 999; // Return high distance for invalid comparison
        }

        $sum = 0;
        for ($i = 0; $i < count($descriptor1); $i++) {
            $diff = $descriptor1[$i] - $descriptor2[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    /**
     * Compare two faces and return similarity information
     *
     * @param  array  $descriptor1  First face descriptor
     * @param  array  $descriptor2  Second face descriptor
     * @return array ['distance' => float, 'similarity_percentage' => float, 'are_similar' => bool]
     */
    public function compareFaces(array $descriptor1, array $descriptor2): array
    {
        $distance = $this->calculateEuclideanDistance($descriptor1, $descriptor2);
        $threshold = config('hr.face_similarity_threshold', self::DEFAULT_THRESHOLD);

        $similarity = round((1 - $distance) * 100, 2);

        return [
            'distance' => $distance,
            'similarity_percentage' => max(0, $similarity),
            'are_similar' => $distance < $threshold,
            'threshold_used' => $threshold,
        ];
    }

    /**
     * Validate face descriptor format
     *
     * @param  mixed  $descriptor  Descriptor to validate
     */
    public function isValidDescriptor($descriptor): bool
    {
        if (! is_array($descriptor)) {
            return false;
        }

        // Face-api.js descriptors are 128-dimensional
        if (count($descriptor) !== 128) {
            return false;
        }

        // All values should be numeric
        foreach ($descriptor as $value) {
            if (! is_numeric($value)) {
                return false;
            }
        }

        return true;
    }
}
