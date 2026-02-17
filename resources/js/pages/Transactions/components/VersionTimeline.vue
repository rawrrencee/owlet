<script setup lang="ts">
import type { TransactionVersion } from '@/types';
import Tag from 'primevue/tag';
import Timeline from 'primevue/timeline';

defineProps<{
    versions: TransactionVersion[];
}>();

function getChangeIcon(changeType: string): string {
    switch (changeType) {
        case 'created': return 'pi pi-plus-circle';
        case 'item_added': return 'pi pi-plus';
        case 'item_removed': return 'pi pi-minus';
        case 'item_modified': return 'pi pi-pencil';
        case 'customer_changed': return 'pi pi-user';
        case 'payment_added': return 'pi pi-wallet';
        case 'payment_removed': return 'pi pi-wallet';
        case 'discount_applied': return 'pi pi-percentage';
        case 'offer_applied': return 'pi pi-tag';
        case 'completed': return 'pi pi-check-circle';
        case 'suspended': return 'pi pi-pause-circle';
        case 'resumed': return 'pi pi-play-circle';
        case 'voided': return 'pi pi-times-circle';
        case 'refund': return 'pi pi-replay';
        default: return 'pi pi-circle';
    }
}

function getChangeSeverity(changeType: string): string {
    switch (changeType) {
        case 'created': return 'info';
        case 'completed': return 'success';
        case 'voided': return 'danger';
        case 'refund': return 'warn';
        case 'suspended': return 'warn';
        case 'resumed': return 'info';
        case 'payment_added': return 'success';
        case 'payment_removed': return 'danger';
        default: return 'secondary';
    }
}

function getChangeLabel(changeType: string): string {
    switch (changeType) {
        case 'created': return 'Created';
        case 'item_added': return 'Item Added';
        case 'item_removed': return 'Item Removed';
        case 'item_modified': return 'Item Modified';
        case 'customer_changed': return 'Customer Changed';
        case 'payment_added': return 'Payment Added';
        case 'payment_removed': return 'Payment Removed';
        case 'discount_applied': return 'Discount Applied';
        case 'offer_applied': return 'Offer Applied';
        case 'completed': return 'Completed';
        case 'suspended': return 'Suspended';
        case 'resumed': return 'Resumed';
        case 'voided': return 'Voided';
        case 'refund': return 'Refund';
        default: return changeType;
    }
}

function formatDateTime(dateStr: string | null | undefined): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}
</script>

<template>
    <div v-if="versions.length === 0" class="text-center text-muted-foreground py-4 text-sm">
        No version history available.
    </div>
    <div v-else class="max-w-xl mx-auto">
    <Timeline :value="versions" class="w-full">
        <template #marker="{ item }">
            <span
                class="flex items-center justify-center w-8 h-8 rounded-full border-2 bg-surface-0 dark:bg-surface-900"
                :class="{
                    'border-blue-500': getChangeSeverity(item.change_type) === 'info',
                    'border-green-500': getChangeSeverity(item.change_type) === 'success',
                    'border-red-500': getChangeSeverity(item.change_type) === 'danger',
                    'border-yellow-500': getChangeSeverity(item.change_type) === 'warn',
                    'border-surface-300': getChangeSeverity(item.change_type) === 'secondary',
                }"
            >
                <i
                    :class="getChangeIcon(item.change_type)"
                    class="text-sm"
                />
            </span>
        </template>
        <template #content="{ item }">
            <div class="mb-4">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <Tag :value="getChangeLabel(item.change_type)" :severity="getChangeSeverity(item.change_type)" class="!text-xs" />
                    <span class="text-xs text-muted-foreground">v{{ item.version_number }}</span>
                </div>
                <p class="text-sm">{{ item.change_summary }}</p>
                <div class="text-xs text-muted-foreground mt-1">
                    {{ item.changed_by_user?.name ?? 'System' }} &middot; {{ formatDateTime(item.created_at) }}
                </div>
            </div>
        </template>
    </Timeline>
    </div>
</template>
