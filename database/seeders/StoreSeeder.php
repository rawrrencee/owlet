<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Store;
use App\Models\StoreCurrency;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Seed stores with currencies using Faker.
     */
    public function run(): void
    {
        $this->command->info('Seeding stores...');

        $faker = Faker::create();
        $companies = Company::all();
        $countries = Country::where('active', true)->pluck('id')->toArray();
        $currencies = Currency::where('active', true)->get();

        $storesPerCompany = config('seeders.counts.stores_per_company', 2);
        $totalStores = 0;

        foreach ($companies as $companyIndex => $company) {
            for ($i = 1; $i <= $storesPerCompany; $i++) {
                $storeName = $faker->city();
                // Generate 4-char code: 2 letters + company index + store index
                $storeCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $storeName), 0, 2)).(($companyIndex + 1) % 10).($i % 10);

                $store = Store::firstOrCreate(
                    ['store_code' => $storeCode],
                    [
                        'store_name' => $storeName,
                        'store_code' => $storeCode,
                        'company_id' => $company->id,
                        'country_id' => $faker->randomElement($countries),
                        'address_1' => $faker->streetAddress(),
                        'address_2' => $faker->optional(0.5)->secondaryAddress(),
                        'email' => $faker->safeEmail(),
                        'phone_number' => $faker->phoneNumber(),
                        'mobile_number' => $faker->optional(0.5)->phoneNumber(),
                        'active' => true,
                        'include_tax' => $faker->boolean(70),
                        'tax_percentage' => $faker->randomFloat(2, 5, 10),
                    ]
                );

                // Assign currencies to the store
                if ($currencies->isNotEmpty()) {
                    $storeCurrencies = $faker->randomElements($currencies->toArray(), min(2, $currencies->count()));
                    $isFirstCurrency = true;

                    foreach ($storeCurrencies as $currency) {
                        StoreCurrency::firstOrCreate(
                            [
                                'store_id' => $store->id,
                                'currency_id' => $currency['id'],
                            ],
                            [
                                'store_id' => $store->id,
                                'currency_id' => $currency['id'],
                                'is_default' => $isFirstCurrency,
                                'exchange_rate' => $isFirstCurrency ? 1.0000 : $faker->randomFloat(4, 0.5, 2.0),
                            ]
                        );
                        $isFirstCurrency = false;
                    }
                }

                $totalStores++;
            }
        }

        $this->command->info("  Created {$totalStores} stores across {$companies->count()} companies.");
    }
}
