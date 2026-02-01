<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import Editor from 'primevue/editor';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref } from 'vue';
import BackButton from '@/components/BackButton.vue';
import {
    clearSkipPageInHistory,
    skipCurrentPageInHistory,
} from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type Company,
    type EmployeeContract,
} from '@/types';

interface ContractWithEmployee extends EmployeeContract {
    employee?: {
        id: number;
        first_name: string;
        last_name: string;
    };
    employee_is_deleted?: boolean;
}

interface Props {
    contract: ContractWithEmployee;
    companies: Company[];
}

const props = defineProps<Props>();

const confirm = useConfirm();

const documentPreviewVisible = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const uploadingDocument = ref(false);

const form = useForm({
    company_id: props.contract.company_id,
    start_date: props.contract.start_date
        ? new Date(props.contract.start_date)
        : null,
    end_date: props.contract.end_date
        ? new Date(props.contract.end_date)
        : null,
    salary_amount: Number(props.contract.salary_amount) || 0,
    annual_leave_entitled: props.contract.annual_leave_entitled,
    annual_leave_taken: props.contract.annual_leave_taken,
    sick_leave_entitled: props.contract.sick_leave_entitled,
    sick_leave_taken: props.contract.sick_leave_taken,
    external_document_url: props.contract.external_document_url || '',
    comments: props.contract.comments || '',
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Documents', href: '/documents' },
    { title: 'Edit Contract' },
];

const companyOptions = computed(() =>
    props.companies.map((c) => ({
        label: c.company_name,
        value: c.id,
    })),
);

function getEmployeeName(): string {
    if (!props.contract.employee) return '-';
    return `${props.contract.employee.first_name} ${props.contract.employee.last_name}`;
}

function isEmployeeDeleted(): boolean {
    return props.contract.employee_is_deleted === true;
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
    })).put(`/documents/contracts/${props.contract.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit(`/documents/contracts/${props.contract.id}`);
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

    router.post(
        `/documents/contracts/${props.contract.id}/document`,
        formData,
        {
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
        },
    );
}

function confirmDeleteDocument() {
    confirm.require({
        message: `Are you sure you want to delete the document "${props.contract.document_filename}"?`,
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
            router.delete(
                `/documents/contracts/${props.contract.id}/document`,
                {
                    preserveScroll: true,
                },
            );
        },
    });
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

function clearSelectedFile() {
    selectedFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}
</script>

<template>
    <Head title="Edit Contract" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-4">
                    <BackButton
                        :fallback-url="`/documents/contracts/${contract.id}`"
                    />
                    <h1 class="heading-lg">Edit Contract</h1>
                </div>
            </div>

            <div class="mx-auto w-full max-w-2xl">
                <Card>
                    <template #content>
                        <form
                            @submit.prevent="submitForm"
                            class="flex flex-col gap-4"
                        >
                            <!-- Employee (Read Only) -->
                            <div class="flex flex-col gap-2">
                                <label class="font-medium">Employee</label>
                                <div
                                    class="bg-surface-100 dark:bg-surface-800 flex items-center gap-2 rounded-lg border border-border px-3 py-2"
                                >
                                    <span
                                        :class="{
                                            'text-muted-foreground line-through':
                                                isEmployeeDeleted(),
                                        }"
                                    >
                                        {{ getEmployeeName() }}
                                    </span>
                                    <Tag
                                        v-if="isEmployeeDeleted()"
                                        value="Deleted"
                                        severity="danger"
                                    />
                                </div>
                                <small class="text-muted-foreground"
                                    >Employee cannot be changed</small
                                >
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="company_id" class="font-medium"
                                    >Company</label
                                >
                                <Select
                                    id="company_id"
                                    v-model="form.company_id"
                                    :options="companyOptions"
                                    option-label="label"
                                    option-value="value"
                                    :invalid="!!form.errors.company_id"
                                    placeholder="Select company (optional)"
                                    filter
                                    showClear
                                    size="small"
                                    fluid
                                />
                                <small
                                    v-if="form.errors.company_id"
                                    class="text-red-500"
                                >
                                    {{ form.errors.company_id }}
                                </small>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="flex flex-col gap-2">
                                    <label for="start_date" class="font-medium"
                                        >Start Date *</label
                                    >
                                    <DatePicker
                                        id="start_date"
                                        v-model="form.start_date"
                                        :invalid="!!form.errors.start_date"
                                        dateFormat="yy-mm-dd"
                                        showIcon
                                        size="small"
                                        fluid
                                    />
                                    <small
                                        v-if="form.errors.start_date"
                                        class="text-red-500"
                                    >
                                        {{ form.errors.start_date }}
                                    </small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="end_date" class="font-medium"
                                        >End Date</label
                                    >
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
                                    <small
                                        v-if="form.errors.end_date"
                                        class="text-red-500"
                                    >
                                        {{ form.errors.end_date }}
                                    </small>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="salary_amount" class="font-medium"
                                    >Salary *</label
                                >
                                <InputNumber
                                    id="salary_amount"
                                    v-model="form.salary_amount"
                                    :invalid="!!form.errors.salary_amount"
                                    mode="currency"
                                    currency="SGD"
                                    locale="en-SG"
                                    :min-fraction-digits="2"
                                    :max-fraction-digits="4"
                                    size="small"
                                    fluid
                                />
                                <small
                                    v-if="form.errors.salary_amount"
                                    class="text-red-500"
                                >
                                    {{ form.errors.salary_amount }}
                                </small>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="flex flex-col gap-2">
                                    <label class="font-medium"
                                        >Annual Leave *</label
                                    >
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label
                                                class="text-xs text-muted-foreground"
                                                >Entitled</label
                                            >
                                            <InputNumber
                                                v-model="
                                                    form.annual_leave_entitled
                                                "
                                                :invalid="
                                                    !!form.errors
                                                        .annual_leave_entitled
                                                "
                                                :min="0"
                                                :max="255"
                                                size="small"
                                                fluid
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="text-xs text-muted-foreground"
                                                >Taken</label
                                            >
                                            <InputNumber
                                                v-model="
                                                    form.annual_leave_taken
                                                "
                                                :invalid="
                                                    !!form.errors
                                                        .annual_leave_taken
                                                "
                                                :min="0"
                                                :max="255"
                                                size="small"
                                                fluid
                                            />
                                        </div>
                                    </div>
                                    <small
                                        v-if="form.errors.annual_leave_entitled"
                                        class="text-red-500"
                                    >
                                        {{ form.errors.annual_leave_entitled }}
                                    </small>
                                    <small
                                        v-if="form.errors.annual_leave_taken"
                                        class="text-red-500"
                                    >
                                        {{ form.errors.annual_leave_taken }}
                                    </small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="font-medium"
                                        >Sick Leave *</label
                                    >
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label
                                                class="text-xs text-muted-foreground"
                                                >Entitled</label
                                            >
                                            <InputNumber
                                                v-model="
                                                    form.sick_leave_entitled
                                                "
                                                :invalid="
                                                    !!form.errors
                                                        .sick_leave_entitled
                                                "
                                                :min="0"
                                                :max="255"
                                                size="small"
                                                fluid
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="text-xs text-muted-foreground"
                                                >Taken</label
                                            >
                                            <InputNumber
                                                v-model="form.sick_leave_taken"
                                                :invalid="
                                                    !!form.errors
                                                        .sick_leave_taken
                                                "
                                                :min="0"
                                                :max="255"
                                                size="small"
                                                fluid
                                            />
                                        </div>
                                    </div>
                                    <small
                                        v-if="form.errors.sick_leave_entitled"
                                        class="text-red-500"
                                    >
                                        {{ form.errors.sick_leave_entitled }}
                                    </small>
                                    <small
                                        v-if="form.errors.sick_leave_taken"
                                        class="text-red-500"
                                    >
                                        {{ form.errors.sick_leave_taken }}
                                    </small>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label
                                    for="external_document_url"
                                    class="font-medium"
                                    >External Document URL</label
                                >
                                <InputText
                                    id="external_document_url"
                                    v-model="form.external_document_url"
                                    :invalid="
                                        !!form.errors.external_document_url
                                    "
                                    placeholder="https://example.com/document.pdf"
                                    size="small"
                                    fluid
                                />
                                <small
                                    v-if="form.errors.external_document_url"
                                    class="text-red-500"
                                >
                                    {{ form.errors.external_document_url }}
                                </small>
                            </div>

                            <!-- Document Upload -->
                            <div class="flex flex-col gap-2">
                                <label class="font-medium"
                                    >Document Upload</label
                                >
                                <div
                                    class="rounded-lg border border-border p-3"
                                >
                                    <!-- Show existing document if present -->
                                    <div
                                        v-if="contract.document_url"
                                        class="flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-2">
                                            <i
                                                class="pi pi-file text-primary"
                                            ></i>
                                            <span class="text-sm">{{
                                                contract.document_filename
                                            }}</span>
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
                                                id="contract-file-upload"
                                                @change="onFileSelect"
                                            />
                                            <label
                                                for="contract-file-upload"
                                                class="hover:bg-surface-100 dark:bg-surface-800 dark:hover:bg-surface-700 cursor-pointer rounded border border-border bg-muted px-3 py-1.5 text-sm"
                                            >
                                                <i
                                                    class="pi pi-upload mr-2"
                                                ></i>
                                                {{
                                                    selectedFile
                                                        ? selectedFile.name
                                                        : 'Choose file'
                                                }}
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
                                        <small
                                            class="text-xs text-muted-foreground"
                                        >
                                            Max 5MB. Supported: PDF, JPG, PNG,
                                            GIF, DOC, DOCX
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="comments" class="font-medium"
                                    >Comments</label
                                >
                                <Editor
                                    id="comments"
                                    v-model="form.comments"
                                    editorStyle="height: 150px"
                                >
                                    <template #toolbar>
                                        <span class="ql-formats">
                                            <button
                                                class="ql-bold"
                                                v-tooltip.bottom="'Bold'"
                                            ></button>
                                            <button
                                                class="ql-italic"
                                                v-tooltip.bottom="'Italic'"
                                            ></button>
                                            <button
                                                class="ql-underline"
                                                v-tooltip.bottom="'Underline'"
                                            ></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button
                                                class="ql-list"
                                                value="ordered"
                                                v-tooltip.bottom="
                                                    'Numbered List'
                                                "
                                            ></button>
                                            <button
                                                class="ql-list"
                                                value="bullet"
                                                v-tooltip.bottom="'Bullet List'"
                                            ></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button
                                                class="ql-clean"
                                                v-tooltip.bottom="
                                                    'Clear Formatting'
                                                "
                                            ></button>
                                        </span>
                                    </template>
                                </Editor>
                                <small
                                    v-if="form.errors.comments"
                                    class="text-red-500"
                                >
                                    {{ form.errors.comments }}
                                </small>
                            </div>

                            <div class="mt-4 flex justify-end gap-2">
                                <BackButton
                                    :fallback-url="`/documents/contracts/${contract.id}`"
                                />
                                <Button
                                    type="submit"
                                    label="Save Changes"
                                    size="small"
                                    :loading="form.processing"
                                />
                            </div>
                        </form>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Document Preview Dialog -->
        <Dialog
            v-model:visible="documentPreviewVisible"
            header="Document Preview"
            :modal="true"
            class="w-full max-w-3xl"
        >
            <div class="flex items-center justify-center">
                <img
                    v-if="contract.document_url"
                    :src="contract.document_url"
                    alt="Document preview"
                    class="max-h-[70vh] max-w-full object-contain"
                />
            </div>
        </Dialog>

        <ConfirmDialog />
    </AppLayout>
</template>
