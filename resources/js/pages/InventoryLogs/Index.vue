<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, InventoryLog, PaginatedData } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, reactive, ref, watch } from 'vue';

interface Props {
    logs: PaginatedData<InventoryLog>;
    stores: Array<{ id: number; store_name: string; store_code: string }>;
    categories: Array<{ id: number; category_name: string }>;
    subcategories: Array<{ id: number; subcategory_name: string }>;
    activityCodes: Record<string, string>;
    filters?: Record<string, string>;
}

const props = defineProps<Props>();

const filters = reactive({
    search: props.filters?.search ?? '',
    store_id: props.filters?.store_id ?? '',
    activity_code: props.filters?.activity_code ?? '',
    category_id: props.filters?.category_id ?? '',
    subcategory_id: props.filters?.subcategory_id ?? '',
    start_date: props.filters?.start_date ?? '',
    end_date: props.filters?.end_date ?? '',
});

const perPage = ref(props.logs.per_page ?? 20);

const storeOptions = computed(() => [
    { label: 'All Stores', value: '' },
    ...props.stores.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: s.id,
    })),
]);

const activityCodeOptions = computed(() => [
    { label: 'All Activities', value: '' },
    ...Object.entries(props.activityCodes).map(([code, label]) => ({
        label: label,
        value: code,
    })),
]);

const categoryOptions = computed(() => [
    { label: 'All Categories', value: '' },
    ...props.categories.map((c) => ({ label: c.category_name, value: c.id })),
]);

const subcategoryOptions = computed(() => [
    { label: 'All Subcategories', value: '' },
    ...props.subcategories.map((s) => ({ label: s.subcategory_name, value: s.id })),
]);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(() => filters.search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
});

watch(() => filters.store_id, () => applyFilters());
watch(() => filters.activity_code, () => applyFilters());
watch(() => filters.category_id, () => {
    filters.subcategory_id = '';
    applyFilters();
});
watch(() => filters.subcategory_id, () => applyFilters());

