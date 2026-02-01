<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import Image from 'primevue/image';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import { useConfirm } from 'primevue/useconfirm';
import { computed, reactive, ref, watch } from 'vue';
import PagePermissionsSplitButton from '@/components/admin/PagePermissionsSplitButton.vue';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Company, type PaginatedData, type Store } from '@/types';

interface Filters {
    search?: string;
    status?: string;
    company_id?: string | number;
    show_deleted?: boolean;
    per_page?: number;
}

interface Props {
    stores: PaginatedData<Store>;
    companies: Company[];
    filters?: Filters;
}

const props = defineProps<Props>();

// Permission checks
const { canAccessPage } = usePermissions();
const canManageStores = computed(() => canAccessPage('stores.manage'));

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    company_id: props.filters?.company_id ?? '',
    showDeleted: props.filters?.show_deleted ?? false,
});

const perPage = ref(props.stores.per_page ?? 15);

const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];

const companyOptions = computed(() => [
    { label: 'All Companies', value: '' },
    ...props.companies.map(c => ({ label: c.company_name, value: c.id })),
]);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(
    () => filters.search,
    () => {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 300);
    },
);

watch(
    () => filters.status,
    () => {
        applyFilters();
    },
);

watch(
    () => filters.company_id,
    () => {
        applyFilters();
    },
);

watch(
    () => filters.showDeleted,
    () => {
        applyFilters();
    },
);

function applyFilters() {
    const params: Record<string, string | number | boolean> = {};
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.company_id) params.company_id = filters.company_id;
    if (filters.showDeleted) params.show_deleted = true;
    if (perPage.value !== 15) params.per_page = perPage.value;
    router.get('/stores', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    filters.company_id = '';
    filters.showDeleted = false;
    router.get('/stores', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stores' },
];

const expandedRows = ref({});
const hasActiveFilters = computed(() => filters.search || filters.status || filters.company_id || filters.showDeleted);
const confirm = useConfirm();

function getInitials(store: Store): string {
    const words = store.store_name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return store.store_name.substring(0, 2).toUpperCase();
}

function isDeleted(store: Store): boolean {
    return store.is_deleted === true;
}

function navigateToView(store: Store) {
    router.get(`/stores/${store.id}`);
}

function navigateToEdit(store: Store) {
    router.get(`/stores/${store.id}/edit`);
}

function confirmDelete(store: Store) {
    confirm.require({
        message: `Are you sure you want to delete "${store.store_name}"?`,
        header: 'Delete Store',
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
            router.delete(`/stores/${store.id}`);
        },
    });
}

function confirmRestore(store: Store) {
    confirm.require({
        message: `Are you sure you want to restore "${store.store_name}"?`,
        header: 'Restore Store',
        icon: 'pi pi-history',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Restore',
        acceptProps: {
            severity: 'success',
            size: 'small',
        },
        accept: () => {
            router.post(`/stores/${store.id}/restore`);
        },
    });
}

function onRowClick(event: { data: Store }) {
    navigateToView(event.data);
}

function onPage(event: { page: number; rows: number }) {
    perPage.value = event.rows;
    const params: Record<string, string | number | boolean> = { page: event.page + 1 };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.company_id) params.company_id = filters.company_id;
    if (filters.showDeleted) params.show_deleted = true;
    if (event.rows !== 15) params.per_page = event.rows;
    router.get('/stores', params, { preserveState: true });
}
</script>

