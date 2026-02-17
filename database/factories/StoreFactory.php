<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return [
            'store_name' => fake()->company(),
            'store_code' => strtoupper(fake()->unique()->lexify('???')),
            'active' => true,
            'include_tax' => false,
            'tax_percentage' => 0,
        ];
    }

    public function withTax(float $percentage = 7.00, bool $inclusive = false): static
    {
        return $this->state(fn () => [
            'include_tax' => $inclusive,
            'tax_percentage' => $percentage,
        ]);
    }
}
