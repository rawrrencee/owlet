<?php

namespace Database\Seeders;

use App\Constants\PagePermissions;
use App\Models\Employee;
use App\Models\EmployeePermission;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class EmployeePermissionSeeder extends Seeder
{
    /**
     * Seed employee page-level permissions.
     */
    public function run(): void
    {
        $this->command->info('Seeding employee page permissions...');

        $faker = Faker::create();
        $employees = Employee::with('user')->get();
        $allPermissions = PagePermissions::keys();

        if ($employees->isEmpty()) {
            $this->command->warn('  No employees found. Skipping...');

            return;
        }

        $createdCount = 0;

        foreach ($employees as $employee) {
            // Skip if permission already exists
            if ($employee->permission) {
                continue;
            }

            $user = $employee->user;
            $isAdmin = $user && $user->role === 'admin';

            if ($isAdmin) {
                // Admins get all page permissions
                $permissions = $allPermissions;
            } else {
                // Staff get a subset of view permissions, rarely manage permissions
                $permissions = [];

                // Always give view permissions
                $viewPermissions = array_filter($allPermissions, fn ($p) => str_contains($p, '.view') || str_contains($p, '.access'));
                $permissions = array_merge($permissions, $viewPermissions);

                // Occasionally add some manage permissions
                $managePermissions = array_filter($allPermissions, fn ($p) => str_contains($p, '.manage'));
                if ($faker->boolean(30)) {
                    $randomManage = $faker->randomElements($managePermissions, $faker->numberBetween(1, 2));
                    $permissions = array_merge($permissions, $randomManage);
                }

                $permissions = array_unique($permissions);
            }

            EmployeePermission::create([
                'employee_id' => $employee->id,
                'page_permissions' => array_values($permissions),
            ]);

            $createdCount++;
        }

        $this->command->info("  Created {$createdCount} employee permission records.");
    }
}