function applyFilters() {
    const params: Record<string, string | number> = {};
    if (filters.search) params.search = filters.search;
    if (filters.store_id) params.store_id = filters.store_id;
    if (filters.activity_code) params.activity_code = filters.activity_code;
    if (filters.category_id) params.category_id = filters.category_id;
    if (filters.subcategory_id) params.subcategory_id = filters.subcategory_id;
    if (filters.start_date) params.start_date = filters.start_date;
    if (filters.end_date) params.end_date = filters.end_date;
    if (perPage.value !== 20) params.per_page = perPage.value;
    router.get('/inventory-logs', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.store_id = '';
    filters.activity_code = '';
    filters.category_id = '';
    filters.subcategory_id = '';
    filters.start_date = '';
    filters.end_date = '';
    router.get('/inventory-logs', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory Logs' },
];

const expandedRows = ref({});
const hasActiveFilters = computed(
    () => filters.search || filters.store_id || filters.activity_code || filters.category_id || filters.subcategory_id || filters.start_date || filters.end_date,
);

function getActivitySeverity(code: string): string {
    switch (code) {
        case 'FI':
        case 'DI':
        case 'PI': return 'success';
        case 'LI':
        case 'DO': return 'danger';
        case 'DRI':
        case 'DRO':
        case 'PRO': return 'warn';
        default: return 'info';
    }
}

function getSourceLink(log: InventoryLog): { label: string; url: string } | null {
    if (log.delivery_order) {
        return { label: log.delivery_order.order_number, url: `/delivery-orders/${log.delivery_order_id}` };
    }
    if (log.purchase_order) {
        return { label: log.purchase_order.order_number, url: `/purchase-orders/${log.purchase_order_id}` };
    }
    if (log.stocktake_id) {
        return { label: `Stocktake #${log.stocktake_id}`, url: `/management/stocktakes/${log.stocktake_id}` };
    }
    return null;
}

function onPage(event: { page: number; rows: number }) {
    perPage.value = event.rows;
    const params: Record<string, string | number> = { page: event.page + 1 };
    if (filters.search) params.search = filters.search;
    if (filters.store_id) params.store_id = filters.store_id;
    if (filters.activity_code) params.activity_code = filters.activity_code;
    if (filters.category_id) params.category_id = filters.category_id;
    if (filters.subcategory_id) params.subcategory_id = filters.subcategory_id;
    if (filters.start_date) params.start_date = filters.start_date;
    if (filters.end_date) params.end_date = filters.end_date;
    if (event.rows !== 20) params.per_page = event.rows;
    router.get('/inventory-logs', params, { preserveState: true });
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Inventory Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="heading-lg">Inventory Logs</h1>
            </div>

            <!-- Filter Section -->
            <div class="filter-section flex flex-col gap-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <IconField class="flex-1">
                        <InputIcon class="pi pi-search" />
                        <InputText
                            v-model="filters.search"
                            placeholder="Search by product name, number, or barcode..."
                            size="small"
                            fluid
                        />
                    </IconField>
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
                <div class="flex flex-wrap items-center gap-2">
                    <Select
                        v-model="filters.store_id"
                        :options="storeOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Store"
                        size="small"
                        filter
                        class="w-full sm:w-48"
                    />
                    <Select
                        v-model="filters.activity_code"
                        :options="activityCodeOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Activity"
                        size="small"
                        filter
                        class="w-full sm:w-40"
                    />
                    <Select
                        v-model="filters.category_id"
                        :options="categoryOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Category"
                        size="small"
                        filter
                        class="w-full sm:w-44"
                    />
                    <Select
                        v-if="filters.category_id"
                        v-model="filters.subcategory_id"
                        :options="subcategoryOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Subcategory"
                        size="small"
                        filter
                        class="w-full sm:w-44"
                    />
                </div>
            </div>

            <!-- Data Table -->
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="logs.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="perPage"
                :rows-per-page-options="[10, 20, 50, 100]"
                :total-records="logs.total"
                :first="(logs.current_page - 1) * perPage"
                @page="onPage"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No inventory logs found.
                    </div>
                </template>
                <Column expander class="w-12 !pr-0 md:hidden" />
                <Column header="Date" class="w-40">
                    <template #body="{ data }">
                        {{ formatDate(data.created_at) }}
                    </template>
                </Column>
                <Column header="Product">
                    <template #body="{ data }">
                        <div>
                            <div class="font-medium">{{ data.product?.product_name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ data.product?.product_number }}
                                <span v-if="data.product?.variant_name"> - {{ data.product.variant_name }}</span>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Store" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.store?.store_name ?? '-' }}
                    </template>
                </Column>
                <Column header="Activity">
                    <template #body="{ data }">
                        <Tag
                            :value="data.activity_label"
                            :severity="getActivitySeverity(data.activity_code)"
                        />
                    </template>
                </Column>
                <Column header="In" class="w-16 text-center">
                    <template #body="{ data }">
                        <span v-if="data.quantity_in > 0" class="font-medium text-green-600">
                            +{{ data.quantity_in }}
                        </span>
                        <span v-else>-</span>
                    </template>
                </Column>
                <Column header="Out" class="w-16 text-center">
                    <template #body="{ data }">
                        <span v-if="data.quantity_out > 0" class="font-medium text-red-600">
                            -{{ data.quantity_out }}
                        </span>
                        <span v-else>-</span>
                    </template>
                </Column>
                <Column header="Balance" class="w-20 text-center">
                    <template #body="{ data }">
                        {{ data.current_quantity }}
                    </template>
                </Column>
                <Column header="Source" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        <a
                            v-if="getSourceLink(data)"
                            :href="getSourceLink(data)!.url"
                            class="text-primary hover:underline"
                            @click.prevent="router.get(getSourceLink(data)!.url)"
                        >
                            {{ getSourceLink(data)!.label }}
                        </a>
                        <span v-else>-</span>
                    </template>
                </Column>
                <Column header="By" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        {{ data.created_by_user?.name ?? '-' }}
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm md:hidden">
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Store</span>
                            <span>{{ data.store?.store_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Source</span>
                            <span>
                                <a
                                    v-if="getSourceLink(data)"
                                    :href="getSourceLink(data)!.url"
                                    class="text-primary hover:underline"
                                    @click.prevent="router.get(getSourceLink(data)!.url)"
                                >
                                    {{ getSourceLink(data)!.label }}
                                </a>
                                <span v-else>-</span>
                            </span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Created By</span>
                            <span>{{ data.created_by_user?.name ?? '-' }}</span>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
