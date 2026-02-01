<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import Editor from 'primevue/editor';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';
import BackButton from '@/components/BackButton.vue';
import { clearSkipPageInHistory, skipCurrentPageInHistory } from '@/composables/useSmartBack';
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

const confirm = useConfirm();

const documentPreviewVisible = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const uploadingDocument = ref(false);

const form = useForm({
    title: props.insurance.title,
    insurer_name: props.insurance.insurer_name,
    policy_number: props.insurance.policy_number,
    start_date: props.insurance.start_date ? new Date(props.insurance.start_date) : null,
    end_date: props.insurance.end_date ? new Date(props.insurance.end_date) : null,
    external_document_url: props.insurance.external_document_url || '',
    comments: props.insurance.comments || '',
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Documents', href: '/documents?type=insurances' },
    { title: 'Edit Insurance' },
];

function getEmployeeName(): string {
    if (!props.insurance.employee) return '-';
    return `${props.insurance.employee.first_name} ${props.insurance.employee.last_name}`;
}

function isEmployeeDeleted(): boolean {
    return props.insurance.employee_is_deleted === true;
}

function formatDateForBackend(date: Date | null): string | null {
    if (!date) return null;
    return date.toISOString().split('T')[0];
}

function submitForm() {
    skipCurrentPageInHistory();
    form.transform((data) => ({
        ...data,
        start_date: formatDateForBackend(data.start_date as Date | null),
        end_date: formatDateForBackend(data.end_date as Date | null),
        external_document_url: data.external_document_url || null,
        comments: data.comments || null,
    })).put(`/documents/insurances/${props.insurance.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit(`/documents/insurances/${props.insurance.id}`);
        },
        onError: () => {
            clearSkipPageInHistory();
        },
    });
}

function onFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            target.value = '';
            selectedFile.value = null;
            return;
        }
        selectedFile.value = file;
    }
}

function uploadDocument() {
    if (!selectedFile.value) return;

    uploadingDocument.value = true;
    const formData = new FormData();
    formData.append('document', selectedFile.value);

    router.post(`/documents/insurances/${props.insurance.id}/document`, formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            selectedFile.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
        onFinish: () => {
            uploadingDocument.value = false;
        },
    });
}

function confirmDeleteDocument() {
    confirm.require({
        message: `Are you sure you want to delete the document "${props.insurance.document_filename}"?`,
        header: 'Delete Document',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Delete',
        acceptProps: {
            severity: 'danger',
            size: 'small',
        },
        accept: () => {
            router.delete(`/documents/insurances/${props.insurance.id}/document`, {
                preserveScroll: true,
            });
        },
    });
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

function clearSelectedFile() {
    selectedFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}
</script>

<template>
    <Head title="Edit Insurance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <BackButton :fallback-url="`/documents/insurances/${insurance.id}`" />
                    <h1 class="heading-lg">Edit Insurance</h1>
                </div>
            </div>

            <div class="mx-auto w-full max-w-2xl">
                <Card>
                    <template #content>
                        <form @submit.prevent="submitForm" class="flex flex-col gap-4">
                            <!-- Employee (Read Only) -->
                            <div class="flex flex-col gap-2">
                                <label class="font-medium">Employee</label>
                                <div class="flex items-center gap-2 rounded-lg border border-border bg-surface-100 px-3 py-2 dark:bg-surface-800">
                                    <span :class="{ 'text-muted-foreground line-through': isEmployeeDeleted() }">
                                        {{ getEmployeeName() }}
                                    </span>
                                    <Tag v-if="isEmployeeDeleted()" value="Deleted" severity="danger" />
                                </div>
                                <small class="text-muted-foreground">Employee cannot be changed</small>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="title" class="font-medium">Title *</label>
                                <InputText
                                    id="title"
                                    v-model="form.title"
                                    :invalid="!!form.errors.title"
                                    placeholder="e.g., Health Insurance, Life Insurance"
                                    size="small"
                                    fluid
                                />
                                <small v-if="form.errors.title" class="text-red-500">
                                    {{ form.errors.title }}
                                </small>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="flex flex-col gap-2">
                                    <label for="insurer_name" class="font-medium">Insurer Name *</label>
                                    <InputText
                                        id="insurer_name"
                                        v-model="form.insurer_name"
                                        :invalid="!!form.errors.insurer_name"
                                        placeholder="e.g., AIA, Prudential"
                                        size="small"
                                        fluid
                                    />
                                    <small v-if="form.errors.insurer_name" class="text-red-500">
                                        {{ form.errors.insurer_name }}
                                    </small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="policy_number" class="font-medium">Policy Number *</label>
                                    <InputText
                                        id="policy_number"
                                        v-model="form.policy_number"
                                        :invalid="!!form.errors.policy_number"
                                        placeholder="Policy number"
                                        size="small"
                                        fluid
                                    />
                                    <small v-if="form.errors.policy_number" class="text-red-500">
                                        {{ form.errors.policy_number }}
                                    </small>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="flex flex-col gap-2">
                                    <label for="start_date" class="font-medium">Start Date *</label>
                                    <DatePicker
                                        id="start_date"
                                        v-model="form.start_date"
                                        :invalid="!!form.errors.start_date"
                                        dateFormat="yy-mm-dd"
                                        showIcon
                                        size="small"
                                        fluid
                                    />
                                    <small v-if="form.errors.start_date" class="text-red-500">
                                        {{ form.errors.start_date }}
                                    </small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="end_date" class="font-medium">End Date</label>
                                    <DatePicker
                                        id="end_date"
                                        v-model="form.end_date"
                                        :invalid="!!form.errors.end_date"
                                        dateFormat="yy-mm-dd"
                                        showIcon
                                        showClear
                                        size="small"
                                        fluid
                                    />
                                    <small v-if="form.errors.end_date" class="text-red-500">
                                        {{ form.errors.end_date }}
                                    </small>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="external_document_url" class="font-medium">External Document URL</label>
                                <InputText
                                    id="external_document_url"
                                    v-model="form.external_document_url"
                                    :invalid="!!form.errors.external_document_url"
                                    placeholder="https://example.com/document.pdf"
                                    size="small"
                                    fluid
                                />
                                <small v-if="form.errors.external_document_url" class="text-red-500">
                                    {{ form.errors.external_document_url }}
                                </small>
                            </div>

                            <!-- Document Upload -->
                            <div class="flex flex-col gap-2">
                                <label class="font-medium">Document Upload</label>
                                <div class="rounded-lg border border-border p-3 ">
                                    <!-- Show existing document if present -->
                                    <div v-if="insurance.document_url" class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-file text-primary"></i>
                                            <span class="text-sm">{{ insurance.document_filename }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <Button
                                                icon="pi pi-eye"
                                                severity="secondary"
                                                text
                                                rounded
                                                size="small"
                                                @click="viewDocument"
                                                v-tooltip.top="'View'"
                                            />
                                            <Button
                                                icon="pi pi-trash"
                                                severity="danger"
                                                text
                                                rounded
                                                size="small"
                                                @click="confirmDeleteDocument"
                                                v-tooltip.top="'Delete'"
                                            />
                                        </div>
                                    </div>
                                    <!-- Show upload UI if no document -->
                                    <div v-else class="flex flex-col gap-2">
                                        <div class="flex items-center gap-2">
                                            <input
                                                ref="fileInput"
                                                type="file"
                                                accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx"
                                                class="hidden"
                                                id="insurance-file-upload"
                                                @change="onFileSelect"
                                            />
                                            <label
                                                for="insurance-file-upload"
                                                class="cursor-pointer rounded border border-border bg-muted px-3 py-1.5 text-sm hover:bg-surface-100 dark:bg-surface-800 dark:hover:bg-surface-700"
                                            >
                                                <i class="pi pi-upload mr-2"></i>
                                                {{ selectedFile ? selectedFile.name : 'Choose file' }}
                                            </label>
                                            <Button
                                                v-if="selectedFile"
                                                label="Upload"
                                                icon="pi pi-cloud-upload"
                                                size="small"
                                                :loading="uploadingDocument"
                                                @click="uploadDocument"
                                            />
                                            <Button
                                                v-if="selectedFile"
                                                icon="pi pi-times"
                                                severity="secondary"
                                                text
                                                rounded
                                                size="small"
                                                @click="clearSelectedFile"
                                                v-tooltip.top="'Remove'"
                                            />
                                        </div>
                                        <small class="text-xs text-muted-foreground"> Max 5MB. Supported: PDF, JPG, PNG, GIF, DOC, DOCX </small>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="comments" class="font-medium">Comments</label>
                                <Editor id="comments" v-model="form.comments" editorStyle="height: 150px">
                                    <template #toolbar>
                                        <span class="ql-formats">
                                            <button class="ql-bold" v-tooltip.bottom="'Bold'"></button>
                                            <button class="ql-italic" v-tooltip.bottom="'Italic'"></button>
                                            <button class="ql-underline" v-tooltip.bottom="'Underline'"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-list" value="ordered" v-tooltip.bottom="'Numbered List'"></button>
                                            <button class="ql-list" value="bullet" v-tooltip.bottom="'Bullet List'"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-clean" v-tooltip.bottom="'Clear Formatting'"></button>
                                        </span>
                                    </template>
                                </Editor>
                                <small v-if="form.errors.comments" class="text-red-500">
                                    {{ form.errors.comments }}
                                </small>
                            </div>

                            <div class="mt-4 flex justify-end gap-2">
                                <BackButton :fallback-url="`/documents/insurances/${insurance.id}`" />
                                <Button type="submit" label="Save Changes" size="small" :loading="form.processing" />
                            </div>
                        </form>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Document Preview Dialog -->
        <Dialog v-model:visible="documentPreviewVisible" header="Document Preview" :modal="true" class="w-full max-w-3xl">
            <div class="flex items-center justify-center">
                <img
                    v-if="insurance.document_url"
                    :src="insurance.document_url"
                    alt="Document preview"
                    class="max-h-[70vh] max-w-full object-contain"
                />
            </div>
        </Dialog>

        <ConfirmDialog />
    </AppLayout>
</template>
