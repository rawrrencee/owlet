<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import { useConfirm } from 'primevue/useconfirm';
import { computed, reactive, ref } from 'vue';
import { employmentStatusOptions } from '@/constants/company';
import { type Designation, type EmployeeCompany } from '@/types/company';
import { type Employee } from '@/types/employee';

interface EmployeeCompanyWithEmployee extends EmployeeCompany {
    employee?: {
        id: number;
        first_name: string;
        last_name: string;
    };
}

interface Props {
    companyId: number;
    companyEmployees: EmployeeCompanyWithEmployee[];
    employees: Employee[];
    designations: Designation[];
}

const props = defineProps<Props>();
const expandedRows = ref({});

const dialogVisible = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);

const form = reactive({
    employee_id: null as number | null,
    designation_id: null as number | null,
    status: 'FT' as string,
    levy_amount: 0,
    include_shg_donations: false,
    commencement_date: null as Date | null,
    left_date: null as Date | null,
});

const formErrors = reactive<Record<string, string>>({});

const confirm = useConfirm();

const employeeOptions = computed(() =>
    props.employees.map((e) => ({
        label: `${e.first_name} ${e.last_name}`,
        value: e.id,
    })),
);

const designationOptions = computed(() =>
    props.designations.map((d) => ({
        label: `${d.designation_name} (${d.designation_code})`,
        value: d.id,
    })),
);

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function resetForm() {
    form.employee_id = null;
    form.designation_id = null;
    form.status = 'FT';
    form.levy_amount = 0;
    form.include_shg_donations = false;
    form.commencement_date = null;
    form.left_date = null;
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);
}

function openAddDialog() {
    resetForm();
    editingId.value = null;
    dialogVisible.value = true;
}

function openEditDialog(ec: EmployeeCompanyWithEmployee) {
    resetForm();
    editingId.value = ec.id;
    form.employee_id = ec.employee_id;
    form.designation_id = ec.designation_id;
    form.status = ec.status;
    form.levy_amount = Number(ec.levy_amount) || 0;
    form.include_shg_donations = ec.include_shg_donations;
    form.commencement_date = ec.commencement_date
        ? new Date(ec.commencement_date)
        : null;
    form.left_date = ec.left_date ? new Date(ec.left_date) : null;
    dialogVisible.value = true;
}

function formatDateForBackend(date: Date | null): string | null {
    if (!date) return null;
    return date.toISOString().split('T')[0];
}

function saveAssignment() {
    saving.value = true;
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);

    const data = {
        employee_id: form.employee_id,
        designation_id: form.designation_id,
        status: form.status,
        levy_amount: form.levy_amount,
        include_shg_donations: form.include_shg_donations,
        commencement_date: formatDateForBackend(form.commencement_date),
        left_date: formatDateForBackend(form.left_date),
    };

    const url = editingId.value
        ? `/companies/${props.companyId}/employees/${editingId.value}`
        : `/companies/${props.companyId}/employees`;

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

function confirmEndAssignment(ec: EmployeeCompanyWithEmployee) {
    const employeeName = ec.employee
        ? `${ec.employee.first_name} ${ec.employee.last_name}`
        : 'this employee';
    confirm.require({
        message: `Are you sure you want to end the assignment for "${employeeName}"? This will set the left date to today.`,
        header: 'End Assignment',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'End Assignment',
        acceptProps: {
            severity: 'warn',
            size: 'small',
        },
        accept: () => {
            router.put(
                `/companies/${props.companyId}/employees/${ec.id}`,
                { left_date: new Date().toISOString().split('T')[0] },
                { preserveScroll: true },
            );
        },
    });
}

function confirmRemoveAssignment(ec: EmployeeCompanyWithEmployee) {
    const employeeName = ec.employee
        ? `${ec.employee.first_name} ${ec.employee.last_name}`
        : 'this employee';
    confirm.require({
        message: `Are you sure you want to remove the assignment for "${employeeName}"? This action cannot be undone.`,
        header: 'Remove Assignment',
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
            router.delete(`/companies/${props.companyId}/employees/${ec.id}`, {
                preserveScroll: true,
            });
        },
    });
}

