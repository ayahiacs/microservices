<?php

namespace App\Processors;

use Illuminate\Support\Facades\Log;

class EmployeeCreatedProcessor extends BaseProcessor
{
    public function process(array $event): void
    {
        try {
            ['id' => $employeeId, 'data' => $employee, 'country' => $country] = $this->extractEmployeeData($event);

            $this->addEmployeeToCountryIndex($employeeId, $employee, $country);
            $this->invalidateCountryCaches($country);
            $this->broadcastEmployeeEvent('employee_created', $employeeId, $employee);

            Log::info('Processed EmployeeCreated', ['id' => $employeeId]);
        } catch (\Throwable $e) {
            Log::error('EmployeeCreatedProcessor error', ['error' => $e->getMessage(), 'data' => $event]);
            throw $e;
        }
    }
}
