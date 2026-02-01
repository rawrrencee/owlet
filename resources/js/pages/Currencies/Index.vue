<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type Currency,
    type PaginatedData,
} from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { computed, reactive, ref, watch } from 'vue';

interface Filters {
    search?: string;
    status?: string;
    per_page?: number;
}

interface Props {
    currencies: PaginatedData<Currency>;
    filters?: Filters;
}

const props = defineProps<Props>();

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
});

const perPage = ref(props.currencies.per_page ?? 15);
const refreshing = ref(false);

const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];

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
    () => {
        applyFilters();
    },
);

function applyFilters() {
    const params: Record<string, string | number | boolean> = {};
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (perPage.value !== 15) params.per_page = perPage.value;
    router.get('/currencies', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    router.get('/currencies', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Currencies' },
];

const expandedRows = ref({});
const hasActiveFilters = computed(() => filters.search || filters.status);
const confirm = useConfirm();

function navigateToView(currency: Currency) {
    router.get(`/currencies/${currency.id}`);
}

function navigateToEdit(currency: Currency) {
    router.get(`/currencies/${currency.id}/edit`);
}

function navigateToCreate() {
    router.get('/currencies/create');
}

function confirmDelete(currency: Currency) {
    confirm.require({
        message: `Are you sure you want to delete "${currency.name}" (${currency.code})?`,
        header: 'Delete Currency',
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
            router.delete(`/currencies/${currency.id}`);
        },
    });
}

function refreshExchangeRates() {
    refreshing.value = true;
    router.post(
        '/currencies/refresh-rates',
        {},
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                refreshing.value = false;
            },
        },
    );
}

function onRowClick(event: { data: Currency }) {
    navigateToView(event.data);
}

function onPage(event: { page: number; rows: number }) {
    perPage.value = event.rows;
    const params: Record<string, string | number | boolean> = {
        page: event.page + 1,
    };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (event.rows !== 15) params.per_page = event.rows;
    router.get('/currencies', params, { preserveState: true });
}

function formatExchangeRate(currency: Currency): string {
    if (
        currency.exchange_rate === null ||
        currency.exchange_rate === undefined
    ) {
        return '-';
    }
    const rate =
        typeof currency.exchange_rate === 'string'
            ? parseFloat(currency.exchange_rate)
            : currency.exchange_rate;
    return rate.toFixed(6);
}

function formatLastUpdated(currency: Currency): string {
    if (!currency.exchange_rate_updated_at) return '-';
    return new Date(currency.exchange_rate_updated_at).toLocaleString();
}
</script>

<template>
    <Head title="Currencies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <h1 class="heading-lg">Currencies</h1>
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        label="Refresh Rates"
                        icon="pi pi-refresh"
                        severity="secondary"
                        size="small"
                        :loading="refreshing"
                        @click="refreshExchangeRates"
                    />
                    <Button
                        label="Add Currency"
                        icon="pi pi-plus"
                        size="small"
                        @click="navigateToCreate"
                    />
                </div>
            </div>

            <!-- Filter Section -->
            <div
                class="filter-section flex flex-col gap-3 sm:flex-row sm:items-center"
            >
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        placeholder="Search by code, name, or symbol..."
                        size="small"
                        fluid
                    />
                </IconField>
                <div class="flex flex-wrap items-center gap-2">
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

            <!-- Currencies Table -->
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="currencies.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="perPage"
                :rows-per-page-options="[10, 15, 25, 50]"
                :total-records="currencies.total"
                :first="(currencies.current_page - 1) * perPage"
                @page="onPage"
                @row-click="onRowClick"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No currencies found.
                    </div>
                </template>
                <Column expander class="w-12 !pr-0 md:hidden" />
                <Column field="code" header="Code" class="w-24">
                    <template #body="{ data }">
                        <Tag :value="data.code" severity="secondary" />
                    </template>
                </Column>
                <Column field="name" header="Name">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.name }}</span>
                    </template>
                </Column>
                <Column field="symbol" header="Symbol" class="w-20">
                    <template #body="{ data }">
                        <span class="font-mono">{{ data.symbol }}</span>
                    </template>
                </Column>
                <Column
                    field="decimal_places"
                    header="Decimals"
                    class="hidden w-24 md:table-cell"
                >
                    <template #body="{ data }">
                        {{ data.decimal_places }}
                    </template>
                </Column>
                <Column
                    field="exchange_rate"
                    header="Exchange Rate"
                    class="hidden w-32 lg:table-cell"
                >
                    <template #body="{ data }">
                        {{ formatExchangeRate(data) }}
                    </template>
                </Column>
                <Column field="active" header="Status" class="w-24">
                    <template #body="{ data }">
                        <Tag
                            :value="data.active ? 'Active' : 'Inactive'"
                            :severity="data.active ? 'success' : 'danger'"
                        />
                    </template>
                </Column>
                <Column header="" class="w-24 !pr-4">
                    <template #body="{ data }">
                        <div class="flex justify-end gap-1">
                            <Button
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click.stop="navigateToEdit(data)"
                            />
                            <Button
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
                        <div
                            class="flex justify-between border-b border-border pb-2"
                        >
                            <span class="text-muted-foreground"
                                >Decimal Places</span
                            >
                            <span>{{ data.decimal_places }}</span>
                        </div>
                        <div
                            class="flex justify-between border-b border-border pb-2"
                        >
                            <span class="text-muted-foreground"
                                >Exchange Rate</span
                            >
                            <span>{{ formatExchangeRate(data) }}</span>
                        </div>
                        <div
                            class="flex justify-between border-b border-border pb-2"
                        >
                            <span class="text-muted-foreground"
                                >Rate Updated</span
                            >
                            <span>{{ formatLastUpdated(data) }}</span>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <Button
                                label="Edit"
                                icon="pi pi-pencil"
                                severity="secondary"
                                size="small"
                                @click="navigateToEdit(data)"
                                class="flex-1"
                            />
                            <Button
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
