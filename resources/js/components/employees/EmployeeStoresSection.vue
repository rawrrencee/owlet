<script setup lang="ts">
import {
    type EmployeeStore,
    type Store,
    type StorePermissionGroup,
} from '@/types';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import { useConfirm } from 'primevue/useconfirm';
import { computed, onMounted, reactive, ref } from 'vue';

interface Props {
    employeeId: number;
    stores: Store[];
}

const props = defineProps<Props>();
const expandedRows = ref({});

const dialogVisible = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);
const loading = ref(true);

const employeeStores = ref<EmployeeStore[]>([]);
const availablePermissions = ref<StorePermissionGroup>({});
const availableAccessPermissions = ref<StorePermissionGroup>({});

const form = reactive({
    store_id: null as number | null,
    active: true,
    permissions: [] as string[],
    access_permissions: [] as string[],
});

const formErrors = reactive<Record<string, string>>({});

const confirm = useConfirm();

const storeOptions = computed(() =>
    props.stores.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: s.id,
    })),
);

// Filter out stores that are already assigned when adding new
const availableStoreOptions = computed(() => {
    const assignedStoreIds = employeeStores.value.map((es) => es.store_id);
    return storeOptions.value.filter(
        (s) => !assignedStoreIds.includes(s.value),
    );
});

async function fetchEmployeeStores() {
    loading.value = true;
    try {
        const response = await fetch(`/users/${props.employeeId}/stores`, {
            headers: {
                Accept: 'application/json',
            },
        });
        const data = await response.json();
        employeeStores.value = data.data;
        availablePermissions.value = data.available_permissions;
        availableAccessPermissions.value =
            data.available_access_permissions || {};
    } catch (error) {
        console.error('Failed to fetch employee stores:', error);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    fetchEmployeeStores();
});

function resetForm() {
    form.store_id = null;
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

function openEditDialog(es: EmployeeStore) {
    resetForm();
    editingId.value = es.id;
    form.store_id = es.store_id;
    form.active = es.active;
    form.permissions = [...(es.permissions || [])];
    form.access_permissions = [...(es.access_permissions || [])];
    dialogVisible.value = true;
}

function saveAssignment() {
    saving.value = true;
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);

    const data = {
        store_id: form.store_id,
        active: form.active,
        permissions: form.permissions,
        access_permissions: form.access_permissions,
    };

    const url = editingId.value
        ? `/users/${props.employeeId}/stores/${editingId.value}`
        : `/users/${props.employeeId}/stores`;

    const method = editingId.value ? 'put' : 'post';

    router[method](url, data, {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            fetchEmployeeStores();
        },
        onError: (errors) => {
            Object.assign(formErrors, errors);
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}

function confirmDeactivateAssignment(es: EmployeeStore) {
    const storeName =
        props.stores.find((s) => s.id === es.store_id)?.store_name ??
        'this store';
    confirm.require({
        message: `Are you sure you want to deactivate the assignment at "${storeName}"?`,
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
                `/users/${props.employeeId}/stores/${es.id}`,
                { active: false },
                {
                    preserveScroll: true,
                    onSuccess: () => fetchEmployeeStores(),
                },
            );
        },
    });
}

