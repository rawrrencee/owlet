import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

/**
 * Custom PrimeVue theme preset based on Aura with dark neutral colors
 * and compact UI sizing.
 *
 * Note: Using type assertion because PrimeVue's TypeScript types
 * don't fully match the runtime API for design tokens.
 */
export const OwletPreset = definePreset(Aura, {
    semantic: {
        // Map primary to neutral for a neutral dark look
        primary: {
            50: '{neutral.50}',
            100: '{neutral.100}',
            200: '{neutral.200}',
            300: '{neutral.300}',
            400: '{neutral.400}',
            500: '{neutral.500}',
            600: '{neutral.600}',
            700: '{neutral.700}',
            800: '{neutral.800}',
            900: '{neutral.900}',
            950: '{neutral.950}',
        },
        colorScheme: {
            light: {
                primary: {
                    color: '{neutral.700}',
                    inverseColor: '#ffffff',
                    hoverColor: '{neutral.800}',
                    activeColor: '{neutral.900}',
                },
                highlight: {
                    background: '{neutral.100}',
                    focusBackground: '{neutral.200}',
                    color: '{neutral.700}',
                    focusColor: '{neutral.800}',
                },
                surface: {
                    0: '#ffffff',
                    50: '{neutral.50}',
                    100: '{neutral.100}',
                    200: '{neutral.200}',
                    300: '{neutral.300}',
                    400: '{neutral.400}',
                    500: '{neutral.500}',
                    600: '{neutral.600}',
                    700: '{neutral.700}',
                    800: '{neutral.800}',
                    900: '{neutral.900}',
                    950: '{neutral.950}',
                },
            },
            dark: {
                primary: {
                    color: '{neutral.300}',
                    inverseColor: '{neutral.950}',
                    hoverColor: '{neutral.200}',
                    activeColor: '{neutral.100}',
                },
                highlight: {
                    background: 'rgba(156, 163, 175, 0.16)',
                    focusBackground: 'rgba(156, 163, 175, 0.24)',
                    color: 'rgba(255,255,255,.87)',
                    focusColor: 'rgba(255,255,255,.87)',
                },
                surface: {
                    0: '#ffffff',
                    50: '{neutral.50}',
                    100: '{neutral.100}',
                    200: '{neutral.200}',
                    300: '{neutral.300}',
                    400: '{neutral.400}',
                    500: '{neutral.500}',
                    600: '{neutral.600}',
                    700: '{neutral.700}',
                    800: '{neutral.800}',
                    900: '{neutral.900}',
                    950: '{neutral.950}',
                },
            },
        },
    },
    components: {
        button: {
            padding: {
                x: '0.75rem',
                y: '0.375rem',
            },
            sm: {
                font: {
                    size: '0.75rem',
                },
                padding: {
                    x: '0.625rem',
                    y: '0.25rem',
                },
            },
        },
        inputtext: {
            padding: {
                x: '0.625rem',
                y: '0.375rem',
            },
        },
        select: {
            padding: {
                x: '0.625rem',
                y: '0.375rem',
            },
        },
        datatable: {
            header: {
                cell: {
                    padding: '0.5rem 0.75rem',
                },
            },
            body: {
                cell: {
                    padding: '0.5rem 0.75rem',
                },
            },
        },
        tag: {
            padding: {
                x: '0.5rem',
                y: '0.125rem',
            },
            font: {
                size: '0.75rem',
            },
        },
        tabs: {
            tab: {
                padding: '0.5rem 0.875rem',
            },
        },
    },
 
} as any);
