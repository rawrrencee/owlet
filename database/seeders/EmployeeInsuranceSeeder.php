<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeInsurance;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class EmployeeInsuranceSeeder extends Seeder
{
    /**
     * Seed employee insurance records using Faker.
     */
    public function run(): void
    {
        $this->command->info('Seeding employee insurances...');

        $faker = Faker::create();
        $employees = Employee::all();

        if ($employees->isEmpty()) {
            $this->command->warn('  No employees found. Skipping...');

            return;
        }

        $insurancesPerEmployee = config('seeders.counts.insurances_per_employee', 2);

        $insurers = [
            'AIA Singapore',
            'Prudential Singapore',
            'Great Eastern',
            'NTUC Income',
            'Manulife',
            'AXA Insurance',
        ];

        $insuranceTypes = [
            'Group Medical Insurance',
            'Personal Accident Insurance',
            'Life Insurance',
            'Hospitalization Insurance',
            'Critical Illness Coverage',
        ];

        $batchSize = 100;
        $insurances = [];
        $createdCount = 0;

        foreach ($employees as $employee) {
            $hireDate = $employee->hire_date ?? $faker->dateTimeBetween('-2 years', '-3 months');

            for ($i = 1; $i <= $insurancesPerEmployee; $i++) {
                $startDate = $hireDate instanceof \DateTime ? $hireDate : new \DateTime($hireDate);

                $insurances[] = [
                    'employee_id' => $employee->id,
                    'title' => $faker->randomElement($insuranceTypes),
                    'insurer_name' => $faker->randomElement($insurers),
                    'policy_number' => strtoupper($faker->bothify('POL-####-????-##')),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $faker->optional(0.7)->dateTimeBetween('+6 months', '+3 years')?->format('Y-m-d'),
                    'document_path' => null,
                    'document_filename' => null,
                    'document_mime_type' => null,
                    'external_document_url' => null,
                    'comments' => $faker->optional(0.2)->sentence(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($insurances) >= $batchSize) {
                    EmployeeInsurance::insert($insurances);
                    $createdCount += count($insurances);
                    $insurances = [];
                }
            }
        }

        // Insert remaining
        if (! empty($insurances)) {
            EmployeeInsurance::insert($insurances);
            $createdCount += count($insurances);
        }

        $this->command->info("  Created {$createdCount} employee insurance records.");
    }
}
