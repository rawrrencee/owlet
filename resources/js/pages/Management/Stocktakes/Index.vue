<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    BreadcrumbItem,
    Stocktake,
    StocktakeItem,
    StocktakeManagementFilters,
} from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
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
    stores: StoreOption[];
    filters: StocktakeManagementFilters;
    data: {
        items: Stocktake[] | StocktakeItem[];
        pagination: Pagination;
    };
    canViewDifference: boolean;
    canAdjustQuantity: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Manage Stocktakes' },
];

const filters = reactive({
    store_id: props.filters.store_id ?? '',
    start_date: props.filters.start_date ? new Date(props.filters.start_date) : null as Date | null,
    end_date: props.filters.end_date ? new Date(props.filters.end_date) : null as Date | null,
    tab: props.filters.tab ?? 'by-employee',
    search: props.filters.search ?? '',
});

const storeOptions = [
    { label: 'All Stores', value: '' },
    ...props.stores.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: String(s.id),
    })),
];

const expandedRows = ref({});
const adjustDialogVisible = ref(false);

const adjustForm = useForm({
    product_id: null as number | null,
    store_id: null as number | null,
    adjust_quantity: 0,
    stocktake_id: null as number | null,
    notes: '',
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function applyFilters() {
    const params: Record<string, string> = {
        tab: filters.tab,
    };
    if (filters.store_id) params.store_id = filters.store_id;
    if (filters.start_date) params.start_date = formatDateForApi(filters.start_date);
    if (filters.end_date) params.end_date = formatDateForApi(filters.end_date);
    if (filters.search) params.search = filters.search;

    router.get('/management/stocktakes', params, { preserveState: true });
}

function formatDateForApi(date: Date): string {
    return date.toISOString().split('T')[0];
}

watch(() => filters.store_id, () => applyFilters());
watch(() => filters.start_date, () => applyFilters());
watch(() => filters.end_date, () => applyFilters());
watch(
    () => filters.search,
    () => {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => applyFilters(), 300);
    },
);

function switchTab(newTab: string | number) {
    filters.tab = String(newTab);
    applyFilters();
}

function onPage(event: { page: number }) {
    const params: Record<string, string> = {
        tab: filters.tab,
        page: String(event.page + 1),
    };
    if (filters.store_id) params.store_id = filters.store_id;
    if (filters.start_date) params.start_date = formatDateForApi(filters.start_date);
    if (filters.end_date) params.end_date = formatDateForApi(filters.end_date);
    if (filters.search) params.search = filters.search;

    router.get('/management/stocktakes', params, { preserveState: true });
}

function viewStocktake(stocktake: Stocktake) {
    router.get(`/management/stocktakes/${stocktake.id}`);
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function formatDateTime(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
}

function openAdjustDialog(item: StocktakeItem, storeId?: number, stocktakeId?: number) {
    adjustForm.product_id = item.product_id;
    adjustForm.store_id = storeId ?? null;
    adjustForm.stocktake_id = stocktakeId ?? null;
    adjustForm.adjust_quantity = 0;
    adjustForm.notes = '';
    adjustDialogVisible.value = true;
}

function submitAdjustment() {
    adjustForm.post('/management/stocktakes/adjust-quantity', {
        preserveScroll: true,
        onSuccess: () => {
            adjustDialogVisible.value = false;
        },
    });
}
</script>

<template>
    <Head title="Manage Stocktakes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 class="heading-lg">Manage Stocktakes</h1>

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
                <DatePicker
                    v-model="filters.start_date"
                    placeholder="Start date"
                    size="small"
                    dateFormat="dd/mm/yy"
                    showIcon
                    class="w-full sm:w-44"
                />
                <DatePicker
                    v-model="filters.end_date"
                    placeholder="End date"
                    size="small"
                    dateFormat="dd/mm/yy"
                    showIcon
                    class="w-full sm:w-44"
                />
                <IconField v-if="filters.tab === 'by-item'" class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        placeholder="Search products..."
                        size="small"
                        fluid
                    />
                </IconField>
            </div>

            <!-- Tabs -->
            <Tabs :value="filters.tab" @update:value="switchTab">
                <TabList>
                    <Tab value="by-employee">By Employee</Tab>
                    <Tab value="by-item">By Item</Tab>
                </TabList>
            </Tabs>

            <!-- By Employee Tab -->
            <DataTable
                v-if="filters.tab === 'by-employee'"
                v-model:expandedRows="expandedRows"
                :value="(data.items as Stocktake[])"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="data.pagination.per_page"
                :total-records="data.pagination.total"
                :first="(data.pagination.current_page - 1) * data.pagination.per_page"
                @page="onPage"
                @row-click="(e: any) => viewStocktake(e.data)"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No submitted stocktakes found.
                    </div>
                </template>
                <Column expander style="width: 3rem" class="!pr-0 md:hidden" />
                <Column header="Employee">
                    <template #body="{ data: st }">
                        <div class="flex items-center gap-2">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium">
                                    {{ st.employee?.name }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ st.employee?.employee_number ?? '' }}
                                </div>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Store" class="hidden sm:table-cell">
                    <template #body="{ data: st }">
                        {{ st.store?.store_name }}
                    </template>
                </Column>
                <Column header="Items" :style="{ width: '5rem' }" class="hidden sm:table-cell">
                    <template #body="{ data: st }">
                        {{ st.items_count ?? 0 }}
                    </template>
                </Column>
                <Column header="Issues" :style="{ width: '5rem' }">
                    <template #body="{ data: st }">
                        <Tag
                            :value="st.has_issues ? 'Yes' : 'No'"
                            :severity="st.has_issues ? 'danger' : 'success'"
                            class="!text-xs"
                        />
                    </template>
                </Column>
                <Column header="Submitted" class="hidden md:table-cell" :style="{ width: '10rem' }">
                    <template #body="{ data: st }">
                        {{ formatDateTime(st.submitted_at) }}
                    </template>
                </Column>
                <template #expansion="{ data: st }">
                    <div class="grid gap-2 p-3 text-sm md:hidden">
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Store</span>
                            <span>{{ st.store?.store_name }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Items</span>
                            <span>{{ st.items_count ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Submitted</span>
                            <span>{{ formatDateTime(st.submitted_at) }}</span>
                        </div>
                    </div>
                </template>
            </DataTable>

            <!-- By Item Tab -->
            <DataTable
                v-else
                v-model:expandedRows="expandedRows"
                :value="(data.items as StocktakeItem[])"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="data.pagination.per_page"
                :total-records="data.pagination.total"
                :first="(data.pagination.current_page - 1) * data.pagination.per_page"
                @page="onPage"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No stocktake items found.
                    </div>
                </template>
                <Column expander style="width: 3rem" class="!pr-0 md:hidden" />
                <Column header="Product">
                    <template #body="{ data: item }">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium">
                                {{ item.product?.product_name }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ item.product?.product_number }}
                                <span v-if="item.product?.variant_name">
                                    - {{ item.product.variant_name }}
                                </span>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Counted" :style="{ width: '5rem' }">
                    <template #body="{ data: item }">
                        {{ item.counted_quantity }}
                    </template>
                </Column>
                <Column
                    v-if="canViewDifference"
                    header="System"
                    :style="{ width: '5rem' }"
                    class="hidden sm:table-cell"
                >
                    <template #body="{ data: item }">
                        {{ item.system_quantity ?? '-' }}
                    </template>
                </Column>
                <Column
                    v-if="canViewDifference"
                    header="Diff"
                    :style="{ width: '5rem' }"
                    class="hidden sm:table-cell"
                >
                    <template #body="{ data: item }">
                        <span
                            v-if="item.difference !== undefined"
                            :class="{
                                'text-red-600 dark:text-red-400': item.difference !== 0,
                                'text-green-600 dark:text-green-400': item.difference === 0,
                            }"
                        >
                            {{ item.difference > 0 ? '+' : '' }}{{ item.difference }}
                        </span>
                    </template>
                </Column>
                <Column header="Status" :style="{ width: '6rem' }">
                    <template #body="{ data: item }">
                        <Tag
                            :value="item.has_discrepancy ? 'Incorrect' : 'OK'"
                            :severity="item.has_discrepancy ? 'danger' : 'success'"
                            class="!text-xs"
                        />
                    </template>
                </Column>
                <Column
                    v-if="canAdjustQuantity"
                    header=""
                    :style="{ width: '4rem' }"
                >
                    <template #body="{ data: item }">
                        <Button
                            v-if="item.has_discrepancy"
                            icon="pi pi-sliders-h"
                            severity="warn"
                            text
                            rounded
                            size="small"
                            @click="openAdjustDialog(item, item.stocktake?.store_id, item.stocktake?.id)"
                            v-tooltip.top="'Adjust quantity'"
                        />
                    </template>
                </Column>
                <template #expansion="{ data: item }">
                    <div class="grid gap-2 p-3 text-sm md:hidden">
                        <div v-if="canViewDifference" class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">System Qty</span>
                            <span>{{ item.system_quantity ?? '-' }}</span>
                        </div>
                        <div v-if="canViewDifference" class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Difference</span>
                            <span>{{ item.difference !== undefined ? ((item.difference > 0 ? '+' : '') + item.difference) : '-' }}</span>
                        </div>
                    </div>
                </template>
            </DataTable>

            <!-- Adjust Quantity Dialog -->
            <Dialog
                v-model:visible="adjustDialogVisible"
                header="Adjust Inventory Quantity"
                modal
                :style="{ width: '24rem' }"
            >
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Adjustment</label>
                        <p class="mb-2 text-xs text-muted-foreground">
                            Positive = Found Item, Negative = Lost Item
                        </p>
                        <InputNumber
                            v-model="adjustForm.adjust_quantity"
                            size="small"
                            fluid
                            showButtons
                        />
                        <small v-if="adjustForm.errors.adjust_quantity" class="text-red-500">
                            {{ adjustForm.errors.adjust_quantity }}
                        </small>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Notes</label>
                        <Textarea
                            v-model="adjustForm.notes"
                            rows="2"
                            size="small"
                            fluid
                            placeholder="Optional notes..."
                        />
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="adjustDialogVisible = false"
                    />
                    <Button
                        label="Apply Adjustment"
                        icon="pi pi-check"
                        size="small"
                        :loading="adjustForm.processing"
                        @click="submitAdjustment"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
