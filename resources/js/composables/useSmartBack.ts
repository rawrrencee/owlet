import { router } from '@inertiajs/vue3';

export function useSmartBack(fallbackUrl: string) {
    const goBack = () => {
        // Check if referrer is from same origin (internal navigation)
        if (document.referrer) {
            try {
                const referrerOrigin = new URL(document.referrer).origin;
                if (referrerOrigin === window.location.origin) {
                    window.history.back();
                    return;
                }
            } catch {
                // Invalid URL, fall through to fallback
            }
        }
        // Fallback to specified URL for direct access
        router.get(fallbackUrl);
    };

    return { goBack };
}
