<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\Traits\SeedsWithWorkOS;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkOSCleanupSeeder extends Seeder
{
    use SeedsWithWorkOS;

    /**
     * Clean up seeded WorkOS accounts and local records.
     */
    public function run(): void
    {
        $this->command->info('Cleaning up seeded WorkOS accounts...');

        $deletedWorkOSCount = 0;
        $deletedLocalCount = 0;

        // Step 1: List all WorkOS users and delete seeded ones
        try {
            foreach ($this->listAllWorkOSUsers() as $workosUser) {
                if ($this->isSeededUser($workosUser->externalId ?? null)) {
                    if ($this->deleteWorkOSUser($workosUser->id)) {
                        $deletedWorkOSCount++;
                        $this->command->line("  Deleted WorkOS user: {$workosUser->email}");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->command->error("  Error listing WorkOS users: {$e->getMessage()}");
            $this->command->warn('  Continuing with local cleanup...');
        }

        // Step 2: Delete local User records with seeder emails
        DB::transaction(function () use (&$deletedLocalCount) {
            // Find users with seeder email pattern
            $seederUsers = User::where('email', 'like', 'seeder-%@owlet-seed.test')->get();

            foreach ($seederUsers as $user) {
                // Store the employee/customer IDs before deleting user
                $employeeId = $user->employee_id;
                $customerId = $user->customer_id;

                // Force delete the user (not soft delete)
                $user->forceDelete();
                $deletedLocalCount++;
                $this->command->line("  Deleted local user: {$user->email}");

                // Delete orphaned employee (if not linked to other users)
                if ($employeeId) {
                    $employee = Employee::find($employeeId);
                    if ($employee && ! User::where('employee_id', $employeeId)->exists()) {
                        // Delete related records first
                        $employee->employeeCompanies()->delete();
                        $employee->employeeStores()->delete();
                        $employee->permission()?->delete();
                        $employee->contracts()->delete();
                        $employee->insurances()->delete();
                        $employee->timecards()->each(function ($timecard) {
                            $timecard->details()->delete();
                            $timecard->delete();
                        });
                        $employee->subordinateRelations()->delete();
                        $employee->managerRelations()->delete();
                        $employee->hierarchyVisibility()?->delete();

                        $employee->forceDelete();
                        $this->command->line("  Deleted orphaned employee: {$employee->full_name}");
                    }
                }

                // Delete orphaned customer (if not linked to other users)
                if ($customerId) {
                    $customer = Customer::find($customerId);
                    if ($customer && ! User::where('customer_id', $customerId)->exists()) {
                        $customer->forceDelete();
                        $this->command->line("  Deleted orphaned customer: {$customer->full_name}");
                    }
                }
            }
        });

        $this->command->info("Cleanup complete: {$deletedWorkOSCount} WorkOS users, {$deletedLocalCount} local users deleted.");
    }
}
