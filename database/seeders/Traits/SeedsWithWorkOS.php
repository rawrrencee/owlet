<?php

namespace Database\Seeders\Traits;

use Illuminate\Support\Facades\Log;
use Laravel\WorkOS\WorkOS;
use WorkOS\UserManagement;

trait SeedsWithWorkOS
{
    protected ?UserManagement $userManagement = null;

    /**
     * Get the WorkOS UserManagement instance.
     */
    protected function getWorkOS(): UserManagement
    {
        if ($this->userManagement === null) {
            WorkOS::configure();
            $this->userManagement = new UserManagement;
        }

        return $this->userManagement;
    }

    /**
     * Get the organization ID for WorkOS.
     */
    protected function getOrganizationId(): string
    {
        return config('services.workos.organization_id');
    }

    /**
     * Get the external ID prefix for seeded users.
     */
    protected function getExternalIdPrefix(): string
    {
        return config('seeders.workos.external_id_prefix');
    }

    /**
     * Generate an external ID for a seeded user.
     */
    protected function generateExternalId(string $type, int $index): string
    {
        return $this->getExternalIdPrefix().$type.'-'.$index;
    }

    /**
     * Generate an email for a seeded user.
     */
    protected function generateSeederEmail(string $type, int $index): string
    {
        return "seeder-{$type}-{$index}@owlet-seed.test";
    }

    /**
     * Check if an external ID belongs to a seeded user.
     */
    protected function isSeededUser(?string $externalId): bool
    {
        if ($externalId === null) {
            return false;
        }

        return str_starts_with($externalId, $this->getExternalIdPrefix());
    }

    /**
     * Create a WorkOS user with organization membership.
     * If a user with the same external ID exists, delete it first.
     *
     * @return array{workos_id: string, email: string}|null
     */
    protected function createWorkOSUser(
        string $email,
        string $firstName,
        string $lastName,
        string $externalId,
        string $organizationRole = 'staff',
        int $maxRetries = 3
    ): ?array {
        $retryCount = 0;
        $lastException = null;

        while ($retryCount < $maxRetries) {
            try {
                // Create the user in WorkOS
                $workosUser = $this->getWorkOS()->createUser(
                    email: $email,
                    password: config('seeders.default_password'),
                    firstName: $firstName,
                    lastName: $lastName,
                    emailVerified: true,
                    externalId: $externalId,
                );

                // Add to organization with specified role
                $this->getWorkOS()->createOrganizationMembership(
                    userId: $workosUser->id,
                    organizationId: $this->getOrganizationId(),
                    roleSlug: $organizationRole,
                );

                $this->command->info("  Created WorkOS user: {$email} (role: {$organizationRole})");

                return [
                    'workos_id' => $workosUser->id,
                    'email' => $email,
                ];
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();

                // If external_id already used, find and delete the existing user
                if (str_contains($errorMessage, 'external_id_already_used') || str_contains($errorMessage, 'email_already_exists')) {
                    $this->command->warn('  User with external ID or email already exists, cleaning up...');
                    $this->deleteExistingSeededUser($externalId, $email);
                    $retryCount++;

                    continue;
                }

                $lastException = $e;
                $retryCount++;

                if ($retryCount < $maxRetries) {
                    // Exponential backoff
                    $sleepTime = pow(2, $retryCount) * 100000; // microseconds
                    usleep($sleepTime);
                    $this->command->warn("  Retrying WorkOS user creation for {$email} (attempt {$retryCount}/{$maxRetries})...");
                }
            }
        }

        Log::error('Failed to create WorkOS user after retries', [
            'email' => $email,
            'external_id' => $externalId,
            'error' => $lastException?->getMessage(),
        ]);

        $this->command->error("  Failed to create WorkOS user: {$email} - {$lastException?->getMessage()}");

        return null;
    }

    /**
     * Find and delete an existing seeded user by external ID or email.
     */
    protected function deleteExistingSeededUser(string $externalId, string $email): void
    {
        try {
            // Try to find user by email in the organization
            [$before, $after, $users] = $this->getWorkOS()->listUsers(
                email: $email,
                limit: 1,
            );

            if (! empty($users)) {
                $this->deleteWorkOSUser($users[0]->id);
                $this->command->line("  Deleted existing WorkOS user: {$email}");

                return;
            }

            // If not found by email, search all org users for matching external ID
            foreach ($this->listAllWorkOSUsers() as $user) {
                if (($user->externalId ?? null) === $externalId) {
                    $this->deleteWorkOSUser($user->id);
                    $this->command->line("  Deleted existing WorkOS user with external ID: {$externalId}");

                    return;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to delete existing seeded user', [
                'external_id' => $externalId,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete a WorkOS user.
     */
    protected function deleteWorkOSUser(string $workosId): bool
    {
        try {
            $this->getWorkOS()->deleteUser($workosId);

            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to delete WorkOS user', [
                'workos_id' => $workosId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * List all WorkOS users in the organization with pagination.
     * WorkOS SDK returns [before, after, users] array format.
     *
     * @return \Generator<object>
     */
    protected function listAllWorkOSUsers(): \Generator
    {
        $after = null;

        do {
            // WorkOS SDK returns array: [before, after, users[]]
            [$before, $after, $users] = $this->getWorkOS()->listUsers(
                organizationId: $this->getOrganizationId(),
                limit: 100,
                after: $after,
            );

            foreach ($users as $user) {
                yield $user;
            }
        } while ($after !== null);
    }
}
