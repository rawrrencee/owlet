<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Chart from 'primevue/chart';
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

interface Widgets {
    total_sales: number;
    transaction_count: number;
    avg_order_value: number;
    total_refunds: number;
    refund_count: number;
    prev_total_sales: number;
    prev_transaction_count: number;
    prev_avg_order_value: number;
}

interface SalesOverTime {
    period: string;
    label: string;
    total_sales: number;
    count: number;
}

interface SalesByStore {
    store_name: string;
    store_code: string;
    total_sales: number;
    count: number;
}

interface TopProduct {
    product_id: number;
    product_name: string;
    variant_name: string | null;
    total_qty: number;
    total_revenue: number;
}

interface PaymentMethodData {
    payment_method: string;
    total_amount: number;
    count: number;
}

interface DiscountBreakdown {
    label: string;
    amount: number;
}

interface EmployeePerformance {
    employee_id: number;
    employee_name: string;
    total_sales: number;
    transaction_count: number;
    avg_order_value: number;
}

interface Props {
    stores: StoreOption[];
    currencies: CurrencyOption[];
    data: {
        widgets?: Widgets;
        salesOverTime?: SalesOverTime[];
        salesByStore?: SalesByStore[];
        topProducts?: TopProduct[];
        salesByPaymentMethod?: PaymentMethodData[];
        discountBreakdown?: DiscountBreakdown[];
        employeePerformance?: EmployeePerformance[];
    };
    filters: {
        from: string;
        to: string;
        store_id: number | null;
        currency_id: number | null;
        granularity: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Analytics' },
];

const filters = reactive({
    from: props.filters.from,
    to: props.filters.to,
    store_id: props.filters.store_id ? String(props.filters.store_id) : '',
    currency_id: props.filters.currency_id ? String(props.filters.currency_id) : '',
    granularity: props.filters.granularity,
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

const granularityOptions = [
    { label: 'Daily', value: 'daily' },
    { label: 'Weekly', value: 'weekly' },
    { label: 'Monthly', value: 'monthly' },
];

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
    if (filters.granularity) params.granularity = filters.granularity;
    router.get('/analytics', params, { preserveState: true });

    // Persist filter preferences in background
    router.post('/analytics/preferences', {
        store_id: filters.store_id || null,
        currency_id: filters.currency_id || null,
        granularity: filters.granularity || null,
    }, { preserveState: true, preserveScroll: true, only: [] });
}

watch(() => filters.store_id, applyFilters);
watch(() => filters.currency_id, applyFilters);
watch(() => filters.granularity, applyFilters);
watch(dateRange, (val) => {
    if (val[0] && val[1]) {
        applyFilters();
    }
});

const widgets = computed(() => props.data.widgets);

function percentChange(current: number, previous: number): string {
    if (previous === 0) return current > 0 ? '+100%' : '0%';
    const change = ((current - previous) / previous) * 100;
    const sign = change > 0 ? '+' : '';
    return `${sign}${change.toFixed(1)}%`;
}

function changeClass(current: number, previous: number): string {
    if (current > previous) return 'text-green-600';
    if (current < previous) return 'text-red-600';
    return 'text-muted-foreground';
}

// Sales over time chart
const salesOverTimeChartData = computed(() => {
    const data = props.data.salesOverTime ?? [];
    return {
        labels: data.map((d) => d.label),
        datasets: [
            {
                label: 'Sales',
                data: data.map((d) => d.total_sales),
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
            },
        ],
    };
});

const salesOverTimeOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true },
    },
};

// Sales by store chart
const salesByStoreChartData = computed(() => {
    const data = props.data.salesByStore ?? [];
    return {
        labels: data.map((d) => `${d.store_name}`),
        datasets: [
            {
                label: 'Revenue',
                data: data.map((d) => d.total_sales),
                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'],
            },
        ],
    };
});

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true },
    },
};

