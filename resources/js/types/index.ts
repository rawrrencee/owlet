export * from './audit';
export * from './auth';
export * from './brand';
export * from './category';
export * from './company';
export * from './country';
export * from './currency';
export * from './customer';
export * from './delivery-order';
export * from './employee';
export * from './hierarchy';
export * from './inventory-log';
export * from './leave';
export * from './navigation';
export * from './offer';
export * from './pagination';
export * from './payment-mode';
export * from './permissions';
export * from './product';
export * from './purchase-order';
export * from './quotation';
export * from './stocktake';
export * from './store';
export * from './supplier';
export * from './tag';
export * from './timecard';
export * from './transaction';
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
