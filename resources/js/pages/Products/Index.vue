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
import { type BreadcrumbItem, type PaginatedData, type Product } from '@/types';

interface Filters {
    search?: string;
    status?: string;
    brand_id?: string | number;
    category_id?: string | number;
    supplier_id?: string | number;
    show_deleted?: boolean;
}

interface Props {
    products: PaginatedData<Product>;
    brands: Array<{ id: number; brand_name: string; brand_code: string }>;
    categories: Array<{ id: number; category_name: string; category_code: string }>;
    suppliers: Array<{ id: number; supplier_name: string }>;
    filters?: Filters;
}

const props = defineProps<Props>();

// Permission checks
const { canAccessPage } = usePermissions();
const canCreate = computed(() => canAccessPage('products.create'));
const canEdit = computed(() => canAccessPage('products.edit'));
const canDelete = computed(() => canAccessPage('products.delete'));

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    brand_id: props.filters?.brand_id ?? '',
    category_id: props.filters?.category_id ?? '',
    supplier_id: props.filters?.supplier_id ?? '',
    showDeleted: props.filters?.show_deleted ?? false,
});

const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];

const brandOptions = computed(() => [
    { label: 'All Brands', value: '' },
    ...props.brands.map(b => ({ label: b.brand_name, value: b.id })),
]);

const categoryOptions = computed(() => [
    { label: 'All Categories', value: '' },
    ...props.categories.map(c => ({ label: c.category_name, value: c.id })),
]);

const supplierOptions = computed(() => [
    { label: 'All Suppliers', value: '' },
    ...props.suppliers.map(s => ({ label: s.supplier_name, value: s.id })),
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
    () => applyFilters(),
);

watch(
    () => filters.brand_id,
    () => applyFilters(),
);

watch(
    () => filters.category_id,
    () => applyFilters(),
);

watch(
    () => filters.supplier_id,
    () => applyFilters(),
);

watch(
    () => filters.showDeleted,
    () => applyFilters(),
);

function applyFilters() {
    const params: Record<string, string | number | boolean> = {};
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.brand_id) params.brand_id = filters.brand_id;
    if (filters.category_id) params.category_id = filters.category_id;
    if (filters.supplier_id) params.supplier_id = filters.supplier_id;
    if (filters.showDeleted) params.show_deleted = true;
    router.get('/products', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    filters.brand_id = '';
    filters.category_id = '';
    filters.supplier_id = '';
    filters.showDeleted = false;
    router.get('/products', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products' },
];

const expandedRows = ref({});
const hasActiveFilters = computed(() =>
    filters.search || filters.status || filters.brand_id || filters.category_id || filters.supplier_id || filters.showDeleted
);
const confirm = useConfirm();

function getInitials(product: Product): string {
    const words = product.product_name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return product.product_name.substring(0, 2).toUpperCase();
}

function isDeleted(product: Product): boolean {
    return product.is_deleted === true;
}

function getFirstPrice(product: Product): string {
    if (product.prices && product.prices.length > 0) {
        const price = product.prices[0];
        return `${price.currency?.symbol ?? ''}${price.unit_price ?? '-'}`;
    }
    return '-';
}

function navigateToView(product: Product) {
    router.get(`/products/${product.id}`);
}

function navigateToEdit(product: Product) {
    router.get(`/products/${product.id}/edit`);
}

function confirmDelete(product: Product) {
    confirm.require({
        message: `Are you sure you want to delete "${product.product_name}"?`,
        header: 'Delete Product',
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
            router.delete(`/products/${product.id}`);
        },
    });
}

function confirmRestore(product: Product) {
    confirm.require({
        message: `Are you sure you want to restore "${product.product_name}"?`,
        header: 'Restore Product',
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
            router.post(`/products/${product.id}/restore`);
        },
    });
}

function onRowClick(event: { data: Product }) {
    navigateToView(event.data);
}

