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
        employeePerformance?: EmployeePerformance[];
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
    { title: 'Employee Performance' },
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
    router.get('/analytics/employees', params, { preserveState: true });

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

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y' as const,
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true } },
};

const fmt = (n: number) => n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <Head title="Employee Performance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 class="heading-lg">Employee Performance</h1>

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
                <!-- Chart -->
                <div v-if="employees.length > 0" class="rounded-lg border border-border p-4">
                    <h3 class="mb-3 text-sm font-semibold">Sales by Employee</h3>
                    <div :style="{ height: Math.max(200, employees.length * 40) + 'px' }">
                        <Chart type="bar" :data="employeeChartData" :options="chartOptions" class="h-full" />
                    </div>
                </div>

                <!-- Table -->
                <DataTable
                    :value="employees"
                    striped-rows
                    size="small"
                    class="overflow-hidden rounded-lg border border-border"
                >
                    <template #empty>
                        <div class="p-4 text-center text-muted-foreground">No data for this period.</div>
                    </template>
                    <Column header="#" :style="{ width: '3rem' }">
                        <template #body="{ index }">{{ index + 1 }}</template>
                    </Column>
                    <Column header="Employee" field="employee_name" />
                    <Column header="Total Sales">
                        <template #body="{ data }">
                            {{ currencySymbol }}{{ fmt(data.total_sales) }}
                        </template>
                    </Column>
                    <Column header="Transactions" field="transaction_count" class="hidden sm:table-cell" />
                    <Column header="Avg Order" class="hidden sm:table-cell">
                        <template #body="{ data }">
                            {{ currencySymbol }}{{ fmt(data.avg_order_value) }}
                        </template>
                    </Column>
                </DataTable>
            </template>
        </div>
    </AppLayout>
</template>
