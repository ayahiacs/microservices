<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class EmployeeService
{
    public function getEmployeesByCountry(string $country, int $page, int $perPage)
    {
        $cacheKey = sprintf('employees:all:%s:%d:%d', $country, $page, $perPage);

        // $payload = Cache::get($cacheKey);
        // if ($payload) {
        //     return response()->json($payload);
        // }

        // Build items by paginating directly against Redis sorted set
        $indexKey = sprintf('employees:list:%s', $country);
        // total count
        $total = Redis::zcard($indexKey) ?: 0;
        $offset = ($page - 1) * $perPage;
        $members = Redis::zrange($indexKey, $offset, $offset + $perPage - 1) ?: [];

        // deserialize employees list for this page
        $slice = [];
        $employeeMap = [];

        foreach ($members as $id) {
            $json = Redis::hget(sprintf('employees:map:%s', $country), $id);

            if ($json) {
                $emp = json_decode($json, true);
                if ($emp && isset($emp['id'])) {
                    $employeeMap[$emp['id']] = $emp;
                }
            }
            $emp = json_decode($json, true);
            if ($emp && isset($emp['id'])) {
                $slice[] = $emp['id'];
                $employeeMap[$emp['id']] = $emp;
            }
        }

        $items = [];
        foreach ($slice as $id) {
            // Use employee data from paged set only (Redis holds full payload)
            $employee = $employeeMap[$id] ?? null;
            if (! $employee) {
                continue;
            }

            // Map country-specific columns
            if ($country === 'USA') {
                $ssn = $employee['country_data']['ssn'] ?? null;
                $masked = $this->maskSSN($ssn);
                $row = [
                    'id' => $id,
                    'first_name' => $employee['first_name'] ?? null,
                    'last_name' => $employee['last_name'] ?? null,
                    'salary_per_annum' => $employee['salary_per_annum'] ?? null,
                    'ssn' => $masked,
                ];
                $columns = [
                    ['key' => 'first_name', 'label' => 'First name', 'order' => 1],
                    ['key' => 'last_name', 'label' => 'Last name', 'order' => 2],
                    ['key' => 'salary_per_annum', 'label' => 'Salary', 'order' => 3],
                    ['key' => 'ssn', 'label' => 'SSN', 'order' => 4, 'masked' => true],
                ];
            } else { // default => Germany behavior
                $goal = $employee['country_data']['goal'] ?? null;
                $row = [
                    'id' => $id,
                    'first_name' => $employee['first_name'] ?? null,
                    'last_name' => $employee['last_name'] ?? null,
                    'salary_per_annum' => $employee['salary_per_annum'] ?? null,
                    'goal' => $goal,
                ];
                $columns = [
                    ['key' => 'first_name', 'label' => 'First name', 'order' => 1],
                    ['key' => 'last_name', 'label' => 'Last name', 'order' => 2],
                    ['key' => 'salary_per_annum', 'label' => 'Salary', 'order' => 3],
                    ['key' => 'goal', 'label' => 'Goal', 'order' => 4],
                ];
            }

            $items[] = $row;
        }

        $payload = [
            'data' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
            'columns' => $columns ?? [],
        ];

        // cache for short time; processors will invalidate when events arrive
        Cache::put($cacheKey, $payload, 60);

        return $payload;
    }

    protected function maskSSN(?string $ssn): ?string
    {
        if (! $ssn) {
            return null;
        }

        // simple mask keep last 4 characters
        $len = strlen($ssn);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', max(0, $len - 4)).substr($ssn, -4);
    }
}
