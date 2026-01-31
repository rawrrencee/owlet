import { router } from '@inertiajs/vue3';

const NAV_COUNT_KEY = 'app_nav_count';
let listenerAttached = false;

// Initialize navigation tracking on first script load
if (typeof window !== 'undefined') {
    // Reset counter on fresh page load (not SPA navigation)
    if (sessionStorage.getItem(NAV_COUNT_KEY) === null) {
        sessionStorage.setItem(NAV_COUNT_KEY, '0');
    }

    // Listen for Inertia navigations to increment counter
    if (!listenerAttached) {
        listenerAttached = true;
        router.on('navigate', () => {
            const count = parseInt(sessionStorage.getItem(NAV_COUNT_KEY) || '0', 10);
            sessionStorage.setItem(NAV_COUNT_KEY, String(count + 1));
        });
    }
}

export function useSmartBack(fallbackUrl: string) {
    const goBack = () => {
        // Navigation count > 1 means user has navigated at least once within the app
        // (first navigate event is the initial page load)
        const navCount = parseInt(sessionStorage.getItem(NAV_COUNT_KEY) || '0', 10);
        const hasInternalNavigation = navCount > 1;

        if (hasInternalNavigation) {
            window.history.back();
            return;
        }

        // Fallback to specified URL for direct access
        router.get(fallbackUrl);
    };

    return { goBack };
}
