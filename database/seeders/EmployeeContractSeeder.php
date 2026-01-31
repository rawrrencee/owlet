<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeContract;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class EmployeeContractSeeder extends Seeder
{
    /**
     * Seed employee contracts using Faker.
     */
    public function run(): void
    {
        $this->command->info('Seeding employee contracts...');

        $faker = Faker::create();
        $employees = Employee::with('companies')->get();
        $companies = Company::all();

        if ($employees->isEmpty()) {
            $this->command->warn('  No employees found. Skipping...');

            return;
        }

        $contractsPerEmployee = config('seeders.counts.contracts_per_employee', 1);
        $createdCount = 0;

        foreach ($employees as $employee) {
            // Use employee's assigned companies if available, otherwise random
            $employeeCompanies = $employee->companies;
            if ($employeeCompanies->isEmpty()) {
                $employeeCompanies = $companies;
            }

            for ($i = 1; $i <= $contractsPerEmployee; $i++) {
                $company = $faker->randomElement($employeeCompanies->toArray());
                $startDate = $employee->hire_date ?? $faker->dateTimeBetween('-3 years', '-6 months');

                EmployeeContract::create([
                    'employee_id' => $employee->id,
                    'company_id' => $company['id'],
                    'start_date' => $startDate,
                    'end_date' => $faker->optional(0.3)->dateTimeBetween('+6 months', '+2 years'),
                    'salary_amount' => $faker->randomFloat(4, 2000, 10000),
                    'annual_leave_entitled' => $faker->numberBetween(14, 21),
                    'annual_leave_taken' => $faker->numberBetween(0, 10),
                    'sick_leave_entitled' => $faker->numberBetween(14, 30),
                    'sick_leave_taken' => $faker->numberBetween(0, 5),
                    // No document upload for seeder
                    'document_path' => null,
                    'document_filename' => null,
                    'document_mime_type' => null,
                    'external_document_url' => null,
                    'comments' => $faker->optional(0.3)->sentence(),
                ]);

                $createdCount++;
            }
        }

        $this->command->info("  Created {$createdCount} employee contracts.");
    }
}
