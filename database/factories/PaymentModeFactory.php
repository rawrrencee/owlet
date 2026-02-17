<?php

namespace Database\Factories;

use App\Models\PaymentMode;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentModeFactory extends Factory
{
    protected $model = PaymentMode::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn () => [
            'name' => 'Cash',
            'code' => 'CASH',
        ]);
    }

    public function card(): static
    {
        return $this->state(fn () => [
            'name' => 'Card',
            'code' => 'CARD',
        ]);
    }
}
