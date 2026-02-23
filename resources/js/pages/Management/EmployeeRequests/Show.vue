<script setup lang="ts">
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, EmployeeRequest } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { ref } from 'vue';

interface Props {
    employeeRequest: EmployeeRequest;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employee Requests', href: '/management/employee-requests' },
    { title: props.employeeRequest.full_name, href: '#' },
];

// Reject dialog
const showRejectDialog = ref(false);
const rejectForm = useForm({
    rejection_reason: '',
});

function approve() {
    router.post(
        `/management/employee-requests/${props.employeeRequest.id}/approve`,
        {},
        { preserveScroll: true },
    );
}

function reject() {
    rejectForm.post(
        `/management/employee-requests/${props.employeeRequest.id}/reject`,
        {
            preserveScroll: true,
            onSuccess: () => {
                showRejectDialog.value = false;
            },
        },
    );
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const isPending = props.employeeRequest.status === 'pending';
</script>

<template>
    <Head :title="`Request: ${employeeRequest.full_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex flex-wrap items-center gap-3">
                <BackButton fallback-url="/management/employee-requests" />
                <h1 class="heading-lg">{{ employeeRequest.full_name }}</h1>
                <Tag
                    :value="employeeRequest.status_label"
                    :severity="employeeRequest.status_severity"
                />
                <div v-if="isPending" class="ml-auto flex gap-2">
                    <Button
                        label="Approve"
                        size="small"
                        severity="success"
                        @click="approve"
                    />
                    <Button
                        label="Reject"
                        size="small"
                        severity="danger"
                        outlined
                        @click="showRejectDialog = true"
                    />
                </div>
            </div>

            <Card>
                <template #content>
                    <!-- Personal -->
                    <h2 class="text-base font-semibold">Personal Information</h2>
                    <div class="mt-3 grid gap-x-8 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <div class="text-xs text-muted-foreground">First Name</div>
                            <div>{{ employeeRequest.first_name }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-muted-foreground">Last Name</div>
                            <div>{{ employeeRequest.last_name }}</div>
                        </div>
                        <div v-if="employeeRequest.chinese_name">
                            <div class="text-xs text-muted-foreground">Chinese Name</div>
                            <div>{{ employeeRequest.chinese_name }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-muted-foreground">Email</div>
                            <div>{{ employeeRequest.email }}</div>
                        </div>
                        <div v-if="employeeRequest.phone">
                            <div class="text-xs text-muted-foreground">Phone</div>
                            <div>{{ employeeRequest.phone }}</div>
                        </div>
                        <div v-if="employeeRequest.mobile">
                            <div class="text-xs text-muted-foreground">Mobile</div>
                            <div>{{ employeeRequest.mobile }}</div>
                        </div>
                        <div v-if="employeeRequest.gender">
                            <div class="text-xs text-muted-foreground">Gender</div>
                            <div class="capitalize">{{ employeeRequest.gender }}</div>
                        </div>
                        <div v-if="employeeRequest.date_of_birth">
                            <div class="text-xs text-muted-foreground">Date of Birth</div>
                            <div>{{ formatDate(employeeRequest.date_of_birth) }}</div>
                        </div>
                        <div v-if="employeeRequest.race">
                            <div class="text-xs text-muted-foreground">Race</div>
                            <div>{{ employeeRequest.race }}</div>
                        </div>
                    </div>

                    <Divider />

                    <!-- Identity -->
                    <h2 class="text-base font-semibold">Identity</h2>
                    <div class="mt-3 grid gap-x-8 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-if="employeeRequest.nric">
                            <div class="text-xs text-muted-foreground">NRIC</div>
                            <div>{{ employeeRequest.nric }}</div>
                        </div>
                        <div v-if="employeeRequest.nationality_name">
                            <div class="text-xs text-muted-foreground">Nationality</div>
                            <div>{{ employeeRequest.nationality_name }}</div>
                        </div>
                        <div v-if="employeeRequest.residency_status">
                            <div class="text-xs text-muted-foreground">Residency Status</div>
                            <div>{{ employeeRequest.residency_status }}</div>
                        </div>
                    </div>

                    <Divider />

                    <!-- Address -->
                    <h2 class="text-base font-semibold">Address</h2>
                    <div class="mt-3 grid gap-x-8 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-if="employeeRequest.address_1" class="sm:col-span-2 lg:col-span-3">
                            <div class="text-xs text-muted-foreground">Address</div>
                            <div>
                                {{ employeeRequest.address_1 }}
                                <template v-if="employeeRequest.address_2"><br />{{ employeeRequest.address_2 }}</template>
                            </div>
                        </div>
                        <div v-if="employeeRequest.city">
                            <div class="text-xs text-muted-foreground">City</div>
                            <div>{{ employeeRequest.city }}</div>
                        </div>
                        <div v-if="employeeRequest.state">
                            <div class="text-xs text-muted-foreground">State</div>
                            <div>{{ employeeRequest.state }}</div>
                        </div>
                        <div v-if="employeeRequest.postal_code">
                            <div class="text-xs text-muted-foreground">Postal Code</div>
                            <div>{{ employeeRequest.postal_code }}</div>
                        </div>
                        <div v-if="employeeRequest.country_name">
                            <div class="text-xs text-muted-foreground">Country</div>
                            <div>{{ employeeRequest.country_name }}</div>
                        </div>
                    </div>

                    <Divider />

                    <!-- Emergency -->
                    <h2 class="text-base font-semibold">Emergency Contact</h2>
                    <div class="mt-3 grid gap-x-8 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-if="employeeRequest.emergency_name">
                            <div class="text-xs text-muted-foreground">Name</div>
                            <div>{{ employeeRequest.emergency_name }}</div>
                        </div>
                        <div v-if="employeeRequest.emergency_relationship">
                            <div class="text-xs text-muted-foreground">Relationship</div>
                            <div>{{ employeeRequest.emergency_relationship }}</div>
                        </div>
                        <div v-if="employeeRequest.emergency_contact">
                            <div class="text-xs text-muted-foreground">Contact</div>
                            <div>{{ employeeRequest.emergency_contact }}</div>
                        </div>
                    </div>

                    <Divider />

                    <!-- Banking -->
                    <h2 class="text-base font-semibold">Banking</h2>
                    <div class="mt-3 grid gap-x-8 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-if="employeeRequest.bank_name">
                            <div class="text-xs text-muted-foreground">Bank Name</div>
                            <div>{{ employeeRequest.bank_name }}</div>
                        </div>
                        <div v-if="employeeRequest.bank_account_number">
                            <div class="text-xs text-muted-foreground">Account Number</div>
                            <div>{{ employeeRequest.bank_account_number }}</div>
                        </div>
                    </div>

                    <template v-if="employeeRequest.notes">
                        <Divider />
                        <h2 class="text-base font-semibold">Notes</h2>
                        <p class="mt-2 whitespace-pre-wrap text-sm">{{ employeeRequest.notes }}</p>
                    </template>

                    <!-- Status info for non-pending -->
                    <template v-if="employeeRequest.status !== 'pending'">
                        <Divider />
                        <h2 class="text-base font-semibold">Decision</h2>
                        <div class="mt-3 grid gap-x-8 gap-y-3 sm:grid-cols-2">
                            <template v-if="employeeRequest.status === 'approved'">
                                <div>
                                    <div class="text-xs text-muted-foreground">Approved By</div>
                                    <div>{{ employeeRequest.approved_by_name }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-muted-foreground">Approved At</div>
                                    <div>{{ formatDate(employeeRequest.approved_at) }}</div>
                                </div>
                            </template>
                            <template v-else-if="employeeRequest.status === 'rejected'">
                                <div>
                                    <div class="text-xs text-muted-foreground">Rejected By</div>
                                    <div>{{ employeeRequest.rejected_by_name }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-muted-foreground">Rejected At</div>
                                    <div>{{ formatDate(employeeRequest.rejected_at) }}</div>
                                </div>
                                <div class="sm:col-span-2">
                                    <div class="text-xs text-muted-foreground">Reason</div>
                                    <div class="whitespace-pre-wrap">{{ employeeRequest.rejection_reason }}</div>
                                </div>
                            </template>
                        </div>
                    </template>
                </template>
            </Card>
        </div>

        <!-- Reject Dialog -->
        <Dialog
            v-model:visible="showRejectDialog"
            header="Reject Application"
            :style="{ width: '28rem' }"
            modal
        >
            <p class="mb-4 text-sm text-muted-foreground">
                Provide a reason for rejecting this application.
            </p>
            <Textarea
                v-model="rejectForm.rejection_reason"
                rows="4"
                class="w-full"
                placeholder="Reason for rejection..."
                :invalid="!!rejectForm.errors.rejection_reason"
            />
            <small
                v-if="rejectForm.errors.rejection_reason"
                class="text-red-500"
            >
                {{ rejectForm.errors.rejection_reason }}
            </small>
            <template #footer>
                <Button
                    label="Cancel"
                    size="small"
                    severity="secondary"
                    text
                    @click="showRejectDialog = false"
                />
                <Button
                    label="Reject"
                    size="small"
                    severity="danger"
                    :loading="rejectForm.processing"
                    @click="reject"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>
