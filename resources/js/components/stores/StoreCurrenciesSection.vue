<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import { useConfirm } from 'primevue/useconfirm';
import { computed, onMounted, reactive, ref } from 'vue';
import { type Currency, type StoreCurrency } from '@/types';

interface Props {
    storeId: number;
    currencies: Currency[];
}

const props = defineProps<Props>();

const dialogVisible = ref(false);
const saving = ref(false);
const loading = ref(true);

const storeCurrencies = ref<StoreCurrency[]>([]);

const form = reactive({
    currency_id: null as number | null,
});

const formErrors = reactive<Record<string, string>>({});

const confirm = useConfirm();

const currencyOptions = computed(() =>
    props.currencies.map((c) => ({
        label: `${c.code} - ${c.name}`,
        value: c.id,
    })),
);

// Filter out currencies that are already assigned when adding new
const availableCurrencyOptions = computed(() => {
    const assignedCurrencyIds = storeCurrencies.value.map((sc) => sc.currency_id);
    return currencyOptions.value.filter((c) => !assignedCurrencyIds.includes(c.value));
});

async function fetchStoreCurrencies() {
    loading.value = true;
    try {
        const response = await fetch(`/stores/${props.storeId}/currencies`, {
            headers: {
                Accept: 'application/json',
            },
        });
        const data = await response.json();
        storeCurrencies.value = data.data;
    } catch (error) {
        console.error('Failed to fetch store currencies:', error);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    fetchStoreCurrencies();
});

function resetForm() {
    form.currency_id = null;
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);
}

function openAddDialog() {
    resetForm();
    dialogVisible.value = true;
}

function saveAssignment() {
    saving.value = true;
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);

    router.post(
        `/stores/${props.storeId}/currencies`,
        { currency_id: form.currency_id },
        {
            preserveScroll: true,
            onSuccess: () => {
                dialogVisible.value = false;
                fetchStoreCurrencies();
            },
            onError: (errors) => {
                Object.assign(formErrors, errors);
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function confirmRemoveCurrency(sc: StoreCurrency) {
    const currencyCode = sc.currency?.code ?? 'this currency';
    confirm.require({
        message: `Are you sure you want to remove "${currencyCode}" from this store? This action cannot be undone.`,
        header: 'Remove Currency',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Remove',
        acceptProps: {
            severity: 'danger',
            size: 'small',
        },
        accept: () => {
            router.delete(`/stores/${props.storeId}/currencies/${sc.id}`, {
                preserveScroll: true,
                onSuccess: () => fetchStoreCurrencies(),
            });
        },
    });
}

function formatExchangeRate(rate: number | string | null): string {
    if (rate === null || rate === undefined) return '-';
    const num = typeof rate === 'string' ? parseFloat(rate) : rate;
    // Format with up to 4 decimal places, remove trailing zeros
    return parseFloat(num.toFixed(4)).toString();
}

const expandedRows = ref({});
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">Store Currencies</h3>
            <Button
                label="Add Currency"
                icon="pi pi-plus"
                size="small"
                @click="openAddDialog"
                :disabled="availableCurrencyOptions.length === 0"
            />
        </div>

        <DataTable
            v-model:expandedRows="expandedRows"
            :value="storeCurrencies"
            dataKey="id"
            striped-rows
            size="small"
            :loading="loading"
            class="overflow-hidden rounded-xl border border-border dark:border-border"
        >
            <template #empty>
                <div class="p-4 text-center text-muted-foreground">
                    No currencies assigned to this store. Click "Add Currency" to assign a currency.
                </div>
            </template>
            <Column expander style="width: 3rem" class="!pr-0 md:hidden" />
            <Column field="currency" header="Currency">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{ data.currency?.code }}</span>
                        <span class="hidden text-muted-foreground sm:inline">{{ data.currency?.name }}</span>
                    </div>
                </template>
            </Column>
            <Column field="currency.symbol" header="Symbol" class="hidden md:table-cell w-24">
                <template #body="{ data }">
                    {{ data.currency?.symbol ?? '-' }}
                </template>
            </Column>
            <Column field="exchange_rate" class="hidden md:table-cell w-44">
                <template #header>
                    <span v-tooltip.top="'Exchange rate relative to SGD (base currency)'">
                        Exchange Rate
                        <i class="pi pi-info-circle ml-1 text-xs text-muted-foreground"></i>
                    </span>
                </template>
                <template #body="{ data }">
                    {{ formatExchangeRate(data.currency?.exchange_rate) }}
                </template>
            </Column>
            <Column header="" class="hidden w-16 !pr-4 md:table-cell">
                <template #body="{ data }">
                    <div class="flex justify-end gap-1">
                        <Button
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="confirmRemoveCurrency(data)"
                            v-tooltip.top="'Remove'"
                        />
                    </div>
                </template>
            </Column>
            <template #expansion="{ data }">
                <div class="grid gap-3 p-3 text-sm md:hidden">
                    <div class="flex justify-between gap-4 border-b border-border pb-2 sm:hidden">
                        <span class="shrink-0 text-muted-foreground">Name</span>
                        <span class="text-right">{{ data.currency?.name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border pb-2">
                        <span class="shrink-0 text-muted-foreground">Symbol</span>
                        <span class="text-right">{{ data.currency?.symbol ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border pb-2">
                        <span class="shrink-0 text-muted-foreground">
                            Exchange Rate
                            <span class="text-xs">(vs SGD)</span>
                        </span>
                        <span class="text-right">{{ formatExchangeRate(data.currency?.exchange_rate) }}</span>
                    </div>
                    <div class="flex justify-end gap-1 pt-1">
                        <Button
                            icon="pi pi-trash"
                            label="Remove"
                            severity="danger"
                            text
                            size="small"
                            @click="confirmRemoveCurrency(data)"
                        />
                    </div>
                </div>
            </template>
        </DataTable>

        <Dialog
            v-model:visible="dialogVisible"
            header="Add Currency"
            :modal="true"
            :closable="!saving"
            class="w-full max-w-lg"
        >
            <form @submit.prevent="saveAssignment" class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="sc_currency_id" class="font-medium">Currency *</label>
                    <Select
                        id="sc_currency_id"
                        v-model="form.currency_id"
                        :options="availableCurrencyOptions"
                        option-label="label"
                        option-value="value"
                        :invalid="!!formErrors.currency_id"
                        placeholder="Select currency"
                        filter
                        size="small"
                        fluid
                    />
                    <small v-if="formErrors.currency_id" class="text-red-500">
                        {{ formErrors.currency_id }}
                    </small>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <Button
                        type="button"
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="dialogVisible = false"
                        :disabled="saving"
                    />
                    <Button type="submit" label="Add Currency" size="small" :loading="saving" />
                </div>
            </form>
        </Dialog>
    </div>
</template>
