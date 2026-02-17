<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'subcategory_id' => Subcategory::factory(),
            'supplier_id' => Supplier::factory(),
            'product_name' => fake()->words(3, true),
            'product_number' => strtoupper(fake()->unique()->bothify('PRD-####')),
            'barcode' => fake()->unique()->ean13(),
            'is_active' => true,
        ];
    }

    public function forCategory(int $categoryId): static
    {
        return $this->state(fn () => [
            'category_id' => $categoryId,
        ]);
    }
}
