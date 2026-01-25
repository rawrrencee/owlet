<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Image from 'primevue/image';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import { useConfirm } from 'primevue/useconfirm';
import { computed, onMounted, reactive, ref } from 'vue';
import { type StorePermissionGroup } from '@/types';

interface EmployeeOption {
    id: number;
    first_name: string;
    last_name: string;
    employee_number: string | null;
    profile_picture_url?: string | null;
}

interface StoreEmployee {
    id: number;
    employee_id: number;
    store_id: number;
    active: boolean;
    permissions: string[];
    permissions_with_labels: Array<{ key: string; label: string; group: string }>;
    employee?: {
        id: number;
        first_name: string;
        last_name: string;
        full_name: string;
        employee_number: string | null;
        profile_picture_url?: string | null;
    };
}

interface Props {
    storeId: number;
    employees: EmployeeOption[];
}

const props = defineProps<Props>();

const dialogVisible = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);
const loading = ref(true);

const storeEmployees = ref<StoreEmployee[]>([]);
const availablePermissions = ref<StorePermissionGroup>({});

const form = reactive({
    employee_id: null as number | null,
    active: true,
    permissions: [] as string[],
});

const formErrors = reactive<Record<string, string>>({});

const confirm = useConfirm();

const employeeOptions = computed(() =>
    props.employees.map((e) => ({
        label: `${e.first_name} ${e.last_name}${e.employee_number ? ` (${e.employee_number})` : ''}`,
        value: e.id,
    })),
);

// Filter out employees that are already assigned when adding new
const availableEmployeeOptions = computed(() => {
    const assignedEmployeeIds = storeEmployees.value.map((se) => se.employee_id);
    return employeeOptions.value.filter((e) => !assignedEmployeeIds.includes(e.value));
});

async function fetchStoreEmployees() {
    loading.value = true;
    try {
        const response = await fetch(`/stores/${props.storeId}/employees`, {
            headers: {
                Accept: 'application/json',
            },
        });
        const data = await response.json();
        storeEmployees.value = data.data;
        availablePermissions.value = data.available_permissions;
    } catch (error) {
        console.error('Failed to fetch store employees:', error);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    fetchStoreEmployees();
});

function resetForm() {
    form.employee_id = null;
    form.active = true;
    form.permissions = [];
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);
}

function openAddDialog() {
    resetForm();
    editingId.value = null;
    dialogVisible.value = true;
}

function openEditDialog(se: StoreEmployee) {
    resetForm();
    editingId.value = se.id;
    form.employee_id = se.employee_id;
    form.active = se.active;
    form.permissions = [...(se.permissions || [])];
    dialogVisible.value = true;
}

function saveAssignment() {
    saving.value = true;
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);

    const data = {
        employee_id: form.employee_id,
        active: form.active,
        permissions: form.permissions,
    };

    const url = editingId.value
        ? `/stores/${props.storeId}/employees/${editingId.value}`
        : `/stores/${props.storeId}/employees`;

    const method = editingId.value ? 'put' : 'post';

    router[method](url, data, {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            fetchStoreEmployees();
        },
        onError: (errors) => {
            Object.assign(formErrors, errors);
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}

function confirmDeactivateAssignment(se: StoreEmployee) {
    const employeeName = getEmployeeName(se.employee_id);
    confirm.require({
        message: `Are you sure you want to deactivate the assignment for "${employeeName}"?`,
        header: 'Deactivate Assignment',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Deactivate',
        acceptProps: {
            severity: 'warn',
            size: 'small',
        },
        accept: () => {
            router.put(
                `/stores/${props.storeId}/employees/${se.id}`,
                { active: false },
                {
                    preserveScroll: true,
                    onSuccess: () => fetchStoreEmployees(),
                },
            );
        },
    });
}

function confirmRemoveAssignment(se: StoreEmployee) {
    const employeeName = getEmployeeName(se.employee_id);
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
            router.delete(`/stores/${props.storeId}/employees/${se.id}`, {
                preserveScroll: true,
                onSuccess: () => fetchStoreEmployees(),
            });
        },
    });
}

