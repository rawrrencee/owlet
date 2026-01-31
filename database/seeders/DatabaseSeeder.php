<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run order is important due to dependencies between models.
     * WorkOS cleanup runs before creating new users to ensure idempotent seeding.
     */
    public function run(): void
    {
        $this->call([
            // Reference data (no dependencies)
            CountrySeeder::class,
            CurrencySeeder::class,

            // Foundation data (no dependencies on user models)
            DesignationSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,

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
        ]);

        $this->command->newLine();
        $this->command->info('Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->line('You can login with:');
        $this->command->line('  Email: seeder-employee-1@owlet-seed.test (admin)');
        $this->command->line('  Password: '.config('seeders.default_password'));
    }
}
