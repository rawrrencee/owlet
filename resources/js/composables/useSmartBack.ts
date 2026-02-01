import { router } from '@inertiajs/vue3';

const NAV_HISTORY_KEY = 'app_nav_history';
const NAV_INITIALIZED_KEY = 'app_nav_initialized';
const NAV_SKIP_URL_KEY = 'app_nav_skip_url';
let listenerAttached = false;

// Initialize navigation tracking on first script load
if (typeof window !== 'undefined') {
    // Detect if this is a page refresh or direct navigation (not Inertia SPA navigation)
    // Check using PerformanceNavigationTiming API
    const navEntries = performance.getEntriesByType(
        'navigation',
    ) as PerformanceNavigationTiming[];
    const navType = navEntries.length > 0 ? navEntries[0].type : 'navigate';
    const isPageRefreshOrDirectAccess =
        navType === 'reload' || navType === 'navigate';

    // Reset history on page refresh, direct access, or first visit
    if (
        isPageRefreshOrDirectAccess ||
        sessionStorage.getItem(NAV_HISTORY_KEY) === null
    ) {
        sessionStorage.setItem(NAV_HISTORY_KEY, JSON.stringify([]));
        sessionStorage.removeItem(NAV_INITIALIZED_KEY);
        sessionStorage.removeItem(NAV_SKIP_URL_KEY);
    }

    // Listen for Inertia navigations to track URL history
    if (!listenerAttached) {
        listenerAttached = true;
        router.on('navigate', (event) => {
            let history: string[] = JSON.parse(
                sessionStorage.getItem(NAV_HISTORY_KEY) || '[]',
            );
            const currentUrl = event.detail.page.url;
            // Get pathname for comparison (ignore query params)
            const currentPath = currentUrl.split('?')[0];

            // Check if there's a URL to skip (remove from history)
            const skipUrl = sessionStorage.getItem(NAV_SKIP_URL_KEY);
            if (skipUrl) {
                const skipPath = skipUrl.split('?')[0];

                // Only process the skip if we're navigating to a DIFFERENT page
                // This handles the case where the server redirects back to the same Edit page
                if (currentPath !== skipPath) {
                    // Remove all occurrences of the skip URL from history (compare by path)
                    history = history.filter(
                        (url) => url.split('?')[0] !== skipPath,
                    );
                    sessionStorage.removeItem(NAV_SKIP_URL_KEY);
                    // Add the new URL (avoid duplicates)
                    const lastUrl = history[history.length - 1];
                    if (lastUrl !== currentUrl) {
                        history.push(currentUrl);
                        if (history.length > 50) {
                            history.shift();
                        }
                    }
                    sessionStorage.setItem(
                        NAV_HISTORY_KEY,
                        JSON.stringify(history),
                    );
                }
                // If navigating to the same page (skipUrl), don't add it to history again
                // and keep the skip marker for the next navigation
            } else {
                // Avoid adding duplicate consecutive entries
                const lastUrl = history[history.length - 1];
                if (lastUrl !== currentUrl) {
                    history.push(currentUrl);
                    // Keep only last 50 entries to prevent unbounded growth
                    if (history.length > 50) {
                        history.shift();
                    }
                    sessionStorage.setItem(
                        NAV_HISTORY_KEY,
                        JSON.stringify(history),
                    );
                }
            }
            sessionStorage.setItem(NAV_INITIALIZED_KEY, 'true');
        });
    }
}

export function useSmartBack(fallbackUrl: string) {
    const goBack = () => {
        const history: string[] = JSON.parse(
            sessionStorage.getItem(NAV_HISTORY_KEY) || '[]',
        );

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

/**
 * Mark the current page URL to be removed from navigation history on next navigation.
 * Call this BEFORE form submission on Edit pages. If the submission succeeds and
 * redirects to the View page, the Edit page will be removed from history.
 * Call clearSkipPageInHistory() in onError to cancel if submission fails.
 *
 * Example flow:
 * - Index → Edit → (call skipCurrentPageInHistory, then form.put()) → View
 * - When View page loads, Edit URL is removed from history
 * - Back from View goes to Index, not Edit
 */
export function skipCurrentPageInHistory() {
    if (typeof window !== 'undefined') {
        sessionStorage.setItem(
            NAV_SKIP_URL_KEY,
            window.location.pathname + window.location.search,
        );
    }
}

/**
 * Clear the skip page marker. Call this in onError if form submission fails.
 */
export function clearSkipPageInHistory() {
    if (typeof window !== 'undefined') {
        sessionStorage.removeItem(NAV_SKIP_URL_KEY);
    }
}

/**
 * @deprecated Use skipCurrentPageInHistory instead
 */
export function removeCurrentFromHistory() {
    skipCurrentPageInHistory();
}
