export * from './auth';
export * from './navigation';
export * from './ui';

import type { Auth } from './auth';
import type { ServerNavItem } from './navigation';

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    navigation: ServerNavItem[];
    [key: string]: unknown;
};
