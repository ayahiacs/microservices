<?php

namespace App\Services;

class ChecklistValidator
{
    /**
     * Validate an employee against country-specific rules.
     * Returns array of field statuses.
     */
    public static function validate(array $employee, string $country): array
    {
        $country = strtoupper($country);
        $rules = self::getRules($country);
        $status = [];

        foreach ($rules as $field => $rule) {
            $value = self::getNestedValue($employee, $field);
            $isComplete = self::checkField($value, $rule);
            $status[$field] = [
                'complete' => $isComplete,
                'message' => $isComplete ? "✅ {$field} is complete" : "❌ {$field} is missing or invalid",
                'value' => $value,
            ];
        }

        return $status;
    }

    /**
     * Get country-specific validation rules.
     */
    private static function getRules(string $country): array
    {
        if ($country === 'USA') {
            return [
                'country_data.ssn' => ['required' => true, 'pattern' => '/^\d{3}-\d{2}-\d{4}$/'],
                'salary_per_annum' => ['required' => true, 'min' => 1],
                'country_data.address' => ['required' => true],
            ];
        } elseif ($country === 'GERMANY') {
            return [
                'salary_per_annum' => ['required' => true, 'min' => 1],
                'country_data.goal' => ['required' => true],
                'country_data.tax_id' => ['required' => true, 'pattern' => '/^DE\d{9}$/'],
            ];
        }

        return [];
    }

    /**
     * Check if a field meets its validation rule.
     */
    private static function checkField($value, array $rule): bool
    {
        if ($rule['required'] ?? false) {
            if ($value === null || $value === '' || $value === 0) {
                return false;
            }
        }

        if (isset($rule['pattern'])) {
            return (bool) preg_match($rule['pattern'], (string) $value);
        }

        if (isset($rule['min'])) {
            return (int) $value >= $rule['min'];
        }

        return true;
    }

    /**
     * Get nested value from array using dot notation (e.g., 'country_data.ssn').
     */
    private static function getNestedValue(array $data, string $path)
    {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Calculate completion percentage from field statuses.
     */
    public static function calculateCompletion(array $fieldStatus): float
    {
        if (empty($fieldStatus)) {
            return 0;
        }

        $completed = count(array_filter($fieldStatus, fn ($s) => $s['complete']));

        return round(($completed / count($fieldStatus)) * 100, 2);
    }
}
