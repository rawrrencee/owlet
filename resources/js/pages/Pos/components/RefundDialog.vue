<script setup lang="ts">
import type { Transaction, TransactionItem } from '@/types';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import { computed, ref, watch } from 'vue';

interface RefundLine {
    item_id: number;
    product_name: string;
    product_number: string;
    variant_name: string | null;
    quantity: number;
    max_quantity: number;
    unit_price: string;
    line_total: string;
    selected: boolean;
    reason: string;
}

const props = defineProps<{
    visible: boolean;
    transaction: Transaction | null;
    currencySymbol: string;
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    refund: [items: Array<{ item_id: number; quantity: number; reason: string }>];
}>();

const refundLines = ref<RefundLine[]>([]);

watch(() => props.visible, (val) => {
    if (val && props.transaction?.items) {
        refundLines.value = props.transaction.items
            .filter((item) => !item.is_refunded && item.quantity > 0)
            .map((item) => ({
                item_id: item.id,
                product_name: item.product_name,
                product_number: item.product_number,
                variant_name: item.variant_name,
                quantity: item.quantity,
                max_quantity: item.quantity,
                unit_price: item.unit_price,
                line_total: item.line_total,
                selected: false,
                reason: '',
            }));
    }
});

const selectedLines = computed(() => refundLines.value.filter((l) => l.selected));

const estimatedRefund = computed(() => {
    return selectedLines.value.reduce((sum, line) => {
        const unitPrice = parseFloat(line.unit_price);
        return sum + unitPrice * line.quantity;
    }, 0);
});

const canSubmit = computed(() => {
    return selectedLines.value.length > 0 && selectedLines.value.every((l) => l.quantity > 0);
});

function fmt(amount: number): string {
    return `${props.currencySymbol}${amount.toFixed(2)}`;
}

function toggleAll(checked: boolean) {
    refundLines.value.forEach((l) => (l.selected = checked));
}

function submitRefund() {
    const items = selectedLines.value.map((l) => ({
        item_id: l.item_id,
        quantity: l.quantity,
        reason: l.reason,
    }));
    emit('refund', items);
}
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        header="Process Refund"
        modal
        :style="{ width: '560px' }"
        :breakpoints="{ '640px': '95vw' }"
    >
        <div v-if="transaction" class="space-y-4">
            <div class="text-sm text-muted-color">
                Select items to refund for transaction <strong>{{ transaction.transaction_number }}</strong>
            </div>

            <!-- Select All -->
            <div v-if="refundLines.length > 1" class="flex items-center gap-2 border-b pb-2">
                <Checkbox
                    :model-value="refundLines.every(l => l.selected)"
                    binary
                    @update:model-value="toggleAll($event)"
                />
                <span class="text-sm text-muted-color">Select All</span>
            </div>

            <!-- Items -->
            <div v-if="refundLines.length === 0" class="text-center text-muted-color py-4 text-sm">
                No refundable items.
            </div>

            <div v-else class="space-y-3 max-h-[400px] overflow-y-auto">
                <div
                    v-for="line in refundLines"
                    :key="line.item_id"
                    class="rounded border p-3 transition-colors"
                    :class="line.selected ? 'border-primary bg-primary/5' : ''"
                >
                    <div class="flex items-start gap-2">
                        <Checkbox v-model="line.selected" binary class="mt-0.5" />
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="text-sm font-medium">{{ line.product_name }}</div>
                                    <div class="text-xs text-muted-color">
                                        {{ line.product_number }}
                                        <span v-if="line.variant_name"> &middot; {{ line.variant_name }}</span>
                                    </div>
                                </div>
                                <div class="text-sm font-semibold text-right">
                                    {{ fmt(parseFloat(line.unit_price) * line.quantity) }}
                                </div>
                            </div>

                            <div v-if="line.selected" class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-muted-color">Qty:</span>
                                    <InputNumber
                                        v-model="line.quantity"
                                        :min="1"
                                        :max="line.max_quantity"
                                        size="small"
                                        show-buttons
                                        button-layout="horizontal"
                                        :input-style="{ width: '3rem', textAlign: 'center' }"
                                        class="shrink-0"
                                    >
                                        <template #decrementicon>
                                            <i class="pi pi-minus text-xs" />
                                        </template>
                                        <template #incrementicon>
                                            <i class="pi pi-plus text-xs" />
                                        </template>
                                    </InputNumber>
                                    <span class="text-xs text-muted-color">of {{ line.max_quantity }}</span>
                                </div>
                                <InputText
                                    v-model="line.reason"
                                    placeholder="Reason (optional)"
                                    size="small"
                                    class="flex-1"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Refund Summary -->
            <div v-if="selectedLines.length > 0" class="border-t pt-3 space-y-1">
                <div class="flex justify-between text-sm">
                    <span class="text-muted-color">Items to refund</span>
                    <span>{{ selectedLines.length }}</span>
                </div>
                <div class="flex justify-between text-base font-bold text-red-600">
                    <span>Estimated Refund</span>
                    <span>{{ fmt(estimatedRefund) }}</span>
                </div>
                <p class="text-xs text-muted-color">
                    Final refund amount may differ after recalculating offers and discounts.
                </p>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2 pt-2">
                <Button
                    label="Cancel"
                    severity="secondary"
                    size="small"
                    @click="emit('update:visible', false)"
                />
                <Button
                    label="Process Refund"
                    icon="pi pi-replay"
                    severity="danger"
                    size="small"
                    :disabled="!canSubmit"
                    @click="submitRefund"
                />
            </div>
        </div>
    </Dialog>
</template>
