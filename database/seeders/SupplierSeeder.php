<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Supplier;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Seed suppliers using Faker.
     */
    public function run(): void
    {
        $this->command->info('Seeding suppliers...');

        $faker = Faker::create();
        $countries = Country::where('active', true)->pluck('id')->toArray();

        $count = config('seeders.counts.suppliers', 3);

        for ($i = 1; $i <= $count; $i++) {
            $supplierName = $faker->company().' '.$faker->companySuffix();

            Supplier::firstOrCreate(
                ['supplier_name' => $supplierName],
                [
                    'supplier_name' => $supplierName,
                    'country_id' => $faker->randomElement($countries),
                    'address_1' => $faker->streetAddress(),
                    'address_2' => $faker->optional(0.5)->secondaryAddress(),
                    'email' => $faker->companyEmail(),
                    'phone_number' => $faker->phoneNumber(),
                    'mobile_number' => $faker->optional(0.7)->phoneNumber(),
                    'website' => $faker->optional(0.8)->url(),
                    'description' => $faker->paragraph(2),
                    'active' => true,
                ]
            );
        }

        $this->command->info("  Created {$count} suppliers.");
    }
}
