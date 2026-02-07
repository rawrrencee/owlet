<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StocktakeTemplate } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import { useConfirm } from 'primevue/useconfirm';
import { reactive, ref, watch } from 'vue';

interface StoreOption {
    id: number;
    store_name: string;
    store_code: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    templates: StocktakeTemplate[];
    pagination: Pagination;
    stores: StoreOption[];
    filters: {
        search: string | null;
        store_id: string | null;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Manage Stocktake Templates' },
];

const confirm = useConfirm();
const expandedRows = ref({});

const filters = reactive({
    search: props.filters.search ?? '',
    store_id: props.filters.store_id ?? '',
});

const storeOptions = [
    { label: 'All Stores', value: '' },
    ...props.stores.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: String(s.id),
    })),
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function applyFilters() {
    const params: Record<string, string> = {};
    if (filters.store_id) params.store_id = filters.store_id;
    if (filters.search) params.search = filters.search;

    router.get('/management/stocktake-templates', params, { preserveState: true });
}

watch(() => filters.store_id, () => applyFilters());
watch(
    () => filters.search,
    () => {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => applyFilters(), 300);
    },
);

function onPage(event: { page: number }) {
    const params: Record<string, string> = {
        page: String(event.page + 1),
    };
    if (filters.store_id) params.store_id = filters.store_id;
    if (filters.search) params.search = filters.search;

    router.get('/management/stocktake-templates', params, { preserveState: true });
}

function confirmDelete(template: StocktakeTemplate) {
    confirm.require({
        message: `Delete template "${template.name}"?`,
        header: 'Delete Template',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: { severity: 'secondary', size: 'small' },
        acceptLabel: 'Delete',
        acceptProps: { severity: 'danger', size: 'small' },
        accept: () => {
            router.delete(`/management/stocktake-templates/${template.id}`);
        },
    });
}
</script>

<template>
    <Head title="Manage Stocktake Templates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 class="heading-lg">Manage Stocktake Templates</h1>

            <!-- Filters -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <Select
                    v-model="filters.store_id"
                    :options="storeOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Store"
                    size="small"
                    class="w-full sm:w-48"
                />
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        placeholder="Search templates..."
                        size="small"
                        fluid
                    />
                </IconField>
            </div>

            <DataTable
                v-model:expandedRows="expandedRows"
                :value="templates"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="pagination.per_page"
                :total-records="pagination.total"
                :first="(pagination.current_page - 1) * pagination.per_page"
                @page="onPage"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No templates found.
                    </div>
                </template>
                <Column expander style="width: 3rem" class="!pr-0 sm:hidden" />
                <Column field="name" header="Name">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.name }}</span>
                    </template>
                </Column>
                <Column header="Store" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        {{ data.store?.store_name }} ({{ data.store?.store_code }})
                    </template>
                </Column>
                <Column header="Created By" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.employee?.name ?? '-' }}
                    </template>
                </Column>
                <Column header="Products" :style="{ width: '6rem' }">
                    <template #body="{ data }">
                        {{ data.products_count ?? 0 }}
                    </template>
                </Column>
                <Column header="" :style="{ width: '6rem' }">
                    <template #body="{ data }">
                        <div class="flex justify-end gap-1">
                            <Button
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click="router.get(`/management/stocktake-templates/${data.id}/edit`)"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="confirmDelete(data)"
                            />
                        </div>
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-2 p-3 text-sm sm:hidden">
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Store</span>
                            <span>{{ data.store?.store_name }} ({{ data.store?.store_code }})</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Created By</span>
                            <span>{{ data.employee?.name ?? '-' }}</span>
                        </div>
                    </div>
                </template>
            </DataTable>

            <ConfirmDialog />
        </div>
    </AppLayout>
</template>
