<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { computed, onMounted, reactive, ref } from 'vue';
import { type Currency, type StoreCurrency } from '@/types';

interface Props {
    storeId: number;
    currencies: Currency[];
}

const props = defineProps<Props>();

const dialogVisible = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);
const loading = ref(true);

const storeCurrencies = ref<StoreCurrency[]>([]);

const form = reactive({
    currency_id: null as number | null,
    exchange_rate: null as number | null,
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

// Get the default currency for display
const defaultCurrency = computed(() => {
    const defaultSc = storeCurrencies.value.find((sc) => sc.is_default);
    return defaultSc?.currency ?? null;
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
    form.exchange_rate = null;
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);
}

function openAddDialog() {
    resetForm();
    editingId.value = null;
    dialogVisible.value = true;
}

function openEditDialog(sc: StoreCurrency) {
    resetForm();
    editingId.value = sc.id;
    form.currency_id = sc.currency_id;
    form.exchange_rate = sc.exchange_rate ? parseFloat(String(sc.exchange_rate)) : null;
    dialogVisible.value = true;
}

function saveAssignment() {
    saving.value = true;
    Object.keys(formErrors).forEach((key) => delete formErrors[key]);

    const data = {
        currency_id: !editingId.value ? form.currency_id : undefined,
        exchange_rate: form.exchange_rate,
    };

    const url = editingId.value
        ? `/stores/${props.storeId}/currencies/${editingId.value}`
        : `/stores/${props.storeId}/currencies`;

    const method = editingId.value ? 'put' : 'post';

    router[method](url, data as any, {
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
    });
}

function confirmSetDefault(sc: StoreCurrency) {
    const currencyCode = sc.currency?.code ?? 'this currency';
    confirm.require({
        message: `Are you sure you want to set "${currencyCode}" as the default currency?`,
        header: 'Set Default Currency',
        icon: 'pi pi-star',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Set Default',
        acceptProps: {
            severity: 'info',
            size: 'small',
        },
        accept: () => {
            router.post(
                `/stores/${props.storeId}/currencies/${sc.id}/set-default`,
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => fetchStoreCurrencies(),
                },
            );
        },
    });
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

function getCurrencyDisplay(sc: StoreCurrency): string {
    const currency = sc.currency;
    if (!currency) return '-';
    return `${currency.code} - ${currency.name}`;
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
                        <Tag v-if="data.is_default" value="Default" severity="info" class="!text-xs" />
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
                    <span v-tooltip.top="defaultCurrency ? `Rate relative to ${defaultCurrency.code}` : 'Rate relative to default currency'">
                        Exchange Rate
                        <i class="pi pi-info-circle ml-1 text-xs text-muted-foreground"></i>
                    </span>
                </template>
                <template #body="{ data }">
                    {{ formatExchangeRate(data.exchange_rate) }}
                </template>
            </Column>
            <Column header="" class="hidden w-32 !pr-4 md:table-cell">
                <template #body="{ data }">
                    <div class="flex justify-end gap-1">
                        <Button
                            icon="pi pi-pencil"
                            severity="secondary"
                            text
                            rounded
                            size="small"
                            @click="openEditDialog(data)"
                            v-tooltip.top="'Edit'"
                        />
                        <Button
                            v-if="!data.is_default"
                            icon="pi pi-star"
                            severity="info"
                            text
                            rounded
                            size="small"
                            @click="confirmSetDefault(data)"
                            v-tooltip.top="'Set as Default'"
                        />
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
                            <span v-if="defaultCurrency" class="text-xs">(vs {{ defaultCurrency.code }})</span>
                        </span>
                        <span class="text-right">{{ formatExchangeRate(data.exchange_rate) }}</span>
                    </div>
                    <div class="flex justify-end gap-1 pt-1">
                        <Button
                            icon="pi pi-pencil"
                            label="Edit"
                            severity="secondary"
                            text
                            size="small"
                            @click="openEditDialog(data)"
                        />
                        <Button
                            v-if="!data.is_default"
                            icon="pi pi-star"
                            label="Set Default"
                            severity="info"
                            text
                            size="small"
                            @click="confirmSetDefault(data)"
                        />
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
            :header="editingId ? 'Edit Currency Assignment' : 'Add Currency'"
            :modal="true"
            :closable="!saving"
            class="w-full max-w-lg"
        >
            <form @submit.prevent="saveAssignment" class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="sc_currency_id" class="font-medium">Currency *</label>
                    <Select
                        v-if="!editingId"
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
                    <div
                        v-else
                        class="flex items-center gap-2 rounded border border-border bg-surface-50 px-3 py-2 dark:bg-surface-800"
                    >
                        <span class="font-medium">{{ getCurrencyDisplay(storeCurrencies.find(sc => sc.id === editingId)!) }}</span>
                    </div>
                    <small v-if="formErrors.currency_id" class="text-red-500">
                        {{ formErrors.currency_id }}
                    </small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="sc_exchange_rate" class="font-medium">Exchange Rate</label>
                    <InputNumber
                        id="sc_exchange_rate"
                        v-model="form.exchange_rate"
                        :invalid="!!formErrors.exchange_rate"
                        :min="0"
                        :minFractionDigits="2"
                        :maxFractionDigits="6"
                        placeholder="1.000000"
                        size="small"
                        fluid
                    />
                    <small class="text-muted-foreground">
                        Rate relative to default currency{{ defaultCurrency ? ` (${defaultCurrency.code})` : '' }}.
                        E.g., if default is SGD and this is USD, enter how many SGD = 1 USD.
                    </small>
                    <small v-if="formErrors.exchange_rate" class="text-red-500">
                        {{ formErrors.exchange_rate }}
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
                    <Button type="submit" :label="editingId ? 'Save Changes' : 'Add Currency'" size="small" :loading="saving" />
                </div>
            </form>
        </Dialog>
    </div>
</template>
