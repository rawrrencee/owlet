<script setup lang="ts">
import ClockWidget from '@/components/timecards/ClockWidget.vue';
import QuickLinksWidget from '@/components/dashboard/QuickLinksWidget.vue';
import RecentActivityWidget from '@/components/dashboard/RecentActivityWidget.vue';
import SalesPerformanceWidget from '@/components/dashboard/SalesPerformanceWidget.vue';
import TeamPresentWidget from '@/components/dashboard/TeamPresentWidget.vue';
import UpcomingLeaveWidget from '@/components/dashboard/UpcomingLeaveWidget.vue';
import WeeklyTimecardWidget from '@/components/dashboard/WeeklyTimecardWidget.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type {
    BreadcrumbItem,
    QuickLink,
    RecentActivityItem,
    SalesPerformanceData,
    TeamMember,
    Timecard,
    TimecardStore,
    UpcomingLeaveItem,
    WeeklyTimecardData,
} from '@/types';
import { Head } from '@inertiajs/vue3';

interface Props {
    currentTimecard?: Timecard | null;
    isOnBreak?: boolean;
    stores?: TimecardStore[];
    weeklyTimecard?: WeeklyTimecardData | null;
    teamPresence?: TeamMember[] | null;
    salesPerformance?: SalesPerformanceData | null;
    upcomingLeave?: UpcomingLeaveItem[] | null;
    recentActivity?: RecentActivityItem[] | null;
    quickLinks?: QuickLink[] | null;
}

withDefaults(defineProps<Props>(), {
    currentTimecard: null,
    isOnBreak: false,
    stores: () => [],
    weeklyTimecard: null,
    teamPresence: null,
    salesPerformance: null,
    upcomingLeave: null,
    recentActivity: null,
    quickLinks: null,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
            <!-- Row 1: Clock + Quick Links + Team Present -->
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div v-if="stores && stores.length > 0">
                    <ClockWidget
                        :current-timecard="currentTimecard ?? null"
                        :is-on-break="isOnBreak ?? false"
                        :stores="stores ?? []"
                    />
                </div>
                <div v-if="quickLinks && quickLinks.length > 0">
                    <QuickLinksWidget :data="quickLinks" />
                </div>
                <div v-if="teamPresence" class="md:col-span-2 xl:col-span-1">
                    <TeamPresentWidget :data="teamPresence" />
                </div>
            </div>

            <!-- Row 2: Weekly Hours + Sales Performance -->
            <div
                v-if="weeklyTimecard || salesPerformance"
                class="grid gap-4 md:grid-cols-2"
            >
                <div v-if="weeklyTimecard">
                    <WeeklyTimecardWidget :data="weeklyTimecard" />
                </div>
                <div v-if="salesPerformance">
                    <SalesPerformanceWidget :data="salesPerformance" />
                </div>
            </div>

            <!-- Row 3: Upcoming Leave + Recent Activity -->
            <div
                v-if="(upcomingLeave && upcomingLeave.length > 0) || (recentActivity && recentActivity.length > 0)"
                class="grid gap-4 xl:grid-cols-2"
            >
                <div v-if="upcomingLeave && upcomingLeave.length > 0">
                    <UpcomingLeaveWidget :data="upcomingLeave" />
                </div>
                <div v-if="recentActivity && recentActivity.length > 0">
                    <RecentActivityWidget :data="recentActivity" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
