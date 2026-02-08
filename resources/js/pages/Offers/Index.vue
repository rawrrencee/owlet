<script setup lang="ts">
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Offer, PaginatedData } from '@/types';
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
    offers: PaginatedData<Offer>;
    stores: Array<{ id: number; store_name: string; store_code: string }>;
    filters?: Record<string, string>;
    typeOptions: Array<{ value: string; label: string }>;
    statusOptions: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();

const { canAccessPage } = usePermissions();
const canManage = computed(() => canAccessPage('offers.manage'));

const filters = reactive({
    search: props.filters?.search ?? '',
    type: props.filters?.type ?? '',
    status: props.filters?.status ?? '',
    store_id: props.filters?.store_id ?? '',
});

const perPage = ref(props.offers.per_page ?? 15);

const typeFilterOptions = computed(() => [
    { label: 'All Types', value: '' },
    ...props.typeOptions,
]);

const statusFilterOptions = computed(() => [
    { label: 'All Statuses', value: '' },
    ...props.statusOptions,
]);

const storeOptions = computed(() => [
    { label: 'All Stores', value: '' },
    ...props.stores.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: s.id,
    })),
]);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(() => filters.search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
});

watch(() => filters.type, () => applyFilters());
watch(() => filters.status, () => applyFilters());
watch(() => filters.store_id, () => applyFilters());

function applyFilters() {
    const params: Record<string, string | number> = {};
    if (filters.search) params.search = filters.search;
    if (filters.type) params.type = filters.type;
    if (filters.status) params.status = filters.status;
    if (filters.store_id) params.store_id = filters.store_id;
    if (perPage.value !== 15) params.per_page = perPage.value;
    router.get('/offers', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.type = '';
    filters.status = '';
    filters.store_id = '';
    router.get('/offers', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Offers' },
];

const expandedRows = ref({});
const hasActiveFilters = computed(
    () => filters.search || filters.type || filters.status || filters.store_id,
);

function getStatusSeverity(status: string): string {
    switch (status) {
        case 'draft': return 'secondary';
        case 'scheduled': return 'info';
        case 'active': return 'success';
        case 'expired': return 'warn';
        case 'disabled': return 'danger';
        default: return 'info';
    }
}

function getTypeSeverity(type: string): string {
    switch (type) {
        case 'product': return 'info';
        case 'bundle': return 'warn';
        case 'minimum_spend': return 'success';
        case 'category': return 'secondary';
        case 'brand': return 'contrast';
        default: return 'info';
    }
}

function formatDiscount(offer: Offer): string {
    if (offer.discount_type === 'percentage') {
        return `${offer.discount_percentage}%`;
    }
    if (offer.amounts && offer.amounts.length > 0) {
        return offer.amounts
            .filter((a) => a.discount_amount)
            .map((a) => `${a.currency?.symbol ?? ''}${Number(a.discount_amount).toFixed(2)}`)
            .join(', ') || '-';
    }
    return '-';
}

function formatDateRange(offer: Offer): string {
    if (!offer.starts_at && !offer.ends_at) return 'No dates set';
    const fmt = (d: string) => new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    if (offer.starts_at && offer.ends_at) return `${fmt(offer.starts_at)} - ${fmt(offer.ends_at)}`;
    if (offer.starts_at) return `From ${fmt(offer.starts_at)}`;
    return `Until ${fmt(offer.ends_at!)}`;
}

function formatStoreScope(offer: Offer): string {
    if (offer.apply_to_all_stores) return 'All Stores';
    if (offer.stores && offer.stores.length > 0) {
        return offer.stores.map((s) => s.store_code).join(', ');
    }
    return '-';
}

function onRowClick(event: any) {
    router.get(`/offers/${event.data.id}`);
}

function onPage(event: { page: number; rows: number }) {
    perPage.value = event.rows;
    const params: Record<string, string | number> = { page: event.page + 1 };
    if (filters.search) params.search = filters.search;
    if (filters.type) params.type = filters.type;
    if (filters.status) params.status = filters.status;
    if (filters.store_id) params.store_id = filters.store_id;
    if (event.rows !== 15) params.per_page = event.rows;
    router.get('/offers', params, { preserveState: true });
}
</script>

<template>
    <Head title="Offers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="heading-lg">Offers</h1>
                <Button
                    v-if="canManage"
                    label="New Offer"
                    icon="pi pi-plus"
                    size="small"
                    @click="router.get('/offers/create')"
                />
            </div>

            <!-- Filter Section -->
            <div class="filter-section flex flex-col gap-3 sm:flex-row sm:items-center">
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        placeholder="Search by name or code..."
                        size="small"
                        fluid
                    />
                </IconField>
                <div class="flex flex-wrap items-center gap-2">
                    <Select
                        v-model="filters.type"
                        :options="typeFilterOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Type"
                        size="small"
                        class="w-full sm:w-40"
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
                    <Select
                        v-model="filters.store_id"
                        :options="storeOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Store"
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
                :value="offers.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="perPage"
                :rows-per-page-options="[10, 15, 25, 50]"
                :total-records="offers.total"
                :first="(offers.current_page - 1) * perPage"
                @page="onPage"
                @row-click="onRowClick"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No offers found.
                    </div>
                </template>
                <Column expander class="w-12 !pr-0 md:hidden" />
                <Column field="name" header="Name">
                    <template #body="{ data }">
                        <div>
                            <span class="font-medium">{{ data.name }}</span>
                            <div v-if="data.code" class="text-xs text-muted-foreground">{{ data.code }}</div>
                        </div>
                    </template>
                </Column>
                <Column header="Type" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        <Tag :value="data.type_label" :severity="getTypeSeverity(data.type)" />
                    </template>
                </Column>
                <Column header="Discount" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        {{ formatDiscount(data) }}
                    </template>
                </Column>
                <Column header="Status">
                    <template #body="{ data }">
                        <Tag :value="data.status_label" :severity="getStatusSeverity(data.status)" />
                    </template>
                </Column>
                <Column header="Date Range" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        <span class="text-sm">{{ formatDateRange(data) }}</span>
                    </template>
                </Column>
                <Column header="Stores" class="hidden xl:table-cell">
                    <template #body="{ data }">
                        <span class="text-sm">{{ formatStoreScope(data) }}</span>
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm md:hidden">
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Type</span>
                            <Tag :value="data.type_label" :severity="getTypeSeverity(data.type)" />
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Discount</span>
                            <span>{{ formatDiscount(data) }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Date Range</span>
                            <span>{{ formatDateRange(data) }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Stores</span>
                            <span>{{ formatStoreScope(data) }}</span>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
