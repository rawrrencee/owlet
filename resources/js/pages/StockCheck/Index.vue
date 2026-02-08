<script setup lang="ts">
import BarcodeScanner from '@/components/stocktakes/BarcodeScanner.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Category, Subcategory } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ScanBarcode, Search } from 'lucide-vue-next';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import DataView from 'primevue/dataview';
import Drawer from 'primevue/drawer';
import IconField from 'primevue/iconfield';
import Image from 'primevue/image';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import { computed, ref, watch } from 'vue';

interface StockCheckStore {
    store_id: number;
    store_name: string;
    store_code: string;
    quantity: number | null;
    in_stock: boolean;
}

interface StockCheckProduct {
    id: number;
    product_name: string;
    product_number: string;
    barcode: string | null;
    variant_name: string | null;
    parent_product_id: number | null;
    brand_name: string | null;
    category_name: string | null;
    subcategory_name: string | null;
    image_url: string | null;
    stores: StockCheckStore[];
}

interface StockCheckPaginated {
    data: StockCheckProduct[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface StoreOption {
    id: number;
    store_name: string;
    store_code: string;
}

const props = defineProps<{
    products: StockCheckPaginated | null;
    categories: Category[];
    stores: StoreOption[];
    filters: {
        search: string;
        category_id: string;
        subcategory_id: string;
        store_id: string;
        per_page: number;
    };
    storeStockCountPermissions: Record<number, boolean>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Stock Check' }];

// Search and filter state
const search = ref(props.filters.search || '');
const categoryId = ref(props.filters.category_id || '');
const subcategoryId = ref(props.filters.subcategory_id || '');
const storeId = ref(props.filters.store_id || '');
const perPage = ref(props.filters.per_page || 50);
const rowsPerPageOptions = [10, 25, 50, 100];

// UI state - persist layout preference
const savedLayout = localStorage.getItem('stock-check-layout');
const layout = ref<'list' | 'grid'>(savedLayout === 'grid' ? 'grid' : 'list');
const layoutOptions = ['list', 'grid'];
const scannerActive = ref(false);
const filterDrawerVisible = ref(false);

watch(layout, (val) => {
    localStorage.setItem('stock-check-layout', val);
});

// Computed: active filter count for mobile badge
const activeFilterCount = computed(() => {
    let count = 0;
    if (categoryId.value) count++;
    if (subcategoryId.value) count++;
    if (storeId.value) count++;
    return count;
});

// Computed: subcategories filtered by selected category
const filteredSubcategories = computed<Subcategory[]>(() => {
    if (!categoryId.value) return [];
    const cat = props.categories.find((c) => c.id === Number(categoryId.value));
    return cat?.subcategories ?? [];
});

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function buildParams(): Record<string, string | number> {
    const params: Record<string, string | number> = {};
    if (search.value) params.search = search.value;
    if (categoryId.value) params.category_id = categoryId.value;
    if (subcategoryId.value) params.subcategory_id = subcategoryId.value;
    if (storeId.value) params.store_id = storeId.value;
    if (perPage.value !== 50) params.per_page = perPage.value;
    return params;
}

function performSearch() {
    router.get('/stock-check', buildParams(), {
        preserveState: true,
        preserveScroll: true,
    });
}

watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch();
    }, 300);
});

function onFilterChange() {
    performSearch();
}

function onCategoryChange() {
    subcategoryId.value = '';
    onFilterChange();
}

function onBarcodeScan(barcode: string) {
    search.value = barcode;
    scannerActive.value = false;
    // Trigger search immediately
    if (searchTimeout) clearTimeout(searchTimeout);
    performSearch();
}

