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

        $assignmentCount = 0;

        foreach ($employees as $employee) {
            // Assign employee to one or more companies (most employees belong to one company)
            $numCompanies = $faker->randomElement([1, 1, 1, 2]); // 75% chance of 1 company
            $assignedCompanies = $faker->randomElements($companies->toArray(), min($numCompanies, $companies->count()));

            foreach ($assignedCompanies as $company) {
                $designation = $designations->isNotEmpty()
                    ? $faker->randomElement($designations->toArray())
                    : null;

                EmployeeCompany::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'company_id' => $company['id'],
                    ],
                    [
                        'employee_id' => $employee->id,
                        'company_id' => $company['id'],
                        'designation_id' => $designation ? $designation['id'] : null,
                        'levy_amount' => $faker->boolean(30) ? $faker->randomFloat(4, 0, 100) : 0,
                        'status' => $faker->randomElement(['FT', 'FT', 'FT', 'PT', 'CT']), // Mostly full-time
                        'include_shg_donations' => $faker->boolean(30),
                        'commencement_date' => $employee->hire_date ?? $faker->dateTimeBetween('-3 years', '-1 month'),
                        'left_date' => null, // Active assignment
                    ]
                );
                $assignmentCount++;
            }
        }

        $this->command->info("  Created {$assignmentCount} employee-company assignments.");
    }
}
