<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    /**
     * Seed designations (job titles).
     * Uses predefined values since job titles should be consistent.
     */
    public function run(): void
    {
        $this->command->info('Seeding designations...');

        // Job titles are predefined as they represent standard positions
        $designations = [
            ['designation_name' => 'Store Manager', 'designation_code' => 'MGR'],
            ['designation_name' => 'Assistant Manager', 'designation_code' => 'AMGR'],
            ['designation_name' => 'Sales Associate', 'designation_code' => 'SA'],
            ['designation_name' => 'Cashier', 'designation_code' => 'CSH'],
            ['designation_name' => 'Stock Clerk', 'designation_code' => 'STK'],
            ['designation_name' => 'Senior Sales Associate', 'designation_code' => 'SSA'],
            ['designation_name' => 'Inventory Specialist', 'designation_code' => 'INV'],
            ['designation_name' => 'Customer Service Rep', 'designation_code' => 'CSR'],
        ];

        $count = config('seeders.counts.designations', 5);
        $designationsToSeed = array_slice($designations, 0, $count);

        foreach ($designationsToSeed as $designation) {
            Designation::firstOrCreate(
                ['designation_code' => $designation['designation_code']],
                $designation
            );
        }

        $this->command->info("  Created {$count} designations.");
    }
}
