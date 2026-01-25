<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Editor from 'primevue/editor';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type EmployeeInsurance } from '@/types';

interface EmployeeOption {
    id: number;
    first_name: string;
    last_name: string;
}

interface Props {
    employees: EmployeeOption[];
    selectedEmployee?: EmployeeOption;
    employeeInsurances?: EmployeeInsurance[];
}

const props = defineProps<Props>();

const indexUrl = '/documents?type=insurances';

const selectedEmployeeId = ref<number | null>(props.selectedEmployee?.id ?? null);
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);

// View dialog state
const viewDialogVisible = ref(false);
const viewingInsurance = ref<EmployeeInsurance | null>(null);

function openViewDialog(insurance: EmployeeInsurance) {
    viewingInsurance.value = insurance;
    viewDialogVisible.value = true;
}

const form = useForm({
    employee_id: props.selectedEmployee?.id ?? null,
    title: '',
    insurer_name: '',
    policy_number: '',
    start_date: null as Date | null,
    end_date: null as Date | null,
    external_document_url: '',
    comments: '',
    document: null as File | null,
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Documents', href: '/documents?type=insurances' },
    { title: 'Create Insurance' },
];

const employeeOptions = computed(() =>
    props.employees.map((e) => ({
        label: `${e.first_name} ${e.last_name}`,
        value: e.id,
    })),
);

watch(selectedEmployeeId, (newId) => {
    form.employee_id = newId;
    if (newId && newId !== props.selectedEmployee?.id) {
        router.get('/documents/insurances/create', { employee_id: newId }, { preserveState: true });
    }
});

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function formatDateForBackend(date: Date | null): string | null {
    if (!date) return null;
    return date.toISOString().split('T')[0];
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

function submitForm() {
    const formData = new FormData();
    formData.append('employee_id', String(form.employee_id));
    formData.append('title', form.title);
    formData.append('insurer_name', form.insurer_name);
    formData.append('policy_number', form.policy_number);
    formData.append('start_date', formatDateForBackend(form.start_date) ?? '');
    if (form.end_date) formData.append('end_date', formatDateForBackend(form.end_date) ?? '');
    if (form.external_document_url) formData.append('external_document_url', form.external_document_url);
    if (form.comments) formData.append('comments', form.comments);
    if (selectedFile.value) formData.append('document', selectedFile.value);

    router.post('/documents/insurances', formData, {
        forceFormData: true,
        onError: (errors) => {
            form.errors = errors as typeof form.errors;
        },
    });
}
</script>

<template>
    <Head title="Create Insurance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <Button icon="pi pi-arrow-left" severity="secondary" text rounded size="small" @click="router.visit(indexUrl)" />
                    <h1 class="text-2xl font-semibold">Create Insurance</h1>
                </div>
            </div>

            <div class="mx-auto w-full max-w-2xl">
                <Card>
                    <template #content>
                        <form @submit.prevent="submitForm" class="flex flex-col gap-4">
                            <!-- Employee Selection -->
                            <div class="flex flex-col gap-2">
                                <label for="employee_id" class="font-medium">Employee *</label>
                                <Select
                                    id="employee_id"
                                    v-model="selectedEmployeeId"
                                    :options="employeeOptions"
                                    option-label="label"
                                    option-value="value"
                                    :invalid="!!form.errors.employee_id"
                                    placeholder="Select an employee"
                                    filter
                                    size="small"
                                    fluid
                                />
                                <small v-if="form.errors.employee_id" class="text-red-500">
                                    {{ form.errors.employee_id }}
                                </small>
                            </div>

                            <!-- Show employee's existing insurances if employee selected -->
                            <div v-if="selectedEmployee && employeeInsurances && employeeInsurances.length > 0">
                                <Divider />
                                <div class="flex flex-col gap-2">
                                    <label class="font-medium">Existing Insurances for {{ selectedEmployee.first_name }} {{ selectedEmployee.last_name }}</label>
                                    <DataTable
                                        :value="employeeInsurances"
                                        dataKey="id"
                                        striped-rows
                                        size="small"
                                        class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
                                    >
                                        <Column field="title" header="Title" style="width: 30%">
                                            <template #body="{ data }">
                                                {{ data.title }}
                                            </template>
                                        </Column>
                                        <Column field="insurer_name" header="Insurer" style="width: 25%">
                                            <template #body="{ data }">
                                                {{ data.insurer_name }}
                                            </template>
                                        </Column>
                                        <Column field="start_date" header="Start" style="width: 15%">
                                            <template #body="{ data }">
                                                {{ formatDate(data.start_date) }}
                                            </template>
                                        </Column>
                                        <Column header="Status" style="width: 15%">
                                            <template #body="{ data }">
                                                <Tag :value="data.is_active ? 'Active' : 'Expired'" :severity="data.is_active ? 'success' : 'secondary'" />
                                            </template>
                                        </Column>
                                        <Column header="" style="width: 3rem">
                                            <template #body="{ data }">
                                                <Button
                                                    icon="pi pi-eye"
                                                    severity="secondary"
                                                    text
                                                    rounded
                                                    size="small"
                                                    @click="openViewDialog(data)"
                                                    v-tooltip.top="'View Details'"
                                                />
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                                <Divider />
                            </div>

                            <!-- Show message if employee selected but no insurances -->
                            <div v-else-if="selectedEmployee && (!employeeInsurances || employeeInsurances.length === 0)">
                                <Divider />
                                <div class="rounded-lg border border-sidebar-border/70 bg-surface-50 p-3 text-center text-sm text-muted-foreground dark:bg-surface-900">
                                    {{ selectedEmployee.first_name }} {{ selectedEmployee.last_name }} has no existing insurances.
                                </div>
                                <Divider />
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
                                <div class="rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                                    <div class="flex flex-col gap-2">
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
                                <small v-if="form.errors.document" class="text-red-500">
                                    {{ form.errors.document }}
                                </small>
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
                                <Button type="button" label="Cancel" severity="secondary" size="small" @click="router.visit(indexUrl)" :disabled="form.processing" />
                                <Button type="submit" label="Create Insurance" size="small" :loading="form.processing" :disabled="!selectedEmployeeId" />
                            </div>
                        </form>
                    </template>
                </Card>
            </div>
        </div>

        <!-- View Insurance Dialog -->
        <Dialog
            v-model:visible="viewDialogVisible"
            header="Insurance Details"
            :modal="true"
            :dismissableMask="true"
            class="w-full max-w-2xl"
        >
            <div v-if="viewingInsurance" class="flex flex-col gap-4">
                <!-- Status -->
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Status:</span>
                    <Tag
                        :value="viewingInsurance.is_active ? 'Active' : 'Expired'"
                        :severity="viewingInsurance.is_active ? 'success' : 'secondary'"
                    />
                </div>

                <Divider />

                <!-- Insurance Information -->
                <div>
                    <h4 class="mb-3 font-medium">Insurance Information</h4>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <span class="text-sm text-muted-foreground">Title</span>
                            <span class="font-medium">{{ viewingInsurance.title }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-sm text-muted-foreground">Insurer</span>
                            <span>{{ viewingInsurance.insurer_name }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-sm text-muted-foreground">Policy Number</span>
                            <span class="font-mono">{{ viewingInsurance.policy_number }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-sm text-muted-foreground">Start Date</span>
                            <span>{{ formatDate(viewingInsurance.start_date) }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-sm text-muted-foreground">End Date</span>
                            <span>{{ formatDate(viewingInsurance.end_date) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Document -->
                <template v-if="viewingInsurance.has_document">
                    <Divider />
                    <div>
                        <h4 class="mb-3 font-medium">Document</h4>
                        <div class="flex items-center gap-3">
                            <i class="pi pi-file text-xl text-primary"></i>
                            <span v-if="viewingInsurance.document_filename">{{ viewingInsurance.document_filename }}</span>
                            <span v-else-if="viewingInsurance.external_document_url" class="text-sm text-muted-foreground">External Document</span>
                        </div>
                    </div>
                </template>

                <!-- Comments -->
                <template v-if="viewingInsurance.comments">
                    <Divider />
                    <div>
                        <h4 class="mb-3 font-medium">Comments</h4>
                        <div class="prose prose-sm max-w-none" v-html="viewingInsurance.comments"></div>
                    </div>
                </template>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button
                        label="View Full Page"
                        icon="pi pi-external-link"
                        severity="secondary"
                        size="small"
                        @click="router.visit(`/documents/insurances/${viewingInsurance?.id}`)"
                    />
                    <Button label="Close" size="small" @click="viewDialogVisible = false" />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
