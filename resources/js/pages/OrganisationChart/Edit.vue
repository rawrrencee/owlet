<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import IconField from 'primevue/iconfield';
import Image from 'primevue/image';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type EmployeeWithManagers, type ManagerInfo } from '@/types';

interface Props {
    employees: EmployeeWithManagers[];
    companies: Array<{ label: string; value: number }>;
}

const props = defineProps<Props>();

const toast = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Organisation Chart', href: '/organisation-chart' },
    { title: 'Edit' },
];

// Filter state
const searchQuery = ref('');
const selectedCompany = ref<number | null>(null);

// Selection state for bulk operations
const selectedEmployees = ref<EmployeeWithManagers[]>([]);

// Expanded rows for mobile view
const expandedRows = ref({});

// Dialog state
const editDialogVisible = ref(false);
const bulkDialogVisible = ref(false);
const editingEmployee = ref<EmployeeWithManagers | null>(null);
const employeeManagers = ref<ManagerInfo[]>([]);
const availableManagers = ref<Array<{ label: string; value: number }>>([]);
const loadingManagers = ref(false);
const selectedManager = ref<number | null>(null);
const saving = ref(false);

// Bulk dialog state
const bulkManager = ref<number | null>(null);
const bulkSaving = ref(false);
const bulkResult = ref<{ success: number[]; failed: Array<{ id: number; name: string; reason: string }> } | null>(null);

// Computed filtered employees
const filteredEmployees = computed(() => {
    let result = props.employees;

    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (emp) =>
                emp.name.toLowerCase().includes(query) ||
                (emp.designation?.toLowerCase().includes(query) ?? false) ||
                (emp.company?.toLowerCase().includes(query) ?? false) ||
                (emp.employee_number?.toLowerCase().includes(query) ?? false),
        );
    }

    if (selectedCompany.value) {
        result = result.filter((emp) => {
            // Match by company name since that's what we have
            const companyOption = props.companies.find((c) => c.value === selectedCompany.value);
            return emp.company === companyOption?.label;
        });
    }

    return result;
});

// Get available managers for bulk assign (exclude selected employees)
const bulkAvailableManagers = computed(() => {
    const selectedIds = selectedEmployees.value.map((e) => e.id);
    return props.employees
        .filter((emp) => !selectedIds.includes(emp.id))
        .map((emp) => ({
            label: `${emp.name}${emp.employee_number ? ` (${emp.employee_number})` : ''}`,
            value: emp.id,
        }));
});

function getTierColor(tier: number): string {
    const colors: Record<number, string> = {
        1: 'secondary',
        2: 'info',
        3: 'warn',
        4: 'success',
        5: 'danger',
    };
    return colors[tier] || 'secondary';
}

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

async function openEditDialog(employee: EmployeeWithManagers) {
    editingEmployee.value = employee;
    employeeManagers.value = [...employee.managers];
    selectedManager.value = null;
    loadingManagers.value = true;
    editDialogVisible.value = true;

    try {
        const response = await fetch(`/organisation-chart/employees/${employee.id}/managers`, {
            headers: { Accept: 'application/json' },
        });
        const data = await response.json();
        employeeManagers.value = data.managers;
        availableManagers.value = data.available_managers;
    } catch (error) {
        console.error('Failed to fetch managers:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load manager data.',
            life: 3000,
        });
    } finally {
        loadingManagers.value = false;
    }
}

async function addManager() {
    if (!selectedManager.value || !editingEmployee.value) return;

    saving.value = true;

    try {
        const response = await fetch(`/organisation-chart/employees/${editingEmployee.value.id}/managers`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ manager_id: selectedManager.value }),
        });

        if (!response.ok) {
            const errorData = await response.json();
            const errorMessage = errorData.errors?.manager_id?.[0] || errorData.message || 'Failed to add manager.';
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errorMessage,
                life: 5000,
            });
            return;
        }

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Manager added successfully.',
            life: 3000,
        });

        selectedManager.value = null;

        // Refresh dialog data
        await refreshManagerData();

        // Refresh page data for the DataTable
        router.reload({ only: ['employees'] });
    } catch (error) {
        console.error('Failed to add manager:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An error occurred while adding the manager.',
            life: 3000,
        });
    } finally {
        saving.value = false;
    }
}

