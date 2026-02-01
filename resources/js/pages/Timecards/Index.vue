<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Card from 'primevue/card';
import { computed, ref } from 'vue';
import ClockWidget from '@/components/timecards/ClockWidget.vue';
import TimecardCalendar from '@/components/timecards/TimecardCalendar.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    BreadcrumbItem,
    CalendarDayData,
    Timecard,
    TimecardStore,
} from '@/types';

interface MonthlyStats {
    total_hours: number;
    days_worked: number;
    daily_average: number;
}

interface Props {
    month: string;
    monthlyData: Record<string, CalendarDayData>;
    monthlyStats: MonthlyStats;
    currentTimecard: Timecard | null;
    isOnBreak: boolean;
    stores: TimecardStore[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Timecards' },
];

const selectedDate = ref<string | undefined>(undefined);

function handleDateClick(date: string) {
    selectedDate.value = date;
    router.visit(`/timecards/${date}`);
}

function handleMonthChange(month: Date) {
    const monthString = month.toISOString().split('T')[0];
    router.get('/timecards', { month: monthString }, { preserveState: true });
}

function formatHours(hours: number): string {
    if (hours === 0) return '0h';
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
}

const monthDisplayName = computed(() => {
    const date = new Date(props.month);
    return date.toLocaleString('default', { month: 'long', year: 'numeric' });
});
</script>

<template>
    <Head title="My Timecards" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 class="heading-lg">My Timecards</h1>

            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Clock Widget and Stats -->
                <div class="flex flex-col gap-4 lg:col-span-1">
                    <ClockWidget
                        :current-timecard="currentTimecard"
                        :is-on-break="isOnBreak"
                        :stores="stores"
                    />

                    <!-- Monthly Stats -->
                    <Card>
                        <template #content>
                            <div class="flex flex-col gap-3">
                                <h3
                                    class="text-sm font-medium text-muted-foreground"
                                >
                                    {{ monthDisplayName }} Summary
                                </h3>
                                <div class="grid grid-cols-3 gap-3">
                                    <div
                                        class="flex flex-col items-center rounded-lg bg-green-50 p-3 dark:bg-green-900/20"
                                    >
                                        <span
                                            class="text-xl font-bold text-green-600 dark:text-green-400"
                                        >
                                            {{
                                                formatHours(
                                                    monthlyStats.total_hours,
                                                )
                                            }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >Total</span
                                        >
                                    </div>
                                    <div
                                        class="flex flex-col items-center rounded-lg bg-muted/50 p-3"
                                    >
                                        <span class="text-xl font-bold">
                                            {{ monthlyStats.days_worked }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >Days</span
                                        >
                                    </div>
                                    <div
                                        class="flex flex-col items-center rounded-lg bg-muted/50 p-3"
                                    >
                                        <span class="text-xl font-bold">
                                            {{
                                                formatHours(
                                                    monthlyStats.daily_average,
                                                )
                                            }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >Avg/Day</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Calendar -->
                <div class="lg:col-span-2">
                    <Card>
                        <template #content>
                            <TimecardCalendar
                                :month="month"
                                :data="monthlyData"
                                :selected-date="selectedDate"
                                @date-click="handleDateClick"
                                @month-change="handleMonthChange"
                            />
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
