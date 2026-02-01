<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * In production (APP_DEBUG=false), only essential reference data is seeded.
     * In development (APP_DEBUG=true), all seeders run including test data.
     */
    public function run(): void
    {
        // Essential seeders that run in all environments
        $this->call([
            DesignationSeeder::class,
            CurrencySeeder::class,
        ]);

        // Development-only seeders (test/demo data)
        if (config('app.debug')) {
            $this->call([
                // Reference data
                CountrySeeder::class,

                // Foundation data (no dependencies on user models)
                BrandSeeder::class,
                CategorySeeder::class,
                SupplierSeeder::class,
                TagSeeder::class,

                // Company structure
                CompanySeeder::class,
                StoreSeeder::class,

                // WorkOS cleanup BEFORE creating new users (idempotent seeding)
                WorkOSCleanupSeeder::class,

                // People with WorkOS integration
                EmployeeSeeder::class,
                CustomerSeeder::class,

                // Employee relationships (depend on employees, companies, stores)
                EmployeeCompanySeeder::class,
                EmployeeStoreSeeder::class,
                EmployeePermissionSeeder::class,

                // Hierarchy (depends on employees)
                EmployeeHierarchySeeder::class,
                HierarchyVisibilitySeeder::class,

                // Documents (depends on employees, companies)
                EmployeeContractSeeder::class,
                EmployeeInsuranceSeeder::class,

                // Time tracking (depends on employees, stores)
                TimecardSeeder::class,

                // Products (depends on brands, categories, suppliers, stores, tags)
                ProductSeeder::class,
            ]);

            $this->command->newLine();
            $this->command->info('Database seeding completed successfully!');
            $this->command->newLine();
            $this->command->line('You can login with:');
            $this->command->line('  Email: seeder-employee-1@owlet-seed.test (admin)');
            $this->command->line('  Password: '.config('seeders.default_password'));
        } else {
            $this->command->newLine();
            $this->command->info('Production seeding completed (designations and currencies only).');
        }
    }
}
