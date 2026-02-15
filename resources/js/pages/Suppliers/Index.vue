<script setup lang="ts">
import PagePermissionsSplitButton from '@/components/admin/PagePermissionsSplitButton.vue';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type Country,
    type PaginatedData,
    type Supplier,
} from '@/types';
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

interface Filters {
    search?: string;
    status?: string;
    country_id?: string | number;
    show_deleted?: boolean;
    per_page?: number;
}

interface Props {
    suppliers: PaginatedData<Supplier>;
    countries: Country[];
    filters?: Filters;
}

const props = defineProps<Props>();

// Permission checks
const { canAccessPage } = usePermissions();
const canManage = computed(() => canAccessPage('suppliers.manage'));

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    country_id: props.filters?.country_id ?? '',
    showDeleted: props.filters?.show_deleted ?? false,
});

const perPage = ref(props.suppliers.per_page ?? 15);

const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];

const countryOptions = computed(() => [
    { label: 'All Countries', value: '' },
    ...props.countries.map((c) => ({ label: c.name, value: c.id })),
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
    () => filters.country_id,
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
    if (filters.country_id) params.country_id = filters.country_id;
    if (filters.showDeleted) params.show_deleted = true;
    if (perPage.value !== 15) params.per_page = perPage.value;
    router.get('/suppliers', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    filters.country_id = '';
    filters.showDeleted = false;
    router.get('/suppliers', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Suppliers' },
];

const expandedRows = ref({});
const hasActiveFilters = computed(
    () =>
        filters.search ||
        filters.status ||
        filters.country_id ||
        filters.showDeleted,
);
const confirm = useConfirm();

function getInitials(supplier: Supplier): string {
    const words = supplier.supplier_name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return supplier.supplier_name.substring(0, 2).toUpperCase();
}

function isDeleted(supplier: Supplier): boolean {
    return supplier.is_deleted === true;
}

function navigateToView(supplier: Supplier) {
    router.get(`/suppliers/${supplier.id}`);
}

function navigateToEdit(supplier: Supplier) {
    router.get(`/suppliers/${supplier.id}/edit`);
}

function confirmDelete(supplier: Supplier) {
    confirm.require({
        message: `Are you sure you want to delete "${supplier.supplier_name}"?`,
        header: 'Delete Supplier',
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
            router.delete(`/suppliers/${supplier.id}`);
        },
    });
}

function confirmRestore(supplier: Supplier) {
    confirm.require({
        message: `Are you sure you want to restore "${supplier.supplier_name}"?`,
        header: 'Restore Supplier',
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
            router.post(`/suppliers/${supplier.id}/restore`);
        },
    });
}

function onRowClick(event: { data: Supplier }) {
    navigateToView(event.data);
}

function onPage(event: { page: number; rows: number }) {
    perPage.value = event.rows;
    const params: Record<string, string | number | boolean> = {
        page: event.page + 1,
    };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.country_id) params.country_id = filters.country_id;
    if (filters.showDeleted) params.show_deleted = true;
    if (event.rows !== 15) params.per_page = event.rows;
    router.get('/suppliers', params, { preserveState: true });
}
</script>

<template>
    <Head title="Suppliers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <h1 class="heading-lg">Suppliers</h1>
                <PagePermissionsSplitButton
                    page="suppliers"
                    page-label="Supplier"
                    create-route="/suppliers/create"
                    :can-manage="canManage"
                />
            </div>

            <!-- Filter Section -->
            <div
                class="filter-section flex flex-col gap-3 sm:flex-row sm:items-center"
            >
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        placeholder="Search by name or email..."
                        size="small"
                        fluid
                    />
                </IconField>
                <div class="flex flex-wrap items-center gap-2">
                    <Select
                        v-model="filters.country_id"
                        :options="countryOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Country"
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
                        <span class="text-sm whitespace-nowrap"
                            >Show Deleted</span
                        >
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

            <!-- Suppliers Table -->
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="suppliers.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="perPage"
                :rows-per-page-options="[10, 15, 25, 50]"
                :total-records="suppliers.total"
                :first="(suppliers.current_page - 1) * perPage"
                @page="onPage"
                @row-click="onRowClick"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No suppliers found.
                    </div>
                </template>
                <Column expander class="w-[12%] sm:w-12 !pr-0 md:hidden" />
                <Column header="" class="hidden w-12 !pr-0 !pl-4 sm:table-cell">
                    <template #body="{ data }">
                        <div v-if="data.logo_url" @click.stop>
                            <Image
                                :src="data.logo_url"
                                :alt="data.supplier_name"
                                image-class="h-8 w-8 rounded-full object-cover cursor-pointer"
                                :pt="{
                                    root: {
                                        class: 'rounded-full overflow-hidden',
                                    },
                                    previewMask: { class: 'rounded-full' },
                                }"
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
                <Column
                    field="supplier_name"
                    header="Supplier Name"
                    class="!pl-3"
                >
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span
                                class="font-medium"
                                :class="{
                                    'text-muted-foreground line-through':
                                        isDeleted(data),
                                }"
                            >
                                {{ data.supplier_name }}
                            </span>
                            <Tag
                                v-if="isDeleted(data)"
                                value="Deleted"
                                severity="danger"
                                class="!text-xs"
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    field="country_name"
                    header="Country"
                    class="hidden md:table-cell"
                >
                    <template #body="{ data }">
                        {{ data.country_name ?? '-' }}
                    </template>
                </Column>
                <Column
                    field="email"
                    header="Email"
                    class="hidden lg:table-cell"
                >
                    <template #body="{ data }">
                        {{ data.email ?? '-' }}
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
                <Column header="" class="hidden w-24 !pr-4 md:table-cell">
                    <template #body="{ data }">
                        <div
                            v-if="isDeleted(data)"
                            class="flex justify-end gap-1"
                        >
                            <Button
                                v-if="canManage"
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
                                v-if="canManage"
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click.stop="navigateToEdit(data)"
                            />
                            <Button
                                v-if="canManage"
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
                        <div
                            class="flex justify-between border-b border-border pb-2"
                        >
                            <span class="text-muted-foreground">Country</span>
                            <span>{{ data.country_name ?? '-' }}</span>
                        </div>
                        <div
                            class="flex justify-between border-b border-border pb-2"
                        >
                            <span class="text-muted-foreground">Email</span>
                            <span>{{ data.email ?? '-' }}</span>
                        </div>
                        <div
                            class="flex justify-between border-b border-border pb-2"
                        >
                            <span class="text-muted-foreground">Phone</span>
                            <span>{{ data.phone_number ?? '-' }}</span>
                        </div>
                        <div
                            class="flex justify-between border-b border-border pb-2"
                        >
                            <span class="text-muted-foreground">Website</span>
                            <span>{{ data.website ?? '-' }}</span>
                        </div>
                        <div
                            v-if="isDeleted(data) && canManage"
                            class="flex gap-2 pt-2"
                        >
                            <Button
                                label="Restore"
                                icon="pi pi-history"
                                severity="success"
                                size="small"
                                @click="confirmRestore(data)"
                                class="flex-1"
                            />
                        </div>
                        <div
                            v-else-if="!isDeleted(data) && canManage"
                            class="flex gap-2 pt-2"
                        >
                            <Button
                                label="Edit"
                                icon="pi pi-pencil"
                                severity="secondary"
                                size="small"
                                @click="navigateToEdit(data)"
                                class="flex-1"
                            />
                            <Button
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
