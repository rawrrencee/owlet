<script setup lang="ts">
import { type Company, type EmployeeContract } from '@/types/company';
import type { LeaveType } from '@/types/leave';
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

interface Props {
    employeeId: number;
    contracts: EmployeeContract[];
    companies: Company[];
    leaveTypes: LeaveType[];
}

const props = defineProps<Props>();
const expandedRows = ref({});

const dialogVisible = ref(false);
const editingId = ref<number | null>(null);
const editingContract = ref<EmployeeContract | null>(null);
const saving = ref(false);
const documentPreviewVisible = ref(false);
const documentPreviewUrl = ref<string | null>(null);

interface EntitlementFormRow {
    leave_type_id: number;
    entitled_days: number;
    taken_days: number;
}

const form = reactive({
    company_id: null as number | null,
    start_date: null as Date | null,
    end_date: null as Date | null,
    salary_amount: 0,
    entitlements: [] as EntitlementFormRow[],
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
    return (
        props.contracts.find((c) => c.id === editingId.value) ||
        editingContract.value
    );
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
    return new Intl.NumberFormat('en-SG', {
        style: 'currency',
        currency: 'SGD',
    }).format(num);
}

function initEntitlements() {
    form.entitlements = props.leaveTypes.map((lt) => ({
        leave_type_id: lt.id,
        entitled_days: 0,
        taken_days: 0,
    }));
}

function resetForm() {
    form.company_id = null;
    form.start_date = null;
    form.end_date = null;
    form.salary_amount = 0;
    initEntitlements();
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
    form.start_date = contract.start_date
        ? new Date(contract.start_date)
        : null;
    form.end_date = contract.end_date ? new Date(contract.end_date) : null;
    form.salary_amount = Number(contract.salary_amount) || 0;

    // Populate entitlements from contract's leave_entitlements
    const entitlements = Array.isArray(contract.leave_entitlements) ? contract.leave_entitlements : [];
    form.entitlements = props.leaveTypes.map((lt) => {
        const existing = entitlements.find(
            (e: any) => e.leave_type_id === lt.id,
        );
        return {
            leave_type_id: lt.id,
            entitled_days: existing ? Number(existing.entitled_days) : 0,
            taken_days: existing ? Number(existing.taken_days) : 0,
        };
    });

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
        if (form.company_id)
            formData.append('company_id', String(form.company_id));
        if (form.start_date)
            formData.append(
                'start_date',
                formatDateForBackend(form.start_date)!,
            );
        if (form.end_date)
            formData.append('end_date', formatDateForBackend(form.end_date)!);
        formData.append('salary_amount', String(form.salary_amount));

        // Append entitlements
        form.entitlements.forEach((ent, idx) => {
            formData.append(
                `entitlements[${idx}][leave_type_id]`,
                String(ent.leave_type_id),
            );
            formData.append(
                `entitlements[${idx}][entitled_days]`,
                String(ent.entitled_days),
            );
            formData.append(
                `entitlements[${idx}][taken_days]`,
                String(ent.taken_days),
            );
        });

        if (form.external_document_url)
            formData.append(
                'external_document_url',
                form.external_document_url,
            );
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
            entitlements: form.entitlements,
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
            router.delete(
                `/users/${props.employeeId}/contracts/${contract.id}`,
                {
                    preserveScroll: true,
                },
            );
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
            documentPreviewUrl.value = contract.document_url;
            documentPreviewVisible.value = true;
        } else {
            window.open(contract.document_url, '_blank');
        }
    }
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

    router.post(
        `/users/${props.employeeId}/contracts/${editingContract.value.id}/document`,
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
            router.delete(
                `/users/${props.employeeId}/contracts/${editingContract.value!.id}/document`,
                {
                    preserveScroll: true,
                },
            );
        },
    });
}

function viewDocumentInDialog() {
    if (!editingContract.value) return;
    viewDocument(editingContract.value);
}

function getLeaveDisplay(contract: EmployeeContract, leaveTypeId: number): string {
    const entitlements = Array.isArray(contract.leave_entitlements) ? contract.leave_entitlements : [];
    const ent = entitlements.find(
        (e: any) => e.leave_type_id === leaveTypeId,
    );
    if (!ent) return '-';
    const remaining = Math.max(0, Number(ent.entitled_days) - Number(ent.taken_days));
    return `${remaining}/${ent.entitled_days}`;
}

