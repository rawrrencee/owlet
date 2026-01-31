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
import AppLayout from '@/layouts/AppLayout.vue';
import { type Brand, type BreadcrumbItem, type Country, type PaginatedData } from '@/types';

interface Filters {
    search?: string;
    status?: string;
    country_id?: string | number;
    show_deleted?: boolean;
}

interface Props {
    brands: PaginatedData<Brand>;
    countries: Country[];
    filters?: Filters;
}

const props = defineProps<Props>();

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    country_id: props.filters?.country_id ?? '',
    showDeleted: props.filters?.show_deleted ?? false,
});

const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];

const countryOptions = computed(() => [
    { label: 'All Countries', value: '' },
    ...props.countries.map(c => ({ label: c.name, value: c.id })),
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
    router.get('/brands', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    filters.country_id = '';
    filters.showDeleted = false;
    router.get('/brands', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Brands' },
];

const expandedRows = ref({});
const hasActiveFilters = computed(() => filters.search || filters.status || filters.country_id || filters.showDeleted);
const confirm = useConfirm();

function getInitials(brand: Brand): string {
    const words = brand.brand_name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return brand.brand_name.substring(0, 2).toUpperCase();
}

function isDeleted(brand: Brand): boolean {
    return brand.is_deleted === true;
}

function navigateToCreate() {
    router.get('/brands/create');
}

function navigateToView(brand: Brand) {
    router.get(`/brands/${brand.id}`);
}

function navigateToEdit(brand: Brand) {
    router.get(`/brands/${brand.id}/edit`);
}

function confirmDelete(brand: Brand) {
    confirm.require({
        message: `Are you sure you want to delete "${brand.brand_name}"?`,
        header: 'Delete Brand',
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
            router.delete(`/brands/${brand.id}`);
        },
    });
}

function confirmRestore(brand: Brand) {
    confirm.require({
        message: `Are you sure you want to restore "${brand.brand_name}"?`,
        header: 'Restore Brand',
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
            router.post(`/brands/${brand.id}/restore`);
        },
    });
}

function onRowClick(event: { data: Brand }) {
    navigateToView(event.data);
}

function onPage(event: { page: number }) {
    const params: Record<string, string | number | boolean> = { page: event.page + 1 };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.country_id) params.country_id = filters.country_id;
    if (filters.showDeleted) params.show_deleted = true;
    router.get('/brands', params, { preserveState: true });
}
</script>

<template>
    <Head title="Brands" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="heading-lg">Brands</h1>
                <Button
                    label="Create Brand"
                    icon="pi pi-plus"
                    size="small"
                    @click="navigateToCreate"
                />
            </div>

            <!-- Filter Section -->
            <div class="filter-section flex flex-col gap-3 sm:flex-row sm:items-center">
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        placeholder="Search by name, code, or email..."
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

            <!-- Brands Table -->
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="brands.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="15"
                :total-records="brands.total"
                :first="((brands.current_page - 1) * 15)"
                @page="onPage"
                @row-click="onRowClick"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No brands found.
                    </div>
                </template>
                <Column expander class="w-12 !pr-0 md:hidden" />
                <Column header="" class="w-12 !pl-4 !pr-0">
                    <template #body="{ data }">
                        <Image
                            v-if="data.logo_url"
                            :src="data.logo_url"
                            :alt="data.brand_name"
                            image-class="h-8 w-8 rounded-full object-cover cursor-pointer"
                            :pt="{ root: { class: 'rounded-full overflow-hidden' }, previewMask: { class: 'rounded-full' } }"
                            preview
                        />
                        <Avatar
                            v-else
                            :label="getInitials(data)"
                            shape="circle"
                            class="!h-8 !w-8 bg-primary/10 text-primary"
                        />
                    </template>
                </Column>
                <Column field="brand_name" header="Brand Name" class="!pl-3">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span
                                class="font-medium"
                                :class="{ 'text-muted-foreground line-through': isDeleted(data) }"
                            >
                                {{ data.brand_name }}
                            </span>
                            <Tag v-if="isDeleted(data)" value="Deleted" severity="danger" class="!text-xs" />
                        </div>
                    </template>
                </Column>
                <Column field="brand_code" header="Code" class="w-20">
                    <template #body="{ data }">
                        <Tag :value="data.brand_code" severity="secondary" />
                    </template>
                </Column>
                <Column field="country_name" header="Country" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.country_name ?? '-' }}
                    </template>
                </Column>
                <Column field="email" header="Email" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        {{ data.email ?? '-' }}
                    </template>
                </Column>
                <Column field="is_active" header="Status">
                    <template #body="{ data }">
                        <Tag
                            :value="data.is_active ? 'Active' : 'Inactive'"
                            :severity="data.is_active ? 'success' : 'danger'"
                        />
                    </template>
                </Column>
                <Column header="" class="w-24 !pr-4">
                    <template #body="{ data }">
                        <div v-if="isDeleted(data)" class="flex justify-end gap-1">
                            <Button
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
                            <span class="text-muted-foreground">Country</span>
                            <span>{{ data.country_name ?? '-' }}</span>
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
                        <div v-if="isDeleted(data)" class="flex gap-2 pt-2">
                            <Button
                                label="Restore"
                                icon="pi pi-history"
                                severity="success"
                                size="small"
                                @click="confirmRestore(data)"
                                class="flex-1"
                            />
                        </div>
                        <div v-else class="flex gap-2 pt-2">
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