function onPage(event: any) {
    const params = buildParams();
    params.page = event.page + 1;
    if (event.rows && event.rows !== perPage.value) {
        perPage.value = event.rows;
        params.per_page = event.rows;
        delete params.page; // reset to page 1 when changing rows
    }

    router.get('/stock-check', params, {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    search.value = '';
    categoryId.value = '';
    subcategoryId.value = '';
    storeId.value = '';
    perPage.value = 50;
    if (searchTimeout) clearTimeout(searchTimeout);
    router.get('/stock-check', {}, { preserveState: true });
}

function getStockText(store: StockCheckStore): string {
    if (store.quantity !== null) {
        return String(store.quantity);
    }
    return store.in_stock ? 'In Stock' : 'Out of Stock';
}

function getStockColorClass(store: StockCheckStore): string {
    return store.in_stock ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
}

// Filter stores shown for a product based on store filter
function getDisplayStores(product: StockCheckProduct): StockCheckStore[] {
    if (storeId.value) {
        return product.stores.filter((s) => s.store_id === Number(storeId.value));
    }
    return product.stores;
}
</script>

<template>
    <Head title="Stock Check" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-7xl space-y-4 p-4">
            <!-- Page header -->
            <div>
                <h1 class="text-xl font-semibold">Stock Check</h1>
                <p class="mt-1 text-sm text-muted-foreground">Search for products to check stock availability across stores.</p>
            </div>

            <!-- Search bar -->
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="flex flex-1 gap-2">
                    <IconField class="flex-1">
                        <InputIcon>
                            <Search class="h-4 w-4" />
                        </InputIcon>
                        <InputText
                            v-model="search"
                            placeholder="Search by name, number, or barcode..."
                            size="small"
                            class="w-full"
                        />
                        <InputIcon v-if="search" class="cursor-pointer" @click="search = ''; performSearch()">
                            <i class="pi pi-times text-xs" />
                        </InputIcon>
                    </IconField>
                    <Button
                        size="small"
                        :severity="scannerActive ? 'primary' : 'secondary'"
                        :variant="scannerActive ? undefined : 'outlined'"
                        @click="scannerActive = !scannerActive"
                        v-tooltip.top="'Scan barcode'"
                    >
                        <ScanBarcode class="h-4 w-4" />
                    </Button>
                    <!-- Mobile/tablet filter button -->
                    <Button
                        size="small"
                        severity="secondary"
                        variant="outlined"
                        icon="pi pi-filter"
                        :badge="activeFilterCount > 0 ? String(activeFilterCount) : undefined"
                        :pt="{ root: { class: 'lg:!hidden' } }"
                        @click="filterDrawerVisible = true"
                    />
                </div>

                <!-- Desktop filters -->
                <div class="hidden gap-2 lg:flex">
                    <Select
                        v-model="categoryId"
                        :options="categories"
                        option-label="category_name"
                        option-value="id"
                        placeholder="Category"
                        size="small"
                        show-clear
                        class="w-40"
                        @change="onCategoryChange"
                    />
                    <Select
                        v-model="subcategoryId"
                        :options="filteredSubcategories"
                        option-label="subcategory_name"
                        option-value="id"
                        placeholder="Subcategory"
                        size="small"
                        show-clear
                        class="w-40"
                        :disabled="!categoryId"
                        @change="onFilterChange"
                    />
                    <Select
                        v-model="storeId"
                        :options="stores"
                        option-label="store_name"
                        option-value="id"
                        placeholder="Store"
                        size="small"
                        show-clear
                        class="w-40"
                        @change="onFilterChange"
                    />
                </div>
            </div>

            <!-- Barcode scanner -->
            <BarcodeScanner :active="scannerActive" @scan="onBarcodeScan" @close="scannerActive = false" />

            <!-- Mobile filter drawer -->
            <Drawer v-model:visible="filterDrawerVisible" header="Filters" position="bottom" class="!h-auto">
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Category</label>
                        <Select
                            v-model="categoryId"
                            :options="categories"
                            option-label="category_name"
                            option-value="id"
                            placeholder="All Categories"
                            size="small"
                            show-clear
                            class="w-full"
                            @change="onCategoryChange"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Subcategory</label>
                        <Select
                            v-model="subcategoryId"
                            :options="filteredSubcategories"
                            option-label="subcategory_name"
                            option-value="id"
                            placeholder="All Subcategories"
                            size="small"
                            show-clear
                            class="w-full"
                            :disabled="!categoryId"
                            @change="onFilterChange"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Store</label>
                        <Select
                            v-model="storeId"
                            :options="stores"
                            option-label="store_name"
                            option-value="id"
                            placeholder="All Stores"
                            size="small"
                            show-clear
                            class="w-full"
                            @change="onFilterChange"
                        />
                    </div>
                    <div class="flex gap-2">
                        <Button
                            size="small"
                            severity="secondary"
                            variant="outlined"
                            label="Clear All"
                            class="flex-1"
                            @click="clearFilters(); filterDrawerVisible = false"
                        />
                        <Button
                            size="small"
                            label="Apply"
                            class="flex-1"
                            @click="filterDrawerVisible = false"
                        />
                    </div>
                </div>
            </Drawer>

            <!-- Results -->
            <div v-if="products">
                <DataView
                    :value="products.data"
                    :layout="layout"
                    paginator
                    lazy
                    :rows="products.per_page"
                    :total-records="products.total"
                    :first="(products.current_page - 1) * products.per_page"
                    data-key="id"
                    paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                    :rows-per-page-options="rowsPerPageOptions"
                    :dt="{
                        borderRadius: '0.5rem',
                        header: { borderRadius: '0.5rem 0.5rem 0 0', padding: '0.75rem 1rem' },
                        content: { padding: layout === 'grid' ? '0.75rem' : '0.75rem 0', borderRadius: '0 0 0.5rem 0.5rem' },
                    }"
                    @page="onPage"
                >
                    <template #header>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">
                                {{ products.total }} result{{ products.total !== 1 ? 's' : '' }}
                            </span>
                            <SelectButton
                                v-model="layout"
                                :options="layoutOptions"
                                :allow-empty="false"
                                size="small"
                            >
                                <template #option="{ option }">
                                    <i :class="[option === 'list' ? 'pi pi-bars' : 'pi pi-th-large']" />
                                </template>
                            </SelectButton>
                        </div>
                    </template>

                    <template #list="slotProps">
                        <div class="flex flex-col">
                            <div v-for="(item, index) in (slotProps.items as StockCheckProduct[])" :key="item.id">
                                <div
                                    class="flex items-start gap-3 p-3"
                                    :class="{ 'border-t border-surface-200 dark:border-surface-700': index !== 0 }"
                                >
                                    <!-- Image with preview -->
                                    <div class="flex-shrink-0" @click.stop>
                                        <Image
                                            v-if="item.image_url"
                                            :src="item.image_url"
                                            :alt="item.product_name"
                                            image-class="h-10 w-10 rounded object-cover cursor-pointer"
                                            :pt="{
                                                root: { class: 'rounded overflow-hidden' },
                                                previewMask: { class: 'rounded' },
                                            }"
                                            preview
                                        />
                                        <Avatar
                                            v-else
                                            icon="pi pi-box"
                                            shape="square"
                                            class="!h-10 !w-10"
                                        />
                                    </div>

                                    <!-- Product info + stock table -->
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-medium">
                                            {{ item.product_name }}
                                            <span v-if="item.variant_name" class="text-muted-foreground">
                                                &mdash; {{ item.variant_name }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground">
                                            <span>{{ item.product_number }}</span>
                                            <span v-if="item.brand_name">&middot; {{ item.brand_name }}</span>
                                            <span v-if="item.barcode">&middot; {{ item.barcode }}</span>
                                        </div>

                                        <!-- Stock table -->
                                        <table class="mt-2 text-xs">
                                            <tr v-for="store in getDisplayStores(item)" :key="store.store_id">
                                                <td class="pr-3 text-muted-foreground">{{ store.store_name }}</td>
                                                <td :class="getStockColorClass(store)" class="font-medium">
                                                    {{ getStockText(store) }}
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #grid="slotProps">
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                            <div
                                v-for="item in (slotProps.items as StockCheckProduct[])"
                                :key="item.id"
                                class="rounded-lg border border-surface-200 bg-surface-0 p-2 dark:border-surface-700 dark:bg-surface-900"
                            >
                                <!-- Image with preview -->
                                <div class="relative mb-2 aspect-square overflow-hidden rounded bg-surface-100 dark:bg-surface-800" @click.stop>
                                    <Image
                                        v-if="item.image_url"
                                        :src="item.image_url"
                                        :alt="item.product_name"
                                        image-class="h-full w-full object-cover cursor-pointer"
                                        :pt="{
                                            root: { class: 'h-full w-full' },
                                            previewMask: { class: 'rounded' },
                                        }"
                                        preview
                                    />
                                    <div v-else class="flex h-full w-full items-center justify-center">
                                        <i class="pi pi-box text-2xl text-muted-foreground" />
                                    </div>
                                </div>

                                <!-- Info -->
                                <div class="space-y-1">
                                    <div class="truncate text-xs font-medium" :title="item.product_name">
                                        {{ item.product_name }}
                                    </div>
                                    <div v-if="item.variant_name" class="truncate text-xs text-muted-foreground">
                                        {{ item.variant_name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">{{ item.product_number }}</div>

                                    <!-- Stock table -->
                                    <table class="text-xs">
                                        <tr v-for="store in getDisplayStores(item)" :key="store.store_id">
                                            <td class="pr-2 text-muted-foreground">{{ store.store_code }}</td>
                                            <td :class="getStockColorClass(store)" class="font-medium">
                                                {{ getStockText(store) }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #empty>
                        <div class="py-12 text-center">
                            <i class="pi pi-search mb-3 text-4xl text-muted-foreground" />
                            <p class="text-muted-foreground">No products found matching your search.</p>
                            <Button
                                size="small"
                                severity="secondary"
                                variant="outlined"
                                label="Clear Filters"
                                class="mt-3"
                                @click="clearFilters"
                            />
                        </div>
                    </template>
                </DataView>
            </div>

            <!-- Empty state: no search yet -->
            <div v-else class="py-16 text-center">
                <Search class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                <h2 class="text-lg font-medium">Check Stock Availability</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Search by product name, number, or scan a barcode to check stock levels across stores.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
