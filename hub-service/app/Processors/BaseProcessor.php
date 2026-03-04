<?php

namespace App\Processors;

use App\Services\ChecklistService;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

abstract class BaseProcessor
{
    abstract public function process(array $data): void;

    protected function cacheKey(string $type, $id): string
    {
        return sprintf('%s:%s', $type, $id);
    }

    protected function updateCache(string $key, $value, int $ttl = 300): void
    {
        try {
            Cache::put($key, $value, $ttl);
        } catch (\Throwable $e) {
            Log::error('Cache put failed', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    protected function invalidateCache(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (\Throwable $e) {
            Log::error('Cache forget failed', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    protected function broadcast(string $channel, array $payload): void
    {
        try {
            event(new \App\Events\HubEvent($channel, $payload));
        } catch (\Throwable $e) {
            Log::error('Broadcast failed', ['channel' => $channel, 'error' => $e->getMessage()]);
        }
    }

    protected function extractEmployeeData(array $event): array
    {
        $employeeId = $event['data']['employee_id'] ?? $event['data']['employee']['id'] ?? null;
        $employee = $event['data']['employee'] ?? [];

        if (! $employeeId) {
            throw new \InvalidArgumentException('Missing employee id');
        }

        return [
            'id' => $employeeId,
            'data' => $employee,
            'country' => strtoupper($employee['country'] ?? ''),
        ];
    }

    protected function updateEmployeeInCountryIndex(string $employeeId, array $employee, string $country): void
    {
        if (! $country) {
            return;
        }

        $mapKey = sprintf('employees:map:%s', $country);

        try {
            Redis::hset($mapKey, $employeeId, json_encode($employee));
            Redis::expire($mapKey, 3600);
        } catch (\Throwable $e) {
            Log::error('Failed updating employees map index', [
                'error' => $e->getMessage(),
                'key' => $mapKey,
                'employee_id' => $employeeId,
            ]);
        }
    }

    protected function addEmployeeToCountryIndex(string $employeeId, array $employee, string $country): void
    {
        if (! $country) {
            return;
        }

        $mapKey = sprintf('employees:map:%s', $country);
        $listKey = sprintf('employees:list:%s', $country);

        try {
            // Calculate score from ULID or use timestamp
            $score = $this->calculateEmployeeScore($employeeId);

            Redis::zadd($listKey, $score, $employeeId);
            Redis::expire($listKey, 3600);
            Redis::hset($mapKey, $employeeId, json_encode($employee));
            Redis::expire($mapKey, 3600);
        } catch (\Throwable $e) {
            Log::error('Failed updating employees list index (create)', [
                'error' => $e->getMessage(),
                'key' => $mapKey,
                'employee_id' => $employeeId,
            ]);
        }
    }

    protected function removeEmployeeFromCountryIndex(string $employeeId, string $country): void
    {
        if (! $country) {
            return;
        }

        $indexKey = sprintf('employees:list:%s', $country);

        try {
            // Remove from Redis sorted set by finding and removing employee
            $members = Redis::zrange($indexKey, 0, -1);
            foreach ($members as $member) {
                $emp = json_decode($member, true);
                if (isset($emp['id']) && $emp['id'] === $employeeId) {
                    Redis::zrem($indexKey, $member);
                    break;
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed updating employees list index (delete)', [
                'error' => $e->getMessage(),
                'key' => $indexKey,
                'employee_id' => $employeeId,
            ]);
        }

        $this->invalidateCache(sprintf('employees:all:%s', $country));
    }

    protected function broadcastEmployeeEvent(string $eventType, string $employeeId, array $employee = []): void
    {
        $country = strtoupper($employee['country'] ?? '');

        // Broadcast to employee-specific channel
        $this->broadcast(sprintf('employee.%s', $employeeId), $employee);

        // Broadcast to country-specific checklist channel
        if ($country) {
            $this->broadcast(sprintf('checklists.%s', $country), [
                'event' => $eventType,
                'employee_id' => $employeeId,
            ]);

            // Broadcast to employee-specific checklist channel
            $this->broadcast(sprintf('checklist.employee.%s', $employeeId), [
                'event' => str_replace('employee_', '', $eventType),
            ]);
        }
    }

    protected function invalidateCountryCaches(string $country): void
    {
        if ($country) {
            ChecklistService::invalidateCountryCaches($country);
        }
    }

    private function calculateEmployeeScore(string $employeeId): int
    {
        try {
            $ulid = \Symfony\Component\Uid\Ulid::fromString($employeeId);
            $date = $ulid->getDateTime();
            $timestampMs = (int) ($date->format('Uv'));

            return $timestampMs > 0 ? $timestampMs : time();
        } catch (\Throwable $e) {
            return time();
        }
    }
}
