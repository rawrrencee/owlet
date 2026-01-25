<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import { ref } from 'vue';
import { useSmartBack } from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type EmployeeInsurance } from '@/types';

interface InsuranceWithEmployee extends EmployeeInsurance {
    employee?: {
        id: number;
        first_name: string;
        last_name: string;
    };
    employee_is_deleted?: boolean;
}

interface Props {
    insurance: InsuranceWithEmployee;
}

const props = defineProps<Props>();

const { goBack } = useSmartBack('/documents?type=insurances');

const documentPreviewVisible = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Documents', href: '/documents?type=insurances' },
    { title: 'Insurance Details' },
];

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function getEmployeeName(): string {
    if (!props.insurance.employee) return '-';
    return `${props.insurance.employee.first_name} ${props.insurance.employee.last_name}`;
}

function isEmployeeDeleted(): boolean {
    return props.insurance.employee_is_deleted === true;
}

function navigateToEmployee() {
    if (props.insurance.employee) {
        router.get(`/users/${props.insurance.employee.id}`);
    }
}

function navigateToEdit() {
    router.get(`/documents/insurances/${props.insurance.id}/edit`);
}

function viewDocument() {
    if (props.insurance.external_document_url) {
        window.open(props.insurance.external_document_url, '_blank');
        return;
    }

    if (props.insurance.document_url) {
        if (props.insurance.is_document_viewable_inline) {
            documentPreviewVisible.value = true;
        } else {
            window.open(props.insurance.document_url, '_blank');
        }
    }
}
</script>

<template>
    <Head title="Insurance Details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <Button icon="pi pi-arrow-left" severity="secondary" text rounded size="small" @click="goBack" />
                    <h1 class="text-2xl font-semibold">Insurance Details</h1>
                    <Tag :value="insurance.is_active ? 'Active' : 'Expired'" :severity="insurance.is_active ? 'success' : 'secondary'" />
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="insurance.has_document"
                        label="View Document"
                        icon="pi pi-file"
                        severity="secondary"
                        size="small"
                        @click="viewDocument"
                    />
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
                                        v-if="insurance.employee && !isEmployeeDeleted()"
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

                            <!-- Insurance Details -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Insurance Information</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Title</span>
                                        <span class="text-lg font-medium">{{ insurance.title }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Status</span>
                                        <Tag
                                            :value="insurance.is_active ? 'Active' : 'Expired'"
                                            :severity="insurance.is_active ? 'success' : 'secondary'"
                                            class="w-fit"
                                        />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Insurer</span>
                                        <span>{{ insurance.insurer_name }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Policy Number</span>
                                        <span class="font-mono">{{ insurance.policy_number }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Start Date</span>
                                        <span>{{ formatDate(insurance.start_date) }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">End Date</span>
                                        <span>{{ formatDate(insurance.end_date) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Document -->
                            <template v-if="insurance.has_document">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Document</h3>
                                    <div class="flex items-center gap-3">
                                        <i class="pi pi-file text-2xl text-primary"></i>
                                        <div class="flex flex-col">
                                            <span v-if="insurance.document_filename" class="font-medium">{{ insurance.document_filename }}</span>
                                            <span v-else-if="insurance.external_document_url" class="text-sm text-muted-foreground">External Document</span>
                                        </div>
                                        <Button label="View" icon="pi pi-eye" severity="secondary" size="small" @click="viewDocument" />
                                    </div>
                                </div>
                            </template>

                            <!-- Comments -->
                            <template v-if="insurance.comments">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Comments</h3>
                                    <div class="prose prose-sm max-w-none" v-html="insurance.comments"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Document Preview Dialog -->
        <Dialog v-model:visible="documentPreviewVisible" header="Document Preview" :modal="true" class="w-full max-w-3xl">
            <div class="flex items-center justify-center">
                <img v-if="insurance.document_url" :src="insurance.document_url" alt="Document preview" class="max-h-[70vh] max-w-full object-contain" />
            </div>
        </Dialog>
    </AppLayout>
</template>
