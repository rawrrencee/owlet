<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ClockWidget from '@/components/timecards/ClockWidget.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import {
    type BreadcrumbItem,
    type Timecard,
    type TimecardStore,
} from '@/types';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

interface Props {
    currentTimecard?: Timecard | null;
    isOnBreak?: boolean;
    stores?: TimecardStore[];
}

withDefaults(defineProps<Props>(), {
    currentTimecard: null,
    isOnBreak: false,
    stores: () => [],
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
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="grid max-w-4xl auto-rows-min gap-4 md:grid-cols-3">
                <!-- Clock Widget (only show if user has stores assigned) -->
                <div v-if="stores && stores.length > 0" class="md:col-span-2">
                    <ClockWidget
                        :current-timecard="currentTimecard ?? null"
                        :is-on-break="isOnBreak ?? false"
                        :stores="stores ?? []"
                    />
                </div>
                <div
                    v-else
                    class="relative aspect-video overflow-hidden rounded-lg border border-border md:col-span-2"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-lg border border-border"
                >
                    <PlaceholderPattern />
                </div>
            </div>
            <div
                class="relative min-h-[100vh] flex-1 rounded-lg border border-border md:min-h-min"
            >
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>
