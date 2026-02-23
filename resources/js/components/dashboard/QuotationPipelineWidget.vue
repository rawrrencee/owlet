<script setup lang="ts">
import type { QuotationPipelineData } from '@/types';
import { Link } from '@inertiajs/vue3';
import Card from 'primevue/card';

const props = defineProps<{
    data: QuotationPipelineData;
}>();

const statusColors: Record<string, string> = {
    draft: 'bg-stone-400',
    sent: 'bg-blue-500',
    accepted: 'bg-amber-500',
    paid: 'bg-green-500',
};

const statusTextColors: Record<string, string> = {
    draft: 'text-stone-500',
    sent: 'text-blue-600 dark:text-blue-400',
    accepted: 'text-amber-600 dark:text-amber-400',
    paid: 'text-green-600 dark:text-green-400',
};

function barWidth(count: number): string {
    if (props.data.total === 0) return '0%';
    return `${(count / props.data.total) * 100}%`;
}
</script>

<template>
    <Card class="h-full">
        <template #title>
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold">Quotation Pipeline</span>
                <Link
                    href="/quotations"
                    class="text-xs text-muted-foreground transition-colors hover:text-primary"
                >
                    View all
                </Link>
            </div>
        </template>
        <template #content>
            <div v-if="data.total === 0" class="py-4 text-center text-sm text-muted-foreground">
                No quotations this month
            </div>
            <div v-else class="space-y-3">
                <div class="text-2xl font-bold">{{ data.total }}</div>
                <p class="text-xs text-muted-foreground">Quotations this month</p>

                <!-- Pipeline bar -->
                <div class="flex h-2 w-full overflow-hidden rounded-full bg-stone-100 dark:bg-stone-800">
                    <div
                        v-for="item in data.by_status"
                        :key="item.status"
                        :style="{ width: barWidth(item.count) }"
                        :class="statusColors[item.status] ?? 'bg-stone-400'"
                        class="transition-all duration-500"
                    ></div>
                </div>

                <!-- Status breakdown -->
                <div class="grid grid-cols-2 gap-2">
                    <div
                        v-for="item in data.by_status"
                        :key="item.status"
                        class="flex items-center gap-2"
                    >
                        <div
                            class="h-2 w-2 flex-shrink-0 rounded-full"
                            :class="statusColors[item.status] ?? 'bg-stone-400'"
                        ></div>
                        <span class="text-xs text-muted-foreground">{{ item.label }}</span>
                        <span
                            class="ml-auto text-xs font-semibold"
                            :class="statusTextColors[item.status] ?? ''"
                        >{{ item.count }}</span>
                    </div>
                </div>
            </div>
        </template>
    </Card>
</template>