function getEmployeeName(ec: EmployeeCompanyWithEmployee): string {
    if (ec.employee) {
        return `${ec.employee.first_name} ${ec.employee.last_name}`;
    }
    return '-';
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">Employees</h3>
            <Button
                label="Add Employee"
                icon="pi pi-plus"
                size="small"
                @click="openAddDialog"
            />
        </div>

        <DataTable
            v-model:expandedRows="expandedRows"
            :value="companyEmployees"
            dataKey="id"
            striped-rows
            size="small"
            class="overflow-hidden rounded-xl border border-border dark:border-border"
        >
            <template #empty>
                <div class="p-4 text-center text-muted-foreground">
                    No employees assigned to this company. Click "Add Employee"
                    to assign employees.
                </div>
            </template>
            <Column expander style="width: 3rem" class="!pr-0 sm:hidden" />
            <Column header="Employee">
                <template #body="{ data }">
                    <span class="font-medium">{{ getEmployeeName(data) }}</span>
                </template>
            </Column>
            <Column
                field="designation.designation_name"
                header="Designation"
                class="hidden md:table-cell"
            >
                <template #body="{ data }">
                    {{ data.designation?.designation_name ?? '-' }}
                </template>
            </Column>
            <Column
                field="status_label"
                header="Type"
                class="hidden sm:table-cell"
            >
                <template #body="{ data }">
                    {{ data.status_label }}
                </template>
            </Column>
            <Column
                field="commencement_date"
                header="Start Date"
                class="hidden lg:table-cell"
            >
                <template #body="{ data }">
                    {{ formatDate(data.commencement_date) }}
                </template>
            </Column>
            <Column header="Active">
                <template #body="{ data }">
                    <Tag
                        :value="data.is_active ? 'Active' : 'Ended'"
                        :severity="data.is_active ? 'success' : 'secondary'"
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
                            v-if="data.is_active"
                            icon="pi pi-calendar-times"
                            severity="warn"
                            text
                            rounded
                            size="small"
                            @click="confirmEndAssignment(data)"
                            v-tooltip.top="'End Assignment'"
                        />
                        <Button
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="confirmRemoveAssignment(data)"
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
                            >Designation</span
                        >
                        <span class="text-right">{{
                            data.designation?.designation_name ?? '-'
                        }}</span>
                    </div>
                    <div
                        class="flex justify-between gap-4 border-b border-border pb-2"
                    >
                        <span class="shrink-0 text-muted-foreground">Type</span>
                        <span class="text-right">{{ data.status_label }}</span>
                    </div>
                    <div
                        class="flex justify-between gap-4 border-b border-border pb-2"
                    >
                        <span class="shrink-0 text-muted-foreground"
                            >Start Date</span
                        >
                        <span class="text-right">{{
                            formatDate(data.commencement_date)
                        }}</span>
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
                            v-if="data.is_active"
                            icon="pi pi-calendar-times"
                            label="End"
                            severity="warn"
                            text
                            size="small"
                            @click="confirmEndAssignment(data)"
                        />
                        <Button
                            icon="pi pi-trash"
                            label="Remove"
                            severity="danger"
                            text
                            size="small"
                            @click="confirmRemoveAssignment(data)"
                        />
                    </div>
                </div>
            </template>
        </DataTable>

        <Dialog
            v-model:visible="dialogVisible"
            :header="
                editingId
                    ? 'Edit Employee Assignment'
                    : 'Add Employee to Company'
            "
            :modal="true"
            :closable="!saving"
            class="w-full max-w-lg"
        >
            <form @submit.prevent="saveAssignment" class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="ce_employee_id" class="font-medium"
                        >Employee *</label
                    >
                    <Select
                        id="ce_employee_id"
                        v-model="form.employee_id"
                        :options="employeeOptions"
                        option-label="label"
                        option-value="value"
                        :invalid="!!formErrors.employee_id"
                        placeholder="Select employee"
                        filter
                        size="small"
                        fluid
                        :disabled="!!editingId"
                    />
                    <small v-if="formErrors.employee_id" class="text-red-500">
                        {{ formErrors.employee_id }}
                    </small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="ce_designation_id" class="font-medium"
                        >Designation</label
                    >
                    <Select
                        id="ce_designation_id"
                        v-model="form.designation_id"
                        :options="designationOptions"
                        option-label="label"
                        option-value="value"
                        :invalid="!!formErrors.designation_id"
                        placeholder="Select designation"
                        filter
                        showClear
                        size="small"
                        fluid
                    />
                    <small
                        v-if="formErrors.designation_id"
                        class="text-red-500"
                    >
                        {{ formErrors.designation_id }}
                    </small>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <label for="ce_status" class="font-medium"
                            >Employment Status *</label
                        >
                        <Select
                            id="ce_status"
                            v-model="form.status"
                            :options="[...employmentStatusOptions]"
                            option-label="label"
                            option-value="value"
                            :invalid="!!formErrors.status"
                            size="small"
                            fluid
                        />
                        <small v-if="formErrors.status" class="text-red-500">
                            {{ formErrors.status }}
                        </small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="ce_levy_amount" class="font-medium"
                            >Levy Amount</label
                        >
                        <InputNumber
                            id="ce_levy_amount"
                            v-model="form.levy_amount"
                            :invalid="!!formErrors.levy_amount"
                            mode="currency"
                            currency="SGD"
                            locale="en-SG"
                            :min-fraction-digits="2"
                            :max-fraction-digits="4"
                            size="small"
                            fluid
                        />
                        <small
                            v-if="formErrors.levy_amount"
                            class="text-red-500"
                        >
                            {{ formErrors.levy_amount }}
                        </small>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <label for="ce_commencement_date" class="font-medium"
                            >Start Date *</label
                        >
                        <DatePicker
                            id="ce_commencement_date"
                            v-model="form.commencement_date"
                            :invalid="!!formErrors.commencement_date"
                            dateFormat="yy-mm-dd"
                            showIcon
                            size="small"
                            fluid
                        />
                        <small
                            v-if="formErrors.commencement_date"
                            class="text-red-500"
                        >
                            {{ formErrors.commencement_date }}
                        </small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="ce_left_date" class="font-medium"
                            >End Date</label
                        >
                        <DatePicker
                            id="ce_left_date"
                            v-model="form.left_date"
                            :invalid="!!formErrors.left_date"
                            dateFormat="yy-mm-dd"
                            showIcon
                            showClear
                            size="small"
                            fluid
                        />
                        <small v-if="formErrors.left_date" class="text-red-500">
                            {{ formErrors.left_date }}
                        </small>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <ToggleSwitch v-model="form.include_shg_donations" />
                    <label class="font-medium">Include SHG Donations</label>
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
                        :label="editingId ? 'Save Changes' : 'Add Employee'"
                        size="small"
                        :loading="saving"
                    />
                </div>
            </form>
        </Dialog>
    </div>
</template>