function confirmRemoveAssignment(es: EmployeeStore) {
    const storeName =
        props.stores.find((s) => s.id === es.store_id)?.store_name ??
        'this store';
    confirm.require({
        message: `Are you sure you want to remove the assignment at "${storeName}"? This action cannot be undone.`,
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
            router.delete(`/users/${props.employeeId}/stores/${es.id}`, {
                preserveScroll: true,
                onSuccess: () => fetchEmployeeStores(),
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

function getStoreName(storeId: number): string {
    return props.stores.find((s) => s.id === storeId)?.store_name ?? '-';
}

function getStoreCode(storeId: number): string {
    return props.stores.find((s) => s.id === storeId)?.store_code ?? '';
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
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">Store Assignments</h3>
            <Button
                label="Add Assignment"
                icon="pi pi-plus"
                size="small"
                @click="openAddDialog"
                :disabled="availableStoreOptions.length === 0"
            />
        </div>

        <DataTable
            v-model:expandedRows="expandedRows"
            :value="employeeStores"
            dataKey="id"
            striped-rows
            size="small"
            :loading="loading"
            class="overflow-hidden rounded-xl border border-border dark:border-border"
        >
            <template #empty>
                <div class="p-4 text-center text-muted-foreground">
                    No store assignments found. Click "Add Assignment" to assign
                    this employee to a store.
                </div>
            </template>
            <Column expander style="width: 3rem" class="!pr-0 md:hidden" />
            <Column field="store_id" header="Store">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{
                            getStoreName(data.store_id)
                        }}</span>
                        <Tag
                            :value="getStoreCode(data.store_id)"
                            severity="secondary"
                            class="hidden !text-xs sm:inline-flex"
                        />
                        <Tag
                            v-if="data.is_creator"
                            value="Creator"
                            severity="info"
                            class="!text-xs"
                        />
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
                        <Tag
                            v-for="perm in (
                                data.permissions_with_labels || []
                            ).slice(0, 3)"
                            :key="perm.key"
                            :value="perm.label"
                            severity="secondary"
                            class="!text-xs"
                        />
                        <Tag
                            v-if="
                                (data.permissions_with_labels || []).length > 3
                            "
                            :value="`+${(data.permissions_with_labels || []).length - 3}`"
                            severity="info"
                            class="!text-xs"
                            v-tooltip.top="
                                (data.permissions_with_labels || [])
                                    .slice(3)
                                    .map((p: any) => p.label)
                                    .join(', ')
                            "
                        />
                        <span
                            v-if="!(data.permissions_with_labels || []).length"
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
                <div class="grid gap-3 p-3 text-sm sm:hidden">
                    <div
                        class="flex justify-between gap-4 border-b border-border pb-2"
                    >
                        <span class="shrink-0 text-muted-foreground"
                            >Store Code</span
                        >
                        <span class="text-right">{{
                            getStoreCode(data.store_id)
                        }}</span>
                    </div>
                    <div
                        class="flex flex-col gap-2 border-b border-border pb-2"
                    >
                        <span class="shrink-0 text-muted-foreground"
                            >Permissions</span
                        >
                        <div class="flex flex-wrap gap-1">
                            <Tag
                                v-for="perm in data.permissions_with_labels ||
                                []"
                                :key="perm.key"
                                :value="perm.label"
                                severity="secondary"
                                class="!text-xs"
                            />
                            <span
                                v-if="
                                    !(data.permissions_with_labels || []).length
                                "
                                class="text-sm text-muted-foreground"
                            >
                                No permissions
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
            :header="
                editingId ? 'Edit Store Assignment' : 'Add Store Assignment'
            "
            :modal="true"
            :closable="!saving"
            class="w-full max-w-lg"
        >
            <form @submit.prevent="saveAssignment" class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="es_store_id" class="font-medium">Store *</label>
                    <Select
                        v-if="!editingId"
                        id="es_store_id"
                        v-model="form.store_id"
                        :options="availableStoreOptions"
                        option-label="label"
                        option-value="value"
                        :invalid="!!formErrors.store_id"
                        placeholder="Select store"
                        filter
                        size="small"
                        fluid
                    />
                    <div
                        v-else
                        class="bg-surface-50 dark:bg-surface-800 flex items-center gap-2 rounded border border-border px-3 py-2"
                    >
                        <span class="font-medium">{{
                            getStoreName(form.store_id!)
                        }}</span>
                        <Tag
                            :value="getStoreCode(form.store_id!)"
                            severity="secondary"
                            class="!text-xs"
                        />
                    </div>
                    <small v-if="formErrors.store_id" class="text-red-500">
                        {{ formErrors.store_id }}
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
                        Controls what the employee can do with the store
                        settings and management.
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
                        :label="editingId ? 'Save Changes' : 'Add Assignment'"
                        size="small"
                        :loading="saving"
                    />
                </div>
            </form>
        </Dialog>
    </div>
</template>
