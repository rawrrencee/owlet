<script setup lang="ts">
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
import { type AppPageProps, type NavSection } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    BadgeCheck,
    BellRing,
    Building2,
    CalendarClock,
    ClipboardCheck,
    ClipboardList,
    Clock,
    Coins,
    DatabaseZap,
    Folder,
    Layers,
    LayoutGrid,
    ListChecks,
    Network,
    Package,
    PackageSearch,
    Percent,
    ScrollText,
    ShoppingCart,
    Store,
    Tag,
    Truck,
    Users,
    UsersRound,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const iconMap: Record<string, LucideIcon> = {
    ArrowLeftRight,
    BadgeCheck,
    BellRing,
    Building2,
    CalendarClock,
    ClipboardCheck,
    ClipboardList,
    Clock,
    Coins,
    DatabaseZap,
    Folder,
    Layers,
    LayoutGrid,
    ListChecks,
    Network,
    Package,
    PackageSearch,
    Percent,
    ScrollText,
    ShoppingCart,
    Store,
    Tag,
    Truck,
    Users,
    UsersRound,
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
