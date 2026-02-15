<script setup lang="ts">
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    BreadcrumbItem,
    DeliveryOrder,
    PaginatedData,
} from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, reactive, ref, watch } from 'vue';

interface Props {
    orders: PaginatedData<DeliveryOrder>;
    stores: Array<{ id: number; store_name: string; store_code: string }>;
    filters?: Record<string, string>;
    statusOptions: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();

const { canAccessPage } = usePermissions();
const canSubmit = computed(() => canAccessPage('delivery_orders.submit'));

const filters = reactive({
    search: props.filters?.search ?? '',
    store_id: props.filters?.store_id ?? '',
    status: props.filters?.status ?? '',
    start_date: props.filters?.start_date ?? '',
    end_date: props.filters?.end_date ?? '',
});

const perPage = ref(props.orders.per_page ?? 15);

const storeOptions = computed(() => [
    { label: 'All Stores', value: '' },
    ...props.stores.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: s.id,
    })),
]);

const statusFilterOptions = computed(() => [
    { label: 'All Statuses', value: '' },
    ...props.statusOptions,
]);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(() => filters.search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
});

watch(() => filters.store_id, () => applyFilters());
watch(() => filters.status, () => applyFilters());
watch(() => filters.start_date, () => applyFilters());
watch(() => filters.end_date, () => applyFilters());

function applyFilters() {
    const params: Record<string, string | number> = {};
    if (filters.search) params.search = filters.search;
    if (filters.store_id) params.store_id = filters.store_id;
    if (filters.status) params.status = filters.status;
    if (filters.start_date) params.start_date = filters.start_date;
    if (filters.end_date) params.end_date = filters.end_date;
    if (perPage.value !== 15) params.per_page = perPage.value;
    router.get('/delivery-orders', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.store_id = '';
    filters.status = '';
    filters.start_date = '';
    filters.end_date = '';
    router.get('/delivery-orders', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Delivery Orders' },
];

const expandedRows = ref({});
const hasActiveFilters = computed(
    () => filters.search || filters.store_id || filters.status || filters.start_date || filters.end_date,
);

function getStatusSeverity(status: string): string {
    switch (status) {
        case 'draft': return 'secondary';
        case 'submitted': return 'warn';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        default: return 'info';
    }
}

function onRowClick(event: any) {
    router.get(`/delivery-orders/${event.data.id}`);
}

function onPage(event: { page: number; rows: number }) {
    perPage.value = event.rows;
    const params: Record<string, string | number> = { page: event.page + 1 };
    if (filters.search) params.search = filters.search;
    if (filters.store_id) params.store_id = filters.store_id;
    if (filters.status) params.status = filters.status;
    if (filters.start_date) params.start_date = filters.start_date;
    if (filters.end_date) params.end_date = filters.end_date;
    if (event.rows !== 15) params.per_page = event.rows;
    router.get('/delivery-orders', params, { preserveState: true });
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Delivery Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="heading-lg">Delivery Orders</h1>
                <Button
                    v-if="canSubmit"
                    label="New Delivery Order"
                    icon="pi pi-plus"
                    size="small"
                    @click="router.get('/delivery-orders/create')"
                />
            </div>

            <!-- Filter Section -->
            <div class="filter-section flex flex-col gap-3 sm:flex-row sm:items-center">
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        placeholder="Search by order number..."
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
                        filter
                        class="w-full sm:w-48"
                    />
                    <Select
                        v-model="filters.status"
                        :options="statusFilterOptions"
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
                :value="orders.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="perPage"
                :rows-per-page-options="[10, 15, 25, 50]"
                :total-records="orders.total"
                :first="(orders.current_page - 1) * perPage"
                @page="onPage"
                @row-click="onRowClick"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No delivery orders found.
                    </div>
                </template>
                <Column expander class="w-[12%] sm:w-12 !pr-0 md:hidden" />
                <Column field="order_number" header="Order #">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.order_number }}</span>
                    </template>
                </Column>
                <Column header="From Store" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        {{ data.store_from?.store_name ?? '-' }}
                    </template>
                </Column>
                <Column header="To Store" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        {{ data.store_to?.store_name ?? '-' }}
                    </template>
                </Column>
                <Column header="Status">
                    <template #body="{ data }">
                        <Tag
                            :value="data.status_label"
                            :severity="getStatusSeverity(data.status)"
                        />
                    </template>
                </Column>
                <Column header="Items" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.items_count ?? 0 }}
                    </template>
                </Column>
                <Column header="Submitted" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        {{ formatDate(data.submitted_at) }}
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm md:hidden">
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">From</span>
                            <span>{{ data.store_from?.store_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">To</span>
                            <span>{{ data.store_to?.store_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Items</span>
                            <span>{{ data.items_count ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Submitted</span>
                            <span>{{ formatDate(data.submitted_at) }}</span>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
