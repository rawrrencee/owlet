<script setup lang="ts">
import ProductSearchBar from '@/components/stocktakes/ProductSearchBar.vue';
import StocktakeBarcodeScannerDialog from '@/components/stocktakes/StocktakeBarcodeScannerDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    BreadcrumbItem,
    Stocktake,
    StocktakeProductSearchResult,
    StocktakeTemplate,
} from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref } from 'vue';

interface Props {
    stocktake: Stocktake;
    canViewDifference: boolean;
    templates: StocktakeTemplate[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stocktake', href: '/stocktakes' },
    { title: props.stocktake.store?.store_name ?? 'Counting' },
];

const confirm = useConfirm();
const showScannerDialog = ref(false);
const productSearchBarRef = ref<InstanceType<typeof ProductSearchBar>>();
const submitDialogVisible = ref(false);
const expandedRows = ref({});

const addItemForm = useForm({
    product_id: null as number | null,
    counted_quantity: 0,
});

const submitForm = useForm({
    notes: '' as string,
});

function onProductSelected(product: StocktakeProductSearchResult) {
    addItemForm.product_id = product.id;
    addItemForm.counted_quantity = 0;
    addItemForm.post(`/stocktakes/${props.stocktake.id}/items`, {
        preserveScroll: true,
        onSuccess: () => {
            addItemForm.reset();
        },
    });
}

function onBarcodeScan(barcode: string) {
    productSearchBarRef.value?.scanBarcode(barcode);
}

function updateItemQuantity(itemId: number, quantity: number) {
    router.put(
        `/stocktakes/${props.stocktake.id}/items/${itemId}`,
        { counted_quantity: quantity },
        { preserveScroll: true },
    );
}

function removeItem(itemId: number) {
    confirm.require({
        message: 'Remove this item from the stocktake?',
        header: 'Remove Item',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: { severity: 'secondary', size: 'small' },
        acceptLabel: 'Remove',
        acceptProps: { severity: 'danger', size: 'small' },
        accept: () => {
            router.delete(
                `/stocktakes/${props.stocktake.id}/items/${itemId}`,
                { preserveScroll: true },
            );
        },
    });
}

function openSubmitDialog() {
    submitDialogVisible.value = true;
}

function handleSubmit() {
    router.post(`/stocktakes/${props.stocktake.id}/submit`, {}, {
        onSuccess: () => {
            submitDialogVisible.value = false;
        },
    });
}

function confirmDelete() {
    confirm.require({
        message:
            'Are you sure you want to delete this stocktake? All counted items will be lost.',
        header: 'Delete Stocktake',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: { severity: 'secondary', size: 'small' },
        acceptLabel: 'Delete',
        acceptProps: { severity: 'danger', size: 'small' },
        accept: () => {
            router.delete(`/stocktakes/${props.stocktake.id}`);
        },
    });
}

function applyTemplate(templateId: number) {
    router.post(
        `/stocktakes/${props.stocktake.id}/apply-template`,
        { template_id: templateId },
        { preserveScroll: true },
    );
}

const templateOptions = props.templates.map((t) => ({
    label: `${t.name} (${t.products_count ?? 0} items)`,
    value: t.id,
}));
const selectedTemplateId = ref<number | null>(null);

const items = computed(() => {
    const raw = props.stocktake.items;
    if (Array.isArray(raw)) return raw;
    if (raw && typeof raw === 'object') return Object.values(raw);
    return [];
});
const itemCount = computed(() => items.value.length);
const discrepancyCount = computed(
    () => items.value.filter((i: any) => i.has_discrepancy).length,
);
</script>

<template>
    <Head title="Stocktake Session" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <Button
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        text
                        rounded
                        size="small"
                        @click="router.get('/stocktakes')"
                    />
                    <div>
                        <h1 class="heading-lg flex items-center gap-2">
                            {{ stocktake.store?.store_name }}
                            <Tag
                                :value="stocktake.status_label"
                                :severity="stocktake.status === 'in_progress' ? 'warn' : 'success'"
                                class="!text-xs"
                            />
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ itemCount }} items &middot;
                            {{ discrepancyCount }} discrepancies
                        </p>
                    </div>
                </div>
                <div v-if="stocktake.status === 'in_progress'" class="flex gap-2">
                    <Button
                        label="Delete"
                        icon="pi pi-trash"
                        severity="danger"
                        outlined
                        size="small"
                        @click="confirmDelete"
                    />
                    <Button
                        label="Submit"
                        icon="pi pi-check"
                        size="small"
                        :disabled="itemCount === 0"
                        @click="openSubmitDialog"
                    />
                </div>
            </div>

            <!-- Scanner Toggle + Template (only for in-progress) -->
            <template v-if="stocktake.status === 'in_progress'">
                <div class="flex flex-wrap gap-2">
                    <Button
                        label="Scan Barcode"
                        icon="pi pi-camera"
                        severity="info"
                        outlined
                        size="small"
                        @click="showScannerDialog = true"
                    />
                    <div v-if="templateOptions.length > 0" class="flex gap-2">
                        <Select
                            v-model="selectedTemplateId"
                            :options="templateOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Apply template..."
                            size="small"
                            class="w-48"
                        />
                        <Button
                            label="Apply"
                            icon="pi pi-plus"
                            severity="secondary"
                            size="small"
                            :disabled="!selectedTemplateId"
                            @click="selectedTemplateId && applyTemplate(selectedTemplateId)"
                        />
                    </div>
                </div>

                <!-- Product Search -->
                <div>
                    <label class="mb-1 block text-sm text-muted-foreground"
                        >Add Product</label
                    >
                    <ProductSearchBar
                        ref="productSearchBarRef"
                        :stocktake-id="stocktake.id"
                        @select="onProductSelected"
                    />
                </div>
            </template>

            <!-- Items DataTable -->
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="items"
                dataKey="id"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No items added yet. Search for products or scan a
                        barcode to begin.
                    </div>
                </template>
                <Column
                    expander
                    style="width: 3rem"
                    class="!pr-0 sm:hidden"
                />
                <Column header="Product">
                    <template #body="{ data }">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium">
                                {{ data.product?.product_name }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ data.product?.product_number }}
                                <span v-if="data.product?.variant_name">
                                    - {{ data.product.variant_name }}
                                </span>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column
                    header="Counted"
                    :style="{ width: '8rem' }"
                >
                    <template #body="{ data }">
                        <InputNumber
                            v-if="stocktake.status === 'in_progress'"
                            :modelValue="data.counted_quantity"
                            @update:modelValue="(val: number | null) => updateItemQuantity(data.id, val ?? 0)"
                            :min="0"
                            size="small"
                            input-class="w-20"
                            showButtons
                            buttonLayout="horizontal"
                            :step="1"
                            incrementButtonIcon="pi pi-plus"
                            decrementButtonIcon="pi pi-minus"
                        />
                        <span v-else>{{ data.counted_quantity }}</span>
                    </template>
                </Column>
                <Column
                    v-if="canViewDifference"
                    header="System"
                    class="hidden sm:table-cell"
                    :style="{ width: '5rem' }"
                >
                    <template #body="{ data }">
                        {{ data.system_quantity ?? '-' }}
                    </template>
                </Column>
                <Column
                    v-if="canViewDifference"
                    header="Diff"
                    class="hidden sm:table-cell"
                    :style="{ width: '5rem' }"
                >
                    <template #body="{ data }">
                        <span
                            v-if="data.difference !== undefined"
                            :class="{
                                'text-red-600 dark:text-red-400': data.difference !== 0,
                                'text-green-600 dark:text-green-400': data.difference === 0,
                            }"
                        >
                            {{ data.difference > 0 ? '+' : '' }}{{ data.difference }}
                        </span>
                        <span v-else>-</span>
                    </template>
                </Column>
                <Column header="Status" :style="{ width: '6rem' }">
                    <template #body="{ data }">
                        <Tag
                            :value="data.has_discrepancy ? 'Incorrect' : 'OK'"
                            :severity="data.has_discrepancy ? 'danger' : 'success'"
                            class="!text-xs"
                        />
                    </template>
                </Column>
                <Column
                    v-if="stocktake.status === 'in_progress'"
                    header=""
                    :style="{ width: '3rem' }"
                >
                    <template #body="{ data }">
                        <Button
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="removeItem(data.id)"
                        />
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-2 p-3 text-sm sm:hidden">
                        <div
                            v-if="canViewDifference"
                            class="flex justify-between border-b border-border pb-2"
                        >
                            <span class="text-muted-foreground"
                                >System Qty</span
                            >
                            <span>{{ data.system_quantity ?? '-' }}</span>
                        </div>
                        <div
                            v-if="canViewDifference"
                            class="flex justify-between border-b border-border pb-2"
                        >
                            <span class="text-muted-foreground">Difference</span>
                            <span
                                :class="{
                                    'text-red-600 dark:text-red-400': data.difference !== 0,
                                    'text-green-600 dark:text-green-400': data.difference === 0,
                                }"
                            >
                                {{
                                    data.difference !== undefined
                                        ? (data.difference > 0 ? '+' : '') + data.difference
                                        : '-'
                                }}
                            </span>
                        </div>
                    </div>
                </template>
            </DataTable>

            <!-- Submit Dialog -->
            <Dialog
                v-model:visible="submitDialogVisible"
                header="Submit Stocktake"
                modal
                :style="{ width: '28rem' }"
            >
                <div class="space-y-4">
                    <div class="text-sm">
                        <p>
                            You are about to submit this stocktake with
                            <strong>{{ itemCount }}</strong> items.
                        </p>
                        <p
                            v-if="discrepancyCount > 0"
                            class="mt-1 text-red-600 dark:text-red-400"
                        >
                            {{ discrepancyCount }} item(s) have discrepancies.
                        </p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-muted-foreground">
                            Notes (optional)
                        </label>
                        <Textarea
                            v-model="submitForm.notes"
                            rows="3"
                            fluid
                            size="small"
                            placeholder="Any notes about this stocktake..."
                        />
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="submitDialogVisible = false"
                    />
                    <Button
                        label="Submit Stocktake"
                        icon="pi pi-check"
                        size="small"
                        @click="handleSubmit"
                    />
                </template>
            </Dialog>

            <!-- Barcode Scanner Dialog -->
            <StocktakeBarcodeScannerDialog
                v-model:visible="showScannerDialog"
                @scan="onBarcodeScan"
            />

            <ConfirmDialog />
        </div>
    </AppLayout>
</template>
