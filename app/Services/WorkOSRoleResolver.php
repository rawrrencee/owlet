<?php

namespace App\Services;

use Laravel\WorkOS\WorkOS;
use WorkOS\UserManagement;

class WorkOSRoleResolver
{
    public function resolve(string $workosUserId, ?string $organizationId = null): string
    {
        $organizationId ??= config('services.workos.organization_id');

        if (! $organizationId) {
            return 'customer';
        }

        try {
            WorkOS::configure();

            // listOrganizationMemberships returns [$before, $after, $memberships]
            [, , $memberships] = (new UserManagement)->listOrganizationMemberships(
                userId: $workosUserId,
                organizationId: $organizationId,
                limit: 1
            );

            if (empty($memberships)) {
                return 'customer';
            }

            $membership = $memberships[0];

            return match ($membership->role?->slug ?? 'member') {
                'admin' => 'admin',
                'staff' => 'staff',
                'member' => 'customer',
                default => 'customer',
            };
        } catch (\Throwable) {
            return 'customer';
        }
    }
}
