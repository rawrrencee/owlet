<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Card from 'primevue/card';
import { computed, ref } from 'vue';
import ClockWidget from '@/components/timecards/ClockWidget.vue';
import TimecardCalendar from '@/components/timecards/TimecardCalendar.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, CalendarDayData, Timecard, TimecardStore } from '@/types';

interface Props {
    month: string;
    monthlyData: Record<string, CalendarDayData>;
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
</script>

<template>
    <Head title="My Timecards" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 class="heading-lg">My Timecards</h1>

            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Clock Widget -->
                <div class="lg:col-span-1">
                    <ClockWidget
                        :current-timecard="currentTimecard"
                        :is-on-break="isOnBreak"
                        :stores="stores"
                    />
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
