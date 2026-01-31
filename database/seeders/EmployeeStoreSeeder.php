<?php

namespace Database\Seeders;

use App\Constants\StoreAccessPermissions;
use App\Constants\StorePermissions;
use App\Models\Employee;
use App\Models\EmployeeStore;
use App\Models\Store;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class EmployeeStoreSeeder extends Seeder
{
    /**
     * Seed employee-store assignments with permissions.
     */
    public function run(): void
    {
        $this->command->info('Seeding employee-store assignments...');

        $faker = Faker::create();
        $employees = Employee::with('user')->get();
        $stores = Store::all();

        if ($employees->isEmpty() || $stores->isEmpty()) {
            $this->command->warn('  No employees or stores found. Skipping...');

            return;
        }

        $allStorePermissions = StorePermissions::keys();
        $allAccessPermissions = StoreAccessPermissions::keys();

        $assignmentCount = 0;

        foreach ($employees as $employee) {
            $user = $employee->user;
            $isAdmin = $user && $user->role === 'admin';

            if ($isAdmin) {
                // Admins get access to all stores with full permissions
                foreach ($stores as $store) {
                    EmployeeStore::firstOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'store_id' => $store->id,
                        ],
                        [
                            'employee_id' => $employee->id,
                            'store_id' => $store->id,
                            'active' => true,
                            'permissions' => $allStorePermissions,
                            'access_permissions' => $allAccessPermissions,
                            'is_creator' => false,
                        ]
                    );
                    $assignmentCount++;
                }
            } else {
                // Staff get assigned to 1-2 stores with subset of permissions
                $numStores = $faker->randomElement([1, 1, 2]);
                $assignedStores = $faker->randomElements($stores->toArray(), min($numStores, $stores->count()));

                foreach ($assignedStores as $store) {
                    // Give staff a random subset of permissions
                    $permissions = $faker->randomElements(
                        $allStorePermissions,
                        $faker->numberBetween(3, count($allStorePermissions))
                    );
                    $accessPermissions = $faker->randomElements(
                        $allAccessPermissions,
                        $faker->numberBetween(1, count($allAccessPermissions))
                    );

                    EmployeeStore::firstOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'store_id' => $store['id'],
                        ],
                        [
                            'employee_id' => $employee->id,
                            'store_id' => $store['id'],
                            'active' => true,
                            'permissions' => $permissions,
                            'access_permissions' => $accessPermissions,
                            'is_creator' => false,
                        ]
                    );
                    $assignmentCount++;
                }
            }
        }

        $this->command->info("  Created {$assignmentCount} employee-store assignments.");
    }
}
