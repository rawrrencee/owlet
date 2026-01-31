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
     * Seed employees with WorkOS user accounts using Faker.
     */
    public function run(): void
    {
        $this->command->info('Seeding employees with WorkOS accounts...');

        $faker = Faker::create();
        $countries = Country::where('active', true)->pluck('id')->toArray();

        $count = config('seeders.counts.employees', 2);
        $createdCount = 0;

        for ($i = 1; $i <= $count; $i++) {
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $email = $this->generateSeederEmail('employee', $i);

            // Determine role: first employee is admin, rest are staff
            $role = $i === 1 ? 'admin' : 'staff';
            $workosRole = $role; // WorkOS uses same role slugs

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
                $employee = Employee::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'employee_number' => 'EMP'.str_pad($i, 4, '0', STR_PAD_LEFT),
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
                $this->command->line("  Created employee: {$firstName} {$lastName} ({$email}) - Role: {$role}");
            });
        }

        $this->command->info("  Created {$createdCount} employees with WorkOS accounts.");
    }
}
