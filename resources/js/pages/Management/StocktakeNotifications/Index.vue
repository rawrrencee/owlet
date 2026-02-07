<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StocktakeNotificationRecipient } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputSwitch from 'primevue/inputswitch';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { reactive, ref, watch } from 'vue';

interface StoreOption {
    id: number;
    store_name: string;
    store_code: string;
}

interface Props {
    stores: StoreOption[];
    recipients: StocktakeNotificationRecipient[];
    filters: {
        store_id: string | null;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stocktake Notifications' },
];

const confirm = useConfirm();

const filters = reactive({
    store_id: props.filters.store_id ?? '',
});

const storeOptions = [
    { label: 'Select store...', value: '' },
    ...props.stores.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: String(s.id),
    })),
];

watch(() => filters.store_id, () => {
    const params: Record<string, string> = {};
    if (filters.store_id) params.store_id = filters.store_id;
    router.get('/management/stocktake-notifications', params, { preserveState: true });
});

// Add dialog
const addDialogVisible = ref(false);
const addForm = useForm({
    store_id: null as number | null,
    email: '',
    name: '',
});

function openAddDialog() {
    addForm.store_id = filters.store_id ? Number(filters.store_id) : null;
    addForm.email = '';
    addForm.name = '';
    addDialogVisible.value = true;
}

function submitAdd() {
    addForm.post('/management/stocktake-notifications', {
        preserveScroll: true,
        onSuccess: () => {
            addDialogVisible.value = false;
        },
    });
}

// Edit dialog
const editDialogVisible = ref(false);
const editForm = useForm({
    email: '',
    name: '',
    is_active: true,
});
const editingRecipientId = ref<number | null>(null);

function openEditDialog(recipient: StocktakeNotificationRecipient) {
    editingRecipientId.value = recipient.id;
    editForm.email = recipient.email;
    editForm.name = recipient.name ?? '';
    editForm.is_active = recipient.is_active;
    editDialogVisible.value = true;
}

function submitEdit() {
    if (!editingRecipientId.value) return;
    editForm.put(`/management/stocktake-notifications/${editingRecipientId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            editDialogVisible.value = false;
        },
    });
}

function confirmDelete(recipient: StocktakeNotificationRecipient) {
    confirm.require({
        message: `Remove "${recipient.email}" from notifications?`,
        header: 'Remove Recipient',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: { severity: 'secondary', size: 'small' },
        acceptLabel: 'Remove',
        acceptProps: { severity: 'danger', size: 'small' },
        accept: () => {
            router.delete(`/management/stocktake-notifications/${recipient.id}`, {
                preserveScroll: true,
            });
        },
    });
}
</script>

<template>
    <Head title="Stocktake Notifications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="heading-lg">Stocktake Notifications</h1>
                <Button
                    v-if="filters.store_id"
                    label="Add Recipient"
                    icon="pi pi-plus"
                    size="small"
                    @click="openAddDialog"
                />
            </div>

            <!-- Store Selector -->
            <div>
                <Select
                    v-model="filters.store_id"
                    :options="storeOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select store..."
                    size="small"
                    class="w-full sm:w-64"
                />
            </div>

            <!-- No store selected message -->
            <div v-if="!filters.store_id" class="rounded-lg border border-border p-8 text-center text-muted-foreground">
                Select a store to view and manage notification recipients.
            </div>

            <!-- Recipients table -->
            <DataTable
                v-else
                :value="recipients"
                dataKey="id"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No notification recipients for this store.
                    </div>
                </template>
                <Column header="Name">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.name || '-' }}</span>
                    </template>
                </Column>
                <Column header="Email">
                    <template #body="{ data }">
                        {{ data.email }}
                    </template>
                </Column>
                <Column header="Status" :style="{ width: '6rem' }">
                    <template #body="{ data }">
                        <Tag
                            :value="data.is_active ? 'Active' : 'Inactive'"
                            :severity="data.is_active ? 'success' : 'secondary'"
                            class="!text-xs"
                        />
                    </template>
                </Column>
                <Column header="" :style="{ width: '6rem' }">
                    <template #body="{ data }">
                        <div class="flex justify-end gap-1">
                            <Button
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click="openEditDialog(data)"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="confirmDelete(data)"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!-- Add Recipient Dialog -->
            <Dialog
                v-model:visible="addDialogVisible"
                header="Add Notification Recipient"
                modal
                :style="{ width: '24rem' }"
            >
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Email</label>
                        <InputText
                            v-model="addForm.email"
                            size="small"
                            fluid
                            placeholder="email@example.com"
                        />
                        <small v-if="addForm.errors.email" class="text-red-500">
                            {{ addForm.errors.email }}
                        </small>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Name (optional)</label>
                        <InputText
                            v-model="addForm.name"
                            size="small"
                            fluid
                            placeholder="Display name"
                        />
                        <small v-if="addForm.errors.name" class="text-red-500">
                            {{ addForm.errors.name }}
                        </small>
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="addDialogVisible = false"
                    />
                    <Button
                        label="Add"
                        icon="pi pi-plus"
                        size="small"
                        :loading="addForm.processing"
                        @click="submitAdd"
                    />
                </template>
            </Dialog>

            <!-- Edit Recipient Dialog -->
            <Dialog
                v-model:visible="editDialogVisible"
                header="Edit Notification Recipient"
                modal
                :style="{ width: '24rem' }"
            >
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Email</label>
                        <InputText
                            v-model="editForm.email"
                            size="small"
                            fluid
                        />
                        <small v-if="editForm.errors.email" class="text-red-500">
                            {{ editForm.errors.email }}
                        </small>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Name (optional)</label>
                        <InputText
                            v-model="editForm.name"
                            size="small"
                            fluid
                        />
                        <small v-if="editForm.errors.name" class="text-red-500">
                            {{ editForm.errors.name }}
                        </small>
                    </div>
                    <div class="flex items-center gap-3">
                        <InputSwitch v-model="editForm.is_active" />
                        <label class="text-sm font-medium">Active</label>
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="editDialogVisible = false"
                    />
                    <Button
                        label="Update"
                        icon="pi pi-check"
                        size="small"
                        :loading="editForm.processing"
                        @click="submitEdit"
                    />
                </template>
            </Dialog>

            <ConfirmDialog />
        </div>
    </AppLayout>
</template>
