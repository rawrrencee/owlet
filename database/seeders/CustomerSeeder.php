<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\Traits\SeedsWithWorkOS;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    use SeedsWithWorkOS;

    /**
     * Seed customers with WorkOS user accounts using Faker.
     */
    public function run(): void
    {
        $this->command->info('Seeding customers with WorkOS accounts...');

        $faker = Faker::create();
        $countries = Country::where('active', true)->pluck('id')->toArray();

        $count = config('seeders.counts.customers', 2);
        $createdCount = 0;

        for ($i = 1; $i <= $count; $i++) {
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $email = $this->generateSeederEmail('customer', $i);

            DB::transaction(function () use (
                $faker,
                $countries,
                $firstName,
                $lastName,
                $email,
                $i,
                &$createdCount
            ) {
                // Check if customer with this email already exists
                $existingUser = User::where('email', $email)->first();
                if ($existingUser) {
                    $this->command->line("  Skipping existing customer: {$email}");

                    return;
                }

                // Create WorkOS user with customer role
                $workosData = $this->createWorkOSUser(
                    email: $email,
                    firstName: $firstName,
                    lastName: $lastName,
                    externalId: $this->generateExternalId('customer', $i),
                    organizationRole: 'customer',
                );

                if (! $workosData) {
                    $this->command->error("  Failed to create WorkOS account for customer {$i}, skipping...");

                    return;
                }

                // Create customer record
                $customer = Customer::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $faker->optional(0.5)->phoneNumber(),
                    'mobile' => $faker->phoneNumber(),
                    'date_of_birth' => $faker->optional(0.7)->dateTimeBetween('-70 years', '-18 years'),
                    'gender' => $faker->optional(0.8)->randomElement(['Male', 'Female']),
                    'race' => $faker->optional(0.5)->randomElement(['Chinese', 'Malay', 'Indian', 'Others']),
                    'country_id' => $faker->randomElement($countries),
                    'nationality_id' => $faker->randomElement($countries),
                    'company_name' => $faker->optional(0.3)->company(),
                    'discount_percentage' => $faker->optional(0.2)->randomFloat(2, 5, 20),
                    'loyalty_points' => $faker->numberBetween(0, 5000),
                    'customer_since' => $faker->dateTimeBetween('-3 years', 'now'),
                    'notes' => $faker->optional(0.3)->sentence(),
                ]);

                // Create user record linked to customer
                User::create([
                    'workos_id' => $workosData['workos_id'],
                    'email' => $email,
                    'name' => "{$firstName} {$lastName}",
                    'avatar' => '',
                    'role' => 'customer',
                    'customer_id' => $customer->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);

                $createdCount++;
                $this->command->line("  Created customer: {$firstName} {$lastName} ({$email})");
            });
        }

        $this->command->info("  Created {$createdCount} customers with WorkOS accounts.");
    }
}
