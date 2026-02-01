<script setup lang="ts">
import type { AuditUser } from '@/types';

interface Props {
    createdBy?: AuditUser | null;
    updatedBy?: AuditUser | null;
    previousUpdatedBy?: AuditUser | null;
    createdAt?: string | null;
    updatedAt?: string | null;
    previousUpdatedAt?: string | null;
}

defineProps<Props>();

function formatDateTime(dateString: string | null | undefined): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
}
</script>

<template>
    <div>
        <h3 class="mb-3 text-sm font-medium text-muted-foreground">
            Audit Information
        </h3>
        <div class="grid gap-1 text-sm sm:grid-cols-2">
            <div>
                <span class="text-muted-foreground">Created by:</span>
                <span class="ml-1">{{ createdBy?.name ?? '-' }}</span>
            </div>
            <div>
                <span class="text-muted-foreground">Created at:</span>
                <span class="ml-1">{{ formatDateTime(createdAt) }}</span>
            </div>
            <div>
                <span class="text-muted-foreground">Last updated by:</span>
                <span class="ml-1">{{ updatedBy?.name ?? '-' }}</span>
            </div>
            <div>
                <span class="text-muted-foreground">Last updated at:</span>
                <span class="ml-1">{{ formatDateTime(updatedAt) }}</span>
            </div>
            <template v-if="previousUpdatedBy">
                <div>
                    <span class="text-muted-foreground"
                        >Previously updated by:</span
                    >
                    <span class="ml-1">{{ previousUpdatedBy.name }}</span>
                </div>
                <div v-if="previousUpdatedAt">
                    <span class="text-muted-foreground"
                        >Previously updated at:</span
                    >
                    <span class="ml-1">{{
                        formatDateTime(previousUpdatedAt)
                    }}</span>
                </div>
            </template>
        </div>
    </div>
</template>
