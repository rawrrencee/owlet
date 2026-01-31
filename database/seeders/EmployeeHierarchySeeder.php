<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeHierarchy;
use Illuminate\Database\Seeder;

class EmployeeHierarchySeeder extends Seeder
{
    /**
     * Seed employee hierarchy (manager-subordinate relationships).
     */
    public function run(): void
    {
        $this->command->info('Seeding employee hierarchy...');

        $employees = Employee::with('user')->get();

        if ($employees->count() < 2) {
            $this->command->warn('  Need at least 2 employees for hierarchy. Skipping...');

            return;
        }

        // Find admin employee to be manager (first admin user's employee)
        $manager = $employees->first(function ($employee) {
            return $employee->user && $employee->user->role === 'admin';
        });

        if (! $manager) {
            // Fallback to first employee if no admin found
            $manager = $employees->first();
        }

        $subordinates = $employees->reject(fn ($e) => $e->id === $manager->id);

        $createdCount = 0;

        foreach ($subordinates as $subordinate) {
            EmployeeHierarchy::firstOrCreate(
                [
                    'manager_id' => $manager->id,
                    'subordinate_id' => $subordinate->id,
                ],
                [
                    'manager_id' => $manager->id,
                    'subordinate_id' => $subordinate->id,
                    'active' => true,
                ]
            );
            $createdCount++;
        }

        $this->command->info("  Created {$createdCount} hierarchy relationships. Manager: {$manager->full_name}");
    }
}
