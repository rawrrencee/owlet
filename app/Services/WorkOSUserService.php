<?php

namespace App\Services;

use App\Exceptions\WorkOSException;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\WorkOS\WorkOS;
use Throwable;
use WorkOS\UserManagement;

class WorkOSUserService
{
    protected UserManagement $userManagement;

    public function __construct()
    {
        WorkOS::configure();
        $this->userManagement = new UserManagement;
    }

    /**
     * Create a user in WorkOS and locally as an employee.
     *
     * @param array{email: string, first_name: string, last_name: string, role: string} $data
     *
     * @throws WorkOSException
     */
    public function createEmployee(array $data): User
    {
        // Validate role before proceeding
        if (! in_array($data['role'] ?? null, self::VALID_EMPLOYEE_ROLES, true)) {
            throw new WorkOSException(
                "Invalid role '{$data['role']}'. Must be one of: " . implode(', ', self::VALID_EMPLOYEE_ROLES),
                'invalid_role'
            );
        }

        return DB::transaction(function () use ($data) {
            // Remove any soft-deleted user with the same email to allow re-creation
            $existingUser = User::withTrashed()->where('email', $data['email'])->first();
            if ($existingUser?->trashed()) {
                // Force delete the associated employee if it exists and is soft-deleted
                if ($existingUser->employee_id) {
                    Employee::withTrashed()->where('id', $existingUser->employee_id)->forceDelete();
                }
                $existingUser->forceDelete();
            }

            try {
                // Create user in WorkOS
                $workosUser = $this->userManagement->createUser(
                    email: $data['email'],
                    firstName: $data['first_name'],
                    lastName: $data['last_name'],
                    emailVerified: false,
                );
            } catch (Throwable $e) {
                throw WorkOSException::fromWorkOS($e);
            }

            try {
                // Add to organization with role
                // Available WorkOS roles: admin, staff, customer
                $organizationId = config('services.workos.organization_id');
                if ($organizationId) {
                    $this->userManagement->createOrganizationMembership(
                        userId: $workosUser->id,
                        organizationId: $organizationId,
                        roleSlug: $data['role'], // admin or staff
                    );
                }
            } catch (Throwable $e) {
                // Clean up the WorkOS user if membership creation fails
                try {
                    $this->userManagement->deleteUser($workosUser->id);
                } catch (Throwable) {
                    // Ignore cleanup errors
                }
                throw WorkOSException::fromWorkOS($e);
            }

            // Create local Employee record with all profile fields
            $employeeFields = [
                'first_name', 'last_name', 'chinese_name', 'employee_number', 'nric',
                'phone', 'mobile', 'address_1', 'address_2', 'city', 'state',
                'postal_code', 'country', 'date_of_birth', 'gender', 'race',
                'nationality', 'residency_status', 'pr_conversion_date',
                'emergency_name', 'emergency_relationship', 'emergency_contact',
                'emergency_address_1', 'emergency_address_2', 'bank_name',
                'bank_account_number', 'hire_date', 'termination_date', 'notes',
            ];

            $employeeData = collect($data)->only($employeeFields)->filter()->toArray();
            $employeeData['first_name'] = $data['first_name'];
            $employeeData['last_name'] = $data['last_name'];
            $employeeData['hire_date'] = $data['hire_date'] ?? now();

            $employee = Employee::create($employeeData);

            // Create local User record
            return User::create([
                'name' => "{$data['first_name']} {$data['last_name']}",
                'email' => $data['email'],
                'workos_id' => $workosUser->id,
                'avatar' => $workosUser->profilePictureUrl ?? '',
                'role' => $data['role'],
                'employee_id' => $employee->id,
            ]);
        });
    }

    /**
     * Send a password reset email to allow user to set their password.
     *
     * @throws WorkOSException
     */
    public function sendPasswordSetupEmail(string $email): void
    {
        try {
            $this->userManagement->createPasswordReset($email);
        } catch (Throwable $e) {
            throw WorkOSException::fromWorkOS($e);
        }
    }

    /**
     * Get a user from WorkOS by their ID.
     *
     * @throws WorkOSException
     */
    public function getUser(string $workosId): object
    {
        try {
            return $this->userManagement->getUser($workosId);
        } catch (Throwable $e) {
            throw WorkOSException::fromWorkOS($e);
        }
    }

    /**
     * Delete a user from WorkOS.
     *
     * @throws WorkOSException
     */
    public function deleteUser(string $workosId): void
    {
        try {
            $this->userManagement->deleteUser($workosId);
        } catch (Throwable $e) {
            throw WorkOSException::fromWorkOS($e);
        }
    }

    /**
     * Valid roles for employees in this application.
     */
    private const VALID_EMPLOYEE_ROLES = ['admin', 'staff'];

    /**
     * Get the user's role from their WorkOS organization membership.
     * Only returns valid employee roles ('admin' or 'staff').
     *
     * @throws WorkOSException
     */
    public function getUserRole(string $workosId): ?string
    {
        $organizationId = config('services.workos.organization_id');
        if (! $organizationId) {
            return null;
        }

        try {
            // Returns array: [before_cursor, after_cursor, memberships[]]
            [$before, $after, $memberships] = $this->userManagement->listOrganizationMemberships(
                userId: $workosId,
                organizationId: $organizationId,
            );

            if (! empty($memberships)) {
                $role = $memberships[0]->role?->slug ?? null;

                // Only return valid employee roles, otherwise return null to use fallback
                if ($role && in_array($role, self::VALID_EMPLOYEE_ROLES, true)) {
                    return $role;
                }
            }

            return null;
        } catch (Throwable $e) {
            throw WorkOSException::fromWorkOS($e);
        }
    }

    /**
     * Update a user in WorkOS and locally.
     *
     * @param array{email?: string, first_name?: string, last_name?: string, role?: string} $data
     *
     * @throws WorkOSException
     */
    public function updateEmployee(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Update user in WorkOS
            try {
                $this->userManagement->updateUser(
                    userId: $user->workos_id,
                    firstName: $data['first_name'] ?? null,
                    lastName: $data['last_name'] ?? null,
                    emailVerified: null,
                );
            } catch (Throwable $e) {
                throw WorkOSException::fromWorkOS($e);
            }

            // Update role in organization membership
            // Always update to ensure WorkOS stays in sync with our application
            if (isset($data['role'])) {
                $organizationId = config('services.workos.organization_id');
                if (! $organizationId) {
                    throw new WorkOSException(
                        'Organization ID is not configured. Cannot update user role.',
                        'missing_organization_id'
                    );
                }

                try {
                    // Get existing membership
                    // Returns array: [before_cursor, after_cursor, memberships[]]
                    [$before, $after, $memberships] = $this->userManagement->listOrganizationMemberships(
                        userId: $user->workos_id,
                        organizationId: $organizationId,
                    );

                    if (empty($memberships)) {
                        throw new WorkOSException(
                            'User does not have a membership in the organization. Cannot update role.',
                            'membership_not_found'
                        );
                    }

                    $membership = $memberships[0];
                    $this->userManagement->updateOrganizationMembership(
                        organizationMembershipId: $membership->id,
                        roleSlug: $data['role'],
                    );
                } catch (WorkOSException $e) {
                    throw $e;
                } catch (Throwable $e) {
                    throw WorkOSException::fromWorkOS($e);
                }
            }

            // Update local User record (employee record is updated separately in controller)
            $user->update([
                'name' => "{$data['first_name']} {$data['last_name']}",
                'role' => $data['role'] ?? $user->role,
            ]);

            return $user->fresh(['employee']);
        });
    }
}
