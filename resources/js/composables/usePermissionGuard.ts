import { useToast } from '@/composables/useToast';
import type { AppPageProps } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { onUnmounted } from 'vue';

/**
 * Routes that require admin role access only.
 */
const ADMIN_ONLY_ROUTES = [
    '/users',
    '/customers',
    '/companies',
    '/designations',
    '/documents',
    '/organisation-chart',
    '/management/timecards',
    '/management/employee-requests',
];

/**
 * Route permission mappings for commerce routes.
 * Maps route prefix to required page permission.
 */
const PERMISSION_ROUTES: Record<string, string> = {
    '/brands': 'brands.view',
    '/categories': 'categories.view',
    '/products': 'products.view',
    '/suppliers': 'suppliers.view',
    '/stores': 'stores.access',
};

/**
 * Composable that guards routes based on user permissions.
 * - Admin-only routes block staff users
 * - Commerce routes check for specific page permissions
 */
export function usePermissionGuard() {
    const page = usePage<AppPageProps>();
    const { showError } = useToast();

    const isAdminOnlyRoute = (pathname: string): boolean => {
        return ADMIN_ONLY_ROUTES.some((prefix) => pathname.startsWith(prefix));
    };

    const getRequiredPermission = (pathname: string): string | null => {
        for (const [prefix, permission] of Object.entries(PERMISSION_ROUTES)) {
            if (pathname.startsWith(prefix)) {
                return permission;
            }
        }
        return null;
    };

    const hasPagePermission = (permission: string): boolean => {
        const permissions = page.props.auth?.permissions;
        if (!permissions) return false;
        if (permissions.is_admin) return true;
        return permissions.page_permissions?.includes(permission) ?? false;
    };

    const removeListener = router.on('before', (event) => {
        const user = page.props.auth?.user;
        if (!user) return true;

        const targetUrl = event.detail.visit.url.href;
        let pathname: string;

        try {
            pathname = new URL(targetUrl, window.location.origin).pathname;
        } catch {
            return true;
        }

        // Check admin-only routes
        if (isAdminOnlyRoute(pathname)) {
            if (user.role !== 'admin') {
                showError(
                    "You're not allowed to access this page",
                    'Access Denied',
                );
                return false;
            }
            return true;
        }

        // Check permission-based routes
        const requiredPermission = getRequiredPermission(pathname);
        if (requiredPermission) {
            if (!hasPagePermission(requiredPermission)) {
                showError(
                    "You don't have permission to access this page",
                    'Access Denied',
                );
                return false;
            }
        }

        return true;
    });

    onUnmounted(() => {
        removeListener();
    });
}
