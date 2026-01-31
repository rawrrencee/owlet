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

        $count = config('seeders.counts.companies', 2);

        for ($i = 1; $i <= $count; $i++) {
            $companyName = $faker->company();

            Company::firstOrCreate(
                ['company_name' => $companyName],
                [
                    'company_name' => $companyName,
                    'country_id' => $faker->randomElement($countries),
                    'address_1' => $faker->streetAddress(),
                    'address_2' => $faker->optional(0.5)->secondaryAddress(),
                    'email' => $faker->companyEmail(),
                    'phone_number' => $faker->phoneNumber(),
                    'mobile_number' => $faker->optional(0.6)->phoneNumber(),
                    'website' => $faker->optional(0.8)->url(),
                    'active' => true,
                ]
            );
        }

        $this->command->info("  Created {$count} companies.");
    }
}
