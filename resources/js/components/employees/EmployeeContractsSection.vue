<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import Editor from 'primevue/editor';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { computed, reactive, ref } from 'vue';
import { type Company, type EmployeeContract } from '@/types/company';

interface Props {
    employeeId: number;
    contracts: EmployeeContract[];
    companies: Company[];
}

const props = defineProps<Props>();

const dialogVisible = ref(false);
const editingId = ref<number | null>(null);
const editingContract = ref<EmployeeContract | null>(null);
const saving = ref(false);
const documentPreviewVisible = ref(false);
const documentPreviewUrl = ref<string | null>(null);

const form = reactive({
    company_id: null as number | null,
    start_date: null as Date | null,
    end_date: null as Date | null,
    salary_amount: 0,
    annual_leave_entitled: 0,
    annual_leave_taken: 0,
    sick_leave_entitled: 0,
    sick_leave_taken: 0,
    external_document_url: '',
    comments: '',
});

const formErrors = reactive<Record<string, string>>({});

const confirm = useConfirm();

const companyOptions = computed(() =>
    props.companies.map((c) => ({
        label: c.company_name,
        value: c.id,
    })),
);

// Get the current editing contract from props (updated after upload/delete)
const currentEditingContract = computed(() => {
    if (!editingId.value) return null;
    return props.contracts.find(c => c.id === editingId.value) || editingContract.value;
});

// File input ref
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const uploadingDocument = ref(false);

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function formatCurrency(value: string | number | null): string {
    if (value === null || value === undefined) return '-';
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('en-SG', { style: 'currency', currency: 'SGD' }).format(num);
}

function resetForm() {
    form.company_id = null;
    form.start_date = null;
    form.end_date = null;
    form.salary_amount = 0;
    form.annual_leave_entitled = 0;
    form.annual_leave_taken = 0;
    form.sick_leave_entitled = 0;
    form.sick_leave_taken = 0;
    form.external_document_url = '';
    form.comments = '';
    selectedFile.value = null;
    editingContract.value = null;
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

function openEditDialog(contract: EmployeeContract) {
    resetForm();
    editingId.value = contract.id;
    editingContract.value = contract;
    form.company_id = contract.company_id;
    form.start_date = contract.start_date ? new Date(contract.start_date) : null;
    form.end_date = contract.end_date ? new Date(contract.end_date) : null;
    form.salary_amount = Number(contract.salary_amount) || 0;
    form.annual_leave_entitled = contract.annual_leave_entitled;
    form.annual_leave_taken = contract.annual_leave_taken;
    form.sick_leave_entitled = contract.sick_leave_entitled;
    form.sick_leave_taken = contract.sick_leave_taken;
    form.external_document_url = contract.external_document_url || '';
    form.comments = contract.comments || '';
    dialogVisible.value = true;
}

function formatDateForBackend(date: Date | null): string | null {
    if (!date) return null;
    return date.toISOString().split('T')[0];
}

function saveContract() {
    saving.value = true;
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);

    const url = editingId.value
        ? `/users/${props.employeeId}/contracts/${editingId.value}`
        : `/users/${props.employeeId}/contracts`;

    // For new contracts, use FormData to include file
    if (!editingId.value && selectedFile.value) {
        const formData = new FormData();
        if (form.company_id) formData.append('company_id', String(form.company_id));
        if (form.start_date) formData.append('start_date', formatDateForBackend(form.start_date)!);
        if (form.end_date) formData.append('end_date', formatDateForBackend(form.end_date)!);
        formData.append('salary_amount', String(form.salary_amount));
        formData.append('annual_leave_entitled', String(form.annual_leave_entitled));
        formData.append('annual_leave_taken', String(form.annual_leave_taken));
        formData.append('sick_leave_entitled', String(form.sick_leave_entitled));
        formData.append('sick_leave_taken', String(form.sick_leave_taken));
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
            company_id: form.company_id,
            start_date: formatDateForBackend(form.start_date),
            end_date: formatDateForBackend(form.end_date),
            salary_amount: form.salary_amount,
            annual_leave_entitled: form.annual_leave_entitled,
            annual_leave_taken: form.annual_leave_taken,
            sick_leave_entitled: form.sick_leave_entitled,
            sick_leave_taken: form.sick_leave_taken,
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

function confirmRemoveContract(contract: EmployeeContract) {
    const companyName = contract.company?.company_name || 'Unknown Company';
    confirm.require({
        message: `Are you sure you want to remove this contract with "${companyName}"? This action cannot be undone.`,
        header: 'Remove Contract',
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
            router.delete(`/users/${props.employeeId}/contracts/${contract.id}`, {
                preserveScroll: true,
            });
        },
    });
}

function viewDocument(contract: EmployeeContract) {
    if (contract.external_document_url) {
        window.open(contract.external_document_url, '_blank');
        return;
    }

    if (contract.document_url) {
        if (contract.is_document_viewable_inline) {
            // Show image in dialog
            documentPreviewUrl.value = contract.document_url;
            documentPreviewVisible.value = true;
        } else {
            // Open PDF/other docs in new tab
            window.open(contract.document_url, '_blank');
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
    if (!selectedFile.value || !editingContract.value) return;

    uploadingDocument.value = true;
    const formData = new FormData();
    formData.append('document', selectedFile.value);

    router.post(`/users/${props.employeeId}/contracts/${editingContract.value.id}/document`, formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            selectedFile.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
            // Update the editingContract with new document info (will be refreshed from page props)
        },
        onFinish: () => {
            uploadingDocument.value = false;
        },
    });
}

function confirmDeleteDocumentInDialog() {
    if (!editingContract.value) return;

    confirm.require({
        message: `Are you sure you want to delete the document "${editingContract.value.document_filename}"?`,
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
            router.delete(`/users/${props.employeeId}/contracts/${editingContract.value!.id}/document`, {
                preserveScroll: true,
            });
        },
    });
}

