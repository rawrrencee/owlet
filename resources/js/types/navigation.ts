import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export type BreadcrumbItem = {
    title: string;
    href?: string;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
};

export type NavSection = {
    title: string;
    items: NavItem[];
};

export type ServerNavItem = {
    title: string;
    href: string;
    icon?: string;
};

export type ServerNavSection = {
    title: string;
    items: ServerNavItem[];
};
