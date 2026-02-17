<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubcategoryFactory extends Factory
{
    protected $model = Subcategory::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'subcategory_name' => fake()->unique()->word(),
            'subcategory_code' => strtoupper(fake()->unique()->lexify('SUB-???')),
            'is_active' => true,
        ];
    }
}
