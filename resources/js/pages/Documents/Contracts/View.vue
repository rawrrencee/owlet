<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import { ref } from 'vue';
import AuditInfo from '@/components/AuditInfo.vue';
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type EmployeeContract } from '@/types';
import { type HasAuditTrail } from '@/types/audit';

interface ContractWithEmployee extends EmployeeContract {
    employee?: {
        id: number;
        first_name: string;
        last_name: string;
    };
    employee_is_deleted?: boolean;
}

interface Props {
    contract: ContractWithEmployee & HasAuditTrail;
}

const props = defineProps<Props>();


const documentPreviewVisible = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Documents', href: '/documents' },
    { title: 'Contract Details' },
];

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function formatCurrency(value: string | number | null): string {
    if (value === null || value === undefined) return '-';
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('en-SG', { style: 'currency', currency: 'SGD' }).format(num);
}

function getLeaveDisplay(entitled: number, taken: number): string {
    const remaining = Math.max(0, entitled - taken);
    return `${remaining}/${entitled} (${remaining} remaining)`;
}

function getEmployeeName(): string {
    if (!props.contract.employee) return '-';
    return `${props.contract.employee.first_name} ${props.contract.employee.last_name}`;
}

function isEmployeeDeleted(): boolean {
    return props.contract.employee_is_deleted === true;
}

function navigateToEmployee() {
    if (props.contract.employee) {
        router.get(`/users/${props.contract.employee.id}`);
    }
}

function navigateToEdit() {
    router.get(`/documents/contracts/${props.contract.id}/edit`);
}

function viewDocument() {
    if (props.contract.external_document_url) {
        window.open(props.contract.external_document_url, '_blank');
        return;
    }

    if (props.contract.document_url) {
        if (props.contract.is_document_viewable_inline) {
            documentPreviewVisible.value = true;
        } else {
            window.open(props.contract.document_url, '_blank');
        }
    }
}
</script>

<template>
    <Head title="Contract Details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <BackButton fallback-url="/documents" />
                    <h1 class="heading-lg">Contract Details</h1>
                    <Tag :value="contract.is_active ? 'Active' : 'Expired'" :severity="contract.is_active ? 'success' : 'secondary'" />
                </div>
                <div class="flex gap-2">
                    <Button v-if="contract.has_document" label="View Document" icon="pi pi-file" severity="secondary" size="small" @click="viewDocument" />
                    <Button label="Edit" icon="pi pi-pencil" size="small" @click="navigateToEdit" />
                </div>
            </div>

            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <template #content>
                        <div class="flex flex-col gap-6">
                            <!-- Employee -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Employee</h3>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-medium"
                                        :class="{ 'text-muted-foreground line-through': isEmployeeDeleted() }"
                                    >
                                        {{ getEmployeeName() }}
                                    </span>
                                    <Tag v-if="isEmployeeDeleted()" value="Deleted" severity="danger" />
                                    <Button
                                        v-if="contract.employee && !isEmployeeDeleted()"
                                        icon="pi pi-external-link"
                                        severity="secondary"
                                        text
                                        rounded
                                        size="small"
                                        @click="navigateToEmployee"
                                        v-tooltip.top="'View Employee'"
                                    />
                                </div>
                            </div>

                            <Divider />

                            <!-- Contract Details -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Contract Information</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Company</span>
                                        <span>{{ contract.company?.company_name ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Status</span>
                                        <Tag :value="contract.is_active ? 'Active' : 'Expired'" :severity="contract.is_active ? 'success' : 'secondary'" class="w-fit" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Start Date</span>
                                        <span>{{ formatDate(contract.start_date) }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">End Date</span>
                                        <span>{{ formatDate(contract.end_date) }}</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Salary -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Compensation</h3>
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm text-muted-foreground">Salary</span>
                                    <span class="text-xl font-semibold">{{ formatCurrency(contract.salary_amount) }}</span>
                                </div>
                            </div>

                            <Divider />

                            <!-- Leave Entitlements -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Leave Entitlements</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Annual Leave</span>
                                        <span>{{ getLeaveDisplay(contract.annual_leave_entitled, contract.annual_leave_taken) }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Sick Leave</span>
                                        <span>{{ getLeaveDisplay(contract.sick_leave_entitled, contract.sick_leave_taken) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Document -->
                            <template v-if="contract.has_document">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Document</h3>
                                    <div class="flex items-center gap-3">
                                        <i class="pi pi-file text-2xl text-primary"></i>
                                        <div class="flex flex-col">
                                            <span v-if="contract.document_filename" class="font-medium">{{ contract.document_filename }}</span>
                                            <span v-else-if="contract.external_document_url" class="text-sm text-muted-foreground">External Document</span>
                                        </div>
                                        <Button label="View" icon="pi pi-eye" severity="secondary" size="small" @click="viewDocument" />
                                    </div>
                                </div>
                            </template>

                            <!-- Comments -->
                            <template v-if="contract.comments">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Comments</h3>
                                    <div class="prose prose-sm max-w-none" v-html="contract.comments"></div>
                                </div>
                            </template>

                            <Divider />

                            <!-- Audit Info -->
                            <AuditInfo
                                :created-by="contract.created_by"
                                :updated-by="contract.updated_by"
                                :previous-updated-by="contract.previous_updated_by"
                                :created-at="contract.created_at"
                                :updated-at="contract.updated_at"
                                :previous-updated-at="contract.previous_updated_at"
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Document Preview Dialog -->
        <Dialog v-model:visible="documentPreviewVisible" header="Document Preview" :modal="true" class="w-full max-w-3xl">
            <div class="flex items-center justify-center">
                <img v-if="contract.document_url" :src="contract.document_url" alt="Document preview" class="max-h-[70vh] max-w-full object-contain" />
            </div>
        </Dialog>
    </AppLayout>
</template>
