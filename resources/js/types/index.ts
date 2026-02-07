export * from './audit';
export * from './auth';
export * from './brand';
export * from './category';
export * from './company';
export * from './country';
export * from './currency';
export * from './customer';
export * from './employee';
export * from './hierarchy';
export * from './navigation';
export * from './pagination';
export * from './permissions';
export * from './product';
export * from './stocktake';
export * from './store';
export * from './supplier';
export * from './tag';
export * from './timecard';
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
