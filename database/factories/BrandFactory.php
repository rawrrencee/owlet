<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'brand_name' => fake()->unique()->company(),
            'brand_code' => strtoupper(fake()->unique()->lexify('BRD-???')),
            'is_active' => true,
        ];
    }
}
