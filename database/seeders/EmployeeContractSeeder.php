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

        $contractsPerEmployee = config('seeders.counts.contracts_per_employee', 2);
        $batchSize = 100;
        $contracts = [];
        $createdCount = 0;
        $companyIds = $companies->pluck('id')->toArray();

        foreach ($employees as $employee) {
            // Use employee's assigned companies if available, otherwise random
            $employeeCompanyIds = $employee->companies->pluck('id')->toArray();
            if (empty($employeeCompanyIds)) {
                $employeeCompanyIds = $companyIds;
            }

            for ($i = 1; $i <= $contractsPerEmployee; $i++) {
                $companyId = $faker->randomElement($employeeCompanyIds);
                $hireDate = $employee->hire_date ?? $faker->dateTimeBetween('-3 years', '-6 months');
                $startDate = $hireDate instanceof \DateTime ? $hireDate : new \DateTime($hireDate);

                // Adjust start date for subsequent contracts
                if ($i > 1) {
                    $startDate = $faker->dateTimeBetween($startDate, 'now');
                }

                $contracts[] = [
                    'employee_id' => $employee->id,
                    'company_id' => $companyId,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $faker->optional(0.3)->dateTimeBetween('+6 months', '+2 years')?->format('Y-m-d'),
                    'salary_amount' => $faker->randomFloat(4, 2000, 10000),
                    'annual_leave_entitled' => $faker->numberBetween(14, 21),
                    'annual_leave_taken' => $faker->numberBetween(0, 10),
                    'sick_leave_entitled' => $faker->numberBetween(14, 30),
                    'sick_leave_taken' => $faker->numberBetween(0, 5),
                    'document_path' => null,
                    'document_filename' => null,
                    'document_mime_type' => null,
                    'external_document_url' => null,
                    'comments' => $faker->optional(0.3)->sentence(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($contracts) >= $batchSize) {
                    EmployeeContract::insert($contracts);
                    $createdCount += count($contracts);
                    $contracts = [];
                }
            }
        }

        // Insert remaining
        if (! empty($contracts)) {
            EmployeeContract::insert($contracts);
            $createdCount += count($contracts);
        }

        $this->command->info("  Created {$createdCount} employee contracts.");
    }
}
