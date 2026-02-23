<script setup lang="ts">
import type { UpcomingLeaveItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import Card from 'primevue/card';
import Tag from 'primevue/tag';

defineProps<{
    data: UpcomingLeaveItem[];
}>();

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
    });
}

function statusSeverity(
    status: string,
): 'warn' | 'success' | 'danger' | 'secondary' {
    const map: Record<string, 'warn' | 'success' | 'danger' | 'secondary'> = {
        pending: 'warn',
        approved: 'success',
        rejected: 'danger',
    };
    return map[status] ?? 'secondary';
}
</script>

<template>
    <Card class="h-full">
        <template #title>
            <span class="text-sm font-semibold">Upcoming Leave</span>
        </template>
        <template #content>
            <div
                v-if="data.length === 0"
                class="py-4 text-center text-sm text-muted-foreground"
            >
                No upcoming leave.
            </div>
            <div v-else class="space-y-2">
                <Link
                    v-for="item in data"
                    :key="item.id"
                    :href="`/leave/${item.id}`"
                    class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-stone-100 dark:hover:bg-stone-800"
                >
                    <div
                        v-if="item.type_color"
                        class="h-2.5 w-2.5 shrink-0 rounded-full"
                        :style="{ backgroundColor: item.type_color }"
                    ></div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm">
                            {{ item.type_name }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ formatDate(item.start_date) }}
                            <template v-if="item.start_date !== item.end_date">
                                — {{ formatDate(item.end_date) }}
                            </template>
                            ({{ item.total_days }}d)
                        </div>
                    </div>
                    <Tag
                        :value="item.status_label"
                        :severity="statusSeverity(item.status)"
                        class="!text-xs"
                    />
                </Link>
            </div>
        </template>
    </Card>
</template>
