<?php

namespace App\Processors;

use Illuminate\Support\Facades\Log;

class EmployeeUpdatedProcessor extends BaseProcessor
{
    public function process(array $event): void
    {
        try {
            ['id' => $employeeId, 'data' => $employee, 'country' => $country] = $this->extractEmployeeData($event);

            $this->updateEmployeeInCountryIndex($employeeId, $employee, $country);
            $this->invalidateCountryCaches($country);
            $this->broadcastEmployeeEvent('employee_updated', $employeeId, $employee);

            Log::info('Processed EmployeeUpdated', ['id' => $employeeId]);
        } catch (\Throwable $e) {
            Log::error('EmployeeUpdatedProcessor error', ['error' => $e->getMessage(), 'data' => $event]);
            throw $e;
        }
    }
}
