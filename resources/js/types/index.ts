export * from './auth';
export * from './navigation';
export * from './ui';

import type { Auth } from './auth';
import type { ServerNavSection } from './navigation';

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    navigation: ServerNavSection[];
    [key: string]: unknown;
};
