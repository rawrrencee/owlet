<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Country;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Seed product brands using Faker.
     */
    public function run(): void
    {
        $this->command->info('Seeding brands...');

        $faker = Faker::create();
        $countries = Country::where('active', true)->pluck('id')->toArray();

        $count = config('seeders.counts.brands', 300);
        $batchSize = 50;
        $brands = [];
        $createdCount = 0;

        for ($i = 1; $i <= $count; $i++) {
            $brandName = $faker->unique()->company();
            // Generate unique 4-char code: B + 3 digits (supports up to 999)
            $brandCode = 'B'.str_pad($i, 3, '0', STR_PAD_LEFT);

            $brands[] = [
                'brand_name' => $brandName,
                'brand_code' => $brandCode,
                'description' => $faker->sentence(10),
                'email' => $faker->unique()->companyEmail(),
                'phone_number' => $faker->phoneNumber(),
                'website' => $faker->url(),
                'address_1' => $faker->streetAddress(),
                'address_2' => $faker->optional(0.5)->secondaryAddress(),
                'country_id' => $faker->randomElement($countries),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($brands) >= $batchSize) {
                Brand::insert($brands);
                $createdCount += count($brands);
                $brands = [];
            }
        }

        // Insert remaining
        if (! empty($brands)) {
            Brand::insert($brands);
            $createdCount += count($brands);
        }

        $this->command->info("  Created {$createdCount} brands.");
    }
}
