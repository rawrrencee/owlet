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
        // Only use SGD, MYR, KRW for simplified testing
        $currencies = Currency::where('active', true)
            ->whereIn('code', ['SGD', 'MYR', 'KRW'])
            ->pluck('id')
            ->toArray();
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

        // Load store currencies for all stores (grouped by store_id)
        $storeCurrencies = StoreCurrency::all()->groupBy('store_id');

        // Seed prices and store assignments together (prices must exist before store assignment)
        $this->seedProductPricesAndStores($faker, $stores, $storeCurrencies, $currencies);
        $this->seedProductTags($faker, $tags);
    }

    /**
     * Seed product prices and store assignments together.
     * Products must have prices for store currencies BEFORE being assigned to stores.
     */
    private function seedProductPricesAndStores($faker, $stores, $storeCurrencies, array $currencies): void
    {
        if ($stores->isEmpty()) {
            $this->command->warn('  No stores available. Skipping product prices and store assignments.');

            return;
        }

        $this->command->line('  Adding product prices and store assignments...');

        $products = Product::pluck('id')->toArray();
        $storeIds = $stores->pluck('id')->toArray();
        $batchSize = 100;

        $allPrices = [];
        $allProductStores = [];
        $priceCount = 0;
        $storeCount = 0;

        foreach ($products as $productId) {
            // Determine which stores this product will be assigned to
            $numStores = $faker->numberBetween(1, min(3, count($storeIds)));
            $selectedStoreIds = $faker->randomElements($storeIds, $numStores);

            // Collect all unique currencies needed for these stores
            $requiredCurrencyIds = [];
            foreach ($selectedStoreIds as $storeId) {
                $storesCurrencyRecords = $storeCurrencies->get($storeId, collect());
                foreach ($storesCurrencyRecords->pluck('currency_id') as $currencyId) {
                    $requiredCurrencyIds[$currencyId] = true;
                }
            }
            $requiredCurrencyIds = array_keys($requiredCurrencyIds);

            // Create product prices for all required currencies
            foreach ($requiredCurrencyIds as $currencyId) {
                $costPrice = $faker->randomFloat(4, 1, 500);
                $sellingPrice = $costPrice * $faker->randomFloat(2, 1.2, 3.0);

                $allPrices[] = [
                    'product_id' => $productId,
                    'currency_id' => $currencyId,
                    'cost_price' => round($costPrice, 4),
                    'unit_price' => round($sellingPrice, 4),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($allPrices) >= $batchSize) {
                    ProductPrice::insert($allPrices);
                    $priceCount += count($allPrices);
                    $allPrices = [];
                }
            }

            // Create product-store assignments
            foreach ($selectedStoreIds as $storeId) {
                $allProductStores[] = [
                    'product_id' => $productId,
                    'store_id' => $storeId,
                    'quantity' => $faker->numberBetween(0, 100),
                    'is_active' => $faker->boolean(90),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($allProductStores) >= $batchSize) {
                    ProductStore::insert($allProductStores);
                    $storeCount += count($allProductStores);
                    $allProductStores = [];
                }
            }
        }

        // Insert remaining prices
        if (! empty($allPrices)) {
            ProductPrice::insert($allPrices);
            $priceCount += count($allPrices);
        }

        // Insert remaining product-store assignments
        if (! empty($allProductStores)) {
            ProductStore::insert($allProductStores);
            $storeCount += count($allProductStores);
        }

        $this->command->info("  Created {$priceCount} product prices.");
        $this->command->info("  Created {$storeCount} product-store assignments.");

        // Now add store-specific prices (overrides)
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
