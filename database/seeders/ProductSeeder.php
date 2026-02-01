<?php

namespace Database\Seeders;

use App\Enums\WeightUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductStore;
use App\Models\ProductStorePrice;
use App\Models\Store;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\StoreCurrency;
use App\Models\Tag;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Seed products with prices, store assignments, and tags.
     */
    public function run(): void
    {
        $this->command->info('Seeding products...');

        $faker = Faker::create();

        // Load reference data
        $brands = Brand::where('is_active', true)->pluck('id')->toArray();
        $categories = Category::where('is_active', true)->get();
        $subcategoriesByCategory = Subcategory::where('is_active', true)->get()->groupBy('category_id');
        $suppliers = Supplier::where('active', true)->pluck('id')->toArray();
        $stores = Store::where('active', true)->get();
        $currencies = Currency::where('active', true)->pluck('id')->toArray();
        $tags = Tag::pluck('id')->toArray();
        $employees = Employee::pluck('id')->toArray();

        if (empty($brands) || $categories->isEmpty() || empty($suppliers)) {
            $this->command->warn('  Missing required reference data (brands, categories, suppliers). Skipping...');

            return;
        }

        $count = config('seeders.counts.products', 300);
        $batchSize = 50;
        $products = [];
        $createdCount = 0;

        // Get a default employee for audit trail
        $defaultEmployee = $employees[0] ?? null;

        $this->command->line("  Creating {$count} products...");

        for ($i = 1; $i <= $count; $i++) {
            $productName = $faker->words(3, true);
            $productNumber = 'P'.str_pad($i, 5, '0', STR_PAD_LEFT);
            $category = $faker->randomElement($categories->toArray());
            $categoryId = $category['id'];
            $subcategoryId = null;

            // Get subcategory for this category if available
            if (isset($subcategoriesByCategory[$categoryId]) && $subcategoriesByCategory[$categoryId]->isNotEmpty()) {
                $subcategoryId = $faker->randomElement($subcategoriesByCategory[$categoryId]->pluck('id')->toArray());
            }

            $products[] = [
                'brand_id' => $faker->randomElement($brands),
                'category_id' => $categoryId,
                'subcategory_id' => $subcategoryId,
                'supplier_id' => $faker->randomElement($suppliers),
                'product_name' => ucwords($productName),
                'product_number' => $productNumber,
                'barcode' => $faker->optional(0.8)->ean13(),
                'supplier_number' => $faker->optional(0.5)->regexify('[A-Z]{2}[0-9]{4}'),
                'description' => $faker->optional(0.7)->paragraph(),
                'cost_price_remarks' => $faker->optional(0.3)->sentence(),
                'weight' => $faker->optional(0.6)->randomFloat(3, 0.1, 50),
                'weight_unit' => $faker->randomElement(array_column(WeightUnit::cases(), 'value')),
                'is_active' => $faker->boolean(90),
                'created_by' => $defaultEmployee,
                'updated_by' => $defaultEmployee,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($products) >= $batchSize) {
                Product::insert($products);
                $createdCount += count($products);
                $products = [];
            }
        }

        // Insert remaining products
        if (! empty($products)) {
            Product::insert($products);
            $createdCount += count($products);
        }

        $this->command->info("  Created {$createdCount} products.");

        // Now add prices, store assignments, and tags
        $this->seedProductPrices($faker, $currencies);
        $this->seedProductStores($faker, $stores, $currencies);
        $this->seedProductTags($faker, $tags);
    }

    /**
     * Seed base product prices (per currency).
     */
    private function seedProductPrices($faker, array $currencies): void
    {
        if (empty($currencies)) {
            return;
        }

        $this->command->line('  Adding product prices...');

        $products = Product::pluck('id')->toArray();
        $batchSize = 100;
        $prices = [];
        $count = 0;

        foreach ($products as $productId) {
            // Each product gets 1-2 currency prices
            $numPrices = $faker->numberBetween(1, min(2, count($currencies)));
            $selectedCurrencies = $faker->randomElements($currencies, $numPrices);

            foreach ($selectedCurrencies as $currencyId) {
                $costPrice = $faker->randomFloat(4, 1, 500);
                $sellingPrice = $costPrice * $faker->randomFloat(2, 1.2, 3.0);

                $prices[] = [
                    'product_id' => $productId,
                    'currency_id' => $currencyId,
                    'cost_price' => round($costPrice, 4),
                    'unit_price' => round($sellingPrice, 4),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($prices) >= $batchSize) {
                    ProductPrice::insert($prices);
                    $count += count($prices);
                    $prices = [];
                }
            }
        }

        if (! empty($prices)) {
            ProductPrice::insert($prices);
            $count += count($prices);
        }

        $this->command->info("  Created {$count} product prices.");
    }

    /**
     * Seed product-store assignments with store-specific prices.
     */
    private function seedProductStores($faker, $stores, array $currencies): void
    {
        if ($stores->isEmpty()) {
            return;
        }

        $this->command->line('  Adding product store assignments...');

        $products = Product::pluck('id')->toArray();
        $storeIds = $stores->pluck('id')->toArray();
        $batchSize = 100;
        $productStores = [];
        $psCount = 0;

        // Load store currencies for all stores (grouped by store_id)
        $storeCurrencies = StoreCurrency::all()->groupBy('store_id');

        // Load existing product prices (grouped by product_id, then currency_id)
        $existingProductPrices = ProductPrice::all()->groupBy('product_id');

        // Track additional product prices to create
        $additionalPrices = [];

        foreach ($products as $productId) {
            // Each product is assigned to 1-3 stores
            $numStores = $faker->numberBetween(1, min(3, count($storeIds)));
            $selectedStores = $faker->randomElements($storeIds, $numStores);

            // Get this product's existing currency IDs
            $productCurrencyIds = $existingProductPrices->get($productId, collect())
                ->pluck('currency_id')
                ->toArray();

            foreach ($selectedStores as $storeId) {
                $productStores[] = [
                    'product_id' => $productId,
                    'store_id' => $storeId,
                    'quantity' => $faker->numberBetween(0, 100),
                    'is_active' => $faker->boolean(90),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Check if product has a price for any of this store's currencies
                $storesCurrencyRecords = $storeCurrencies->get($storeId, collect());
                $storeCurrencyIds = $storesCurrencyRecords->pluck('currency_id')->toArray();

                $hasMatchingCurrency = ! empty(array_intersect($productCurrencyIds, $storeCurrencyIds));

                // If no matching currency, create a price for the store's default currency
                if (! $hasMatchingCurrency && ! empty($storeCurrencyIds)) {
                    // Try to get default currency, otherwise use first
                    $defaultCurrencyRecord = $storesCurrencyRecords->firstWhere('is_default', true);
                    $currencyIdToUse = $defaultCurrencyRecord
                        ? $defaultCurrencyRecord->currency_id
                        : $storeCurrencyIds[0];

                    // Only add if we haven't already added this currency for this product
                    $priceKey = "{$productId}_{$currencyIdToUse}";
                    if (! isset($additionalPrices[$priceKey]) && ! in_array($currencyIdToUse, $productCurrencyIds)) {
                        $costPrice = $faker->randomFloat(4, 1, 500);
                        $sellingPrice = $costPrice * $faker->randomFloat(2, 1.2, 3.0);

                        $additionalPrices[$priceKey] = [
                            'product_id' => $productId,
                            'currency_id' => $currencyIdToUse,
                            'cost_price' => round($costPrice, 4),
                            'unit_price' => round($sellingPrice, 4),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        // Also track this in our local cache
                        $productCurrencyIds[] = $currencyIdToUse;
                    }
                }

                if (count($productStores) >= $batchSize) {
                    ProductStore::insert($productStores);
                    $psCount += count($productStores);
                    $productStores = [];
                }
            }
        }

        if (! empty($productStores)) {
            ProductStore::insert($productStores);
            $psCount += count($productStores);
        }

        $this->command->info("  Created {$psCount} product-store assignments.");

        // Insert additional prices for store currency compatibility
        if (! empty($additionalPrices)) {
            $priceValues = array_values($additionalPrices);
            $chunks = array_chunk($priceValues, $batchSize);
            foreach ($chunks as $chunk) {
                ProductPrice::insert($chunk);
            }
            $this->command->info('  Created '.count($additionalPrices).' additional product prices for store currency compatibility.');
        }

        // Now add store-specific prices
        $this->seedProductStorePrices($faker, $currencies);
    }

    /**
     * Seed store-specific product prices (overrides base price).
     */
    private function seedProductStorePrices($faker, array $currencies): void
    {
        if (empty($currencies)) {
            return;
        }

        $this->command->line('  Adding store-specific prices...');

        $productStores = ProductStore::pluck('id')->toArray();
        $batchSize = 100;
        $storePrices = [];
        $count = 0;

        // Only ~30% of product-store combinations get custom prices
        $selectedProductStores = $faker->randomElements(
            $productStores,
            (int) (count($productStores) * 0.3)
        );

        foreach ($selectedProductStores as $productStoreId) {
            $currencyId = $faker->randomElement($currencies);
            $costPrice = $faker->randomFloat(4, 1, 500);
            $sellingPrice = $costPrice * $faker->randomFloat(2, 1.2, 3.0);

            $storePrices[] = [
                'product_store_id' => $productStoreId,
                'currency_id' => $currencyId,
                'cost_price' => round($costPrice, 4),
                'unit_price' => round($sellingPrice, 4),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($storePrices) >= $batchSize) {
                ProductStorePrice::insert($storePrices);
                $count += count($storePrices);
                $storePrices = [];
            }
        }

        if (! empty($storePrices)) {
            ProductStorePrice::insert($storePrices);
            $count += count($storePrices);
        }

        $this->command->info("  Created {$count} store-specific prices.");
    }

    /**
     * Seed product-tag relationships.
     */
    private function seedProductTags($faker, array $tags): void
    {
        if (empty($tags)) {
            return;
        }

        $this->command->line('  Adding product tags...');

        $products = Product::pluck('id')->toArray();
        $batchSize = 100;
        $productTags = [];
        $count = 0;

        foreach ($products as $productId) {
            // Each product gets 0-4 tags
            $numTags = $faker->numberBetween(0, min(4, count($tags)));
            if ($numTags === 0) {
                continue;
            }

            $selectedTags = $faker->randomElements($tags, $numTags);

            foreach ($selectedTags as $tagId) {
                $productTags[] = [
                    'product_id' => $productId,
                    'tag_id' => $tagId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($productTags) >= $batchSize) {
                    DB::table('product_tag')->insert($productTags);
                    $count += count($productTags);
                    $productTags = [];
                }
            }
        }

        if (! empty($productTags)) {
            DB::table('product_tag')->insert($productTags);
            $count += count($productTags);
        }

        $this->command->info("  Created {$count} product-tag relationships.");
    }
}
