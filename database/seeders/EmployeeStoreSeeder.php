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
        $storeIds = $stores->pluck('id')->toArray();

        $batchSize = 100;
        $assignments = [];
        $assignmentCount = 0;

        foreach ($employees as $employee) {
            $user = $employee->user;
            $isAdmin = $user && $user->role === 'admin';

            if ($isAdmin) {
                // Admins get access to all stores with full permissions
                foreach ($storeIds as $storeId) {
                    $assignments[] = [
                        'employee_id' => $employee->id,
                        'store_id' => $storeId,
                        'active' => true,
                        'permissions' => json_encode($allStorePermissions),
                        'access_permissions' => json_encode($allAccessPermissions),
                        'is_creator' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($assignments) >= $batchSize) {
                        EmployeeStore::insert($assignments);
                        $assignmentCount += count($assignments);
                        $assignments = [];
                    }
                }
            } else {
                // Staff get assigned to 1-2 stores with subset of permissions
                $numStores = $faker->randomElement([1, 1, 2]);
                $assignedStoreIds = $faker->randomElements($storeIds, min($numStores, count($storeIds)));

                foreach ($assignedStoreIds as $storeId) {
                    // Give staff a random subset of permissions
                    $permissions = $faker->randomElements(
                        $allStorePermissions,
                        $faker->numberBetween(3, count($allStorePermissions))
                    );
                    $accessPermissions = $faker->randomElements(
                        $allAccessPermissions,
                        $faker->numberBetween(1, count($allAccessPermissions))
                    );

                    $assignments[] = [
                        'employee_id' => $employee->id,
                        'store_id' => $storeId,
                        'active' => true,
                        'permissions' => json_encode($permissions),
                        'access_permissions' => json_encode($accessPermissions),
                        'is_creator' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($assignments) >= $batchSize) {
                        EmployeeStore::insert($assignments);
                        $assignmentCount += count($assignments);
                        $assignments = [];
                    }
                }
            }
        }

        // Insert remaining
        if (! empty($assignments)) {
            EmployeeStore::insert($assignments);
            $assignmentCount += count($assignments);
        }

        $this->command->info("  Created {$assignmentCount} employee-store assignments.");
    }
}
