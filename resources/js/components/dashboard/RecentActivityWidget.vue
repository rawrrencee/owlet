<script setup lang="ts">
import type { RecentActivityItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import Card from 'primevue/card';
import Tag from 'primevue/tag';

defineProps<{
    data: RecentActivityItem[];
}>();

function typeLabel(type: string): string {
    const map: Record<string, string> = {
        delivery_order: 'DO',
        purchase_order: 'PO',
        stocktake: 'Stocktake',
        quotation: 'Quote',
    };
    return map[type] ?? type;
}

function formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffHours = Math.floor(diffMs / 3600000);

    if (diffHours < 1) return 'Just now';
    if (diffHours < 24) return `${diffHours}h ago`;

    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 7) return `${diffDays}d ago`;

    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
    });
}

function formatStatus(status: string): string {
    return status.replace(/_/g, ' ');
}

function statusSeverity(
    status: string,
): 'warn' | 'success' | 'danger' | 'secondary' | 'info' {
    const map: Record<
        string,
        'warn' | 'success' | 'danger' | 'secondary' | 'info'
    > = {
        draft: 'secondary',
        submitted: 'info',
        approved: 'success',
        accepted: 'success',
        rejected: 'danger',
        in_progress: 'warn',
        sent: 'info',
        viewed: 'info',
        expired: 'secondary',
        completed: 'success',
        paid: 'success',
    };
    return map[status] ?? 'secondary';
}
</script>

<template>
    <Card class="h-full">
        <template #title>
            <span class="text-sm font-semibold">Recent Activity</span>
        </template>
        <template #content>
            <div
                v-if="data.length === 0"
                class="py-4 text-center text-sm text-muted-foreground"
            >
                No recent activity.
            </div>
            <div v-else class="space-y-2">
                <Link
                    v-for="(item, index) in data"
                    :key="index"
                    :href="item.url"
                    class="flex items-center gap-3 rounded-md px-2 py-1.5 hover:bg-stone-100 dark:hover:bg-stone-800"
                >
                    <span
                        class="inline-flex shrink-0 rounded bg-stone-200 px-1.5 py-0.5 text-xs font-medium dark:bg-stone-700"
                    >
                        {{ typeLabel(item.type) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm">{{ item.label }}</div>
                    </div>
                    <Tag
                        :value="formatStatus(item.status)"
                        :severity="statusSeverity(item.status)"
                        class="!text-xs capitalize"
                    />
                    <span class="shrink-0 text-xs text-muted-foreground">
                        {{ formatDate(item.date) }}
                    </span>
                </Link>
            </div>
        </template>
    </Card>
</template>