function getLeaveTypeName(leaveTypeId: number): string {
    return props.leaveTypes.find((lt) => lt.id === leaveTypeId)?.name ?? '';
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">Contracts</h3>
            <Button
                label="Add Contract"
                icon="pi pi-plus"
                size="small"
                @click="openAddDialog"
            />
        </div>

        <DataTable
            v-model:expandedRows="expandedRows"
            :value="contracts"
            dataKey="id"
            striped-rows
            size="small"
            class="overflow-hidden rounded-xl border border-border dark:border-border"
        >
            <template #empty>
                <div class="p-4 text-center text-muted-foreground">
                    No contracts found. Click "Add Contract" to add a new
                    contract.
                </div>
            </template>
            <Column expander style="width: 3rem" class="!pr-0 sm:hidden" />
            <Column field="company.company_name" header="Company">
                <template #body="{ data }">
                    <span class="font-medium">{{
                        data.company?.company_name ?? '-'
                    }}</span>
                </template>
            </Column>
            <Column
                field="start_date"
                header="Start Date"
                class="hidden md:table-cell"
            >
                <template #body="{ data }">
                    {{ formatDate(data.start_date) }}
                </template>
            </Column>
            <Column
                field="end_date"
                header="End Date"
                class="hidden lg:table-cell"
            >
                <template #body="{ data }">
                    {{ formatDate(data.end_date) }}
                </template>
            </Column>
            <Column
                field="salary_amount"
                header="Salary"
                class="hidden md:table-cell"
            >
                <template #body="{ data }">
                    {{ formatCurrency(data.salary_amount) }}
                </template>
            </Column>
            <Column
                v-for="lt in leaveTypes.slice(0, 2)"
                :key="lt.id"
                :header="lt.name"
                class="hidden sm:table-cell"
            >
                <template #body="{ data }">
                    {{ getLeaveDisplay(data, lt.id) }}
                </template>
            </Column>
            <Column header="Status">
                <template #body="{ data }">
                    <Tag
                        :value="data.is_active ? 'Active' : 'Expired'"
                        :severity="data.is_active ? 'success' : 'secondary'"
                    />
                </template>
            </Column>
            <Column header="Doc" class="hidden w-12 sm:table-cell">
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
            <Column header="" class="hidden w-32 !pr-4 sm:table-cell">
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
            <template #expansion="{ data }">
                <div class="grid gap-3 p-3 text-sm sm:hidden">
                    <div
                        class="flex justify-between gap-4 border-b border-border pb-2"
                    >
                        <span class="shrink-0 text-muted-foreground"
                            >Start Date</span
                        >
                        <span class="text-right">{{
                            formatDate(data.start_date)
                        }}</span>
                    </div>
                    <div
                        class="flex justify-between gap-4 border-b border-border pb-2"
                    >
                        <span class="shrink-0 text-muted-foreground"
                            >End Date</span
                        >
                        <span class="text-right">{{
                            formatDate(data.end_date)
                        }}</span>
                    </div>
                    <div
                        class="flex justify-between gap-4 border-b border-border pb-2"
                    >
                        <span class="shrink-0 text-muted-foreground"
                            >Salary</span
                        >
                        <span class="text-right">{{
                            formatCurrency(data.salary_amount)
                        }}</span>
                    </div>
                    <div
                        v-for="lt in leaveTypes"
                        :key="lt.id"
                        class="flex justify-between gap-4 border-b border-border pb-2"
                    >
                        <span class="shrink-0 text-muted-foreground">{{
                            lt.name
                        }}</span>
                        <span class="text-right">{{
                            getLeaveDisplay(data, lt.id)
                        }}</span>
                    </div>
                    <div
                        class="flex justify-between gap-4 border-b border-border pb-2"
                    >
                        <span class="shrink-0 text-muted-foreground"
                            >Document</span
                        >
                        <span class="text-right">
                            <Button
                                v-if="data.has_document"
                                icon="pi pi-file"
                                label="View"
                                severity="secondary"
                                text
                                size="small"
                                @click="viewDocument(data)"
                            />
                            <span v-else>-</span>
                        </span>
                    </div>
                    <div class="flex justify-end gap-1 pt-1">
                        <Button
                            icon="pi pi-pencil"
                            label="Edit"
                            severity="secondary"
                            text
                            size="small"
                            @click="openEditDialog(data)"
                        />
                        <Button
                            icon="pi pi-trash"
                            label="Remove"
                            severity="danger"
                            text
                            size="small"
                            @click="confirmRemoveContract(data)"
                        />
                    </div>
                </div>
            </template>
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
                    <label for="contract_company_id" class="font-medium"
                        >Company</label
                    >
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
                        <label for="contract_start_date" class="font-medium"
                            >Start Date *</label
                        >
                        <DatePicker
                            id="contract_start_date"
                            v-model="form.start_date"
                            :invalid="!!formErrors.start_date"
                            dateFormat="yy-mm-dd"
                            showIcon
                            size="small"
                            fluid
                        />
                        <small
                            v-if="formErrors.start_date"
                            class="text-red-500"
                        >
                            {{ formErrors.start_date }}
                        </small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="contract_end_date" class="font-medium"
                            >End Date</label
                        >
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
                    <label for="contract_salary" class="font-medium"
                        >Salary *</label
                    >
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

                <!-- Dynamic Leave Entitlements -->
                <div class="flex flex-col gap-3">
                    <label class="font-medium">Leave Entitlements</label>
                    <div
                        v-for="(ent, idx) in form.entitlements"
                        :key="ent.leave_type_id"
                        class="flex flex-col gap-2"
                    >
                        <label class="text-sm font-medium">{{
                            getLeaveTypeName(ent.leave_type_id)
                        }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label
                                    class="text-xs text-muted-foreground"
                                    >Entitled</label
                                >
                                <InputNumber
                                    v-model="form.entitlements[idx].entitled_days"
                                    :min="0"
                                    :max="999"
                                    :minFractionDigits="0"
                                    :maxFractionDigits="1"
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
                                    v-model="form.entitlements[idx].taken_days"
                                    :min="0"
                                    :max="999"
                                    :minFractionDigits="0"
                                    :maxFractionDigits="1"
                                    size="small"
                                    fluid
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="contract_external_url" class="font-medium"
                        >External Document URL</label
                    >
                    <InputText
                        id="contract_external_url"
                        v-model="form.external_document_url"
                        :invalid="!!formErrors.external_document_url"
                        placeholder="https://example.com/document.pdf"
                        size="small"
                        fluid
                    />
                    <small
                        v-if="formErrors.external_document_url"
                        class="text-red-500"
                    >
                        {{ formErrors.external_document_url }}
                    </small>
                </div>

                <!-- Document Upload -->
                <div class="flex flex-col gap-2">
                    <label class="font-medium">Document Upload</label>
                    <div
                        class="rounded-lg border border-border p-3 dark:border-border"
                    >
                        <div
                            v-if="
                                editingId &&
                                currentEditingContract?.document_url
                            "
                            class="flex items-center justify-between"
                        >
                            <div class="flex items-center gap-2">
                                <i class="pi pi-file text-primary"></i>
                                <span class="text-sm">{{
                                    currentEditingContract.document_filename
                                }}</span>
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
                                    class="bg-surface-50 hover:bg-surface-100 dark:bg-surface-800 dark:hover:bg-surface-700 cursor-pointer rounded border border-border px-3 py-1.5 text-sm"
                                >
                                    <i class="pi pi-upload mr-2"></i>
                                    {{
                                        selectedFile
                                            ? selectedFile.name
                                            : 'Choose file'
                                    }}
                                </label>
                                <Button
                                    v-if="editingId && selectedFile"
                                    label="Upload"
                                    icon="pi pi-cloud-upload"
                                    size="small"
                                    :loading="uploadingDocument"
                                    @click="uploadDocumentInDialog"
                                />
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
                                Max 5MB. Supported: PDF, JPG, PNG, GIF, DOC,
                                DOCX
                                <template v-if="!editingId && selectedFile">
                                    <br />File will be uploaded when you save
                                    the contract.
                                </template>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="contract_comments" class="font-medium"
                        >Comments</label
                    >
                    <Editor
                        id="contract_comments"
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
                                    v-tooltip.bottom="'Numbered List'"
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
                                    v-tooltip.bottom="'Clear Formatting'"
                                ></button>
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
                    <Button
                        type="submit"
                        :label="editingId ? 'Save Changes' : 'Add Contract'"
                        size="small"
                        :loading="saving"
                    />
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
    </div>
</template>
