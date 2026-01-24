<script setup lang="ts">
import { type EmployeeInsurance } from '@/types/company';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import Editor from 'primevue/editor';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { computed, reactive, ref } from 'vue';

interface Props {
    employeeId: number;
    insurances: EmployeeInsurance[];
}

const props = defineProps<Props>();

const dialogVisible = ref(false);
const editingId = ref<number | null>(null);
const editingInsurance = ref<EmployeeInsurance | null>(null);
const saving = ref(false);
const documentPreviewVisible = ref(false);
const documentPreviewUrl = ref<string | null>(null);

const form = reactive({
    title: '',
    insurer_name: '',
    policy_number: '',
    start_date: null as Date | null,
    end_date: null as Date | null,
    external_document_url: '',
    comments: '',
});

const formErrors = reactive<Record<string, string>>({});

const confirm = useConfirm();

// Get the current editing insurance from props (updated after upload/delete)
const currentEditingInsurance = computed(() => {
    if (!editingId.value) return null;
    return props.insurances.find(i => i.id === editingId.value) || editingInsurance.value;
});

// File input ref
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const uploadingDocument = ref(false);

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function resetForm() {
    form.title = '';
    form.insurer_name = '';
    form.policy_number = '';
    form.start_date = null;
    form.end_date = null;
    form.external_document_url = '';
    form.comments = '';
    selectedFile.value = null;
    editingInsurance.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);
}

function openAddDialog() {
    resetForm();
    editingId.value = null;
    dialogVisible.value = true;
}

function openEditDialog(insurance: EmployeeInsurance) {
    resetForm();
    editingId.value = insurance.id;
    editingInsurance.value = insurance;
    form.title = insurance.title;
    form.insurer_name = insurance.insurer_name;
    form.policy_number = insurance.policy_number;
    form.start_date = insurance.start_date ? new Date(insurance.start_date) : null;
    form.end_date = insurance.end_date ? new Date(insurance.end_date) : null;
    form.external_document_url = insurance.external_document_url || '';
    form.comments = insurance.comments || '';
    dialogVisible.value = true;
}

function formatDateForBackend(date: Date | null): string | null {
    if (!date) return null;
    return date.toISOString().split('T')[0];
}

function saveInsurance() {
    saving.value = true;
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);

    const url = editingId.value
        ? `/users/${props.employeeId}/insurances/${editingId.value}`
        : `/users/${props.employeeId}/insurances`;

    // For new insurances, use FormData to include file
    if (!editingId.value && selectedFile.value) {
        const formData = new FormData();
        formData.append('title', form.title);
        formData.append('insurer_name', form.insurer_name);
        formData.append('policy_number', form.policy_number);
        if (form.start_date) formData.append('start_date', formatDateForBackend(form.start_date)!);
        if (form.end_date) formData.append('end_date', formatDateForBackend(form.end_date)!);
        if (form.external_document_url) formData.append('external_document_url', form.external_document_url);
        if (form.comments) formData.append('comments', form.comments);
        formData.append('document', selectedFile.value);

        router.post(url, formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                dialogVisible.value = false;
            },
            onError: (errors) => {
                Object.assign(formErrors, errors);
            },
            onFinish: () => {
                saving.value = false;
            },
        });
    } else {
        // For updates or creates without file, use regular data
        const data = {
            title: form.title,
            insurer_name: form.insurer_name,
            policy_number: form.policy_number,
            start_date: formatDateForBackend(form.start_date),
            end_date: formatDateForBackend(form.end_date),
            external_document_url: form.external_document_url || null,
            comments: form.comments || null,
        };

        const method = editingId.value ? 'put' : 'post';

        router[method](url, data, {
            preserveScroll: true,
            onSuccess: () => {
                dialogVisible.value = false;
            },
            onError: (errors) => {
                Object.assign(formErrors, errors);
            },
            onFinish: () => {
                saving.value = false;
            },
        });
    }
}

function confirmRemoveInsurance(insurance: EmployeeInsurance) {
    confirm.require({
        message: `Are you sure you want to remove the insurance "${insurance.title}"? This action cannot be undone.`,
        header: 'Remove Insurance',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Remove',
        acceptProps: {
            severity: 'danger',
            size: 'small',
        },
        accept: () => {
            router.delete(`/users/${props.employeeId}/insurances/${insurance.id}`, {
                preserveScroll: true,
            });
        },
    });
}

function viewDocument(insurance: EmployeeInsurance) {
    if (insurance.external_document_url) {
        window.open(insurance.external_document_url, '_blank');
        return;
    }

    if (insurance.document_url) {
        if (insurance.is_document_viewable_inline) {
            // Show image in dialog
            documentPreviewUrl.value = insurance.document_url;
            documentPreviewVisible.value = true;
        } else {
            // Open PDF/other docs in new tab
            window.open(insurance.document_url, '_blank');
        }
    }
}

function onFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        // Check file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            target.value = '';
            selectedFile.value = null;
            return;
        }
        selectedFile.value = file;
    }
}

function clearSelectedFile() {
    selectedFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function uploadDocumentInDialog() {
    if (!selectedFile.value || !editingInsurance.value) return;

    uploadingDocument.value = true;
    const formData = new FormData();
    formData.append('document', selectedFile.value);

    router.post(`/users/${props.employeeId}/insurances/${editingInsurance.value.id}/document`, formData, {
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

function confirmDeleteDocumentInDialog() {
    if (!editingInsurance.value) return;

    confirm.require({
        message: `Are you sure you want to delete the document "${editingInsurance.value.document_filename}"?`,
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
            router.delete(`/users/${props.employeeId}/insurances/${editingInsurance.value!.id}/document`, {
                preserveScroll: true,
            });
        },
    });
}

function viewDocumentInDialog() {
    if (!editingInsurance.value) return;
    viewDocument(editingInsurance.value);
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">Insurances</h3>
            <Button label="Add Insurance" icon="pi pi-plus" size="small" @click="openAddDialog" />
        </div>

        <DataTable
            :value="insurances"
            dataKey="id"
            striped-rows
            size="small"
            class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <template #empty>
                <div class="p-4 text-center text-muted-foreground">
                    No insurances found. Click "Add Insurance" to add a new insurance.
                </div>
            </template>
            <Column field="title" header="Title">
                <template #body="{ data }">
                    <span class="font-medium">{{ data.title }}</span>
                </template>
            </Column>
            <Column field="insurer_name" header="Insurer" class="hidden sm:table-cell">
                <template #body="{ data }">
                    {{ data.insurer_name }}
                </template>
            </Column>
            <Column field="policy_number" header="Policy #" class="hidden md:table-cell">
                <template #body="{ data }">
                    {{ data.policy_number }}
                </template>
            </Column>
            <Column field="start_date" header="Start Date" class="hidden lg:table-cell">
                <template #body="{ data }">
                    {{ formatDate(data.start_date) }}
                </template>
            </Column>
            <Column field="end_date" header="End Date" class="hidden lg:table-cell">
                <template #body="{ data }">
                    {{ formatDate(data.end_date) }}
                </template>
            </Column>
            <Column header="Status">
                <template #body="{ data }">
                    <Tag :value="data.is_active ? 'Active' : 'Expired'" :severity="data.is_active ? 'success' : 'secondary'" />
                </template>
            </Column>
            <Column header="Doc" class="w-12">
                <template #body="{ data }">
                    <Button
                        v-if="data.has_document"
                        icon="pi pi-file"
                        severity="secondary"
                        text
                        rounded
                        size="small"
                        @click="viewDocument(data)"
                        v-tooltip.top="'View Document'"
                    />
                </template>
            </Column>
            <Column header="" class="w-32 !pr-4">
                <template #body="{ data }">
                    <div class="flex justify-end gap-1">
                        <Button
                            icon="pi pi-pencil"
                            severity="secondary"
                            text
                            rounded
                            size="small"
                            @click="openEditDialog(data)"
                            v-tooltip.top="'Edit'"
                        />
                        <Button
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="confirmRemoveInsurance(data)"
                            v-tooltip.top="'Remove'"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>

        <Dialog
            v-model:visible="dialogVisible"
            :header="editingId ? 'Edit Insurance' : 'Add Insurance'"
            :modal="true"
            :closable="!saving"
            class="w-full max-w-2xl"
        >
            <form @submit.prevent="saveInsurance" class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="insurance_title" class="font-medium">Title *</label>
                    <InputText
                        id="insurance_title"
                        v-model="form.title"
                        :invalid="!!formErrors.title"
                        placeholder="e.g., Health Insurance, Life Insurance"
                        size="small"
                        fluid
                    />
                    <small v-if="formErrors.title" class="text-red-500">
                        {{ formErrors.title }}
                    </small>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <label for="insurance_insurer_name" class="font-medium">Insurer Name *</label>
                        <InputText
                            id="insurance_insurer_name"
                            v-model="form.insurer_name"
                            :invalid="!!formErrors.insurer_name"
                            placeholder="e.g., AIA, Prudential"
                            size="small"
                            fluid
                        />
                        <small v-if="formErrors.insurer_name" class="text-red-500">
                            {{ formErrors.insurer_name }}
                        </small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="insurance_policy_number" class="font-medium">Policy Number *</label>
                        <InputText
                            id="insurance_policy_number"
                            v-model="form.policy_number"
                            :invalid="!!formErrors.policy_number"
                            placeholder="Policy number"
                            size="small"
                            fluid
                        />
                        <small v-if="formErrors.policy_number" class="text-red-500">
                            {{ formErrors.policy_number }}
                        </small>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <label for="insurance_start_date" class="font-medium">Start Date *</label>
                        <DatePicker
                            id="insurance_start_date"
                            v-model="form.start_date"
                            :invalid="!!formErrors.start_date"
                            dateFormat="yy-mm-dd"
                            showIcon
                            size="small"
                            fluid
                        />
                        <small v-if="formErrors.start_date" class="text-red-500">
                            {{ formErrors.start_date }}
                        </small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="insurance_end_date" class="font-medium">End Date</label>
                        <DatePicker
                            id="insurance_end_date"
                            v-model="form.end_date"
                            :invalid="!!formErrors.end_date"
                            dateFormat="yy-mm-dd"
                            showIcon
                            showClear
                            size="small"
                            fluid
                        />
                        <small v-if="formErrors.end_date" class="text-red-500">
                            {{ formErrors.end_date }}
                        </small>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="insurance_external_url" class="font-medium">External Document URL</label>
                    <InputText
                        id="insurance_external_url"
                        v-model="form.external_document_url"
                        :invalid="!!formErrors.external_document_url"
                        placeholder="https://example.com/document.pdf"
                        size="small"
                        fluid
                    />
                    <small v-if="formErrors.external_document_url" class="text-red-500">
                        {{ formErrors.external_document_url }}
                    </small>
                </div>

                <!-- Document Upload -->
                <div class="flex flex-col gap-2">
                    <label class="font-medium">Document Upload</label>
                    <div class="rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                        <!-- Show existing document if present (only when editing) -->
                        <div v-if="editingId && currentEditingInsurance?.document_url" class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-file text-primary"></i>
                                <span class="text-sm">{{ currentEditingInsurance.document_filename }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <Button
                                    icon="pi pi-eye"
                                    severity="secondary"
                                    text
                                    rounded
                                    size="small"
                                    @click="viewDocumentInDialog"
                                    v-tooltip.top="'View'"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    severity="danger"
                                    text
                                    rounded
                                    size="small"
                                    @click="confirmDeleteDocumentInDialog"
                                    v-tooltip.top="'Delete'"
                                />
                            </div>
                        </div>
                        <!-- Show upload UI if no document or creating new -->
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
                                    class="cursor-pointer rounded border border-sidebar-border bg-surface-50 px-3 py-1.5 text-sm hover:bg-surface-100 dark:bg-surface-800 dark:hover:bg-surface-700"
                                >
                                    <i class="pi pi-upload mr-2"></i>
                                    {{ selectedFile ? selectedFile.name : 'Choose file' }}
                                </label>
                                <!-- For existing insurances, show separate upload button -->
                                <Button
                                    v-if="editingId && selectedFile"
                                    label="Upload"
                                    icon="pi pi-cloud-upload"
                                    size="small"
                                    :loading="uploadingDocument"
                                    @click="uploadDocumentInDialog"
                                />
                                <!-- For new insurances, file will be submitted with form -->
                                <Button
                                    v-if="!editingId && selectedFile"
                                    icon="pi pi-times"
                                    severity="secondary"
                                    text
                                    rounded
                                    size="small"
                                    @click="clearSelectedFile"
                                    v-tooltip.top="'Remove'"
                                />
                            </div>
                            <small class="text-xs text-muted-foreground">
                                Max 5MB. Supported: PDF, JPG, PNG, GIF, DOC, DOCX
                                <template v-if="!editingId && selectedFile">
                                    <br />File will be uploaded when you save the insurance.
                                </template>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="insurance_comments" class="font-medium">Comments</label>
                    <Editor
                        id="insurance_comments"
                        v-model="form.comments"
                        editorStyle="height: 150px"
                    >
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
                    <small v-if="formErrors.comments" class="text-red-500">
                        {{ formErrors.comments }}
                    </small>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <Button
                        type="button"
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="dialogVisible = false"
                        :disabled="saving"
                    />
                    <Button type="submit" :label="editingId ? 'Save Changes' : 'Add Insurance'" size="small" :loading="saving" />
                </div>
            </form>
        </Dialog>

        <!-- Document Preview Dialog -->
        <Dialog
            v-model:visible="documentPreviewVisible"
            header="Document Preview"
            :modal="true"
            class="w-full max-w-3xl"
        >
            <div class="flex items-center justify-center">
                <img
                    v-if="documentPreviewUrl"
                    :src="documentPreviewUrl"
                    alt="Document preview"
                    class="max-h-[70vh] max-w-full object-contain"
                />
            </div>
        </Dialog>

        <ConfirmDialog />
    </div>
</template>
