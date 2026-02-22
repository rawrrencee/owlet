<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Select from 'primevue/select';
import { computed, reactive, ref, watch } from 'vue';

interface StoreOption {
    id: number;
    store_name: string;
    store_code: string;
}

interface CurrencyOption {
    id: number;
    code: string;
    symbol: string;
    name: string;
}

interface TopProduct {
    product_id: number;
    product_name: string;
    variant_name: string | null;
    total_qty: number;
    total_revenue: number;
}

interface Props {
    stores: StoreOption[];
    currencies: CurrencyOption[];
    data: {
        topProducts?: TopProduct[];
    };
    filters: {
        from: string;
        to: string;
        store_id: number | null;
        currency_id: number | null;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Analytics', href: '/analytics' },
    { title: 'Product Analytics' },
];

const filters = reactive({
    from: props.filters.from,
    to: props.filters.to,
    store_id: props.filters.store_id ? String(props.filters.store_id) : '',
    currency_id: props.filters.currency_id ? String(props.filters.currency_id) : '',
});

const dateRange = ref<Date[]>([
    new Date(props.filters.from),
    new Date(props.filters.to),
]);

const storeOptions = [
    { label: 'All Stores', value: '' },
    ...props.stores.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: String(s.id),
    })),
];

const currencyOptions = props.currencies.map((c) => ({
    label: `${c.code} (${c.symbol})`,
    value: String(c.id),
}));

const currencySymbol = computed(() => {
    const c = props.currencies.find((c) => String(c.id) === filters.currency_id);
    return c?.symbol ?? '$';
});

function applyFilters() {
    const params: Record<string, string> = {};
    if (dateRange.value[0]) params.from = dateRange.value[0].toISOString().split('T')[0];
    if (dateRange.value[1]) params.to = dateRange.value[1].toISOString().split('T')[0];
    if (filters.store_id) params.store_id = filters.store_id;
    if (filters.currency_id) params.currency_id = filters.currency_id;
    router.get('/analytics/products', params, { preserveState: true });

    // Persist filter preferences in background
    router.post('/analytics/preferences', {
        store_id: filters.store_id || null,
        currency_id: filters.currency_id || null,
    }, { preserveState: true, preserveScroll: true, only: [] });
}

watch(() => filters.store_id, applyFilters);
watch(() => filters.currency_id, applyFilters);
watch(dateRange, (val) => {
    if (val[0] && val[1]) applyFilters();
});

const fmt = (n: number) => n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <Head title="Product Analytics" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 class="heading-lg">Product Analytics</h1>

            <!-- Filters -->
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium">Date Range</label>
                    <DatePicker
                        v-model="dateRange"
                        selectionMode="range"
                        dateFormat="dd M yy"
                        size="small"
                        showIcon
                        class="w-64"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Store</label>
                    <Select
                        v-model="filters.store_id"
                        :options="storeOptions"
                        optionLabel="label"
                        optionValue="value"
                        size="small"
                        class="w-48"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Currency</label>
                    <Select
                        v-model="filters.currency_id"
                        :options="currencyOptions"
                        optionLabel="label"
                        optionValue="value"
                        size="small"
                        class="w-36"
                    />
                </div>
            </div>

            <div v-if="!filters.currency_id" class="rounded-lg border border-border p-8 text-center text-muted-foreground">
                Please select a currency to view analytics.
            </div>

            <template v-else>
                <DataTable
                    :value="data.topProducts ?? []"
                    striped-rows
                    size="small"
                    class="overflow-hidden rounded-lg border border-border"
                >
                    <template #empty>
                        <div class="p-4 text-center text-muted-foreground">No product data for this period.</div>
                    </template>
                    <Column header="#" :style="{ width: '3rem' }">
                        <template #body="{ index }">{{ index + 1 }}</template>
                    </Column>
                    <Column header="Product">
                        <template #body="{ data: p }">
                            {{ p.product_name }}
                            <span v-if="p.variant_name" class="text-muted-foreground"> - {{ p.variant_name }}</span>
                        </template>
                    </Column>
                    <Column header="Qty Sold" field="total_qty" />
                    <Column header="Revenue">
                        <template #body="{ data: p }">
                            {{ currencySymbol }}{{ fmt(p.total_revenue) }}
                        </template>
                    </Column>
                </DataTable>
            </template>
        </div>
    </AppLayout>
</template>
