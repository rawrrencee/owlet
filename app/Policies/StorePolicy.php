<?php

namespace App\Policies;

use App\Constants\StoreAccessPermissions;
use App\Models\Store;
use App\Models\User;
use App\Services\PermissionService;

class StorePolicy
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    /**
     * Determine whether the user can view the list of stores.
     */
    public function viewAny(User $user): bool
    {
        // Admins can always view
        if ($user->isAdmin()) {
            return true;
        }

        // Staff need stores.access page permission
        return $this->permissionService->canAccessPage($user, 'stores.access');
    }

    /**
     * Determine whether the user can view the store.
     */
    public function view(User $user, Store $store): bool
    {
        // Admins can always view
        if ($user->isAdmin()) {
            return true;
        }

        // Staff need to be assigned to the store with store.view access permission
        return $this->permissionService->canAccessStore($user, $store->id, StoreAccessPermissions::STORE_VIEW);
    }

    /**
     * Determine whether the user can create stores.
     */
    public function create(User $user): bool
    {
        // Only admins can create stores
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the store.
     */
    public function update(User $user, Store $store): bool
    {
        // Admins can always update
        if ($user->isAdmin()) {
            return true;
        }

        // Staff need to be assigned to the store with store.edit access permission
        return $this->permissionService->canAccessStore($user, $store->id, StoreAccessPermissions::STORE_EDIT);
    }

    /**
     * Determine whether the user can delete the store.
     */
    public function delete(User $user, Store $store): bool
    {
        // Only admins can delete stores
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can manage employees for the store.
     */
    public function manageEmployees(User $user, Store $store): bool
    {
        // Admins can always manage employees
        if ($user->isAdmin()) {
            return true;
        }

        // Staff need to be assigned to the store with store.manage_employees access permission
        return $this->permissionService->canAccessStore($user, $store->id, StoreAccessPermissions::STORE_MANAGE_EMPLOYEES);
    }

    /**
     * Determine whether the user can manage currencies for the store.
     */
    public function manageCurrencies(User $user, Store $store): bool
    {
        // Admins can always manage currencies
        if ($user->isAdmin()) {
            return true;
        }

        // Staff need to be assigned to the store with store.manage_currencies access permission
        return $this->permissionService->canAccessStore($user, $store->id, StoreAccessPermissions::STORE_MANAGE_CURRENCIES);
    }
}
