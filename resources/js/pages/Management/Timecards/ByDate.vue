<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import TimecardSummaryCard from '@/components/timecards/TimecardSummaryCard.vue';
import { useSmartBack } from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Timecard } from '@/types';

interface GroupedTimecard {
    employee: {
        id: number;
        name: string;
        employee_number: string | null;
        profile_picture_url: string | null;
    };
    timecards: { data: Timecard[] };
    total_hours: number;
}

interface Props {
    date: string;
    dateFormatted: string;
    groupedTimecards: GroupedTimecard[];
    totalHours: number;
    employeeCount: number;
}

const props = defineProps<Props>();

const { goBack } = useSmartBack('/management/timecards');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Management' },
    { title: 'Timecards', href: '/management/timecards' },
    { title: props.dateFormatted },
];

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

function navigateToEmployee(employeeId: number) {
    router.visit(`/management/timecards/employee/${employeeId}`);
}

function navigateToCreate() {
    router.visit(`/management/timecards/create?date=${props.date}`);
}
</script>

<template>
    <Head :title="`Timecards - ${dateFormatted}`" />

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
                    <div>
                        <h1 class="heading-lg">{{ dateFormatted }}</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ employeeCount }} employee{{ employeeCount === 1 ? '' : 's' }} -
                            Total: {{ formatHours(totalHours) }}
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

            <!-- Grouped by Employee -->
            <div v-if="groupedTimecards.length > 0" class="flex flex-col gap-6">
                <div
                    v-for="group in groupedTimecards"
                    :key="group.employee.id"
                    class="flex flex-col gap-3"
                >
                    <!-- Employee Header -->
                    <div
                        class="flex cursor-pointer items-center gap-3 rounded-lg bg-muted/50 p-3 transition-colors hover:bg-muted"
                        @click="navigateToEmployee(group.employee.id)"
                    >
                        <Avatar
                            v-if="group.employee.profile_picture_url"
                            :image="group.employee.profile_picture_url"
                            shape="circle"
                        />
                        <Avatar
                            v-else
                            :label="getInitials(group.employee.name)"
                            shape="circle"
                            class="bg-primary/10 text-primary"
                        />
                        <div class="flex-1">
                            <p class="font-semibold">{{ group.employee.name }}</p>
                            <p
                                v-if="group.employee.employee_number"
                                class="text-sm text-muted-foreground"
                            >
                                #{{ group.employee.employee_number }}
                            </p>
                        </div>
                        <Tag
                            :value="formatHours(group.total_hours)"
                            severity="success"
                        />
                        <i class="pi pi-chevron-right text-muted-foreground"></i>
                    </div>

                    <!-- Timecards -->
                    <div class="ml-4 flex flex-col gap-3 border-l-2 border-muted pl-4">
                        <TimecardSummaryCard
                            v-for="timecard in group.timecards.data"
                            :key="timecard.id"
                            :timecard="timecard"
                            :show-link="true"
                            :link-url="`/management/timecards/${timecard.id}`"
                        />
                    </div>
                </div>
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
                        <Button
                            label="Create Timecard"
                            icon="pi pi-plus"
                            size="small"
                            @click="navigateToCreate"
                        />
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
