<?php

namespace App\Services;

class StepService
{
    public function getSteps(string $country): array
    {
        return match ($country) {
            'USA' => $this->getUsaSteps(),
            'Germany' => $this->getGermanySteps(),
            default => $this->getUsaSteps(),
        };
    }

    private function getUsaSteps(): array
    {
        return [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'order' => 1, 'path' => '/dashboard'],
            ['id' => 'employees', 'label' => 'Employees', 'icon' => 'users', 'order' => 2, 'path' => '/employees'],
        ];
    }

    private function getGermanySteps(): array
    {
        return [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'order' => 1, 'path' => '/dashboard'],
            ['id' => 'employees', 'label' => 'Employees', 'icon' => 'users', 'order' => 2, 'path' => '/employees'],
            ['id' => 'documentation', 'label' => 'Documentation', 'icon' => 'book', 'order' => 3, 'path' => '/docs'],
        ];
    }
}
