<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeCompany;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class EmployeeCompanySeeder extends Seeder
{
    /**
     * Seed employee-company assignments.
     */
    public function run(): void
    {
        $this->command->info('Seeding employee-company assignments...');

        $faker = Faker::create();
        $employees = Employee::all();
        $companies = Company::all();
        $designations = Designation::all();

        if ($employees->isEmpty() || $companies->isEmpty()) {
            $this->command->warn('  No employees or companies found. Skipping...');

            return;
        }

        $batchSize = 100;
        $assignments = [];
        $assignmentCount = 0;
        $companyIds = $companies->pluck('id')->toArray();
        $designationIds = $designations->pluck('id')->toArray();

        foreach ($employees as $employee) {
            // Assign employee to one or more companies (most employees belong to one company)
            $numCompanies = $faker->randomElement([1, 1, 1, 2]); // 75% chance of 1 company
            $assignedCompanyIds = $faker->randomElements($companyIds, min($numCompanies, count($companyIds)));

            foreach ($assignedCompanyIds as $companyId) {
                $designationId = ! empty($designationIds) ? $faker->randomElement($designationIds) : null;
                $hireDate = $employee->hire_date ?? $faker->dateTimeBetween('-3 years', '-1 month');

                $assignments[] = [
                    'employee_id' => $employee->id,
                    'company_id' => $companyId,
                    'designation_id' => $designationId,
                    'levy_amount' => $faker->boolean(30) ? $faker->randomFloat(4, 0, 100) : 0,
                    'status' => $faker->randomElement(['FT', 'FT', 'FT', 'PT', 'CT']),
                    'include_shg_donations' => $faker->boolean(30),
                    'commencement_date' => $hireDate instanceof \DateTime ? $hireDate->format('Y-m-d') : $hireDate,
                    'left_date' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($assignments) >= $batchSize) {
                    EmployeeCompany::insert($assignments);
                    $assignmentCount += count($assignments);
                    $assignments = [];
                }
            }
        }

        // Insert remaining
        if (! empty($assignments)) {
            EmployeeCompany::insert($assignments);
            $assignmentCount += count($assignments);
        }

        $this->command->info("  Created {$assignmentCount} employee-company assignments.");
    }
}
