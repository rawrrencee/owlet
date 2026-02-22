<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputSwitch from 'primevue/inputswitch';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { computed, reactive, ref, watch } from 'vue';

interface StoreOption {
    id: number;
    store_name: string;
    store_code: string;
}

interface EventType {
    value: string;
    label: string;
}

interface NotificationRecipient {
    id: number;
    event_type: string;
    store_id: number;
    store_name: string | null;
    store_code: string | null;
    email: string;
    name: string | null;
    is_active: boolean;
}

interface Props {
    stores: StoreOption[];
    recipients: NotificationRecipient[];
    eventTypes: EventType[];
    filters: {
        store_id: string | null;
        event_type: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Notifications' },
];

const confirm = useConfirm();

const filters = reactive({
    store_id: props.filters.store_id ?? 'all',
    event_type: props.filters.event_type ?? 'stocktake',
});

const storeOptions = [
    { label: 'All Stores', value: 'all' },
    ...props.stores.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: String(s.id),
    })),
];

function reloadWithFilters() {
    const params: Record<string, string> = {};
    if (filters.store_id) params.store_id = filters.store_id;
    if (filters.event_type) params.event_type = filters.event_type;
    router.get('/management/notifications', params, { preserveState: true });
}

watch(() => filters.store_id, () => {
    reloadWithFilters();
});

function onTabChange(eventType: string) {
    filters.event_type = eventType;
    reloadWithFilters();
}

// Add dialog
const addDialogVisible = ref(false);
const selectedStoreIds = ref<number[]>([]);
const selectAllStores = ref(false);
const addForm = useForm({
    event_type: '',
    store_ids: [] as number[],
    email: '',
    name: '',
});

const allStoreIds = computed(() => props.stores.map((s) => s.id));

watch(selectAllStores, (val) => {
    selectedStoreIds.value = val ? [...allStoreIds.value] : [];
});

watch(selectedStoreIds, (val) => {
    selectAllStores.value = val.length === allStoreIds.value.length && val.length > 0;
}, { deep: true });

function openAddDialog() {
    addForm.event_type = filters.event_type;
    addForm.email = '';
    addForm.name = '';
    // Pre-select current store filter if specific store is selected
    if (filters.store_id && filters.store_id !== 'all') {
        selectedStoreIds.value = [Number(filters.store_id)];
    } else {
        selectedStoreIds.value = [];
    }
    selectAllStores.value = false;
    addDialogVisible.value = true;
}

function submitAdd() {
    addForm.store_ids = selectedStoreIds.value;
    addForm.post('/management/notifications/batch', {
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

function openEditDialog(recipient: NotificationRecipient) {
    editingRecipientId.value = recipient.id;
    editForm.email = recipient.email;
    editForm.name = recipient.name ?? '';
    editForm.is_active = recipient.is_active;
    editDialogVisible.value = true;
}

function submitEdit() {
    if (!editingRecipientId.value) return;
    editForm.put(`/management/notifications/${editingRecipientId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            editDialogVisible.value = false;
        },
    });
}

function confirmDelete(recipient: NotificationRecipient) {
    confirm.require({
        message: `Remove "${recipient.email}" from notifications?`,
        header: 'Remove Recipient',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: { severity: 'secondary', size: 'small' },
        acceptLabel: 'Remove',
        acceptProps: { severity: 'danger', size: 'small' },
        accept: () => {
            router.delete(`/management/notifications/${recipient.id}`, {
                preserveScroll: true,
            });
        },
    });
}
</script>

<template>
    <Head title="Notifications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="heading-lg">Notifications</h1>
                <Button
                    label="Add Recipient"
                    icon="pi pi-plus"
                    size="small"
                    @click="openAddDialog"
                />
            </div>

            <Tabs :value="filters.event_type">
                <TabList>
                    <Tab
                        v-for="et in eventTypes"
                        :key="et.value"
                        :value="et.value"
                        @click="onTabChange(et.value)"
                    >
                        {{ et.label }}
                    </Tab>
                </TabList>
            </Tabs>

            <div class="flex flex-col gap-4">
                <!-- Store Selector -->
                <div>
                    <Select
                        v-model="filters.store_id"
                        :options="storeOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="All Stores"
                        size="small"
                        class="w-full sm:w-64"
                    />
                </div>

                <!-- Recipients table -->
                <DataTable
                    :value="recipients"
                    dataKey="id"
                    striped-rows
                    size="small"
                    class="overflow-hidden rounded-lg border border-border"
                >
                    <template #empty>
                        <div class="p-4 text-center text-muted-foreground">
                            No notification recipients for this event type{{ filters.store_id !== 'all' ? ' and store' : '' }}.
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
                    <Column header="Store" class="hidden sm:table-cell">
                        <template #body="{ data }">
                            <Tag
                                v-if="data.store_name"
                                :value="`${data.store_name} (${data.store_code})`"
                                severity="info"
                                class="!text-xs"
                            />
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
            </div>

            <!-- Add Recipient Dialog -->
            <Dialog
                v-model:visible="addDialogVisible"
                header="Add Notification Recipient"
                modal
                :style="{ width: '28rem' }"
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
                    <div>
                        <label class="mb-2 block text-sm font-medium">Stores</label>
                        <div class="rounded border border-border p-2">
                            <div class="mb-2 flex items-center gap-2 border-b border-border pb-2">
                                <Checkbox v-model="selectAllStores" :binary="true" inputId="select-all" />
                                <label for="select-all" class="text-sm font-medium cursor-pointer">Select All</label>
                            </div>
                            <div class="max-h-48 space-y-1 overflow-y-auto">
                                <div v-for="store in stores" :key="store.id" class="flex items-center gap-2">
                                    <Checkbox
                                        v-model="selectedStoreIds"
                                        :value="store.id"
                                        :inputId="`store-${store.id}`"
                                    />
                                    <label :for="`store-${store.id}`" class="text-sm cursor-pointer">
                                        {{ store.store_name }} ({{ store.store_code }})
                                    </label>
                                </div>
                            </div>
                        </div>
                        <small v-if="addForm.errors.store_ids" class="text-red-500">
                            {{ addForm.errors.store_ids }}
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
                        :disabled="selectedStoreIds.length === 0"
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
