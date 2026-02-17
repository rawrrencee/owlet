<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useSmartBack } from '@/composables/useSmartBack';
import type { BreadcrumbItem, Stocktake } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import InputNumber from 'primevue/inputnumber';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { computed, ref } from 'vue';

interface Props {
    stocktake: Stocktake;
    canViewDifference: boolean;
    canAdjustQuantity: boolean;
}

const props = defineProps<Props>();

const { goBack } = useSmartBack('/management/stocktakes');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Manage Stocktakes', href: '/management/stocktakes' },
    { title: `Stocktake #${props.stocktake.id}` },
];

const expandedRows = ref({});
const adjustDialogVisible = ref(false);

const adjustForm = useForm({
    product_id: null as number | null,
    store_id: props.stocktake.store_id,
    adjust_quantity: 0,
    stocktake_id: props.stocktake.id,
    notes: '',
});

const items = computed(() => props.stocktake.items ?? []);
const totalItems = computed(() => items.value.length);
const discrepancyItems = computed(
    () => items.value.filter((i) => i.has_discrepancy).length,
);

function formatDateTime(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
}

function openAdjustDialog(productId: number) {
    adjustForm.product_id = productId;
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
    <Head :title="`Stocktake #${stocktake.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <Button
                    icon="pi pi-arrow-left"
                    severity="secondary"
                    text
                    rounded
                    size="small"
                    @click="goBack"
                />
                <div>
                    <h1 class="heading-lg flex items-center gap-2">
                        Stocktake #{{ stocktake.id }}
                        <Tag
                            :value="stocktake.status_label"
                            :severity="stocktake.has_issues ? 'danger' : 'success'"
                            class="!text-xs"
                        />
                    </h1>
                </div>
            </div>

            <!-- Details Card -->
            <Card>
                <template #content>
                    <div class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <span class="text-muted-foreground">Employee</span>
                            <div class="font-medium">
                                {{ stocktake.employee?.name }}
                            </div>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Store</span>
                            <div class="font-medium">
                                {{ stocktake.store?.store_name }}
                                ({{ stocktake.store?.store_code }})
                            </div>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Submitted</span>
                            <div class="font-medium">
                                {{ formatDateTime(stocktake.submitted_at) }}
                            </div>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Issues</span>
                            <div>
                                <Tag
                                    :value="stocktake.has_issues ? 'Has Issues' : 'No Issues'"
                                    :severity="stocktake.has_issues ? 'danger' : 'success'"
                                    class="!text-xs"
                                />
                            </div>
                        </div>
                    </div>

                    <template v-if="stocktake.notes">
                        <Divider />
                        <div class="text-sm">
                            <span class="text-muted-foreground">Notes: </span>
                            {{ stocktake.notes }}
                        </div>
                    </template>

                    <Divider />

                    <div class="flex gap-4 text-sm">
                        <div>
                            <span class="text-muted-foreground">Total Items: </span>
                            <span class="font-medium">{{ totalItems }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Discrepancies: </span>
                            <span
                                class="font-medium"
                                :class="{ 'text-red-600 dark:text-red-400': discrepancyItems > 0 }"
                            >
                                {{ discrepancyItems }}
                            </span>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Items Table -->
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
                        No items in this stocktake.
                    </div>
                </template>
                <Column expander style="width: 3rem" class="!pr-0 md:hidden" />
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
                <Column header="Counted" :style="{ width: '5rem' }">
                    <template #body="{ data }">
                        {{ data.counted_quantity }}
                    </template>
                </Column>
                <Column
                    v-if="canViewDifference"
                    header="System"
                    :style="{ width: '5rem' }"
                    class="hidden sm:table-cell"
                >
                    <template #body="{ data }">
                        {{ data.system_quantity ?? '-' }}
                    </template>
                </Column>
                <Column
                    v-if="canViewDifference"
                    header="Diff"
                    :style="{ width: '5rem' }"
                    class="hidden sm:table-cell"
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
                    v-if="canAdjustQuantity"
                    header=""
                    :style="{ width: '4rem' }"
                >
                    <template #body="{ data }">
                        <Button
                            v-if="data.has_discrepancy"
                            icon="pi pi-sliders-h"
                            severity="warn"
                            text
                            rounded
                            size="small"
                            @click="openAdjustDialog(data.product_id)"
                            v-tooltip.top="'Adjust quantity'"
                        />
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-2 p-3 text-sm md:hidden">
                        <div v-if="canViewDifference" class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">System Qty</span>
                            <span>{{ data.system_quantity ?? '-' }}</span>
                        </div>
                        <div v-if="canViewDifference" class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Difference</span>
                            <span>{{ data.difference !== undefined ? ((data.difference > 0 ? '+' : '') + data.difference) : '-' }}</span>
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