// Payment method doughnut
const paymentChartData = computed(() => {
    const data = props.data.salesByPaymentMethod ?? [];
    return {
        labels: data.map((d) => d.payment_method),
        datasets: [
            {
                data: data.map((d) => d.total_amount),
                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'],
            },
        ],
    };
});

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' as const },
    },
};

// Employee performance chart
const employees = computed(() => props.data.employeePerformance ?? []);

const employeeChartData = computed(() => ({
    labels: employees.value.map((e) => e.employee_name),
    datasets: [
        {
            label: 'Total Sales',
            data: employees.value.map((e) => e.total_sales),
            backgroundColor: '#3B82F6',
        },
    ],
}));

const employeeChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y' as const,
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true } },
};

const fmt = (n: number) => n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <Head title="Analytics" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 class="heading-lg">Analytics</h1>

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
                <div>
                    <label class="mb-1 block text-xs font-medium">Granularity</label>
                    <Select
                        v-model="filters.granularity"
                        :options="granularityOptions"
                        optionLabel="label"
                        optionValue="value"
                        size="small"
                        class="w-32"
                    />
                </div>
            </div>

            <div v-if="!filters.currency_id" class="rounded-lg border border-border p-8 text-center text-muted-foreground">
                Please select a currency to view analytics.
            </div>

            <template v-else>
                <!-- Widget Cards -->
                <div v-if="widgets" class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div class="rounded-lg border border-border p-4">
                        <div class="text-xs text-muted-foreground">Total Sales</div>
                        <div class="mt-1 text-xl font-bold">{{ currencySymbol }}{{ fmt(widgets.total_sales) }}</div>
                        <div :class="changeClass(widgets.total_sales, widgets.prev_total_sales)" class="mt-1 text-xs">
                            {{ percentChange(widgets.total_sales, widgets.prev_total_sales) }} vs prev period
                        </div>
                    </div>
                    <div class="rounded-lg border border-border p-4">
                        <div class="text-xs text-muted-foreground">Transactions</div>
                        <div class="mt-1 text-xl font-bold">{{ widgets.transaction_count }}</div>
                        <div :class="changeClass(widgets.transaction_count, widgets.prev_transaction_count)" class="mt-1 text-xs">
                            {{ percentChange(widgets.transaction_count, widgets.prev_transaction_count) }} vs prev period
                        </div>
                    </div>
                    <div class="rounded-lg border border-border p-4">
                        <div class="text-xs text-muted-foreground">Avg Order Value</div>
                        <div class="mt-1 text-xl font-bold">{{ currencySymbol }}{{ fmt(widgets.avg_order_value) }}</div>
                        <div :class="changeClass(widgets.avg_order_value, widgets.prev_avg_order_value)" class="mt-1 text-xs">
                            {{ percentChange(widgets.avg_order_value, widgets.prev_avg_order_value) }} vs prev period
                        </div>
                    </div>
                    <div class="rounded-lg border border-border p-4">
                        <div class="text-xs text-muted-foreground">Refunds</div>
                        <div class="mt-1 text-xl font-bold">{{ currencySymbol }}{{ fmt(widgets.total_refunds) }}</div>
                        <div class="mt-1 text-xs text-muted-foreground">{{ widgets.refund_count }} refund(s)</div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <!-- Sales Over Time -->
                    <div class="rounded-lg border border-border p-4">
                        <h3 class="mb-3 text-sm font-semibold">Sales Over Time</h3>
                        <div class="h-64">
                            <Chart type="line" :data="salesOverTimeChartData" :options="salesOverTimeOptions" class="h-full" />
                        </div>
                    </div>

                    <!-- Sales By Store -->
                    <div class="rounded-lg border border-border p-4">
                        <h3 class="mb-3 text-sm font-semibold">Sales by Store</h3>
                        <div class="h-64">
                            <Chart type="bar" :data="salesByStoreChartData" :options="barChartOptions" class="h-full" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <!-- Payment Methods -->
                    <div class="rounded-lg border border-border p-4">
                        <h3 class="mb-3 text-sm font-semibold">Payment Methods</h3>
                        <div class="h-64">
                            <Chart type="doughnut" :data="paymentChartData" :options="doughnutOptions" class="h-full" />
                        </div>
                    </div>

                    <!-- Discount Breakdown -->
                    <div class="rounded-lg border border-border p-4">
                        <h3 class="mb-3 text-sm font-semibold">Discount Breakdown</h3>
                        <div class="space-y-3">
                            <div v-for="d in data.discountBreakdown" :key="d.label" class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">{{ d.label }}</span>
                                <span class="font-medium">{{ currencySymbol }}{{ fmt(d.amount) }}</span>
                            </div>
                            <div v-if="data.discountBreakdown" class="flex items-center justify-between border-t pt-2 text-sm font-semibold">
                                <span>Total Discounts</span>
                                <span>{{ currencySymbol }}{{ fmt(data.discountBreakdown.reduce((s, d) => s + d.amount, 0)) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products Preview -->
                    <div class="rounded-lg border border-border p-4">
                        <h3 class="mb-3 text-sm font-semibold">Top Products</h3>
                        <div class="space-y-2">
                            <div v-for="(p, i) in (data.topProducts ?? []).slice(0, 5)" :key="p.product_id" class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2 truncate">
                                    <span class="text-xs text-muted-foreground">{{ i + 1 }}.</span>
                                    <span class="truncate">{{ p.product_name }}<span v-if="p.variant_name" class="text-muted-foreground"> - {{ p.variant_name }}</span></span>
                                </div>
                                <span class="shrink-0 font-medium">{{ currencySymbol }}{{ fmt(p.total_revenue) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Products Table -->
                <div class="rounded-lg border border-border">
                    <h3 class="p-4 pb-2 text-sm font-semibold">Top Products (Full List)</h3>
                    <DataTable
                        :value="data.topProducts ?? []"
                        striped-rows
                        size="small"
                    >
                        <Column header="#" :style="{ width: '3rem' }">
                            <template #body="{ index }">{{ index + 1 }}</template>
                        </Column>
                        <Column header="Product">
                            <template #body="{ data: p }">
                                {{ p.product_name }}
                                <span v-if="p.variant_name" class="text-muted-foreground"> - {{ p.variant_name }}</span>
                            </template>
                        </Column>
                        <Column header="Qty Sold" field="total_qty" class="hidden sm:table-cell" />
                        <Column header="Revenue">
                            <template #body="{ data: p }">
                                {{ currencySymbol }}{{ fmt(p.total_revenue) }}
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <!-- Employee Performance -->
                <template v-if="employees.length > 0">
                    <div class="rounded-lg border border-border p-4">
                        <h3 class="mb-3 text-sm font-semibold">Sales by Employee</h3>
                        <div :style="{ height: Math.max(200, employees.length * 40) + 'px' }">
                            <Chart type="bar" :data="employeeChartData" :options="employeeChartOptions" class="h-full" />
                        </div>
                    </div>

                    <div class="rounded-lg border border-border">
                        <h3 class="p-4 pb-2 text-sm font-semibold">Employee Performance</h3>
                        <DataTable
                            :value="employees"
                            striped-rows
                            size="small"
                        >
                            <Column header="#" :style="{ width: '3rem' }">
                                <template #body="{ index }">{{ index + 1 }}</template>
                            </Column>
                            <Column header="Employee" field="employee_name" />
                            <Column header="Total Sales">
                                <template #body="{ data: e }">
                                    {{ currencySymbol }}{{ fmt(e.total_sales) }}
                                </template>
                            </Column>
                            <Column header="Transactions" field="transaction_count" class="hidden sm:table-cell" />
                            <Column header="Avg Order" class="hidden sm:table-cell">
                                <template #body="{ data: e }">
                                    {{ currencySymbol }}{{ fmt(e.avg_order_value) }}
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </template>
        </div>
    </AppLayout>
</template>
