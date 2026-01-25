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
     * Create an employee locally and send a WorkOS invitation.
     * The WorkOS user will be created when the user accepts the invitation.
     *
     * @param  array{email: string, first_name: string, last_name: string, role: string}  $data
     *
     * @throws WorkOSException
     */
    public function createEmployee(array $data): Employee
    {
        // Validate role before proceeding
        if (! in_array($data['role'] ?? null, self::VALID_EMPLOYEE_ROLES, true)) {
            throw new WorkOSException(
                "Invalid role '{$data['role']}'. Must be one of: ".implode(', ', self::VALID_EMPLOYEE_ROLES),
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

            // Also remove any soft-deleted employee with the same pending_email
            Employee::withTrashed()
                ->where('pending_email', $data['email'])
                ->whereNotNull('deleted_at')
                ->forceDelete();

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
            // Store email and role for linking when user accepts invitation
            $employeeData['pending_email'] = $data['email'];
            $employeeData['pending_role'] = $data['role'];

            return Employee::create($employeeData);
        });
    }

    /**
     * Send an invitation email via WorkOS for the user to set up their account.
     * If an invitation already exists for this email, it will be revoked and a new one sent.
     *
     * @throws WorkOSException
     */
    public function sendInvitation(string $email, string $role): void
    {
        $organizationId = config('services.workos.organization_id');

        if (! $organizationId) {
            throw new WorkOSException(
                'Organization ID is not configured. Cannot send invitation.',
                'missing_organization_id'
            );
        }

        try {
            $this->userManagement->sendInvitation(
                email: $email,
                organizationId: $organizationId,
                expiresInDays: 7,
                roleSlug: $role,
            );
        } catch (Throwable $e) {
            // Check if the error is "email already invited" - if so, revoke and resend
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, 'email_already_invited_to_organization')) {
                $this->revokeAndResendInvitation($email, $organizationId, $role);

                return;
            }

            throw WorkOSException::fromWorkOS($e);
        }
    }

    /**
     * Revoke an existing invitation and send a new one.
     *
     * @throws WorkOSException
     */
    private function revokeAndResendInvitation(string $email, string $organizationId, string $role): void
    {
        try {
            // Find the existing invitation
            [$before, $after, $invitations] = $this->userManagement->listInvitations(
                email: $email,
                organizationId: $organizationId,
            );

            // Revoke all pending invitations for this email
            foreach ($invitations as $invitation) {
                if ($invitation->state === 'pending') {
                    $this->userManagement->revokeInvitation($invitation->id);
                }
            }

            // Send a new invitation
            $this->userManagement->sendInvitation(
                email: $email,
                organizationId: $organizationId,
                expiresInDays: 7,
                roleSlug: $role,
            );
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
     * @param  array{email?: string, first_name?: string, last_name?: string, role?: string}  $data
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
