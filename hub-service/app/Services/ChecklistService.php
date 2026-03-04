<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ChecklistService
{
    /**
     * Get comprehensive checklist for all employees in a country.
     */
    public static function getChecklistByCountry(string $country): array
    {
        $cacheKey = sprintf('checklists:%s', $country);
        // try to get from cache
        $cached = Cache::get($cacheKey);
        if ($cached) {
            Log::info('Cache hit for checklist', ['key' => $cacheKey]);

            return $cached;
        }
        Log::info('Cache miss for checklist', ['key' => $cacheKey]);

        // Fetch employee data from Redis sorted set for the country and paginate
        $indexKey = sprintf('employees:list:%s', $country);
        $total = Redis::zcard($indexKey) ?: 0;
        $members = Redis::zrange($indexKey, 0, -1) ?: [];

        // Deserialize members and build ID list for this page
        $ids = [];
        $employeeMap = [];

        foreach ($members as $id) {
            $json = Redis::hget(sprintf('employees:map:%s', $country), $id);
            $emp = json_decode($json, true);
            if ($emp && isset($emp['id'])) {
                $ids[] = $emp['id'];
                $employeeMap[$emp['id']] = $emp;
            }
        }
        $slice = $ids;

        // Build checklist for each employee
        $employees = [];
        $globalCompletion = [];

        foreach ($slice as $id) {
            // Use employee data from sorted set if available, else fetch from cache
            $employee = $employeeMap[$id] ?? Cache::get(sprintf('employee:%s', $id));
            if (! $employee) {
                continue;
            }

            $fieldStatus = ChecklistValidator::validate($employee, $country);
            $completion = ChecklistValidator::calculateCompletion($fieldStatus);

            // Map field names to human-readable labels
            $formattedFields = [];
            $fieldMap = [
                'country_data.ssn' => 'ssn',
                'country_data.address' => 'address',
                'country_data.goal' => 'goal',
                'country_data.tax_id' => 'tax_id',
                'salary_per_annum' => 'salary',
            ];

            foreach ($fieldStatus as $field => $info) {
                $label = $fieldMap[$field] ?? $field;
                $formattedFields[$label] = $info;
            }

            $employees[] = [
                'id' => $id,
                'first_name' => $employee['first_name'] ?? null,
                'last_name' => $employee['last_name'] ?? null,
                'completion_percentage' => $completion,
                'fields' => $formattedFields,
                'incomplete_fields' => array_values(array_filter($formattedFields, fn ($s) => ! $s['complete'])),
                'complete_fields' => array_values(array_filter($formattedFields, fn ($s) => $s['complete'])),
            ];

            $globalCompletion[] = $completion;
        }

        // Calculate overall statistics
        $result = [
            'country' => $country,
            'meta' => [
                'total' => $total,
            ],
            'statistics' => [
                'total_employees' => $total,
                'average_completion' => ! empty($globalCompletion) ? round(array_sum($globalCompletion) / count($globalCompletion), 2) : 0,
                'complete_employees' => count(array_filter($globalCompletion, fn ($c) => $c === 100.0)),
                'incomplete_employees' => count(array_filter($globalCompletion, fn ($c) => $c < 100.0)),
            ],
            'employees' => $employees,
        ];

        Cache::put($cacheKey, $result, 300);

        return $result;
    }

    /**
     * Get checklist for a single employee.
     */
    public static function getChecklistForEmployee(string $employeeId, string $country): ?array
    {
        $country = strtoupper($country);
        $cacheKey = sprintf('checklist:employee:%s:%s', $employeeId, $country);

        // Try cache
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Fetch employee
        $employee = Cache::get(sprintf('employee:%s', $employeeId));
        if (! $employee) {
            return null;
        }

        $fieldStatus = ChecklistValidator::validate($employee, $country);
        $completion = ChecklistValidator::calculateCompletion($fieldStatus);

        $result = [
            'id' => $employeeId,
            'first_name' => $employee['first_name'] ?? null,
            'last_name' => $employee['last_name'] ?? null,
            'country' => $country,
            'completion_percentage' => $completion,
            'fields' => $fieldStatus,
            'incomplete_fields' => array_values(array_filter($fieldStatus, fn ($s) => ! $s['complete'])),
            'complete_fields' => array_values(array_filter($fieldStatus, fn ($s) => $s['complete'])),
        ];

        // Cache for 10 minutes
        Cache::put($cacheKey, $result, 600);

        return $result;
    }

    /**
     * Invalidate all checklist caches for a country.
     */
    public static function invalidateCountryCaches(string $country): void
    {
        try {
            $country = strtoupper($country);
            $key = sprintf('checklists:%s', $country);
            Cache::forget($key);
            Log::info('Invalidated checklist caches for country', ['country' => $country]);
        } catch (\Throwable $e) {
            Log::error('Failed to invalidate checklist caches', ['error' => $e->getMessage(), 'country' => $country]);
        }
    }

    /**
     * Invalidate checklist cache for a specific employee.
     */
    public static function invalidateEmployeeCache(string $employeeId, string $country = ''): void
    {
        $cacheKey = sprintf('checklist:employee:%s:%s', $employeeId, strtoupper($country));
        try {
            Cache::forget($cacheKey);
        } catch (\Throwable $e) {
            Log::error('Failed to invalidate employee checklist cache', ['error' => $e->getMessage(), 'key' => $cacheKey]);
        }
    }
}