<template>
    <Head title="Stores" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="heading-lg">Stores</h1>
                <PagePermissionsSplitButton
                    page="stores"
                    page-label="Store"
                    create-route="/stores/create"
                    :can-manage="canManageStores"
                />
            </div>

            <!-- Filter Section -->
            <div class="filter-section flex flex-col gap-3 sm:flex-row sm:items-center">
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        placeholder="Search by name, code, email, or phone..."
                        size="small"
                        fluid
                    />
                </IconField>
                <div class="flex flex-wrap items-center gap-2">
                    <Select
                        v-model="filters.company_id"
                        :options="companyOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Company"
                        size="small"
                        class="w-full sm:w-44"
                    />
                    <Select
                        v-model="filters.status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Status"
                        size="small"
                        class="w-full sm:w-36"
                    />
                    <label class="flex cursor-pointer items-center gap-2">
                        <ToggleSwitch v-model="filters.showDeleted" />
                        <span class="whitespace-nowrap text-sm">Show Deleted</span>
                    </label>
                    <Button
                        v-if="hasActiveFilters"
                        icon="pi pi-times"
                        severity="secondary"
                        text
                        size="small"
                        @click="clearFilters"
                        v-tooltip.top="'Clear filters'"
                    />
                </div>
            </div>

            <!-- Stores Table -->
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="stores.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="perPage"
                :rows-per-page-options="[10, 15, 25, 50]"
                :total-records="stores.total"
                :first="((stores.current_page - 1) * perPage)"
                @page="onPage"
                @row-click="onRowClick"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border  [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No stores found.
                    </div>
                </template>
                <Column expander class="w-12 !pr-0 md:hidden" />
                <Column header="" class="w-12 !pl-4 !pr-0">
                    <template #body="{ data }">
                        <div v-if="data.logo_url" @click.stop>
                            <Image
                                :src="data.logo_url"
                                :alt="data.store_name"
                                image-class="h-8 w-8 rounded-full object-cover cursor-pointer"
                                :pt="{ root: { class: 'rounded-full overflow-hidden' }, previewMask: { class: 'rounded-full' } }"
                                preview
                            />
                        </div>
                        <Avatar
                            v-else
                            :label="getInitials(data)"
                            shape="circle"
                            class="!h-8 !w-8 bg-primary/10 text-primary"
                        />
                    </template>
                </Column>
                <Column field="store_name" header="Store Name" class="!pl-3">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span
                                class="font-medium"
                                :class="{ 'text-muted-foreground line-through': isDeleted(data) }"
                            >
                                {{ data.store_name }}
                            </span>
                            <Tag v-if="isDeleted(data)" value="Deleted" severity="danger" class="!text-xs" />
                        </div>
                    </template>
                </Column>
                <Column field="store_code" header="Code" class="w-20">
                    <template #body="{ data }">
                        <Tag :value="data.store_code" severity="secondary" />
                    </template>
                </Column>
                <Column field="company" header="Company" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.company?.company_name ?? '-' }}
                    </template>
                </Column>
                <Column field="email" header="Email" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        {{ data.email ?? '-' }}
                    </template>
                </Column>
                <Column field="default_currency" header="Currency" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
                            <Tag
                                v-if="data.default_currency"
                                :value="data.default_currency.code"
                                severity="secondary"
                            />
                            <Tag
                                v-if="data.store_currencies && data.store_currencies.length > 1"
                                :value="`+${data.store_currencies.length - 1}`"
                                severity="info"
                                class="!text-xs"
                                v-tooltip.top="data.store_currencies.filter((sc: any) => !sc.is_default).map((sc: any) => sc.currency?.code).join(', ')"
                            />
                            <span v-if="!data.default_currency && (!data.store_currencies || data.store_currencies.length === 0)" class="text-muted-foreground">-</span>
                        </div>
                    </template>
                </Column>
                <Column field="active" header="Status">
                    <template #body="{ data }">
                        <Tag
                            :value="data.active ? 'Active' : 'Inactive'"
                            :severity="data.active ? 'success' : 'danger'"
                        />
                    </template>
                </Column>
                <Column header="" class="w-24 !pr-4">
                    <template #body="{ data }">
                        <div v-if="isDeleted(data)" class="flex justify-end gap-1">
                            <Button
                                v-if="canManageStores"
                                icon="pi pi-history"
                                severity="success"
                                text
                                rounded
                                size="small"
                                @click.stop="confirmRestore(data)"
                                v-tooltip.top="'Restore'"
                            />
                        </div>
                        <div v-else class="flex justify-end gap-1">
                            <Button
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click.stop="navigateToEdit(data)"
                            />
                            <Button
                                v-if="canManageStores"
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click.stop="confirmDelete(data)"
                            />
                        </div>
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm md:hidden">
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Company</span>
                            <span>{{ data.company?.company_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Email</span>
                            <span>{{ data.email ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Phone</span>
                            <span>{{ data.phone_number ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Website</span>
                            <span>{{ data.website ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2 lg:hidden">
                            <span class="text-muted-foreground">Currency</span>
                            <div class="flex items-center gap-1">
                                <Tag
                                    v-if="data.default_currency"
                                    :value="data.default_currency.code"
                                    severity="secondary"
                                />
                                <Tag
                                    v-if="data.store_currencies && data.store_currencies.length > 1"
                                    :value="`+${data.store_currencies.length - 1}`"
                                    severity="info"
                                    class="!text-xs"
                                />
                                <span v-if="!data.default_currency && (!data.store_currencies || data.store_currencies.length === 0)">-</span>
                            </div>
                        </div>
                        <div v-if="isDeleted(data) && canManageStores" class="flex gap-2 pt-2">
                            <Button
                                label="Restore"
                                icon="pi pi-history"
                                severity="success"
                                size="small"
                                @click="confirmRestore(data)"
                                class="flex-1"
                            />
                        </div>
                        <div v-else-if="!isDeleted(data)" class="flex gap-2 pt-2">
                            <Button
                                label="Edit"
                                icon="pi pi-pencil"
                                severity="secondary"
                                size="small"
                                @click="navigateToEdit(data)"
                                class="flex-1"
                            />
                            <Button
                                v-if="canManageStores"
                                label="Delete"
                                icon="pi pi-trash"
                                severity="danger"
                                size="small"
                                @click="confirmDelete(data)"
                                class="flex-1"
                            />
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>

        <ConfirmDialog />
    </AppLayout>
</template>
