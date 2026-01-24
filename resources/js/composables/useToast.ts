import { usePage } from '@inertiajs/vue3';
import { useToast as usePrimeToast } from 'primevue/usetoast';
import { watch } from 'vue';

export interface ApiError {
    message: string;
    code?: string;
    debug?: string;
}

export interface FlashMessages {
    success?: string;
    error?: ApiError | string;
}

const isDebugMode = import.meta.env.VITE_APP_DEBUG === 'true';

export function useToast() {
    const toast = usePrimeToast();

    function showSuccess(message: string, summary = 'Success') {
        toast.add({
            severity: 'success',
            summary,
            detail: message,
            life: 5000,
        });
    }

    function showError(error: ApiError | string, summary = 'Error') {
        const message = typeof error === 'string' ? error : error.message;
        const code = typeof error === 'string' ? undefined : error.code;
        const debug = typeof error === 'string' ? undefined : error.debug;

        let detail = message;
        if (code) {
            detail = `[${code}] ${message}`;
        }

        toast.add({
            severity: 'error',
            summary,
            detail,
            life: isDebugMode && debug ? 0 : 8000, // Sticky in debug mode with debug info
        });

        // Show debug info in a separate toast if available and in debug mode
        if (isDebugMode && debug) {
            toast.add({
                severity: 'warn',
                summary: 'Debug Info',
                detail: debug,
                life: 0, // Sticky - must be manually dismissed
            });
        }
    }

    function showInfo(message: string, summary = 'Info') {
        toast.add({
            severity: 'info',
            summary,
            detail: message,
            life: 5000,
        });
    }

    function showWarning(message: string, summary = 'Warning') {
        toast.add({
            severity: 'warn',
            summary,
            detail: message,
            life: 6000,
        });
    }

    return {
        toast,
        showSuccess,
        showError,
        showInfo,
        showWarning,
        isDebugMode,
    };
}

/**
 * Composable that watches for flash messages from Inertia and shows toasts.
 * Should be called in the setup function of pages/layouts.
 */
export function useFlashToast() {
    const page = usePage<{ flash?: FlashMessages }>();
    const { showSuccess, showError } = useToast();

    watch(
        () => page.props.flash,
        (flash) => {
            if (flash?.success) {
                showSuccess(flash.success);
            }
            if (flash?.error) {
                showError(flash.error);
            }
        },
        { immediate: true },
    );
}
