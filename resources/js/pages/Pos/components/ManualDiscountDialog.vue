<script setup lang="ts">
import type { Transaction } from '@/types';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import SelectButton from 'primevue/selectbutton';
import { ref, watch } from 'vue';

const props = defineProps<{
    visible: boolean;
    transaction: Transaction | null;
    currencySymbol: string;
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    apply: [type: 'percentage' | 'amount', value: string];
    clear: [];
}>();

const discountType = ref<'percentage' | 'amount'>('percentage');
const discountValue = ref<number | null>(null);

const typeOptions = [
    { label: 'Percentage', value: 'percentage' },
    { label: 'Fixed Amount', value: 'amount' },
];

// Populate from existing manual discount when dialog opens
watch(() => props.visible, (open) => {
    if (open && props.transaction) {
        if (props.transaction.manual_discount_type) {
            discountType.value = props.transaction.manual_discount_type;
            discountValue.value = props.transaction.manual_discount_value
                ? parseFloat(props.transaction.manual_discount_value)
                : null;
        } else {
            discountType.value = 'percentage';
            discountValue.value = null;
        }
    }
});

function onApply() {
    if (!discountValue.value || discountValue.value <= 0) return;
    emit('apply', discountType.value, discountValue.value.toString());
    emit('update:visible', false);
}

function onClear() {
    emit('clear');
    emit('update:visible', false);
}

const hasExistingDiscount = () => {
    if (!props.transaction) return false;
    return parseFloat(props.transaction.manual_discount || '0') > 0;
};
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        header="Manual Discount"
        class="w-full max-w-sm"
        @update:visible="emit('update:visible', $event)"
    >
        <div class="space-y-4">
            <SelectButton
                v-model="discountType"
                :options="typeOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
                :allowEmpty="false"
            />

            <div>
                <label class="block text-sm font-medium mb-1">
                    {{ discountType === 'percentage' ? 'Discount Percentage' : 'Discount Amount' }}
                </label>
                <InputNumber
                    v-model="discountValue"
                    :min="0"
                    :max="discountType === 'percentage' ? 100 : undefined"
                    :minFractionDigits="discountType === 'amount' ? 2 : 0"
                    :maxFractionDigits="discountType === 'amount' ? 2 : 2"
                    :suffix="discountType === 'percentage' ? '%' : undefined"
                    :prefix="discountType === 'amount' ? currencySymbol : undefined"
                    class="w-full"
                    inputClass="w-full"
                    size="small"
                    autofocus
                    @keydown.enter="onApply"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex gap-2 justify-end">
                <Button
                    v-if="hasExistingDiscount()"
                    label="Clear"
                    severity="danger"
                    text
                    size="small"
                    @click="onClear"
                />
                <Button
                    label="Cancel"
                    severity="secondary"
                    text
                    size="small"
                    @click="emit('update:visible', false)"
                />
                <Button
                    label="Apply"
                    size="small"
                    :disabled="!discountValue || discountValue <= 0"
                    @click="onApply"
                />
            </div>
        </template>
    </Dialog>
</template>
