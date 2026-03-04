<?php

namespace App\Processors;

use Illuminate\Support\Facades\Log;

class EmployeeDeletedProcessor extends BaseProcessor
{
    public function process(array $event): void
    {
        try {
            ['id' => $employeeId, 'data' => $employee, 'country' => $country] = $this->extractEmployeeData($event);

            // Remove single employee cache
            $this->invalidateCache($this->cacheKey('employee', $employeeId));

            // Remove from country index
            $this->removeEmployeeFromCountryIndex($employeeId, $country);

            // Invalidate global lists
            $this->invalidateCache('employees:all');

            // Invalidate checklist caches and broadcast
            $this->invalidateCountryCaches($country);
            $this->broadcastEmployeeEvent('employee_deleted', $employeeId, ['id' => $employeeId, 'deleted' => true]);

            Log::info('Processed EmployeeDeleted', ['id' => $employeeId]);
        } catch (\Throwable $e) {
            Log::error('EmployeeDeletedProcessor error', ['error' => $e->getMessage(), 'data' => $event]);
            throw $e;
        }
    }
}