function viewDocumentInDialog() {
    if (!editingContract.value) return;
    viewDocument(editingContract.value);
}

function getLeaveDisplay(entitled: number, taken: number): string {
    const remaining = Math.max(0, entitled - taken);
    return `${remaining}/${entitled}`;
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">Contracts</h3>
            <Button label="Add Contract" icon="pi pi-plus" size="small" @click="openAddDialog" />
        </div>

        <DataTable
            :value="contracts"
            dataKey="id"
            striped-rows
            size="small"
            class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <template #empty>
                <div class="p-4 text-center text-muted-foreground">
                    No contracts found. Click "Add Contract" to add a new contract.
                </div>
            </template>
            <Column field="company.company_name" header="Company">
                <template #body="{ data }">
                    <span class="font-medium">{{ data.company?.company_name ?? '-' }}</span>
                </template>
            </Column>
            <Column field="start_date" header="Start Date" class="hidden md:table-cell">
                <template #body="{ data }">
                    {{ formatDate(data.start_date) }}
                </template>
            </Column>
            <Column field="end_date" header="End Date" class="hidden lg:table-cell">
                <template #body="{ data }">
                    {{ formatDate(data.end_date) }}
                </template>
            </Column>
            <Column field="salary_amount" header="Salary" class="hidden md:table-cell">
                <template #body="{ data }">
                    {{ formatCurrency(data.salary_amount) }}
                </template>
            </Column>
            <Column header="Annual Leave" class="hidden sm:table-cell">
                <template #body="{ data }">
                    {{ getLeaveDisplay(data.annual_leave_entitled, data.annual_leave_taken) }}
                </template>
            </Column>
            <Column header="Sick Leave" class="hidden sm:table-cell">
                <template #body="{ data }">
                    {{ getLeaveDisplay(data.sick_leave_entitled, data.sick_leave_taken) }}
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
                            @click="confirmRemoveContract(data)"
                            v-tooltip.top="'Remove'"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>

        <Dialog
            v-model:visible="dialogVisible"
            :header="editingId ? 'Edit Contract' : 'Add Contract'"
            :modal="true"
            :closable="!saving"
            class="w-full max-w-2xl"
        >
            <form @submit.prevent="saveContract" class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="contract_company_id" class="font-medium">Company</label>
                    <Select
                        id="contract_company_id"
                        v-model="form.company_id"
                        :options="companyOptions"
                        option-label="label"
                        option-value="value"
                        :invalid="!!formErrors.company_id"
                        placeholder="Select company (optional)"
                        filter
                        showClear
                        size="small"
                        fluid
                    />
                    <small v-if="formErrors.company_id" class="text-red-500">
                        {{ formErrors.company_id }}
                    </small>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <label for="contract_start_date" class="font-medium">Start Date *</label>
                        <DatePicker
                            id="contract_start_date"
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
                        <label for="contract_end_date" class="font-medium">End Date</label>
                        <DatePicker
                            id="contract_end_date"
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
                    <label for="contract_salary" class="font-medium">Salary *</label>
                    <InputNumber
                        id="contract_salary"
                        v-model="form.salary_amount"
                        :invalid="!!formErrors.salary_amount"
                        mode="currency"
                        currency="SGD"
                        locale="en-SG"
                        :min-fraction-digits="2"
                        :max-fraction-digits="4"
                        size="small"
                        fluid
                    />
                    <small v-if="formErrors.salary_amount" class="text-red-500">
                        {{ formErrors.salary_amount }}
                    </small>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <label class="font-medium">Annual Leave *</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs text-muted-foreground">Entitled</label>
                                <InputNumber
                                    v-model="form.annual_leave_entitled"
                                    :invalid="!!formErrors.annual_leave_entitled"
                                    :min="0"
                                    :max="255"
                                    size="small"
                                    fluid
                                />
                            </div>
                            <div>
                                <label class="text-xs text-muted-foreground">Taken</label>
                                <InputNumber
                                    v-model="form.annual_leave_taken"
                                    :invalid="!!formErrors.annual_leave_taken"
                                    :min="0"
                                    :max="255"
                                    size="small"
                                    fluid
                                />
                            </div>
                        </div>
                        <small v-if="formErrors.annual_leave_entitled" class="text-red-500">
                            {{ formErrors.annual_leave_entitled }}
                        </small>
                        <small v-if="formErrors.annual_leave_taken" class="text-red-500">
                            {{ formErrors.annual_leave_taken }}
                        </small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="font-medium">Sick Leave *</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs text-muted-foreground">Entitled</label>
                                <InputNumber
                                    v-model="form.sick_leave_entitled"
                                    :invalid="!!formErrors.sick_leave_entitled"
                                    :min="0"
                                    :max="255"
                                    size="small"
                                    fluid
                                />
                            </div>
                            <div>
                                <label class="text-xs text-muted-foreground">Taken</label>
                                <InputNumber
                                    v-model="form.sick_leave_taken"
                                    :invalid="!!formErrors.sick_leave_taken"
                                    :min="0"
                                    :max="255"
                                    size="small"
                                    fluid
                                />
                            </div>
                        </div>
                        <small v-if="formErrors.sick_leave_entitled" class="text-red-500">
                            {{ formErrors.sick_leave_entitled }}
                        </small>
                        <small v-if="formErrors.sick_leave_taken" class="text-red-500">
                            {{ formErrors.sick_leave_taken }}
                        </small>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="contract_external_url" class="font-medium">External Document URL</label>
                    <InputText
                        id="contract_external_url"
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
                        <div v-if="editingId && currentEditingContract?.document_url" class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-file text-primary"></i>
                                <span class="text-sm">{{ currentEditingContract.document_filename }}</span>
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
                                    id="contract-file-upload"
                                    @change="onFileSelect"
                                />
                                <label
                                    for="contract-file-upload"
                                    class="cursor-pointer rounded border border-sidebar-border bg-surface-50 px-3 py-1.5 text-sm hover:bg-surface-100 dark:bg-surface-800 dark:hover:bg-surface-700"
                                >
                                    <i class="pi pi-upload mr-2"></i>
                                    {{ selectedFile ? selectedFile.name : 'Choose file' }}
                                </label>
                                <!-- For existing contracts, show separate upload button -->
                                <Button
                                    v-if="editingId && selectedFile"
                                    label="Upload"
                                    icon="pi pi-cloud-upload"
                                    size="small"
                                    :loading="uploadingDocument"
                                    @click="uploadDocumentInDialog"
                                />
                                <!-- For new contracts, file will be submitted with form -->
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
                                    <br />File will be uploaded when you save the contract.
                                </template>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="contract_comments" class="font-medium">Comments</label>
                    <Editor
                        id="contract_comments"
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
                    <Button type="submit" :label="editingId ? 'Save Changes' : 'Add Contract'" size="small" :loading="saving" />
                </div>
            </form>
        </Dialog>

        <!-- Document Upload Dialog (shown when editing existing contract) -->
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
    </div>
</template>
