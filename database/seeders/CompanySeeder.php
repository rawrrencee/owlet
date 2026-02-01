<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Country;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Seed companies using Faker.
     */
    public function run(): void
    {
        $this->command->info('Seeding companies...');

        $faker = Faker::create();
        $countries = Country::where('active', true)->pluck('id')->toArray();

        $count = config('seeders.counts.companies', 30);
        $batchSize = 50;
        $companies = [];
        $createdCount = 0;

        for ($i = 1; $i <= $count; $i++) {
            $companyName = $faker->unique()->company();

            $companies[] = [
                'company_name' => $companyName,
                'country_id' => $faker->randomElement($countries),
                'address_1' => $faker->streetAddress(),
                'address_2' => $faker->optional(0.5)->secondaryAddress(),
                'email' => $faker->unique()->companyEmail(),
                'phone_number' => $faker->phoneNumber(),
                'mobile_number' => $faker->optional(0.6)->phoneNumber(),
                'website' => $faker->optional(0.8)->url(),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($companies) >= $batchSize) {
                Company::insert($companies);
                $createdCount += count($companies);
                $companies = [];
            }
        }

        // Insert remaining
        if (! empty($companies)) {
            Company::insert($companies);
            $createdCount += count($companies);
        }

        $this->command->info("  Created {$createdCount} companies.");
    }
}
