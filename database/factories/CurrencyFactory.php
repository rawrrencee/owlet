<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->currencyCode(),
            'name' => fake()->word() . ' Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'active' => true,
            'exchange_rate' => 1.0,
        ];
    }

    public function sgd(): static
    {
        return $this->state(fn () => [
            'code' => 'SGD',
            'name' => 'Singapore Dollar',
            'symbol' => 'S$',
        ]);
    }

    public function usd(): static
    {
        return $this->state(fn () => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
        ]);
    }
}
