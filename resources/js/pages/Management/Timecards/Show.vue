<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import BackButton from '@/components/BackButton.vue';
import TimecardDetailsTable from '@/components/timecards/TimecardDetailsTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Timecard } from '@/types';

interface Props {
    timecard: Timecard;
}

const props = defineProps<Props>();


const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Timecards', href: '/management/timecards' },
    { title: `Timecard #${props.timecard.id}` },
];

function formatDateTime(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
}

function formatHours(hours: number): string {
    if (hours === 0) return '0h';
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
}

function getStatusSeverity(): 'success' | 'info' | 'danger' {
    switch (props.timecard.status) {
        case 'COMPLETED':
            return 'success';
        case 'IN_PROGRESS':
            return 'info';
        case 'EXPIRED':
            return 'danger';
        default:
            return 'info';
    }
}

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function navigateToEdit() {
    router.visit(`/management/timecards/${props.timecard.id}/edit`);
}
</script>

<template>
    <Head :title="`Timecard #${timecard.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <BackButton fallback-url="/management/timecards" />
                    <h1 class="heading-lg">Timecard #{{ timecard.id }}</h1>
                    <Tag :value="timecard.status_label" :severity="getStatusSeverity()" />
                </div>
                <Button
                    label="Edit"
                    icon="pi pi-pencil"
                    size="small"
                    @click="navigateToEdit"
                />
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Timecard Info -->
                <Card>
                    <template #title>
                        <span class="text-base font-semibold">Timecard Information</span>
                    </template>
                    <template #content>
                        <div class="flex flex-col gap-4">
                            <!-- Employee -->
                            <div class="flex items-center gap-3">
                                <Avatar
                                    v-if="timecard.employee?.profile_picture_url"
                                    :image="timecard.employee.profile_picture_url"
                                    shape="circle"
                                    size="large"
                                />
                                <Avatar
                                    v-else-if="timecard.employee?.name"
                                    :label="getInitials(timecard.employee.name)"
                                    shape="circle"
                                    size="large"
                                    class="bg-primary/10 text-primary"
                                />
                                <div>
                                    <p class="font-semibold">{{ timecard.employee?.name }}</p>
                                    <p
                                        v-if="timecard.employee?.employee_number"
                                        class="text-sm text-muted-foreground"
                                    >
                                        #{{ timecard.employee.employee_number }}
                                    </p>
                                </div>
                            </div>

                            <Divider />

                            <div class="grid gap-3">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Store</span>
                                    <span class="font-medium">
                                        {{ timecard.store?.name }}
                                        <span class="text-muted-foreground">({{ timecard.store?.store_code }})</span>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Start Time</span>
                                    <span>{{ formatDateTime(timecard.start_date) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">End Time</span>
                                    <span>{{ formatDateTime(timecard.end_date) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Total Hours Worked</span>
                                    <span class="font-bold text-green-600">
                                        {{ formatHours(timecard.hours_worked) }}
                                    </span>
                                </div>
                            </div>

                            <Divider />

                            <!-- Audit Info -->
                            <div class="grid gap-2 text-sm">
                                <div v-if="timecard.created_by" class="flex justify-between">
                                    <span class="text-muted-foreground">Created by</span>
                                    <span>{{ timecard.created_by.name }}</span>
                                </div>
                                <div v-if="timecard.updated_by" class="flex justify-between">
                                    <span class="text-muted-foreground">Last updated by</span>
                                    <span>{{ timecard.updated_by.name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Created at</span>
                                    <span>{{ formatDateTime(timecard.created_at) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Updated at</span>
                                    <span>{{ formatDateTime(timecard.updated_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Time Entries -->
                <Card>
                    <template #title>
                        <span class="text-base font-semibold">Time Entries</span>
                    </template>
                    <template #content>
                        <TimecardDetailsTable
                            :details="timecard.details || []"
                            :editable="false"
                        />
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
