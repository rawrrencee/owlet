import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { AppPageProps } from '@/types';

/**
 * Composable for checking user permissions.
 * Provides reactive permission checking methods.
 */
export function usePermissions() {
    const page = usePage<AppPageProps>();

    const permissions = computed(() => page.props.auth?.permissions);
    const isAdmin = computed(() => permissions.value?.is_admin ?? false);

    /**
     * Check if user can access a page based on permission.
     * Admins always have access.
     */
    const canAccessPage = (permission: string): boolean => {
        if (isAdmin.value) return true;
        return permissions.value?.page_permissions?.includes(permission) ?? false;
    };

    /**
     * Check if user can access a specific store.
     * If accessPermission is provided, checks for that specific permission.
     * Admins always have access.
     */
    const canAccessStore = (storeId: number, accessPermission?: string): boolean => {
        if (isAdmin.value) return true;

        const storePerms = permissions.value?.store_permissions?.[storeId];
        if (!storePerms) return false;

        if (!accessPermission) return true;
        return storePerms.access?.includes(accessPermission) ?? false;
    };

    /**
     * Check if user has a specific store operation permission.
     * Admins always have access.
     */
    const hasStoreOperation = (storeId: number, operation: string): boolean => {
        if (isAdmin.value) return true;

        const storePerms = permissions.value?.store_permissions?.[storeId];
        return storePerms?.operations?.includes(operation) ?? false;
    };

    /**
     * Get all store IDs the user has access to.
     */
    const accessibleStoreIds = computed<number[]>(() => {
        if (!permissions.value?.store_permissions) return [];
        return Object.keys(permissions.value.store_permissions).map(Number);
    });

    return {
        isAdmin,
        permissions,
        canAccessPage,
        canAccessStore,
        hasStoreOperation,
        accessibleStoreIds,
    };
}
