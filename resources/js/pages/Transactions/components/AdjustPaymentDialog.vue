<script setup lang="ts">
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import { ref, watch } from 'vue';

const props = defineProps<{
    visible: boolean;
    paymentModes: Array<{ id: number; name: string }>;
    currencySymbol: string;
    suggestedAmount: number;
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    save: [data: { payment_mode_id: number; amount: number }];
}>();

const paymentModeId = ref<number | null>(null);
const amount = ref(0);

watch(() => props.visible, (val) => {
    if (val) {
        paymentModeId.value = props.paymentModes.length > 0 ? props.paymentModes[0].id : null;
        amount.value = props.suggestedAmount;
    }
});

function submit() {
    if (!paymentModeId.value || amount.value === 0) return;
    emit('save', {
        payment_mode_id: paymentModeId.value,
        amount: amount.value,
    });
    emit('update:visible', false);
}

const isRefund = ref(false);
watch(() => props.suggestedAmount, (val) => {
    isRefund.value = val < 0;
});
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        :header="suggestedAmount < 0 ? 'Record Refund Payment' : 'Record Additional Payment'"
        modal
        :style="{ width: '400px' }"
        :breakpoints="{ '640px': '95vw' }"
    >
        <div class="space-y-4">
            <div v-if="suggestedAmount < 0" class="rounded border border-orange-200 bg-orange-50 p-3 text-sm text-orange-700 dark:border-orange-800 dark:bg-orange-900/20 dark:text-orange-300">
                A price amendment created an overpayment. Record a refund payment to clear it.
            </div>
            <div v-else class="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                Items were added or adjusted, creating a balance due. Record an additional payment.
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Payment Mode</label>
                <Select
                    v-model="paymentModeId"
                    :options="paymentModes"
                    option-label="name"
                    option-value="id"
                    placeholder="Select payment mode"
                    size="small"
                    fluid
                />
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Amount ({{ currencySymbol }})</label>
                <InputNumber
                    v-model="amount"
                    :min-fraction-digits="2"
                    :max-fraction-digits="2"
                    size="small"
                    fluid
                />
                <p class="mt-1 text-xs text-muted-foreground">
                    <template v-if="suggestedAmount < 0">Negative amount indicates a refund.</template>
                    <template v-else>Positive amount for additional payment.</template>
                </p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button
                    label="Cancel"
                    severity="secondary"
                    size="small"
                    @click="emit('update:visible', false)"
                />
                <Button
                    :label="suggestedAmount < 0 ? 'Record Refund' : 'Record Payment'"
                    :icon="suggestedAmount < 0 ? 'pi pi-replay' : 'pi pi-check'"
                    :severity="suggestedAmount < 0 ? 'warn' : undefined"
                    size="small"
                    :disabled="!paymentModeId || amount === 0"
                    @click="submit"
                />
            </div>
        </div>
    </Dialog>
</template>
