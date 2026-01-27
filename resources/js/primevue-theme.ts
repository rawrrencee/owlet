import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

/**
 * Custom PrimeVue theme preset - Industrial Grey
 *
 * A warm grey color scheme with no blue tint, designed for
 * a professional, industrial aesthetic suitable for CRUD applications.
 */

// Warm grey palette (stone-based, no blue tint)
const stone = {
    50: '#fafaf9',
    100: '#f5f5f4',
    200: '#e7e5e4',
    300: '#d6d3d1',
    400: '#a8a29e',
    500: '#78716c',
    600: '#57534e',
    700: '#44403c',
    800: '#292524',
    900: '#1c1917',
    950: '#0c0a09',
};

export const OwletPreset = definePreset(Aura, {
    primitive: {
        // Override neutral with warm stone greys
        neutral: stone,
        // Warmer border radius for industrial look
        borderRadius: {
            none: '0',
            xs: '2px',
            sm: '3px',
            md: '4px',
            lg: '6px',
            xl: '8px',
        },
    },
    semantic: {
        // Map primary to stone for a warm neutral look
        primary: {
            50: stone[50],
            100: stone[100],
            200: stone[200],
            300: stone[300],
            400: stone[400],
            500: stone[500],
            600: stone[600],
            700: stone[700],
            800: stone[800],
            900: stone[900],
            950: stone[950],
        },
        colorScheme: {
            light: {
                primary: {
                    color: stone[800],
                    inverseColor: '#ffffff',
                    hoverColor: stone[900],
                    activeColor: stone[950],
                },
                highlight: {
                    background: stone[100],
                    focusBackground: stone[200],
                    color: stone[800],
                    focusColor: stone[900],
                },
                surface: {
                    0: '#ffffff',
                    50: stone[50],
                    100: stone[100],
                    200: stone[200],
                    300: stone[300],
                    400: stone[400],
                    500: stone[500],
                    600: stone[600],
                    700: stone[700],
                    800: stone[800],
                    900: stone[900],
                    950: stone[950],
                },
                formField: {
                    background: '#ffffff',
                    disabledBackground: stone[100],
                    filledBackground: stone[50],
                    filledHoverBackground: stone[100],
                    filledFocusBackground: stone[50],
                    borderColor: stone[300],
                    hoverBorderColor: stone[400],
                    focusBorderColor: stone[500],
                    invalidBorderColor: '#dc2626',
                    color: stone[900],
                    disabledColor: stone[500],
                    placeholderColor: stone[400],
                    invalidPlaceholderColor: '#dc2626',
                    floatLabelColor: stone[500],
                    floatLabelFocusColor: stone[600],
                    floatLabelActiveColor: stone[600],
                    floatLabelInvalidColor: '#dc2626',
                    iconColor: stone[500],
                    shadow: '0 1px 2px 0 rgba(0, 0, 0, 0.04)',
                },
            },
            dark: {
                primary: {
                    color: stone[200],
                    inverseColor: stone[950],
                    hoverColor: stone[100],
                    activeColor: stone[50],
                },
                highlight: {
                    background: 'rgba(168, 162, 158, 0.16)',
                    focusBackground: 'rgba(168, 162, 158, 0.24)',
                    color: 'rgba(255, 255, 255, 0.87)',
                    focusColor: 'rgba(255, 255, 255, 0.87)',
                },
                surface: {
                    0: '#ffffff',
                    50: stone[50],
                    100: stone[100],
                    200: stone[200],
                    300: stone[300],
                    400: stone[400],
                    500: stone[500],
                    600: stone[600],
                    700: stone[700],
                    800: stone[800],
                    900: stone[900],
                    950: stone[950],
                },
                formField: {
                    background: stone[900],
                    disabledBackground: stone[800],
                    filledBackground: stone[800],
                    filledHoverBackground: stone[700],
                    filledFocusBackground: stone[800],
                    borderColor: stone[600],
                    hoverBorderColor: stone[500],
                    focusBorderColor: stone[400],
                    invalidBorderColor: '#ef4444',
                    color: stone[100],
                    disabledColor: stone[500],
                    placeholderColor: stone[500],
                    invalidPlaceholderColor: '#ef4444',
                    floatLabelColor: stone[400],
                    floatLabelFocusColor: stone[300],
                    floatLabelActiveColor: stone[300],
                    floatLabelInvalidColor: '#ef4444',
                    iconColor: stone[400],
                    shadow: '0 1px 2px 0 rgba(0, 0, 0, 0.2)',
                },
            },
        },
    },
    components: {
        button: {
            root: {
                borderRadius: '4px',
            },
            padding: {
                x: '0.875rem',
                y: '0.5rem',
            },
            sm: {
                font: {
                    size: '0.8125rem',
                },
                padding: {
                    x: '0.75rem',
                    y: '0.375rem',
                },
            },
            colorScheme: {
                light: {
                    root: {
                        primary: {
                            background: stone[800],
                            hoverBackground: stone[900],
                            activeBackground: stone[950],
                            borderColor: stone[800],
                            hoverBorderColor: stone[900],
                            activeBorderColor: stone[950],
                            color: '#ffffff',
                            hoverColor: '#ffffff',
                            activeColor: '#ffffff',
                        },
                        secondary: {
                            background: stone[100],
                            hoverBackground: stone[200],
                            activeBackground: stone[300],
                            borderColor: stone[300],
                            hoverBorderColor: stone[400],
                            activeBorderColor: stone[400],
                            color: stone[800],
                            hoverColor: stone[900],
                            activeColor: stone[900],
                        },
                    },
                },
                dark: {
                    root: {
                        primary: {
                            background: stone[200],
                            hoverBackground: stone[100],
                            activeBackground: stone[50],
                            borderColor: stone[200],
                            hoverBorderColor: stone[100],
                            activeBorderColor: stone[50],
                            color: stone[900],
                            hoverColor: stone[950],
                            activeColor: stone[950],
                        },
                        secondary: {
                            background: stone[700],
                            hoverBackground: stone[600],
                            activeBackground: stone[500],
                            borderColor: stone[600],
                            hoverBorderColor: stone[500],
                            activeBorderColor: stone[500],
                            color: stone[100],
                            hoverColor: stone[50],
                            activeColor: stone[50],
                        },
                    },
                },
            },
        },
        inputtext: {
            root: {
                borderRadius: '4px',
            },
            padding: {
                x: '0.75rem',
                y: '0.5rem',
            },
            sm: {
                font: {
                    size: '0.8125rem',
                },
                padding: {
                    x: '0.625rem',
                    y: '0.375rem',
                },
            },
        },
        select: {
            root: {
                borderRadius: '4px',
            },
            padding: {
                x: '0.75rem',
                y: '0.5rem',
            },
            sm: {
                font: {
                    size: '0.8125rem',
                },
            },
        },
        datatable: {
            root: {
                borderRadius: '6px',
            },
            header: {
                background: stone[50],
                borderColor: stone[200],
                color: stone[700],
                cell: {
                    padding: '0.625rem 0.875rem',
                },
            },
            body: {
                cell: {
                    padding: '0.625rem 0.875rem',
                },
            },
            row: {
                stripedBackground: stone[50],
            },
            colorScheme: {
                light: {
                    header: {
                        background: stone[50],
                        borderColor: stone[200],
                        color: stone[600],
                    },
                    body: {
                        background: '#ffffff',
                        hoverBackground: stone[100],
                    },
                    row: {
                        stripedBackground: 'rgba(250, 250, 249, 0.5)',
                    },
                },
                dark: {
                    header: {
                        background: stone[800],
                        borderColor: stone[700],
                        color: stone[300],
                    },
                    body: {
                        background: stone[900],
                        hoverBackground: stone[800],
                    },
                    row: {
                        stripedBackground: 'rgba(41, 37, 36, 0.5)',
                    },
                },
            },
        },
        tag: {
            root: {
                borderRadius: '3px',
            },
            padding: {
                x: '0.5rem',
                y: '0.1875rem',
            },
            font: {
                size: '0.75rem',
                weight: '600',
            },
        },
        tabs: {
            tab: {
                padding: '0.625rem 1rem',
            },
            tablist: {
                background: 'transparent',
                borderColor: stone[200],
            },
            colorScheme: {
                light: {
                    tablist: {
                        background: 'transparent',
                        borderColor: stone[200],
                    },
                    tab: {
                        background: 'transparent',
                        hoverBackground: stone[100],
                        activeBackground: 'transparent',
                        color: stone[600],
                        hoverColor: stone[800],
                        activeColor: stone[900],
                    },
                    activeBar: {
                        background: stone[800],
                    },
                },
                dark: {
                    tablist: {
                        background: 'transparent',
                        borderColor: stone[700],
                    },
                    tab: {
                        background: 'transparent',
                        hoverBackground: stone[800],
                        activeBackground: 'transparent',
                        color: stone[400],
                        hoverColor: stone[200],
                        activeColor: stone[100],
                    },
                    activeBar: {
                        background: stone[200],
                    },
                },
            },
        },
        card: {
            root: {
                borderRadius: '6px',
            },
            colorScheme: {
                light: {
                    root: {
                        background: '#ffffff',
                        shadow: '0 1px 3px 0 rgba(0, 0, 0, 0.06), 0 1px 2px -1px rgba(0, 0, 0, 0.06)',
                    },
                },
                dark: {
                    root: {
                        background: stone[900],
                        shadow: '0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px -1px rgba(0, 0, 0, 0.3)',
                    },
                },
            },
        },
        dialog: {
            root: {
                borderRadius: '8px',
            },
            colorScheme: {
                light: {
                    root: {
                        background: '#ffffff',
                    },
                    header: {
                        background: '#ffffff',
                    },
                },
                dark: {
                    root: {
                        background: stone[900],
                    },
                    header: {
                        background: stone[900],
                    },
                },
            },
        },
        toast: {
            root: {
                borderRadius: '6px',
            },
        },
        paginator: {
            colorScheme: {
                light: {
                    root: {
                        background: 'transparent',
                    },
                },
                dark: {
                    root: {
                        background: 'transparent',
                    },
                },
            },
        },
    },
} as any);
