<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\Traits\SeedsWithWorkOS;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    use SeedsWithWorkOS;

    /**
     * Seed employees - first N with WorkOS accounts, rest as bulk data.
     */
    public function run(): void
    {
        $this->command->info('Seeding employees...');

        $faker = Faker::create();
        $countries = Country::where('active', true)->pluck('id')->toArray();

        $totalCount = config('seeders.counts.employees', 300);
        $workosCount = config('seeders.counts.employees_with_workos', 5);

        // Create employees with WorkOS accounts
        $workosCreatedCount = $this->createEmployeesWithWorkOS($faker, $countries, $workosCount);

        // Create bulk employees without WorkOS
        $bulkCount = $totalCount - $workosCreatedCount;
        $bulkCreatedCount = $this->createBulkEmployees($faker, $countries, $bulkCount, $workosCreatedCount);

        $totalCreated = $workosCreatedCount + $bulkCreatedCount;
        $this->command->info("  Created {$totalCreated} employees ({$workosCreatedCount} with WorkOS, {$bulkCreatedCount} bulk).");
    }

    /**
     * Create employees with WorkOS accounts.
     */
    private function createEmployeesWithWorkOS($faker, array $countries, int $count): int
    {
        $createdCount = 0;

        for ($i = 1; $i <= $count; $i++) {
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $email = $this->generateSeederEmail('employee', $i);

            // Determine role: first employee is admin, rest are staff
            $role = $i === 1 ? 'admin' : 'staff';
            $workosRole = $role;

            DB::transaction(function () use (
                $faker,
                $countries,
                $firstName,
                $lastName,
                $email,
                $role,
                $workosRole,
                $i,
                &$createdCount
            ) {
                // Check if employee with this email already exists
                $existingUser = User::where('email', $email)->first();
                if ($existingUser) {
                    $this->command->line("  Skipping existing employee: {$email}");

                    return;
                }

                // Create WorkOS user first
                $workosData = $this->createWorkOSUser(
                    email: $email,
                    firstName: $firstName,
                    lastName: $lastName,
                    externalId: $this->generateExternalId('employee', $i),
                    organizationRole: $workosRole,
                );

                if (! $workosData) {
                    $this->command->error("  Failed to create WorkOS account for employee {$i}, skipping...");

                    return;
                }

                // Create employee record
                $employee = $this->createEmployeeRecord($faker, $countries, $firstName, $lastName, $i);

                // Create user record linked to employee
                User::create([
                    'workos_id' => $workosData['workos_id'],
                    'email' => $email,
                    'name' => "{$firstName} {$lastName}",
                    'avatar' => '',
                    'role' => $role,
                    'employee_id' => $employee->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);

                $createdCount++;
                $this->command->line("  Created employee with WorkOS: {$firstName} {$lastName} ({$email}) - Role: {$role}");
            });
        }

        return $createdCount;
    }

    /**
     * Create bulk employees without WorkOS accounts.
     */
    private function createBulkEmployees($faker, array $countries, int $count, int $startIndex): int
    {
        $createdCount = 0;
        $batchSize = 50;
        $employees = [];

        $this->command->line("  Creating {$count} bulk employees...");

        for ($i = 1; $i <= $count; $i++) {
            $index = $startIndex + $i;
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();

            $employees[] = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'employee_number' => 'EMP'.str_pad($index, 4, '0', STR_PAD_LEFT),
                'nric' => $faker->regexify('[STFG][0-9]{7}[A-Z]'),
                'phone' => $faker->optional(0.5)->phoneNumber(),
                'mobile' => $faker->phoneNumber(),
                'address_1' => $faker->streetAddress(),
                'address_2' => $faker->optional(0.5)->secondaryAddress(),
                'city' => $faker->city(),
                'postal_code' => $faker->postcode(),
                'country_id' => $faker->randomElement($countries),
                'nationality_id' => $faker->randomElement($countries),
                'date_of_birth' => $faker->dateTimeBetween('-50 years', '-20 years')->format('Y-m-d'),
                'gender' => $faker->randomElement(['Male', 'Female']),
                'race' => $faker->randomElement(['Chinese', 'Malay', 'Indian', 'Others']),
                'residency_status' => $faker->randomElement(['Citizen', 'PR', 'EP', 'SP', 'WP']),
                'hire_date' => $faker->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d'),
                'emergency_name' => $faker->name(),
                'emergency_relationship' => $faker->randomElement(['Spouse', 'Parent', 'Sibling', 'Friend']),
                'emergency_contact' => $faker->phoneNumber(),
                'bank_name' => $faker->randomElement(['DBS', 'OCBC', 'UOB', 'Standard Chartered', 'HSBC']),
                'bank_account_number' => $faker->numerify('###-######-#'),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insert in batches
            if (count($employees) >= $batchSize) {
                Employee::insert($employees);
                $createdCount += count($employees);
                $employees = [];
            }
        }

        // Insert remaining
        if (! empty($employees)) {
            Employee::insert($employees);
            $createdCount += count($employees);
        }

        return $createdCount;
    }

    /**
     * Create an employee record.
     */
    private function createEmployeeRecord($faker, array $countries, string $firstName, string $lastName, int $index): Employee
    {
        return Employee::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'employee_number' => 'EMP'.str_pad($index, 4, '0', STR_PAD_LEFT),
            'nric' => $faker->regexify('[STFG][0-9]{7}[A-Z]'),
            'phone' => $faker->optional(0.5)->phoneNumber(),
            'mobile' => $faker->phoneNumber(),
            'address_1' => $faker->streetAddress(),
            'address_2' => $faker->optional(0.5)->secondaryAddress(),
            'city' => $faker->city(),
            'postal_code' => $faker->postcode(),
            'country_id' => $faker->randomElement($countries),
            'nationality_id' => $faker->randomElement($countries),
            'date_of_birth' => $faker->dateTimeBetween('-50 years', '-20 years'),
            'gender' => $faker->randomElement(['Male', 'Female']),
            'race' => $faker->randomElement(['Chinese', 'Malay', 'Indian', 'Others']),
            'residency_status' => $faker->randomElement(['Citizen', 'PR', 'EP', 'SP', 'WP']),
            'hire_date' => $faker->dateTimeBetween('-5 years', '-1 month'),
            'emergency_name' => $faker->name(),
            'emergency_relationship' => $faker->randomElement(['Spouse', 'Parent', 'Sibling', 'Friend']),
            'emergency_contact' => $faker->phoneNumber(),
            'bank_name' => $faker->randomElement(['DBS', 'OCBC', 'UOB', 'Standard Chartered', 'HSBC']),
            'bank_account_number' => $faker->numerify('###-######-#'),
        ]);
    }
}
