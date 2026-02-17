<script setup lang="ts">
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, LeaveRequest } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { computed } from 'vue';

interface Props {
    leaveRequest: LeaveRequest;
    breadcrumbs: BreadcrumbItem[];
}

const props = defineProps<Props>();

const confirm = useConfirm();

const canCancel = computed(
    () =>
        props.leaveRequest.status === 'pending' ||
        props.leaveRequest.status === 'approved',
);

const canEdit = computed(
    () => props.leaveRequest.status === 'rejected',
);

function statusSeverity(
    val: string,
): 'warn' | 'success' | 'danger' | 'secondary' {
    const map: Record<string, 'warn' | 'success' | 'danger' | 'secondary'> = {
        pending: 'warn',
        approved: 'success',
        rejected: 'danger',
        cancelled: 'secondary',
    };
    return map[val] ?? 'secondary';
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatDateTime(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function halfDayLabel(half: string): string {
    if (half === 'am') return '(AM only)';
    if (half === 'pm') return '(PM only)';
    return '(Full day)';
}

function confirmCancel() {
    confirm.require({
        message: 'Are you sure you want to cancel this leave request?',
        header: 'Cancel Leave Request',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'No',
        rejectProps: { severity: 'secondary', size: 'small' },
        acceptLabel: 'Yes, Cancel',
        acceptProps: { severity: 'danger', size: 'small' },
        accept: () => {
            router.post(`/leave/${props.leaveRequest.id}/cancel`, {}, {
                preserveScroll: true,
            });
        },
    });
}
</script>

<template>
    <Head title="Leave Request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <BackButton fallback-url="/leave" />
                    <h1 class="heading-lg">Leave Request</h1>
                    <Tag
                        :value="leaveRequest.status_label"
                        :severity="statusSeverity(leaveRequest.status)"
                    />
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="canEdit"
                        label="Edit & Resubmit"
                        icon="pi pi-pencil"
                        size="small"
                        severity="warn"
                        @click="
                            router.visit(
                                `/leave/${leaveRequest.id}/edit`,
                            )
                        "
                    />
                    <Button
                        v-if="canCancel"
                        label="Cancel Request"
                        icon="pi pi-times"
                        size="small"
                        severity="danger"
                        outlined
                        @click="confirmCancel"
                    />
                </div>
            </div>

            <Card class="mx-auto w-full max-w-2xl">
                <template #content>
                    <div class="space-y-4">
                        <!-- Leave Type -->
                        <div class="flex items-center gap-3">
                            <div
                                v-if="leaveRequest.leave_type?.color"
                                class="h-4 w-4 rounded-full"
                                :style="{
                                    backgroundColor:
                                        leaveRequest.leave_type.color,
                                }"
                            ></div>
                            <span class="text-lg font-medium">{{
                                leaveRequest.leave_type?.name ??
                                'Unknown'
                            }}</span>
                        </div>

                        <Divider />

                        <!-- Dates -->
                        <div
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                        >
                            <div>
                                <div
                                    class="text-sm text-muted-foreground"
                                >
                                    Start Date
                                </div>
                                <div class="font-medium">
                                    {{ formatDate(leaveRequest.start_date) }}
                                    <span
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{
                                            halfDayLabel(
                                                leaveRequest.start_half_day,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <div
                                    class="text-sm text-muted-foreground"
                                >
                                    End Date
                                </div>
                                <div class="font-medium">
                                    {{ formatDate(leaveRequest.end_date) }}
                                    <span
                                        v-if="
                                            leaveRequest.start_date !==
                                            leaveRequest.end_date
                                        "
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{
                                            halfDayLabel(
                                                leaveRequest.end_half_day,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Total Days -->
                        <div>
                            <div class="text-sm text-muted-foreground">
                                Total Working Days
                            </div>
                            <div class="text-lg font-semibold">
                                {{ leaveRequest.total_days }}
                            </div>
                        </div>

                        <Divider />

                        <!-- Reason -->
                        <div v-if="leaveRequest.reason">
                            <div class="text-sm text-muted-foreground">
                                Reason
                            </div>
                            <div class="mt-1 whitespace-pre-wrap text-sm">
                                {{ leaveRequest.reason }}
                            </div>
                        </div>

                        <!-- Rejection reason -->
                        <div
                            v-if="
                                leaveRequest.status === 'rejected' &&
                                leaveRequest.rejection_reason
                            "
                            class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-900 dark:bg-red-950"
                        >
                            <div
                                class="mb-1 text-sm font-medium text-red-700 dark:text-red-400"
                            >
                                Rejection Reason
                            </div>
                            <div
                                class="whitespace-pre-wrap text-sm text-red-600 dark:text-red-300"
                            >
                                {{ leaveRequest.rejection_reason }}
                            </div>
                        </div>

                        <Divider v-if="leaveRequest.reason || (leaveRequest.status === 'rejected' && leaveRequest.rejection_reason)" />

                        <!-- Meta info -->
                        <div class="space-y-2 text-sm text-muted-foreground">
                            <div>
                                Submitted:
                                {{ formatDateTime(leaveRequest.created_at) }}
                            </div>
                            <div v-if="leaveRequest.resolved_at">
                                {{
                                    leaveRequest.status === 'approved'
                                        ? 'Approved'
                                        : 'Rejected'
                                }}:
                                {{ formatDateTime(leaveRequest.resolved_at) }}
                                <span v-if="leaveRequest.resolved_by">
                                    by
                                    {{ leaveRequest.resolved_by.name }}
                                </span>
                            </div>
                            <div v-if="leaveRequest.cancelled_at">
                                Cancelled:
                                {{ formatDateTime(leaveRequest.cancelled_at) }}
                                <span v-if="leaveRequest.cancelled_by">
                                    by
                                    {{ leaveRequest.cancelled_by.name }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <ConfirmDialog />
        </div>
    </AppLayout>
</template>
