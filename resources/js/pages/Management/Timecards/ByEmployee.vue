<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import TimecardCalendar from '@/components/timecards/TimecardCalendar.vue';
import { useSmartBack } from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, CalendarDayData, SubordinateInfo } from '@/types';

interface Props {
    employee: SubordinateInfo;
    month: string;
    monthlyData: Record<string, CalendarDayData>;
}

const props = defineProps<Props>();

const { goBack } = useSmartBack('/management/timecards');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Management' },
    { title: 'Timecards', href: '/management/timecards' },
    { title: props.employee.name },
];

function handleDateClick(date: string) {
    // Navigate to the date view filtered by employee
    // Or directly to the timecard if there's only one
    const dayData = props.monthlyData[date];
    if (dayData?.stores?.length === 1) {
        // Could navigate directly to edit if desired
    }
    router.visit(`/management/timecards/date/${date}`);
}

function handleMonthChange(month: Date) {
    const monthString = month.toISOString().split('T')[0];
    router.get(`/management/timecards/employee/${props.employee.id}`, { month: monthString }, { preserveState: true });
}

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function navigateToCreate() {
    router.visit(`/management/timecards/create?employee_id=${props.employee.id}`);
}
</script>

<template>
    <Head :title="`Timecards - ${employee.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Button
                        icon="pi pi-arrow-left"
                        text
                        size="small"
                        severity="secondary"
                        @click="goBack"
                        v-tooltip.top="'Back'"
                    />
                    <Avatar
                        v-if="employee.profile_picture_url"
                        :image="employee.profile_picture_url"
                        shape="circle"
                        size="large"
                    />
                    <Avatar
                        v-else
                        :label="getInitials(employee.name)"
                        shape="circle"
                        size="large"
                        class="bg-primary/10 text-primary"
                    />
                    <div>
                        <h1 class="heading-lg">{{ employee.name }}</h1>
                        <p
                            v-if="employee.employee_number"
                            class="text-sm text-muted-foreground"
                        >
                            #{{ employee.employee_number }}
                        </p>
                    </div>
                </div>
                <Button
                    label="Add Timecard"
                    icon="pi pi-plus"
                    size="small"
                    @click="navigateToCreate"
                />
            </div>

            <!-- Calendar -->
            <Card>
                <template #content>
                    <TimecardCalendar
                        :month="month"
                        :data="monthlyData"
                        @date-click="handleDateClick"
                        @month-change="handleMonthChange"
                    />
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
