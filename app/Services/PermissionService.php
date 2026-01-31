<?php

namespace App\Services;

use App\Constants\PagePermissions;
use App\Constants\StoreAccessPermissions;
use App\Constants\StorePermissions;
use App\Models\User;

class PermissionService
{
    /**
     * Check if a user can access a page based on permission.
     */
    public function canAccessPage(User $user, string $permission): bool
    {
        // Admins have full access
        if ($user->isAdmin()) {
            return true;
        }

        // Staff need explicit permission
        if (! $user->isStaff() || ! $user->employee_id) {
            return false;
        }

        $employee = $user->employee;
        if (! $employee) {
            return false;
        }

        $employeePermission = $employee->permission;
        if (! $employeePermission) {
            return false;
        }

        return $employeePermission->hasPermission($permission);
    }

    /**
     * Check if a user can access a specific store.
     * If accessPermission is null, just checks if user is assigned to the store.
     */
    public function canAccessStore(User $user, int $storeId, ?string $accessPermission = null): bool
    {
        // Admins have full access
        if ($user->isAdmin()) {
            return true;
        }

        // Staff need to be assigned to the store
        if (! $user->isStaff() || ! $user->employee_id) {
            return false;
        }

        $employee = $user->employee;
        if (! $employee) {
            return false;
        }

        $employeeStore = $employee->employeeStores()
            ->where('store_id', $storeId)
            ->where('active', true)
            ->first();

        if (! $employeeStore) {
            return false;
        }

        // If no specific access permission required, just being assigned is enough
        if ($accessPermission === null) {
            return true;
        }

        return $employeeStore->hasAccessPermission($accessPermission);
    }

    /**
     * Get all store IDs the user can access with an optional access permission filter.
     *
     * @return array<int>
     */
    public function getAccessibleStoreIds(User $user, ?string $accessPermission = null): array
    {
        // Admins have access to all stores
        if ($user->isAdmin()) {
            return \App\Models\Store::pluck('id')->toArray();
        }

        // Staff need to be assigned to stores
        if (! $user->isStaff() || ! $user->employee_id) {
            return [];
        }

        $employee = $user->employee;
        if (! $employee) {
            return [];
        }

        $query = $employee->employeeStores()->where('active', true);

        $employeeStores = $query->get();

        if ($accessPermission === null) {
            return $employeeStores->pluck('store_id')->toArray();
        }

        return $employeeStores
            ->filter(fn ($es) => $es->hasAccessPermission($accessPermission))
            ->pluck('store_id')
            ->toArray();
    }

    /**
     * Get all permissions for the frontend.
     *
     * @return array{
     *     is_admin: bool,
     *     page_permissions: array<string>,
     *     store_permissions: array<int, array{access: array<string>, operations: array<string>}>
     * }
     */
    public function getPermissionsForFrontend(User $user): array
    {
        $permissions = [
            'is_admin' => $user->isAdmin(),
            'page_permissions' => [],
            'store_permissions' => [],
        ];

        // Admins get all permissions
        if ($user->isAdmin()) {
            $permissions['page_permissions'] = PagePermissions::keys();

            return $permissions;
        }

        // Staff get their configured permissions
        if (! $user->isStaff() || ! $user->employee_id) {
            return $permissions;
        }

        $employee = $user->employee;
        if (! $employee) {
            return $permissions;
        }

        // Get page permissions
        $employeePermission = $employee->permission;
        if ($employeePermission) {
            $permissions['page_permissions'] = $employeePermission->page_permissions ?? [];
        }

        // Get store permissions
        $employeeStores = $employee->employeeStores()->where('active', true)->get();

        foreach ($employeeStores as $employeeStore) {
            $permissions['store_permissions'][$employeeStore->store_id] = [
                'access' => $employeeStore->access_permissions ?? [],
                'operations' => $employeeStore->permissions ?? [],
            ];
        }

        return $permissions;
    }

    /**
     * Get available page permissions for the UI.
     *
     * @return array<string, array<array{key: string, label: string, group: string}>>
     */
    public function getAvailablePagePermissions(): array
    {
        return PagePermissions::grouped();
    }

    /**
     * Get available store access permissions for the UI.
     *
     * @return array<string, array<array{key: string, label: string, group: string}>>
     */
    public function getAvailableStoreAccessPermissions(): array
    {
        return StoreAccessPermissions::grouped();
    }

    /**
     * Get available store operation permissions for the UI.
     *
     * @return array<string, array<array{key: string, label: string, group: string}>>
     */
    public function getAvailableStoreOperationPermissions(): array
    {
        return StorePermissions::grouped();
    }
}
