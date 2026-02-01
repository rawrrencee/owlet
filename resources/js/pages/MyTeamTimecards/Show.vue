<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Card from 'primevue/card';
import BackButton from '@/components/BackButton.vue';
import TimecardCalendar from '@/components/timecards/TimecardCalendar.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, CalendarDayData, SubordinateInfo } from '@/types';

interface Props {
    employee: SubordinateInfo;
    month: string;
    monthlyData: Record<string, CalendarDayData>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Team Timecards', href: '/my-team-timecards' },
    { title: props.employee.name },
];

function handleDateClick(date: string) {
    router.visit(`/my-team-timecards/${props.employee.id}/${date}`);
}

function handleMonthChange(month: Date) {
    const monthString = month.toISOString().split('T')[0];
    router.get(
        `/my-team-timecards/${props.employee.id}`,
        { month: monthString },
        { preserveState: true },
    );
}

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}
</script>

<template>
    <Head :title="`Timecards - ${employee.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <BackButton fallback-url="/my-team-timecards" />
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