function togglePermission(permissionKey: string) {
    const index = form.permissions.indexOf(permissionKey);
    if (index === -1) {
        form.permissions.push(permissionKey);
    } else {
        form.permissions.splice(index, 1);
    }
}

function toggleGroupPermissions(group: string) {
    const groupPermissions = availablePermissions.value[group]?.map((p) => p.key) ?? [];
    const allSelected = groupPermissions.every((p) => form.permissions.includes(p));

    if (allSelected) {
        // Remove all permissions in this group
        form.permissions = form.permissions.filter((p) => !groupPermissions.includes(p));
    } else {
        // Add all permissions in this group
        groupPermissions.forEach((p) => {
            if (!form.permissions.includes(p)) {
                form.permissions.push(p);
            }
        });
    }
}

function isGroupFullySelected(group: string): boolean {
    const groupPermissions = availablePermissions.value[group]?.map((p) => p.key) ?? [];
    return groupPermissions.length > 0 && groupPermissions.every((p) => form.permissions.includes(p));
}

function isGroupPartiallySelected(group: string): boolean {
    const groupPermissions = availablePermissions.value[group]?.map((p) => p.key) ?? [];
    const selectedCount = groupPermissions.filter((p) => form.permissions.includes(p)).length;
    return selectedCount > 0 && selectedCount < groupPermissions.length;
}

function getEmployeeName(employeeId: number): string {
    const emp = props.employees.find((e) => e.id === employeeId);
    return emp ? `${emp.first_name} ${emp.last_name}` : '-';
}

function getEmployeeNumber(employeeId: number): string | null {
    return props.employees.find((e) => e.id === employeeId)?.employee_number ?? null;
}

function getProfilePictureUrl(se: StoreEmployee): string | null {
    return se.employee?.profile_picture_url ?? null;
}

