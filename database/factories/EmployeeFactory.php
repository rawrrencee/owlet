<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'employee_number' => fake()->unique()->numerify('EMP-####'),
            'phone' => fake()->phoneNumber(),
            'mobile' => fake()->phoneNumber(),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'hire_date' => fake()->dateTimeBetween('-10 years', 'now'),
            'termination_date' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'termination_date' => fake()->dateTimeBetween($attributes['hire_date'] ?? '-1 year', 'now'),
        ]);
    }
}