async function removeManager(manager: ManagerInfo) {
    if (!editingEmployee.value) return;

    saving.value = true;

    try {
        const response = await fetch(`/organisation-chart/employees/${editingEmployee.value.id}/managers/${manager.id}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) {
            const errorData = await response.json();
            const errorMessage = errorData.message || 'Failed to remove manager.';
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errorMessage,
                life: 3000,
            });
            return;
        }

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Manager removed successfully.',
            life: 3000,
        });

        // Refresh dialog data
        await refreshManagerData();

        // Refresh page data for the DataTable
        router.reload({ only: ['employees'] });
    } catch (error) {
        console.error('Failed to remove manager:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An error occurred while removing the manager.',
            life: 3000,
        });
    } finally {
        saving.value = false;
    }
}

async function refreshManagerData() {
    if (!editingEmployee.value) return;

    try {
        const response = await fetch(`/organisation-chart/employees/${editingEmployee.value.id}/managers`, {
            headers: { Accept: 'application/json' },
        });
        const data = await response.json();
        employeeManagers.value = data.managers;
        availableManagers.value = data.available_managers;
    } catch (error) {
        console.error('Failed to refresh manager data:', error);
    }
}

function openBulkDialog() {
    bulkManager.value = null;
    bulkResult.value = null;
    bulkDialogVisible.value = true;
}

function closeBulkDialog() {
    // If there were successful assignments, clear selection and refresh
    if (bulkResult.value?.success?.length) {
        selectedEmployees.value = [];
    }
    bulkDialogVisible.value = false;
    bulkResult.value = null;
}

async function bulkAssign() {
    if (!bulkManager.value || selectedEmployees.value.length === 0) return;

    bulkSaving.value = true;

    try {
        const response = await fetch('/organisation-chart/bulk-assign', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                employee_ids: selectedEmployees.value.map((e) => e.id),
                manager_id: bulkManager.value,
            }),
        });

        const result = await response.json();
        bulkResult.value = result;

        if (result.success.length > 0) {
            toast.add({
                severity: 'success',
                summary: 'Bulk Assignment Complete',
                detail: `Successfully assigned manager to ${result.success.length} employee(s).`,
                life: 5000,
            });

            // Reload page to refresh data
            router.reload({ only: ['employees'] });
            selectedEmployees.value = [];
        }

        if (result.failed.length > 0 && result.success.length === 0) {
            toast.add({
                severity: 'error',
                summary: 'Assignment Failed',
                detail: 'No employees could be assigned. See details below.',
                life: 5000,
            });
        }
    } catch (error) {
        console.error('Bulk assign error:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'An error occurred during bulk assignment.',
            life: 3000,
        });
    } finally {
        bulkSaving.value = false;
    }
}

function clearFilters() {
    searchQuery.value = '';
    selectedCompany.value = null;
}

function goBack() {
    router.visit('/organisation-chart');
}
</script>

<template>
    <Head title="Edit Organisation Chart" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Button icon="pi pi-arrow-left" severity="secondary" text rounded size="small" @click="goBack" v-tooltip.top="'Back to Chart'" />
                    <h1 class="heading-lg">Edit Organisation Chart</h1>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section flex flex-col gap-3 sm:flex-row sm:items-center">
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="searchQuery" placeholder="Search by name, designation, company..." size="small" fluid />
                </IconField>
                <Select
                    v-model="selectedCompany"
                    :options="companies"
                    option-label="label"
                    option-value="value"
                    placeholder="All Companies"
                    size="small"
                    show-clear
                    class="w-full sm:w-48"
                />
                <Button v-if="searchQuery || selectedCompany" icon="pi pi-times" label="Clear" severity="secondary" size="small" text @click="clearFilters" />
            </div>

            <!-- Bulk Action Bar -->
            <div
                v-if="selectedEmployees.length > 0"
                class="flex items-center justify-between rounded-lg border border-primary/50 bg-primary/5 p-3 dark:bg-primary/10"
            >
                <span class="text-sm font-medium">{{ selectedEmployees.length }} employee(s) selected</span>
                <div class="flex items-center gap-2">
                    <Button label="Assign Manager" icon="pi pi-users" size="small" @click="openBulkDialog" />
                    <Button label="Clear" icon="pi pi-times" severity="secondary" size="small" text @click="selectedEmployees = []" />
                </div>
            </div>

            <!-- Employees DataTable -->
            <DataTable
                v-model:selection="selectedEmployees"
                v-model:expandedRows="expandedRows"
                :value="filteredEmployees"
                data-key="id"
                striped-rows
                size="small"
                scrollable
                scroll-height="flex"
                class="flex-1 overflow-hidden rounded-lg border border-border "
                @row-click="(e) => openEditDialog(e.data)"
            >
                <template #empty>
                    <div class="p-8 text-center text-muted-foreground">
                        <i class="pi pi-sitemap mb-3 text-4xl"></i>
                        <p>{{ searchQuery || selectedCompany ? 'No employees match your filters.' : 'No employees found.' }}</p>
                    </div>
                </template>

                <Column selection-mode="multiple" header-style="width: 3rem" :exportable="false" />
                <Column expander style="width: 3rem" class="!pr-0 md:hidden" />

                <Column field="name" header="Employee">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3">
                            <div v-if="data.profile_picture_url" @click.stop>
                                <Image
                                    :src="data.profile_picture_url"
                                    :alt="data.name"
                                    image-class="!h-8 !w-8 rounded-full object-cover cursor-pointer"
                                    :pt="{ root: { class: 'rounded-full overflow-hidden shrink-0' }, previewMask: { class: 'rounded-full' } }"
                                    preview
                                />
                            </div>
                            <Avatar
                                v-else
                                :label="getInitials(data.name)"
                                shape="circle"
                                class="!h-8 !w-8 shrink-0 bg-primary/10 text-primary"
                            />
                            <div class="flex flex-col gap-0.5">
                                <span class="cursor-pointer font-medium hover:text-primary">{{ data.name }}</span>
                                <span v-if="data.employee_number" class="text-xs text-muted-foreground">
                                    {{ data.employee_number }}
                                </span>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="company" header="Company" class="hidden md:table-cell">
                    <template #body="{ data }">
                        <span v-if="data.company">{{ data.company }}</span>
                        <span v-else class="text-muted-foreground">-</span>
                    </template>
                </Column>

                <Column field="designation" header="Designation" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        <span v-if="data.designation">{{ data.designation }}</span>
                        <span v-else class="text-muted-foreground">-</span>
                    </template>
                </Column>

                <Column field="tier" header="Tier" style="width: 5rem">
                    <template #body="{ data }">
                        <Tag :value="`Tier ${data.tier}`" :severity="getTierColor(data.tier)" class="!text-xs" />
                    </template>
                </Column>

                <Column field="managers" header="Manager(s)">
                    <template #body="{ data }">
                        <div v-if="data.managers?.length" class="flex flex-wrap gap-1">
                            <Tag
                                v-for="manager in data.managers.slice(0, 2)"
                                :key="manager.id"
                                :value="manager.name"
                                severity="secondary"
                                class="!text-xs"
                            />
                            <Tag
                                v-if="data.managers.length > 2"
                                :value="`+${data.managers.length - 2}`"
                                severity="info"
                                class="!text-xs"
                                v-tooltip.top="data.managers.slice(2).map((m: ManagerInfo) => m.name).join(', ')"
                            />
                        </div>
                        <span v-else class="text-sm text-muted-foreground">No manager</span>
                    </template>
                </Column>

                <Column header="" style="width: 4rem" :exportable="false" class="hidden md:table-cell">
                    <template #body="{ data }">
                        <Button
                            icon="pi pi-pencil"
                            severity="secondary"
                            text
                            rounded
                            size="small"
                            @click.stop="openEditDialog(data)"
                            v-tooltip.top="'Edit Managers'"
                        />
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm md:hidden">
                        <div class="flex justify-between gap-4 border-b border-border pb-2">
                            <span class="shrink-0 text-muted-foreground">Company</span>
                            <span class="text-right">{{ data.company ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between gap-4 border-b border-border pb-2 lg:hidden">
                            <span class="shrink-0 text-muted-foreground">Designation</span>
                            <span class="text-right">{{ data.designation ?? '-' }}</span>
                        </div>
                        <div class="flex justify-end gap-1 pt-1">
                            <Button
                                icon="pi pi-pencil"
                                label="Edit Managers"
                                severity="secondary"
                                text
                                size="small"
                                @click="openEditDialog(data)"
                            />
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Edit Manager Dialog -->
        <Dialog
            v-model:visible="editDialogVisible"
            :header="`Edit Managers: ${editingEmployee?.name || ''}`"
            :modal="true"
            :closable="!saving"
            class="w-full max-w-lg"
        >
            <div class="flex flex-col gap-4">
                <!-- Employee Info (Read Only) -->
                <div class="flex items-center gap-3 rounded-lg border border-border bg-muted p-3 dark:bg-surface-800">
                    <Image
                        v-if="editingEmployee?.profile_picture_url"
                        :src="editingEmployee.profile_picture_url"
                        :alt="editingEmployee.name"
                        image-class="!h-12 !w-12 rounded-full object-cover cursor-pointer"
                        :pt="{ root: { class: 'rounded-full overflow-hidden shrink-0' }, previewMask: { class: 'rounded-full' } }"
                        preview
                    />
                    <Avatar
                        v-else-if="editingEmployee"
                        :label="getInitials(editingEmployee.name)"
                        shape="circle"
                        size="large"
                        class="shrink-0 bg-primary/10 text-primary"
                    />
                    <div class="flex flex-col gap-0.5">
                        <span class="font-semibold">{{ editingEmployee?.name }}</span>
                        <span v-if="editingEmployee?.designation" class="text-sm text-muted-foreground">
                            {{ editingEmployee.designation }}
                        </span>
                        <span v-if="editingEmployee?.company" class="text-xs text-muted-foreground">
                            {{ editingEmployee.company }}
                        </span>
                    </div>
                    <Tag
                        v-if="editingEmployee"
                        :value="`Tier ${editingEmployee.tier}`"
                        :severity="getTierColor(editingEmployee.tier)"
                        class="ml-auto !text-xs"
                    />
                </div>

                <!-- Current Managers -->
                <div class="flex flex-col gap-2">
                    <label class="font-medium">Current Managers</label>
                    <div v-if="loadingManagers" class="flex items-center justify-center py-4">
                        <i class="pi pi-spin pi-spinner text-xl"></i>
                    </div>
                    <div v-else-if="employeeManagers.length > 0" class="flex flex-col gap-2">
                        <div
                            v-for="manager in employeeManagers"
                            :key="manager.id"
                            class="flex items-center justify-between rounded-lg border border-border bg-muted px-3 py-2 dark:bg-surface-800"
                        >
                            <div class="flex items-center gap-2">
                                <Image
                                    v-if="manager.profile_picture_url"
                                    :src="manager.profile_picture_url"
                                    :alt="manager.name"
                                    image-class="!h-8 !w-8 rounded-full object-cover cursor-pointer"
                                    :pt="{ root: { class: 'rounded-full overflow-hidden shrink-0' }, previewMask: { class: 'rounded-full' } }"
                                    preview
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials(manager.name)"
                                    shape="circle"
                                    class="!h-8 !w-8 shrink-0 bg-primary/10 text-primary"
                                />
                                <div class="flex flex-col gap-0.5">
                                    <span class="font-medium">{{ manager.name }}</span>
                                    <span v-if="manager.employee_number" class="text-xs text-muted-foreground">
                                        {{ manager.employee_number }}
                                    </span>
                                </div>
                            </div>
                            <Button
                                icon="pi pi-times"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="removeManager(manager)"
                                :loading="saving"
                                v-tooltip.top="'Remove Manager'"
                            />
                        </div>
                    </div>
                    <div v-else class="rounded-lg border border-dashed border-border py-4 text-center text-sm text-muted-foreground">
                        No managers assigned
                    </div>
                </div>

                <!-- Add Manager -->
                <div class="flex flex-col gap-2">
                    <label class="font-medium">Add Manager</label>
                    <div class="flex items-center gap-2">
                        <Select
                            v-model="selectedManager"
                            :options="availableManagers"
                            option-label="label"
                            option-value="value"
                            placeholder="Select a manager..."
                            filter
                            size="small"
                            class="flex-1"
                            :disabled="loadingManagers || availableManagers.length === 0"
                        />
                        <Button
                            icon="pi pi-plus"
                            label="Add"
                            size="small"
                            @click="addManager"
                            :disabled="!selectedManager || saving"
                            :loading="saving"
                        />
                    </div>
                    <small v-if="!loadingManagers && availableManagers.length === 0" class="text-muted-foreground">
                        No available managers (all potential managers would create a circular reference)
                    </small>
                </div>
            </div>

            <template #footer>
                <Button label="Close" severity="secondary" size="small" @click="editDialogVisible = false" :disabled="saving" />
            </template>
        </Dialog>

        <!-- Bulk Assign Dialog -->
        <Dialog
            v-model:visible="bulkDialogVisible"
            header="Bulk Assign Manager"
            :modal="true"
            :closable="!bulkSaving"
            class="w-full max-w-md"
        >
            <div class="flex flex-col gap-4">
                <p class="text-sm text-muted-foreground">
                    Assign a manager to <strong>{{ selectedEmployees.length }}</strong> selected employee(s).
                </p>

                <!-- Selected Employees Preview -->
                <div class="max-h-32 overflow-y-auto rounded-lg border border-border bg-muted p-2 dark:bg-surface-800">
                    <div class="flex flex-wrap gap-1">
                        <Tag
                            v-for="emp in selectedEmployees.slice(0, 10)"
                            :key="emp.id"
                            :value="emp.name"
                            severity="secondary"
                            class="!text-xs"
                        />
                        <Tag
                            v-if="selectedEmployees.length > 10"
                            :value="`+${selectedEmployees.length - 10} more`"
                            severity="info"
                            class="!text-xs"
                        />
                    </div>
                </div>

                <!-- Manager Selection -->
                <div class="flex flex-col gap-2">
                    <label for="bulk_manager" class="font-medium">Select Manager *</label>
                    <Select
                        id="bulk_manager"
                        v-model="bulkManager"
                        :options="bulkAvailableManagers"
                        option-label="label"
                        option-value="value"
                        placeholder="Select a manager..."
                        filter
                        size="small"
                        fluid
                        :disabled="bulkSaving"
                    />
                </div>

                <!-- Results Summary (if any) -->
                <div v-if="bulkResult" class="flex flex-col gap-2">
                    <div v-if="bulkResult.success.length > 0" class="rounded-lg border border-green-500/50 bg-green-50 p-3 dark:bg-green-900/20">
                        <div class="flex items-center gap-2 text-green-700 dark:text-green-400">
                            <i class="pi pi-check-circle"></i>
                            <span class="font-medium">{{ bulkResult.success.length }} assignment(s) successful</span>
                        </div>
                    </div>
                    <div v-if="bulkResult.failed.length > 0" class="rounded-lg border border-red-500/50 bg-red-50 p-3 dark:bg-red-900/20">
                        <div class="mb-2 flex items-center gap-2 text-red-700 dark:text-red-400">
                            <i class="pi pi-exclamation-circle"></i>
                            <span class="font-medium">{{ bulkResult.failed.length }} assignment(s) failed</span>
                        </div>
                        <ul class="ml-6 list-disc text-sm text-red-600 dark:text-red-400">
                            <li v-for="failure in bulkResult.failed" :key="failure.id">
                                <strong>{{ failure.name }}:</strong> {{ failure.reason }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button
                        :label="bulkResult ? 'Close' : 'Cancel'"
                        severity="secondary"
                        size="small"
                        @click="closeBulkDialog"
                        :disabled="bulkSaving"
                    />
                    <Button
                        v-if="!bulkResult"
                        label="Assign Manager"
                        size="small"
                        @click="bulkAssign"
                        :disabled="!bulkManager || bulkSaving"
                        :loading="bulkSaving"
                    />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