function onPage(event: { page: number }) {
    const params: Record<string, string | number | boolean> = { page: event.page + 1 };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.brand_id) params.brand_id = filters.brand_id;
    if (filters.category_id) params.category_id = filters.category_id;
    if (filters.supplier_id) params.supplier_id = filters.supplier_id;
    if (filters.showDeleted) params.show_deleted = true;
    router.get('/products', params, { preserveState: true });
}
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="heading-lg">Products</h1>
                <PagePermissionsSplitButton
                    page="products"
                    page-label="Product"
                    create-route="/products/create"
                    :can-manage="canCreate"
                />
            </div>

            <!-- Filter Section -->
            <div class="filter-section flex flex-col gap-3">
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        placeholder="Search by name, SKU, or barcode..."
                        size="small"
                        fluid
                    />
                </IconField>
                <div class="flex flex-wrap items-center gap-2">
                    <Select
                        v-model="filters.brand_id"
                        :options="brandOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Brand"
                        filter
                        size="small"
                        class="w-full sm:w-40"
                    />
                    <Select
                        v-model="filters.category_id"
                        :options="categoryOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Category"
                        filter
                        size="small"
                        class="w-full sm:w-40"
                    />
                    <Select
                        v-model="filters.supplier_id"
                        :options="supplierOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Supplier"
                        filter
                        size="small"
                        class="w-full sm:w-40"
                    />
                    <Select
                        v-model="filters.status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Status"
                        size="small"
                        class="w-full sm:w-32"
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

            <!-- Products Table -->
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="products.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="15"
                :total-records="products.total"
                :first="((products.current_page - 1) * 15)"
                @page="onPage"
                @row-click="onRowClick"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No products found.
                    </div>
                </template>
                <Column expander class="w-12 !pr-0 md:hidden" />
                <Column header="" class="w-12 !pl-4 !pr-0">
                    <template #body="{ data }">
                        <div v-if="data.image_url" @click.stop>
                            <Image
                                :src="data.image_url"
                                :alt="data.product_name"
                                image-class="h-8 w-8 rounded object-cover cursor-pointer"
                                :pt="{ root: { class: 'rounded overflow-hidden' }, previewMask: { class: 'rounded' } }"
                                preview
                            />
                        </div>
                        <Avatar
                            v-else
                            :label="getInitials(data)"
                            shape="square"
                            class="!h-8 !w-8 rounded bg-primary/10 text-primary"
                        />
                    </template>
                </Column>
                <Column field="product_name" header="Product" class="!pl-3">
                    <template #body="{ data }">
                        <div class="flex flex-col gap-0.5">
                            <div class="flex items-center gap-2">
                                <span
                                    class="font-medium"
                                    :class="{ 'text-muted-foreground line-through': isDeleted(data) }"
                                >
                                    {{ data.product_name }}
                                </span>
                                <Tag v-if="isDeleted(data)" value="Deleted" severity="danger" class="!text-xs" />
                            </div>
                            <span class="text-xs text-muted-foreground">{{ data.product_number }}</span>
                        </div>
                    </template>
                </Column>
                <Column field="brand_name" header="Brand" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.brand_name ?? '-' }}
                    </template>
                </Column>
                <Column field="category_name" header="Category" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        <div class="flex flex-col gap-0.5">
                            <span>{{ data.category_name }}</span>
                            <span v-if="data.subcategory_name" class="text-xs text-muted-foreground">
                                {{ data.subcategory_name }}
                            </span>
                        </div>
                    </template>
                </Column>
                <Column header="Price" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        {{ getFirstPrice(data) }}
                    </template>
                </Column>
                <Column field="is_active" header="Status" class="w-24">
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
                                v-if="canDelete"
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
                                v-if="canEdit"
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click.stop="navigateToEdit(data)"
                            />
                            <Button
                                v-if="canDelete"
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
                            <span class="text-muted-foreground">Brand</span>
                            <span>{{ data.brand_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Category</span>
                            <span>{{ data.category_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Subcategory</span>
                            <span>{{ data.subcategory_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Price</span>
                            <span>{{ getFirstPrice(data) }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Barcode</span>
                            <span>{{ data.barcode ?? '-' }}</span>
                        </div>
                        <div v-if="isDeleted(data) && canDelete" class="flex gap-2 pt-2">
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
                                v-if="canEdit"
                                label="Edit"
                                icon="pi pi-pencil"
                                severity="secondary"
                                size="small"
                                @click="navigateToEdit(data)"
                                class="flex-1"
                            />
                            <Button
                                v-if="canDelete"
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
