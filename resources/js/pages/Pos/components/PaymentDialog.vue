<script setup lang="ts">
import type { Transaction } from '@/types';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Tag from 'primevue/tag';
import { computed, ref, watch } from 'vue';

interface PaymentModeOption {
    id: number;
    name: string;
    code: string | null;
}

const props = defineProps<{
    visible: boolean;
    transaction: Transaction | null;
    paymentModes: PaymentModeOption[];
    currencySymbol: string;
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    'add-payment': [paymentModeId: number, amount: string, paymentData?: any];
    'remove-payment': [paymentId: number];
    complete: [];
}>();

const selectedMode = ref<PaymentModeOption | null>(null);
const paymentAmount = ref<number | null>(null);

const balanceDue = computed(() => parseFloat(props.transaction?.balance_due || '0'));
const canComplete = computed(() => balanceDue.value <= 0 && (props.transaction?.items?.length ?? 0) > 0);

watch(() => props.visible, (val) => {
    if (val && props.transaction) {
        paymentAmount.value = parseFloat(props.transaction.balance_due || '0');
        selectedMode.value = props.paymentModes[0] ?? null;
    }
});

function fmt(amount: string | number | null | undefined): string {
    if (amount === null || amount === undefined) return `${props.currencySymbol}0.00`;
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return `${props.currencySymbol}${num.toFixed(2)}`;
}

function addPayment() {
    if (!selectedMode.value || !paymentAmount.value || paymentAmount.value <= 0) return;
    emit('add-payment', selectedMode.value.id, paymentAmount.value.toFixed(4));
    // Reset for next split
    setTimeout(() => {
        if (props.transaction) {
            paymentAmount.value = parseFloat(props.transaction.balance_due || '0');
        }
    }, 300);
}

function selectModeAndPay(mode: PaymentModeOption) {
    selectedMode.value = mode;
    addPayment();
}
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        header="Payment"
        modal
        :style="{ width: '480px' }"
    >
        <div v-if="transaction" class="space-y-4">
            <!-- Total & Balance -->
            <div class="text-center">
                <div class="text-sm text-muted-color">Total</div>
                <div class="text-3xl font-bold">{{ fmt(transaction.total) }}</div>
                <div v-if="balanceDue > 0" class="text-lg text-red-600 font-semibold mt-1">
                    Balance: {{ fmt(transaction.balance_due) }}
                </div>
                <div v-if="parseFloat(transaction.change_amount || '0') > 0" class="text-lg text-blue-600 font-semibold mt-1">
                    Change: {{ fmt(transaction.change_amount) }}
                </div>
            </div>

            <!-- Existing payments -->
            <div v-if="transaction.payments && transaction.payments.length > 0" class="space-y-1">
                <div class="text-xs text-muted-color font-semibold uppercase">Payments</div>
                <div
                    v-for="payment in transaction.payments"
                    :key="payment.id"
                    class="flex items-center justify-between p-2 rounded border text-sm"
                >
                    <span>{{ payment.payment_mode_name }}</span>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">{{ fmt(payment.amount) }}</span>
                        <Button
                            icon="pi pi-times"
                            severity="danger"
                            text
                            size="small"
                            @click="emit('remove-payment', payment.id)"
                        />
                    </div>
                </div>
            </div>

            <!-- Add payment -->
            <div v-if="balanceDue > 0">
                <div class="text-xs text-muted-color font-semibold uppercase mb-2">Add Payment</div>
                <InputNumber
                    v-model="paymentAmount"
                    :min="0.01"
                    :max-fraction-digits="2"
                    :min-fraction-digits="2"
                    mode="currency"
                    :currency="'USD'"
                    class="w-full mb-3"
                    size="small"
                    :prefix="currencySymbol"
                />

                <!-- Payment mode buttons (touch-friendly grid) -->
                <div class="grid grid-cols-2 gap-2">
                    <Button
                        v-for="mode in paymentModes"
                        :key="mode.id"
                        :label="mode.name"
                        severity="secondary"
                        size="small"
                        class="!py-3"
                        @click="selectModeAndPay(mode)"
                    />
                </div>
            </div>

            <!-- Complete button -->
            <Button
                v-if="canComplete"
                label="Complete Sale"
                icon="pi pi-check"
                class="w-full !py-3"
                severity="success"
                @click="emit('complete')"
            />
        </div>
    </Dialog>
</template>
