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

        $count = config('seeders.counts.brands', 3);

        for ($i = 1; $i <= $count; $i++) {
            $brandName = $faker->company();
            // Generate 4-char code: 3 letters + index number (max 4 chars)
            $brandCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $brandName), 0, 3)).($i % 10);

            Brand::firstOrCreate(
                ['brand_code' => $brandCode],
                [
                    'brand_name' => $brandName,
                    'brand_code' => $brandCode,
                    'description' => $faker->sentence(10),
                    'email' => $faker->companyEmail(),
                    'phone_number' => $faker->phoneNumber(),
                    'website' => $faker->url(),
                    'address_1' => $faker->streetAddress(),
                    'address_2' => $faker->optional(0.5)->secondaryAddress(),
                    'country_id' => $faker->randomElement($countries),
                    'is_active' => true,
                ]
            );
        }

        $this->command->info("  Created {$count} brands.");
    }
}
