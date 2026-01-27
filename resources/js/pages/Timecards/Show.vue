<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import TimecardSummaryCard from '@/components/timecards/TimecardSummaryCard.vue';
import { useSmartBack } from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Timecard } from '@/types';

interface Props {
    date: string;
    dateFormatted: string;
    timecards: { data: Timecard[] };
}

const props = defineProps<Props>();

const { goBack } = useSmartBack('/timecards');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Timecards', href: '/timecards' },
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
                            Total: {{ formatHours(getTotalHours()) }}
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
