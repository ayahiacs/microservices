<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $country = $this->faker->randomElement(['USA', 'Germany']);

        // build country-specific data
        $countryData = [];
        if ($country === 'USA') {
            $countryData = [
                'ssn' => $this->faker->numerify('###-##-####'),
                'address' => $this->faker->address(),
            ];
        } else {
            $countryData = [
                'tax_id' => 'DE'.$this->faker->numerify('#########'),
                'goal' => $this->faker->sentence(),
            ];
        }

        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'salary_per_annum' => $this->faker->numberBetween(50000, 100000),
            'country' => $country,
            'country_data' => $countryData,
        ];
    }
}
