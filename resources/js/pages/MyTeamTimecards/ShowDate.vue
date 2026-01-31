<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import BackButton from '@/components/BackButton.vue';
import TimecardSummaryCard from '@/components/timecards/TimecardSummaryCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Timecard, SubordinateInfo } from '@/types';

interface Props {
    employee: SubordinateInfo;
    date: string;
    dateFormatted: string;
    timecards: { data: Timecard[] };
}

const props = defineProps<Props>();


const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Team Timecards', href: '/my-team-timecards' },
    { title: props.employee.name, href: `/my-team-timecards/${props.employee.id}` },
    { title: props.dateFormatted },
];

function getTotalHours(): number {
    return props.timecards.data.reduce((sum, t) => sum + t.hours_worked, 0);
}

function formatHours(hours: number): string {
    if (hours === 0) return '0h';
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
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
    <Head :title="`${employee.name} - ${dateFormatted}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <BackButton :fallback-url="`/my-team-timecards/${employee.id}`" />
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
                        <p class="text-sm text-muted-foreground">
                            {{ dateFormatted }} - Total: {{ formatHours(getTotalHours()) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Timecards List -->
            <div v-if="timecards.data.length > 0" class="flex flex-col gap-4">
                <TimecardSummaryCard
                    v-for="timecard in timecards.data"
                    :key="timecard.id"
                    :timecard="timecard"
                />
            </div>

            <!-- Empty State -->
            <Card v-else>
                <template #content>
                    <div class="flex flex-col items-center justify-center gap-4 py-8">
                        <i class="pi pi-clock text-4xl text-muted-foreground"></i>
                        <div class="text-center">
                            <h3 class="font-medium">No timecards</h3>
                            <p class="text-sm text-muted-foreground">
                                No time entries recorded for this date.
                            </p>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
