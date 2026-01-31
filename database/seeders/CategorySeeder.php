<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed categories and subcategories using Faker.
     */
    public function run(): void
    {
        $this->command->info('Seeding categories and subcategories...');

        $faker = Faker::create();

        $categoryCount = config('seeders.counts.categories', 3);
        $subcategoryCount = config('seeders.counts.subcategories_per_category', 2);

        $totalCategories = 0;
        $totalSubcategories = 0;

        for ($i = 1; $i <= $categoryCount; $i++) {
            $categoryName = ucfirst($faker->unique()->word());
            // Generate 4-char code: 3 letters + index
            $categoryCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $categoryName), 0, 3)).($i % 10);

            $category = Category::firstOrCreate(
                ['category_code' => $categoryCode],
                [
                    'category_name' => $categoryName,
                    'category_code' => $categoryCode,
                    'description' => $faker->sentence(8),
                    'is_active' => true,
                ]
            );
            $totalCategories++;

            // Create subcategories for this category
            for ($j = 1; $j <= $subcategoryCount; $j++) {
                $subcategoryName = ucfirst($faker->word());
                // Generate 4-char code: 2 letters + category index + subcategory index
                $subcategoryCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $subcategoryName), 0, 2)).($i % 10).($j % 10);

                Subcategory::firstOrCreate(
                    [
                        'category_id' => $category->id,
                        'subcategory_code' => $subcategoryCode,
                    ],
                    [
                        'category_id' => $category->id,
                        'subcategory_name' => $subcategoryName,
                        'subcategory_code' => $subcategoryCode,
                        'description' => $faker->sentence(6),
                        'is_default' => $j === 1,
                        'is_active' => true,
                    ]
                );
                $totalSubcategories++;
            }
        }

        $this->command->info("  Created {$totalCategories} categories with {$totalSubcategories} subcategories.");
    }
}
