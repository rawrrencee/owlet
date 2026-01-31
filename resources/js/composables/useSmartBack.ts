import { router } from '@inertiajs/vue3';

const NAV_HISTORY_KEY = 'app_nav_history';
const NAV_INITIALIZED_KEY = 'app_nav_initialized';
let listenerAttached = false;

// Initialize navigation tracking on first script load
if (typeof window !== 'undefined') {
    // Detect if this is a page refresh or direct navigation (not Inertia SPA navigation)
    // Check using PerformanceNavigationTiming API
    const navEntries = performance.getEntriesByType('navigation') as PerformanceNavigationTiming[];
    const navType = navEntries.length > 0 ? navEntries[0].type : 'navigate';
    const isPageRefreshOrDirectAccess = navType === 'reload' || navType === 'navigate';

    // Reset history on page refresh, direct access, or first visit
    if (isPageRefreshOrDirectAccess || sessionStorage.getItem(NAV_HISTORY_KEY) === null) {
        sessionStorage.setItem(NAV_HISTORY_KEY, JSON.stringify([]));
        sessionStorage.removeItem(NAV_INITIALIZED_KEY);
    }

    // Listen for Inertia navigations to track URL history
    if (!listenerAttached) {
        listenerAttached = true;
        router.on('navigate', (event) => {
            const history: string[] = JSON.parse(sessionStorage.getItem(NAV_HISTORY_KEY) || '[]');
            const currentUrl = event.detail.page.url;
            history.push(currentUrl);
            // Keep only last 50 entries to prevent unbounded growth
            if (history.length > 50) {
                history.shift();
            }
            sessionStorage.setItem(NAV_HISTORY_KEY, JSON.stringify(history));
            sessionStorage.setItem(NAV_INITIALIZED_KEY, 'true');
        });
    }
}

export function useSmartBack(fallbackUrl: string) {
    const goBack = () => {
        const history: string[] = JSON.parse(sessionStorage.getItem(NAV_HISTORY_KEY) || '[]');

        // Remove current page from history
        history.pop();

        // Get the previous URL
        const previousUrl = history.pop();

        // Update stored history
        sessionStorage.setItem(NAV_HISTORY_KEY, JSON.stringify(history));

        if (previousUrl) {
            // Use Inertia router to get fresh data
            router.visit(previousUrl);
            return;
        }

        // Fallback to specified URL for direct access
        router.get(fallbackUrl);
    };

    return { goBack };
}
