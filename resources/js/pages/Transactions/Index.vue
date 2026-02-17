<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, PaginatedData, Transaction } from '@/types';
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
    transactions: PaginatedData<Transaction>;
    stores: Array<{ id: number; store_name: string; store_code: string }>;
    statuses: Array<{ value: string; label: string }>;
    filters?: Record<string, string>;
}

const props = defineProps<Props>();

const filters = reactive({
    search: props.filters?.search ?? '',
    store_id: props.filters?.store_id ?? '',
    status: props.filters?.status ?? '',
});

const perPage = ref(props.transactions.per_page ?? 20);

const storeOptions = computed(() => [
    { label: 'All Stores', value: '' },
    ...props.stores.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: s.id,
    })),
]);

const statusOptions = computed(() => [
    { label: 'All Statuses', value: '' },
    ...props.statuses,
]);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(() => filters.search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
});

watch(() => filters.store_id, () => applyFilters());
watch(() => filters.status, () => applyFilters());

function applyFilters() {
    const params: Record<string, string | number> = {};
    if (filters.search) params.search = filters.search;
    if (filters.store_id) params.store_id = filters.store_id;
    if (filters.status) params.status = filters.status;
    if (perPage.value !== 20) params.per_page = perPage.value;
    router.get('/transactions', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.store_id = '';
    filters.status = '';
    router.get('/transactions', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transactions' },
];

const expandedRows = ref({});
const hasActiveFilters = computed(
    () => filters.search || filters.store_id || filters.status,
);

function getStatusSeverity(status: string): string {
    switch (status) {
        case 'draft': return 'info';
        case 'suspended': return 'warn';
        case 'completed': return 'success';
        case 'voided': return 'danger';
        default: return 'info';
    }
}

function getStatusLabel(status: string): string {
    switch (status) {
        case 'draft': return 'Draft';
        case 'suspended': return 'Suspended';
        case 'completed': return 'Completed';
        case 'voided': return 'Voided';
        default: return status;
    }
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatDateTime(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function fmt(amount: string | number | null | undefined, symbol: string): string {
    if (amount === null || amount === undefined) return `${symbol}0.00`;
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return `${symbol}${num.toFixed(2)}`;
}

function onRowClick(event: any) {
    router.get(`/transactions/${event.data.id}`);
}

function onPage(event: { page: number; rows: number }) {
    perPage.value = event.rows;
    const params: Record<string, string | number> = { page: event.page + 1 };
    if (filters.search) params.search = filters.search;
    if (filters.store_id) params.store_id = filters.store_id;
    if (filters.status) params.status = filters.status;
    if (event.rows !== 20) params.per_page = event.rows;
    router.get('/transactions', params, { preserveState: true });
}
</script>

<template>
    <Head title="Transactions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="heading-lg">Transactions</h1>
            </div>

            <!-- Filter Section -->
            <div class="filter-section flex flex-col gap-3 sm:flex-row sm:items-center">
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        placeholder="Search by transaction number, customer..."
                        size="small"
                        fluid
                    />
                </IconField>
                <div class="flex flex-wrap items-center gap-2">
                    <Select
                        v-model="filters.store_id"
                        :options="storeOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Store"
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

            <!-- Data Table -->
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="transactions.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="perPage"
                :rows-per-page-options="[10, 20, 50]"
                :total-records="transactions.total"
                :first="(transactions.current_page - 1) * perPage"
                @page="onPage"
                @row-click="onRowClick"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No transactions found.
                    </div>
                </template>
                <Column expander class="w-[12%] sm:w-12 !pr-0 lg:hidden" />
                <Column field="transaction_number" header="Number">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.transaction_number }}</span>
                    </template>
                </Column>
                <Column header="Store" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        {{ data.store?.store_name ?? '-' }}
                    </template>
                </Column>
                <Column header="Status">
                    <template #body="{ data }">
                        <Tag :value="getStatusLabel(data.status)" :severity="getStatusSeverity(data.status)" />
                    </template>
                </Column>
                <Column header="Total" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ fmt(data.total, data.currency?.symbol ?? '$') }}</span>
                    </template>
                </Column>
                <Column header="Employee" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.employee?.full_name ?? '-' }}
                    </template>
                </Column>
                <Column header="Customer" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        {{ data.customer?.full_name ?? '-' }}
                    </template>
                </Column>
                <Column header="Items" class="hidden md:table-cell w-16">
                    <template #body="{ data }">
                        {{ data.items_count ?? 0 }}
                    </template>
                </Column>
                <Column header="Date" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        <span class="text-sm">{{ formatDate(data.checkout_date || data.created_at) }}</span>
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm lg:hidden">
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Store</span>
                            <span>{{ data.store?.store_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Total</span>
                            <span class="font-semibold">{{ fmt(data.total, data.currency?.symbol ?? '$') }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Employee</span>
                            <span>{{ data.employee?.full_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Customer</span>
                            <span>{{ data.customer?.full_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Items</span>
                            <span>{{ data.items_count ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Date</span>
                            <span>{{ formatDateTime(data.checkout_date || data.created_at) }}</span>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
