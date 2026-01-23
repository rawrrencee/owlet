<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'mobile' => fake()->optional()->phoneNumber(),
            'date_of_birth' => fake()->optional()->dateTimeBetween('-80 years', '-18 years'),
            'gender' => fake()->optional()->randomElement(['male', 'female', 'other']),
            'race' => null,
            'company_name' => fake()->optional()->company(),
            'discount_percentage' => 0,
            'loyalty_points' => 0,
            'customer_since' => fake()->dateTimeBetween('-5 years', 'now'),
            'notes' => fake()->optional()->sentence(),
            'image_url' => null,
        ];
    }

    public function withDiscount(float $percentage = 10.00): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_percentage' => $percentage,
        ]);
    }

    public function withLoyaltyPoints(int $points = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'loyalty_points' => $points,
        ]);
    }
}
