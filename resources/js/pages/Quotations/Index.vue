<script setup lang="ts">
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, PaginatedData, Quotation } from '@/types';
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
    quotations: PaginatedData<Quotation>;
    companies: Array<{ id: number; company_name: string }>;
    filters?: Record<string, string>;
    statusOptions: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();

const { canAccessPage } = usePermissions();
const canCreate = computed(() => canAccessPage('quotations.create'));

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    company_id: props.filters?.company_id ?? '',
});

const perPage = ref(props.quotations.per_page ?? 15);

const statusFilterOptions = computed(() => [
    { label: 'All Statuses', value: '' },
    ...props.statusOptions,
]);

const companyOptions = computed(() => [
    { label: 'All Companies', value: '' },
    ...props.companies.map((c) => ({
        label: c.company_name,
        value: c.id,
    })),
]);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(() => filters.search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
});

watch(() => filters.status, () => applyFilters());
watch(() => filters.company_id, () => applyFilters());

function applyFilters() {
    const params: Record<string, string | number> = {};
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.company_id) params.company_id = filters.company_id;
    if (perPage.value !== 15) params.per_page = perPage.value;
    router.get('/quotations', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    filters.company_id = '';
    router.get('/quotations', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Quotations' },
];

const expandedRows = ref({});
const hasActiveFilters = computed(
    () => filters.search || filters.status || filters.company_id,
);

function getStatusSeverity(status: string): string {
    switch (status) {
        case 'draft': return 'secondary';
        case 'sent': return 'info';
        case 'viewed': return 'warn';
        case 'signed': return 'success';
        case 'accepted': return 'success';
        case 'paid': return 'contrast';
        case 'expired': return 'danger';
        default: return 'info';
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

function onRowClick(event: any) {
    router.get(`/quotations/${event.data.id}`);
}

function onPage(event: { page: number; rows: number }) {
    perPage.value = event.rows;
    const params: Record<string, string | number> = { page: event.page + 1 };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.company_id) params.company_id = filters.company_id;
    if (event.rows !== 15) params.per_page = event.rows;
    router.get('/quotations', params, { preserveState: true });
}
</script>

<template>
    <Head title="Quotations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="heading-lg">Quotations</h1>
                <Button
                    v-if="canCreate"
                    label="New Quotation"
                    icon="pi pi-plus"
                    size="small"
                    @click="router.get('/quotations/create')"
                />
            </div>

            <!-- Filter Section -->
            <div class="filter-section flex flex-col gap-3 sm:flex-row sm:items-center">
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        placeholder="Search by quotation number..."
                        size="small"
                        fluid
                    />
                </IconField>
                <div class="flex flex-wrap items-center gap-2">
                    <Select
                        v-model="filters.status"
                        :options="statusFilterOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Status"
                        size="small"
                        class="w-full sm:w-36"
                    />
                    <Select
                        v-model="filters.company_id"
                        :options="companyOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Company"
                        size="small"
                        filter
                        class="w-full sm:w-44"
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
                :value="quotations.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="perPage"
                :rows-per-page-options="[10, 15, 25, 50]"
                :total-records="quotations.total"
                :first="(quotations.current_page - 1) * perPage"
                @page="onPage"
                @row-click="onRowClick"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No quotations found.
                    </div>
                </template>
                <Column expander class="w-[12%] sm:w-12 !pr-0 md:hidden" />
                <Column field="quotation_number" header="Number">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.quotation_number }}</span>
                    </template>
                </Column>
                <Column header="Company" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        {{ data.company?.company_name ?? '-' }}
                    </template>
                </Column>
                <Column header="Customer" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.customer?.full_name ?? '-' }}
                    </template>
                </Column>
                <Column header="Status">
                    <template #body="{ data }">
                        <Tag :value="data.status_label" :severity="getStatusSeverity(data.status)" />
                    </template>
                </Column>
                <Column header="Items" class="hidden sm:table-cell w-20">
                    <template #body="{ data }">
                        {{ data.item_count ?? 0 }}
                    </template>
                </Column>
                <Column header="Created" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        <span class="text-sm">{{ formatDate(data.created_at) }}</span>
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm md:hidden">
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Company</span>
                            <span>{{ data.company?.company_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Customer</span>
                            <span>{{ data.customer?.full_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Items</span>
                            <span>{{ data.item_count ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Created</span>
                            <span>{{ formatDate(data.created_at) }}</span>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
