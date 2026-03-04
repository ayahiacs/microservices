<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // creating without the WithoutModelEvents trait so that model booted()
    // listeners run and the RabbitPublisher will publish the employee
    // use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            EmployeeSeeder::class,
        ]);
    }
}
