<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    BookOpen,
    Building2,
    Folder,
    LayoutGrid,
    Store,
    Users,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type AppPageProps, type NavItem, type NavSection } from '@/types';
import AppLogo from './AppLogo.vue';

const iconMap: Record<string, LucideIcon> = {
    BadgeCheck,
    BookOpen,
    Building2,
    Folder,
    LayoutGrid,
    Store,
    Users,
};

const page = usePage<AppPageProps>();

const navSections = computed<NavSection[]>(() => {
    return page.props.navigation.map((section) => ({
        title: section.title,
        items: section.items.map((item) => ({
            title: item.title,
            href: item.href,
            icon: item.icon ? iconMap[item.icon] : undefined,
        })),
    }));
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :sections="navSections" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
