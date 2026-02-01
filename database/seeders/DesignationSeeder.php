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

        $designations = [
            ['designation_name' => 'Part Time Sales Associate', 'designation_code' => 'PAR'],
            ['designation_name' => 'Sales Associate', 'designation_code' => 'SAL'],
            ['designation_name' => 'Supervisor', 'designation_code' => 'SUP'],
            ['designation_name' => 'Manager', 'designation_code' => 'MAN'],
            ['designation_name' => 'Director', 'designation_code' => 'DIR'],
            ['designation_name' => 'Web Technology Engineer', 'designation_code' => 'WEB'],
        ];

        foreach ($designations as $designation) {
            Designation::firstOrCreate(
                ['designation_code' => $designation['designation_code']],
                $designation
            );
        }

        $this->command->info('  Created '.count($designations).' designations.');
    }
}
