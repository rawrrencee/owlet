<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, PaginatedData, PaymentMode } from '@/types';
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
    paymentModes: PaginatedData<PaymentMode>;
    filters?: Record<string, string>;
}

const props = defineProps<Props>();

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
});

const perPage = ref(props.paymentModes.per_page ?? 15);

const statusOptions = computed(() => [
    { label: 'All', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
]);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(() => filters.search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
});

watch(() => filters.status, () => applyFilters());

function applyFilters() {
    const params: Record<string, string | number> = {};
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (perPage.value !== 15) params.per_page = perPage.value;
    router.get('/payment-modes', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    router.get('/payment-modes', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Payment Modes' },
];

const expandedRows = ref({});
const hasActiveFilters = computed(() => filters.search || filters.status);

function onRowClick(event: any) {
    router.get(`/payment-modes/${event.data.id}/edit`);
}

function onPage(event: { page: number; rows: number }) {
    perPage.value = event.rows;
    const params: Record<string, string | number> = { page: event.page + 1 };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (event.rows !== 15) params.per_page = event.rows;
    router.get('/payment-modes', params, { preserveState: true });
}
</script>

<template>
    <Head title="Payment Modes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="heading-lg">Payment Modes</h1>
                <Button
                    label="New Payment Mode"
                    icon="pi pi-plus"
                    size="small"
                    @click="router.get('/payment-modes/create')"
                />
            </div>

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

            <DataTable
                v-model:expandedRows="expandedRows"
                :value="paymentModes.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="perPage"
                :rows-per-page-options="[10, 15, 25, 50]"
                :total-records="paymentModes.total"
                :first="(paymentModes.current_page - 1) * perPage"
                @page="onPage"
                @row-click="onRowClick"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No payment modes found.
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
                <Column header="Description" class="hidden md:table-cell">
                    <template #body="{ data }">
                        <span class="text-sm text-muted-foreground">{{ data.description ?? '-' }}</span>
                    </template>
                </Column>
                <Column header="Order" class="hidden sm:table-cell w-20">
                    <template #body="{ data }">
                        {{ data.sort_order }}
                    </template>
                </Column>
                <Column header="Status" class="w-24">
                    <template #body="{ data }">
                        <Tag
                            :value="data.is_active ? 'Active' : 'Inactive'"
                            :severity="data.is_active ? 'success' : 'danger'"
                        />
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm md:hidden">
                        <div v-if="data.description" class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Description</span>
                            <span>{{ data.description }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Sort Order</span>
                            <span>{{ data.sort_order }}</span>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
