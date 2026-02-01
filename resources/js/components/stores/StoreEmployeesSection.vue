<script setup lang="ts">
import { type StorePermissionGroup } from '@/types';
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
    permissions_with_labels: Array<{
        key: string;
        label: string;
        group: string;
    }>;
    access_permissions: string[];
    access_permissions_with_labels: Array<{
        key: string;
        label: string;
        group: string;
    }>;
    is_creator: boolean;
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
const availableAccessPermissions = ref<StorePermissionGroup>({});

const form = reactive({
    employee_id: null as number | null,
    active: true,
    permissions: [] as string[],
    access_permissions: [] as string[],
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
    const assignedEmployeeIds = storeEmployees.value.map(
        (se) => se.employee_id,
    );
    return employeeOptions.value.filter(
        (e) => !assignedEmployeeIds.includes(e.value),
    );
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
        availableAccessPermissions.value =
            data.available_access_permissions || {};
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
    form.access_permissions = [];
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
    form.access_permissions = [...(se.access_permissions || [])];
    dialogVisible.value = true;
}

function saveAssignment() {
    saving.value = true;
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);

    const data = {
        employee_id: form.employee_id,
        active: form.active,
        permissions: form.permissions,
        access_permissions: form.access_permissions,
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
    const groupPermissions =
        availablePermissions.value[group]?.map((p) => p.key) ?? [];
    const allSelected = groupPermissions.every((p) =>
        form.permissions.includes(p),
    );

    if (allSelected) {
        // Remove all permissions in this group
        form.permissions = form.permissions.filter(
            (p) => !groupPermissions.includes(p),
        );
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
    const groupPermissions =
        availablePermissions.value[group]?.map((p) => p.key) ?? [];
    return (
        groupPermissions.length > 0 &&
        groupPermissions.every((p) => form.permissions.includes(p))
    );
}

function isGroupPartiallySelected(group: string): boolean {
    const groupPermissions =
        availablePermissions.value[group]?.map((p) => p.key) ?? [];
    const selectedCount = groupPermissions.filter((p) =>
        form.permissions.includes(p),
    ).length;
    return selectedCount > 0 && selectedCount < groupPermissions.length;
}

function getEmployeeName(employeeId: number): string {
    const emp = props.employees.find((e) => e.id === employeeId);
    return emp ? `${emp.first_name} ${emp.last_name}` : '-';
}

function getEmployeeNumber(employeeId: number): string | null {
    return (
        props.employees.find((e) => e.id === employeeId)?.employee_number ??
        null
    );
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

// Access permission functions
function toggleAccessPermission(permissionKey: string) {
    const index = form.access_permissions.indexOf(permissionKey);
    if (index === -1) {
        form.access_permissions.push(permissionKey);
    } else {
        form.access_permissions.splice(index, 1);
    }
}

function toggleAccessGroupPermissions(group: string) {
    const groupPermissions =
        availableAccessPermissions.value[group]?.map((p) => p.key) ?? [];
    const allSelected = groupPermissions.every((p) =>
        form.access_permissions.includes(p),
    );

    if (allSelected) {
        form.access_permissions = form.access_permissions.filter(
            (p) => !groupPermissions.includes(p),
        );
    } else {
        groupPermissions.forEach((p) => {
            if (!form.access_permissions.includes(p)) {
                form.access_permissions.push(p);
            }
        });
    }
}

function isAccessGroupFullySelected(group: string): boolean {
    const groupPermissions =
        availableAccessPermissions.value[group]?.map((p) => p.key) ?? [];
    return (
        groupPermissions.length > 0 &&
        groupPermissions.every((p) => form.access_permissions.includes(p))
    );
}

function isAccessGroupPartiallySelected(group: string): boolean {
    const groupPermissions =
        availableAccessPermissions.value[group]?.map((p) => p.key) ?? [];
    const selectedCount = groupPermissions.filter((p) =>
        form.access_permissions.includes(p),
    ).length;
    return selectedCount > 0 && selectedCount < groupPermissions.length;
}

const expandedRows = ref({});
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
            v-model:expandedRows="expandedRows"
            :value="storeEmployees"
            dataKey="id"
            striped-rows
            size="small"
            :loading="loading"
            class="overflow-hidden rounded-xl border border-border dark:border-border"
        >
            <template #empty>
                <div class="p-4 text-center text-muted-foreground">
                    No employees assigned to this store. Click "Add Employee" to
                    assign an employee.
                </div>
            </template>
            <Column expander style="width: 3rem" class="!pr-0 md:hidden" />
            <Column field="employee_id" header="Employee">
                <template #body="{ data }">
                    <div class="flex items-center gap-3">
                        <Image
                            v-if="getProfilePictureUrl(data)"
                            :src="getProfilePictureUrl(data) ?? undefined"
                            :alt="getEmployeeName(data.employee_id)"
                            image-class="!h-8 !w-8 rounded-full object-cover cursor-pointer"
                            :pt="{
                                root: {
                                    class: 'rounded-full overflow-hidden shrink-0',
                                },
                                previewMask: { class: 'rounded-full' },
                            }"
                            preview
                        />
                        <Avatar
                            v-else
                            :label="getInitials(data.employee_id)"
                            shape="circle"
                            class="!h-8 !w-8 shrink-0 bg-primary/10 text-primary"
                        />
                        <div class="flex flex-col gap-0.5">
                            <span class="font-medium">{{
                                getEmployeeName(data.employee_id)
                            }}</span>
                            <div class="flex flex-wrap items-center gap-1">
                                <span
                                    v-if="getEmployeeNumber(data.employee_id)"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ getEmployeeNumber(data.employee_id) }}
                                </span>
                                <Tag
                                    v-if="data.is_creator"
                                    value="Creator"
                                    severity="info"
                                    class="!text-xs"
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </Column>
            <Column
                field="permissions"
                header="Permissions"
                class="hidden md:table-cell"
            >
                <template #body="{ data }">
                    <div class="flex flex-wrap gap-1">
                        <!-- Store Access permissions (primary color) -->
                        <Tag
                            v-for="perm in (
                                data.access_permissions_with_labels || []
                            ).slice(0, 2)"
                            :key="`access-${perm.key}`"
                            :value="perm.label"
                            severity="info"
                            class="!text-xs"
                        />
                        <!-- Store Operations permissions (secondary color) -->
                        <Tag
                            v-for="perm in (
                                data.permissions_with_labels || []
                            ).slice(0, 2)"
                            :key="`ops-${perm.key}`"
                            :value="perm.label"
                            severity="secondary"
                            class="!text-xs"
                        />
                        <!-- Overflow indicator -->
                        <Tag
                            v-if="
                                (data.access_permissions_with_labels || [])
                                    .length +
                                    (data.permissions_with_labels || [])
                                        .length >
                                4
                            "
                            :value="`+${(data.access_permissions_with_labels || []).length + (data.permissions_with_labels || []).length - 4}`"
                            severity="contrast"
                            class="!text-xs"
                            v-tooltip.top="
                                [
                                    ...(
                                        data.access_permissions_with_labels ||
                                        []
                                    ).slice(2),
                                    ...(
                                        data.permissions_with_labels || []
                                    ).slice(2),
                                ]
                                    .map((p: any) => p.label)
                                    .join(', ')
                            "
                        />
                        <span
                            v-if="
                                !(data.access_permissions_with_labels || [])
                                    .length &&
                                !(data.permissions_with_labels || []).length
                            "
                            class="text-sm text-muted-foreground"
                        >
                            No permissions
                        </span>
                    </div>
                </template>
            </Column>
            <Column header="Status">
                <template #body="{ data }">
                    <Tag
                        :value="data.active ? 'Active' : 'Inactive'"
                        :severity="data.active ? 'success' : 'secondary'"
                    />
                </template>
            </Column>
            <Column header="" class="hidden w-32 !pr-4 md:table-cell">
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
                            v-if="data.active && !data.is_creator"
                            icon="pi pi-ban"
                            severity="warn"
                            text
                            rounded
                            size="small"
                            @click="confirmDeactivateAssignment(data)"
                            v-tooltip.top="'Deactivate'"
                        />
                        <Button
                            v-if="!data.is_creator"
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
                <div class="grid gap-3 p-3 text-sm md:hidden">
                    <!-- Store Access Permissions -->
                    <div class="flex flex-col gap-2">
                        <span class="shrink-0 text-muted-foreground"
                            >Store Access</span
                        >
                        <div class="flex flex-wrap gap-1">
                            <Tag
                                v-for="perm in data.access_permissions_with_labels ||
                                []"
                                :key="`access-${perm.key}`"
                                :value="perm.label"
                                severity="info"
                                class="!text-xs"
                            />
                            <span
                                v-if="
                                    !(data.access_permissions_with_labels || [])
                                        .length
                                "
                                class="text-muted-foreground"
                            >
                                None
                            </span>
                        </div>
                    </div>
                    <!-- Store Operations Permissions -->
                    <div class="flex flex-col gap-2">
                        <span class="shrink-0 text-muted-foreground"
                            >Store Operations</span
                        >
                        <div class="flex flex-wrap gap-1">
                            <Tag
                                v-for="perm in data.permissions_with_labels ||
                                []"
                                :key="`ops-${perm.key}`"
                                :value="perm.label"
                                severity="secondary"
                                class="!text-xs"
                            />
                            <span
                                v-if="
                                    !(data.permissions_with_labels || []).length
                                "
                                class="text-muted-foreground"
                            >
                                None
                            </span>
                        </div>
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
                            v-if="data.active && !data.is_creator"
                            icon="pi pi-ban"
                            label="Deactivate"
                            severity="warn"
                            text
                            size="small"
                            @click="confirmDeactivateAssignment(data)"
                        />
                        <Button
                            v-if="!data.is_creator"
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
            :header="editingId ? 'Edit Employee Assignment' : 'Add Employee'"
            :modal="true"
            :closable="!saving"
            class="w-full max-w-lg"
        >
            <form @submit.prevent="saveAssignment" class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="se_employee_id" class="font-medium"
                        >Employee *</label
                    >
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
                        class="bg-surface-50 dark:bg-surface-800 flex items-center gap-2 rounded border border-border px-3 py-2"
                    >
                        <span class="font-medium">{{
                            getEmployeeName(form.employee_id!)
                        }}</span>
                        <span
                            v-if="getEmployeeNumber(form.employee_id!)"
                            class="text-xs text-muted-foreground"
                        >
                            ({{ getEmployeeNumber(form.employee_id!) }})
                        </span>
                    </div>
                    <small v-if="formErrors.employee_id" class="text-red-500">
                        {{ formErrors.employee_id }}
                    </small>
                </div>

                <div class="flex items-center gap-3">
                    <ToggleSwitch v-model="form.active" />
                    <span
                        :class="form.active ? 'text-green-600' : 'text-red-600'"
                    >
                        {{ form.active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <!-- Store Access Permissions -->
                <div
                    v-if="Object.keys(availableAccessPermissions).length > 0"
                    class="flex flex-col gap-3"
                >
                    <label class="font-medium">Store Access</label>
                    <p class="-mt-2 text-sm text-muted-foreground">
                        Controls what the employee can do with store settings
                        and management.
                    </p>
                    <div
                        v-for="(
                            permissions, group
                        ) in availableAccessPermissions"
                        :key="`access-${group}`"
                        class="rounded border border-border p-3"
                    >
                        <div class="mb-2 flex items-center gap-2">
                            <Checkbox
                                :model-value="
                                    isAccessGroupFullySelected(group as string)
                                "
                                :indeterminate="
                                    isAccessGroupPartiallySelected(
                                        group as string,
                                    )
                                "
                                binary
                                @change="
                                    toggleAccessGroupPermissions(
                                        group as string,
                                    )
                                "
                            />
                            <span class="text-sm font-medium">{{ group }}</span>
                        </div>
                        <div class="ml-6 grid gap-2 sm:grid-cols-2">
                            <div
                                v-for="perm in permissions"
                                :key="perm.key"
                                class="flex items-center gap-2"
                            >
                                <Checkbox
                                    :model-value="
                                        form.access_permissions.includes(
                                            perm.key,
                                        )
                                    "
                                    binary
                                    @change="toggleAccessPermission(perm.key)"
                                />
                                <span class="text-sm">{{ perm.label }}</span>
                            </div>
                        </div>
                    </div>
                    <small
                        v-if="formErrors.access_permissions"
                        class="text-red-500"
                    >
                        {{ formErrors.access_permissions }}
                    </small>
                </div>

                <!-- Store Operations Permissions -->
                <div class="flex flex-col gap-3">
                    <label class="font-medium">Store Operations</label>
                    <p class="-mt-2 text-sm text-muted-foreground">
                        Controls what the employee can do within the store
                        (sales, inventory, etc.).
                    </p>
                    <div
                        v-for="(permissions, group) in availablePermissions"
                        :key="group"
                        class="rounded border border-border p-3"
                    >
                        <div class="mb-2 flex items-center gap-2">
                            <Checkbox
                                :model-value="
                                    isGroupFullySelected(group as string)
                                "
                                :indeterminate="
                                    isGroupPartiallySelected(group as string)
                                "
                                binary
                                @change="
                                    toggleGroupPermissions(group as string)
                                "
                            />
                            <span class="text-sm font-medium">{{ group }}</span>
                        </div>
                        <div class="ml-6 grid gap-2 sm:grid-cols-2">
                            <div
                                v-for="perm in permissions"
                                :key="perm.key"
                                class="flex items-center gap-2"
                            >
                                <Checkbox
                                    :model-value="
                                        form.permissions.includes(perm.key)
                                    "
                                    binary
                                    @change="togglePermission(perm.key)"
                                />
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
