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

        $storesPerCompany = config('seeders.counts.stores_per_company', 3);
        $totalStores = 0;
        $batchSize = 50;
        $stores = [];
        $storeIndex = 1;

        foreach ($companies as $companyIndex => $company) {
            for ($i = 1; $i <= $storesPerCompany; $i++) {
                $storeName = $faker->city();
                // Generate unique 4-char code: S + 3 chars (base-36 supports up to 46655)
                $storeCode = 'S'.strtoupper(str_pad(base_convert($storeIndex, 10, 36), 3, '0', STR_PAD_LEFT));

                $stores[] = [
                    'store_name' => $storeName,
                    'store_code' => $storeCode,
                    'company_id' => $company->id,
                    'country_id' => $faker->randomElement($countries),
                    'address_1' => $faker->streetAddress(),
                    'address_2' => $faker->optional(0.5)->secondaryAddress(),
                    'email' => $faker->unique()->safeEmail(),
                    'phone_number' => $faker->phoneNumber(),
                    'mobile_number' => $faker->optional(0.5)->phoneNumber(),
                    'active' => true,
                    'include_tax' => $faker->boolean(70),
                    'tax_percentage' => $faker->randomFloat(2, 5, 10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $storeIndex++;

                if (count($stores) >= $batchSize) {
                    Store::insert($stores);
                    $totalStores += count($stores);
                    $stores = [];
                }
            }
        }

        // Insert remaining
        if (! empty($stores)) {
            Store::insert($stores);
            $totalStores += count($stores);
        }

        $this->command->info("  Created {$totalStores} stores across {$companies->count()} companies.");

        // Assign currencies to stores
        $this->assignCurrencies($faker, $currencies);
    }

    /**
     * Assign currencies to all stores.
     */
    private function assignCurrencies($faker, $currencies): void
    {
        if ($currencies->isEmpty()) {
            return;
        }

        $this->command->line('  Assigning currencies to stores...');

        $stores = Store::all();
        $batchSize = 100;
        $storeCurrencies = [];
        $count = 0;

        $currencyIds = $currencies->pluck('id')->unique()->values()->toArray();

        foreach ($stores as $store) {
            // Shuffle and take first N to ensure unique selection
            $shuffled = $currencyIds;
            shuffle($shuffled);
            $numCurrencies = min(2, count($shuffled));
            $selectedCurrencyIds = array_slice($shuffled, 0, $numCurrencies);
            $isFirstCurrency = true;

            foreach ($selectedCurrencyIds as $currencyId) {
                $storeCurrencies[] = [
                    'store_id' => $store->id,
                    'currency_id' => $currencyId,
                    'is_default' => $isFirstCurrency,
                    'exchange_rate' => $isFirstCurrency ? 1.0000 : $faker->randomFloat(4, 0.5, 2.0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $isFirstCurrency = false;

                if (count($storeCurrencies) >= $batchSize) {
                    StoreCurrency::insert($storeCurrencies);
                    $count += count($storeCurrencies);
                    $storeCurrencies = [];
                }
            }
        }

        if (! empty($storeCurrencies)) {
            StoreCurrency::insert($storeCurrencies);
            $count += count($storeCurrencies);
        }

        $this->command->info("  Created {$count} store-currency assignments.");
    }
}