function getInitials(employeeId: number): string {
    const emp = props.employees.find((e) => e.id === employeeId);
    if (!emp) return '';
    const first = emp.first_name?.charAt(0)?.toUpperCase() ?? '';
    const last = emp.last_name?.charAt(0)?.toUpperCase() ?? '';
    return `${first}${last}`;
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">Assigned Employees</h3>
            <Button
                label="Add Employee"
                icon="pi pi-plus"
                size="small"
                @click="openAddDialog"
                :disabled="availableEmployeeOptions.length === 0"
            />
        </div>

        <DataTable
            :value="storeEmployees"
            dataKey="id"
            striped-rows
            size="small"
            :loading="loading"
            class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <template #empty>
                <div class="p-4 text-center text-muted-foreground">
                    No employees assigned to this store. Click "Add Employee" to assign an employee.
                </div>
            </template>
            <Column field="employee_id" header="Employee">
                <template #body="{ data }">
                    <div class="flex items-center gap-3">
                        <Image
                            v-if="getProfilePictureUrl(data)"
                            :src="getProfilePictureUrl(data)"
                            :alt="getEmployeeName(data.employee_id)"
                            image-class="!h-8 !w-8 rounded-full object-cover cursor-pointer"
                            :pt="{ root: { class: 'rounded-full overflow-hidden shrink-0' }, previewMask: { class: 'rounded-full' } }"
                            preview
                        />
                        <Avatar
                            v-else
                            :label="getInitials(data.employee_id)"
                            shape="circle"
                            class="!h-8 !w-8 shrink-0 bg-primary/10 text-primary"
                        />
                        <div class="flex flex-col gap-0.5">
                            <span class="font-medium">{{ getEmployeeName(data.employee_id) }}</span>
                            <span v-if="getEmployeeNumber(data.employee_id)" class="text-xs text-muted-foreground">
                                {{ getEmployeeNumber(data.employee_id) }}
                            </span>
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="permissions" header="Permissions" class="hidden md:table-cell">
                <template #body="{ data }">
                    <div class="flex flex-wrap gap-1">
                        <Tag
                            v-for="perm in (data.permissions_with_labels || []).slice(0, 3)"
                            :key="perm.key"
                            :value="perm.label"
                            severity="secondary"
                            class="!text-xs"
                        />
                        <Tag
                            v-if="(data.permissions_with_labels || []).length > 3"
                            :value="`+${(data.permissions_with_labels || []).length - 3}`"
                            severity="info"
                            class="!text-xs"
                            v-tooltip.top="(data.permissions_with_labels || []).slice(3).map((p: any) => p.label).join(', ')"
                        />
                        <span v-if="!(data.permissions_with_labels || []).length" class="text-sm text-muted-foreground">
                            No permissions
                        </span>
                    </div>
                </template>
            </Column>
            <Column header="Status">
                <template #body="{ data }">
                    <Tag :value="data.active ? 'Active' : 'Inactive'" :severity="data.active ? 'success' : 'secondary'" />
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
                            v-if="data.active"
                            icon="pi pi-ban"
                            severity="warn"
                            text
                            rounded
                            size="small"
                            @click="confirmDeactivateAssignment(data)"
                            v-tooltip.top="'Deactivate'"
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
        </DataTable>

        <Dialog
            v-model:visible="dialogVisible"
            :header="editingId ? 'Edit Employee Assignment' : 'Add Employee'"
            :modal="true"
            :closable="!saving"
            class="w-full max-w-lg"
        >
            <form @submit.prevent="saveAssignment" class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="se_employee_id" class="font-medium">Employee *</label>
                    <Select
                        v-if="!editingId"
                        id="se_employee_id"
                        v-model="form.employee_id"
                        :options="availableEmployeeOptions"
                        option-label="label"
                        option-value="value"
                        :invalid="!!formErrors.employee_id"
                        placeholder="Select employee"
                        filter
                        size="small"
                        fluid
                    />
                    <div
                        v-else
                        class="flex items-center gap-2 rounded border border-sidebar-border/50 bg-surface-50 px-3 py-2 dark:bg-surface-800"
                    >
                        <span class="font-medium">{{ getEmployeeName(form.employee_id!) }}</span>
                        <span v-if="getEmployeeNumber(form.employee_id!)" class="text-xs text-muted-foreground">
                            ({{ getEmployeeNumber(form.employee_id!) }})
                        </span>
                    </div>
                    <small v-if="formErrors.employee_id" class="text-red-500">
                        {{ formErrors.employee_id }}
                    </small>
                </div>

                <div class="flex items-center gap-3">
                    <ToggleSwitch v-model="form.active" />
                    <span :class="form.active ? 'text-green-600' : 'text-red-600'">
                        {{ form.active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <!-- Permissions -->
                <div class="flex flex-col gap-3">
                    <label class="font-medium">Permissions</label>
                    <div
                        v-for="(permissions, group) in availablePermissions"
                        :key="group"
                        class="rounded border border-sidebar-border/50 p-3"
                    >
                        <div class="mb-2 flex items-center gap-2">
                            <Checkbox
                                :model-value="isGroupFullySelected(group as string)"
                                :indeterminate="isGroupPartiallySelected(group as string)"
                                binary
                                @change="toggleGroupPermissions(group as string)"
                            />
                            <span class="text-sm font-medium">{{ group }}</span>
                        </div>
                        <div class="ml-6 grid gap-2 sm:grid-cols-2">
                            <div v-for="perm in permissions" :key="perm.key" class="flex items-center gap-2">
                                <Checkbox :model-value="form.permissions.includes(perm.key)" binary @change="togglePermission(perm.key)" />
                                <span class="text-sm">{{ perm.label }}</span>
                            </div>
                        </div>
                    </div>
                    <small v-if="formErrors.permissions" class="text-red-500">
                        {{ formErrors.permissions }}
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
                    <Button type="submit" :label="editingId ? 'Save Changes' : 'Add Employee'" size="small" :loading="saving" />
                </div>
            </form>
        </Dialog>
    </div>
</template>
