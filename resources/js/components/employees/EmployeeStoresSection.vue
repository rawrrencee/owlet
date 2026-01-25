<script setup lang="ts">
import { type EmployeeStore, type Store, type StorePermissionGroup } from '@/types';
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

const dialogVisible = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);
const loading = ref(true);

const employeeStores = ref<EmployeeStore[]>([]);
const availablePermissions = ref<StorePermissionGroup>({});

const form = reactive({
    store_id: null as number | null,
    active: true,
    permissions: [] as string[],
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
    const assignedStoreIds = employeeStores.value.map(es => es.store_id);
    return storeOptions.value.filter(s => !assignedStoreIds.includes(s.value));
});

async function fetchEmployeeStores() {
    loading.value = true;
    try {
        const response = await fetch(`/users/${props.employeeId}/stores`, {
            headers: {
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        employeeStores.value = data.data;
        availablePermissions.value = data.available_permissions;
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
    dialogVisible.value = true;
}

function saveAssignment() {
    saving.value = true;
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);

    const data = {
        store_id: form.store_id,
        active: form.active,
        permissions: form.permissions,
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
    const storeName = props.stores.find(s => s.id === es.store_id)?.store_name ?? 'this store';
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
    const storeName = props.stores.find(s => s.id === es.store_id)?.store_name ?? 'this store';
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
    const groupPermissions = availablePermissions.value[group]?.map(p => p.key) ?? [];
    const allSelected = groupPermissions.every(p => form.permissions.includes(p));

    if (allSelected) {
        // Remove all permissions in this group
        form.permissions = form.permissions.filter(p => !groupPermissions.includes(p));
    } else {
        // Add all permissions in this group
        groupPermissions.forEach(p => {
            if (!form.permissions.includes(p)) {
                form.permissions.push(p);
            }
        });
    }
}

function isGroupFullySelected(group: string): boolean {
    const groupPermissions = availablePermissions.value[group]?.map(p => p.key) ?? [];
    return groupPermissions.length > 0 && groupPermissions.every(p => form.permissions.includes(p));
}

function isGroupPartiallySelected(group: string): boolean {
    const groupPermissions = availablePermissions.value[group]?.map(p => p.key) ?? [];
    const selectedCount = groupPermissions.filter(p => form.permissions.includes(p)).length;
    return selectedCount > 0 && selectedCount < groupPermissions.length;
}

function getStoreName(storeId: number): string {
    return props.stores.find(s => s.id === storeId)?.store_name ?? '-';
}

function getStoreCode(storeId: number): string {
    return props.stores.find(s => s.id === storeId)?.store_code ?? '';
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
            :value="employeeStores"
            dataKey="id"
            striped-rows
            size="small"
            :loading="loading"
            class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <template #empty>
                <div class="p-4 text-center text-muted-foreground">
                    No store assignments found. Click "Add Assignment" to assign this employee to a store.
                </div>
            </template>
            <Column field="store_id" header="Store">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{ getStoreName(data.store_id) }}</span>
                        <Tag :value="getStoreCode(data.store_id)" severity="secondary" class="!text-xs" />
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
                            v-tooltip.top="(data.permissions_with_labels || []).slice(3).map(p => p.label).join(', ')"
                        />
                        <span v-if="!(data.permissions_with_labels || []).length" class="text-muted-foreground text-sm">
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
            :header="editingId ? 'Edit Store Assignment' : 'Add Store Assignment'"
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
                    <div v-else class="flex items-center gap-2 rounded border border-sidebar-border/50 bg-surface-50 px-3 py-2 dark:bg-surface-800">
                        <span class="font-medium">{{ getStoreName(form.store_id!) }}</span>
                        <Tag :value="getStoreCode(form.store_id!)" severity="secondary" class="!text-xs" />
                    </div>
                    <small v-if="formErrors.store_id" class="text-red-500">
                        {{ formErrors.store_id }}
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
                            <span class="font-medium text-sm">{{ group }}</span>
                        </div>
                        <div class="ml-6 grid gap-2 sm:grid-cols-2">
                            <div
                                v-for="perm in permissions"
                                :key="perm.key"
                                class="flex items-center gap-2"
                            >
                                <Checkbox
                                    :model-value="form.permissions.includes(perm.key)"
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
                    <Button type="submit" :label="editingId ? 'Save Changes' : 'Add Assignment'" size="small" :loading="saving" />
                </div>
            </form>
        </Dialog>
    </div>
</template>
