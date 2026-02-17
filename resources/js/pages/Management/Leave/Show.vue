<script setup lang="ts">
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, LeaveRequest } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';

interface Props {
    leaveRequest: LeaveRequest;
    breadcrumbs: BreadcrumbItem[];
}

const props = defineProps<Props>();

const confirm = useConfirm();

const showRejectDialog = ref(false);
const rejectForm = useForm({
    rejection_reason: '',
});

const isPending = props.leaveRequest.status === 'pending';

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

function confirmApprove() {
    confirm.require({
        message: 'Are you sure you want to approve this leave request?',
        header: 'Approve Leave Request',
        icon: 'pi pi-check-circle',
        rejectLabel: 'Cancel',
        rejectProps: { severity: 'secondary', size: 'small' },
        acceptLabel: 'Approve',
        acceptProps: { severity: 'success', size: 'small' },
        accept: () => {
            router.post(
                `/management/leave/${props.leaveRequest.id}/approve`,
                {},
                { preserveScroll: true },
            );
        },
    });
}

function submitReject() {
    rejectForm.post(
        `/management/leave/${props.leaveRequest.id}/reject`,
        {
            preserveScroll: true,
            onSuccess: () => {
                showRejectDialog.value = false;
            },
        },
    );
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
                    <BackButton fallback-url="/management/leave" />
                    <h1 class="heading-lg">Leave Request</h1>
                    <Tag
                        :value="leaveRequest.status_label"
                        :severity="statusSeverity(leaveRequest.status)"
                    />
                </div>
                <div v-if="isPending" class="flex gap-2">
                    <Button
                        label="Approve"
                        icon="pi pi-check"
                        size="small"
                        severity="success"
                        @click="confirmApprove"
                    />
                    <Button
                        label="Reject"
                        icon="pi pi-times"
                        size="small"
                        severity="danger"
                        outlined
                        @click="showRejectDialog = true"
                    />
                </div>
            </div>

            <Card class="mx-auto w-full max-w-2xl">
                <template #content>
                    <div class="space-y-4">
                        <!-- Employee -->
                        <div class="flex items-center gap-3">
                            <Avatar
                                v-if="
                                    leaveRequest.employee
                                        ?.profile_picture_url
                                "
                                :image="
                                    leaveRequest.employee
                                        .profile_picture_url
                                "
                                size="large"
                                shape="circle"
                            />
                            <Avatar
                                v-else
                                :label="
                                    (
                                        leaveRequest.employee
                                            ?.first_name?.[0] ?? ''
                                    ).toUpperCase()
                                "
                                size="large"
                                shape="circle"
                            />
                            <div>
                                <div class="font-medium">
                                    {{
                                        leaveRequest.employee?.full_name ??
                                        'Unknown'
                                    }}
                                </div>
                                <div
                                    v-if="
                                        leaveRequest.employee
                                            ?.employee_number
                                    "
                                    class="text-sm text-muted-foreground"
                                >
                                    {{
                                        leaveRequest.employee
                                            .employee_number
                                    }}
                                </div>
                            </div>
                        </div>

                        <Divider />

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
                                    {{
                                        formatDate(
                                            leaveRequest.start_date,
                                        )
                                    }}
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
                                    {{
                                        formatDate(leaveRequest.end_date)
                                    }}
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
                            <div
                                class="mt-1 whitespace-pre-wrap text-sm"
                            >
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
                        <div
                            class="space-y-2 text-sm text-muted-foreground"
                        >
                            <div>
                                Submitted:
                                {{
                                    formatDateTime(
                                        leaveRequest.created_at,
                                    )
                                }}
                            </div>
                            <div v-if="leaveRequest.resolved_at">
                                {{
                                    leaveRequest.status === 'approved'
                                        ? 'Approved'
                                        : 'Rejected'
                                }}:
                                {{
                                    formatDateTime(
                                        leaveRequest.resolved_at,
                                    )
                                }}
                                <span v-if="leaveRequest.resolved_by">
                                    by
                                    {{
                                        leaveRequest.resolved_by.name
                                    }}
                                </span>
                            </div>
                            <div v-if="leaveRequest.cancelled_at">
                                Cancelled:
                                {{
                                    formatDateTime(
                                        leaveRequest.cancelled_at,
                                    )
                                }}
                                <span v-if="leaveRequest.cancelled_by">
                                    by
                                    {{
                                        leaveRequest.cancelled_by.name
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <ConfirmDialog />

            <!-- Reject Dialog -->
            <Dialog
                v-model:visible="showRejectDialog"
                header="Reject Leave Request"
                :modal="true"
                :style="{ width: '30rem' }"
            >
                <form @submit.prevent="submitReject" class="space-y-4">
                    <Message
                        v-if="rejectForm.errors.rejection_reason"
                        severity="error"
                        :closable="false"
                    >
                        {{ rejectForm.errors.rejection_reason }}
                    </Message>

                    <div>
                        <label class="mb-1 block text-sm font-medium"
                            >Reason for rejection</label
                        >
                        <Textarea
                            v-model="rejectForm.rejection_reason"
                            rows="3"
                            size="small"
                            class="w-full"
                            placeholder="Please provide a reason..."
                            :class="{
                                'p-invalid':
                                    rejectForm.errors.rejection_reason,
                            }"
                        />
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button
                            label="Cancel"
                            severity="secondary"
                            size="small"
                            @click="showRejectDialog = false"
                        />
                        <Button
                            label="Reject"
                            icon="pi pi-times"
                            severity="danger"
                            size="small"
                            type="submit"
                            :loading="rejectForm.processing"
                        />
                    </div>
                </form>
            </Dialog>
        </div>
    </AppLayout>
</template>
