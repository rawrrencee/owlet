<script setup lang="ts">
import type { TransactionItem } from '@/types';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import { ref, watch } from 'vue';

const props = defineProps<{
    visible: boolean;
    item: TransactionItem | null;
    currencySymbol: string;
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    save: [data: { item_id: number; quantity: number; unit_price: number }];
}>();

const quantity = ref(1);
const unitPrice = ref(0);

watch(() => props.visible, (val) => {
    if (val && props.item) {
        quantity.value = props.item.quantity;
        unitPrice.value = parseFloat(props.item.unit_price);
    }
});

function submit() {
    if (!props.item) return;
    emit('save', {
        item_id: props.item.id,
        quantity: quantity.value,
        unit_price: unitPrice.value,
    });
    emit('update:visible', false);
}
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        header="Adjust Item"
        modal
        :style="{ width: '400px' }"
        :breakpoints="{ '640px': '95vw' }"
    >
        <div v-if="item" class="space-y-4">
            <div class="rounded border p-3">
                <div class="text-sm font-medium">{{ item.product_name }}</div>
                <div class="text-xs text-muted-foreground">
                    {{ item.product_number }}
                    <span v-if="item.variant_name"> &middot; {{ item.variant_name }}</span>
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Quantity</label>
                    <InputNumber
                        v-model="quantity"
                        :min="1"
                        show-buttons
                        button-layout="horizontal"
                        size="small"
                        fluid
                    >
                        <template #decrementicon>
                            <i class="pi pi-minus text-xs" />
                        </template>
                        <template #incrementicon>
                            <i class="pi pi-plus text-xs" />
                        </template>
                    </InputNumber>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Unit Price ({{ currencySymbol }})</label>
                    <InputNumber
                        v-model="unitPrice"
                        :min="0"
                        :min-fraction-digits="2"
                        :max-fraction-digits="2"
                        size="small"
                        fluid
                    />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button
                    label="Cancel"
                    severity="secondary"
                    size="small"
                    @click="emit('update:visible', false)"
                />
                <Button
                    label="Save"
                    icon="pi pi-check"
                    size="small"
                    @click="submit"
                />
            </div>
        </div>
    </Dialog>
</template>
