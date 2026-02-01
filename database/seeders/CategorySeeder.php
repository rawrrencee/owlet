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

        $categoryCount = config('seeders.counts.categories', 50);
        $subcategoryCount = config('seeders.counts.subcategories_per_category', 6);

        $batchSize = 50;
        $categories = [];
        $totalCategories = 0;
        $totalSubcategories = 0;

        // First, create all categories
        for ($i = 1; $i <= $categoryCount; $i++) {
            $categoryName = ucfirst($faker->unique()->word()).' '.$faker->word();
            // Generate unique 4-char code: C + 3 chars (base-36 supports up to 46655)
            $categoryCode = 'C'.strtoupper(str_pad(base_convert($i, 10, 36), 3, '0', STR_PAD_LEFT));

            $categories[] = [
                'category_name' => $categoryName,
                'category_code' => $categoryCode,
                'description' => $faker->sentence(8),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($categories) >= $batchSize) {
                Category::insert($categories);
                $totalCategories += count($categories);
                $categories = [];
            }
        }

        // Insert remaining categories
        if (! empty($categories)) {
            Category::insert($categories);
            $totalCategories += count($categories);
        }

        // Now create subcategories for each category
        $allCategories = Category::all();
        $subcategories = [];
        $subIndex = 1;

        foreach ($allCategories as $categoryIndex => $category) {
            for ($j = 1; $j <= $subcategoryCount; $j++) {
                $subcategoryName = ucfirst($faker->word());
                // Generate unique 4-char code: S + 3 chars (base-36 supports up to 46655)
                $subcategoryCode = 'S'.strtoupper(str_pad(base_convert($subIndex, 10, 36), 3, '0', STR_PAD_LEFT));

                $subcategories[] = [
                    'category_id' => $category->id,
                    'subcategory_name' => $subcategoryName,
                    'subcategory_code' => $subcategoryCode,
                    'description' => $faker->sentence(6),
                    'is_default' => $j === 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $subIndex++;

                if (count($subcategories) >= $batchSize) {
                    Subcategory::insert($subcategories);
                    $totalSubcategories += count($subcategories);
                    $subcategories = [];
                }
            }
        }

        // Insert remaining subcategories
        if (! empty($subcategories)) {
            Subcategory::insert($subcategories);
            $totalSubcategories += count($subcategories);
        }

        $this->command->info("  Created {$totalCategories} categories with {$totalSubcategories} subcategories.");
    }
}
