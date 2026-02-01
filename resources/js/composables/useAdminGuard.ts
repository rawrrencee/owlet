import { router, usePage } from '@inertiajs/vue3';
import { onUnmounted } from 'vue';
import { useToast } from '@/composables/useToast';
import type { AppPageProps } from '@/types';

/**
 * Route prefixes that require admin role access.
 * Staff users will be blocked from navigating to these routes.
 */
const ADMIN_ROUTE_PREFIXES = [
    '/users',
    '/customers',
    '/companies',
    '/designations',
    '/stores',
    '/documents',
    '/organisation-chart',
    '/management/timecards',
];

/**
 * Composable that guards admin routes from staff users.
 * Shows an error toast and cancels navigation if a staff user tries to access admin routes.
 */
export function useAdminGuard() {
    const page = usePage<AppPageProps>();
    const { showError } = useToast();

    const isAdminRoute = (url: string): boolean => {
        // Parse the URL to get the pathname
        try {
            const pathname = new URL(url, window.location.origin).pathname;
            return ADMIN_ROUTE_PREFIXES.some((prefix) =>
                pathname.startsWith(prefix),
            );
        } catch {
            return false;
        }
    };

    const removeListener = router.on('before', (event) => {
        const user = page.props.auth?.user;
        const targetUrl = event.detail.visit.url.href;

        // If user is not admin and trying to access admin route, block it
        if (user?.role !== 'admin' && isAdminRoute(targetUrl)) {
            showError(
                "You're not allowed to access this page",
                'Access Denied',
            );
            return false;
        }

        return true;
    });

    onUnmounted(() => {
        removeListener();
    });
}
