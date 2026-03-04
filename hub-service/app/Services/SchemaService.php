<?php

namespace App\Services;

class SchemaService
{
    public function getDashboardWidgets(string $country): array
    {
        return match ($country) {
            'USA' => $this->getUsaDashboardWidgets(),
            'Germany' => $this->getGermanyDashboardWidgets(),
            default => [],
        };
    }

    private function getUsaDashboardWidgets(): array
    {
        return [
            [
                'id' => 'employee_count',
                'type' => 'stat',
                'title' => 'Employee count',
                'data_source' => ['type' => 'rest', 'endpoint' => '/api/employees', 'params' => ['country' => 'USA', 'per_page' => 1]],
                'real_time_channel' => 'employees.summary',
            ],
            [
                'id' => 'average_salary',
                'type' => 'stat',
                'title' => 'Average salary',
                'data_source' => ['type' => 'rest', 'endpoint' => '/api/employees/summary', 'params' => ['country' => 'USA']],
                'real_time_channel' => 'employees.summary',
            ],
            [
                'id' => 'completion_rate',
                'type' => 'percent',
                'title' => 'Completion rate',
                'data_source' => ['type' => 'rest', 'endpoint' => '/api/metrics/completion', 'params' => ['country' => 'USA']],
                'real_time_channel' => 'employees.completion',
            ],
        ];
    }

    private function getGermanyDashboardWidgets(): array
    {
        return [
            [
                'id' => 'employee_count',
                'type' => 'stat',
                'title' => 'Employee count',
                'data_source' => ['type' => 'rest', 'endpoint' => '/api/employees', 'params' => ['country' => 'Germany', 'per_page' => 1]],
                'real_time_channel' => 'employees.summary',
            ],
            [
                'id' => 'goal_tracking',
                'type' => 'chart',
                'title' => 'Goal tracking',
                'data_source' => ['type' => 'rest', 'endpoint' => '/api/employees/goal-tracking', 'params' => ['country' => 'Germany']],
                'real_time_channel' => 'employees.goal',
            ],
        ];
    }

    public function validateStep(string $step): bool
    {
        return $step === 'dashboard';
    }
}
