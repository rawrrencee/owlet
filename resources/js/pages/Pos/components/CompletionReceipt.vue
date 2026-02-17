<script setup lang="ts">
import type { Transaction } from '@/types';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';

const props = defineProps<{
    visible: boolean;
    transaction: Transaction | null;
    currencySymbol: string;
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    'new-transaction': [];
}>();

function fmt(amount: string | number | null | undefined): string {
    if (amount === null || amount === undefined) return `${props.currencySymbol}0.00`;
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return `${props.currencySymbol}${num.toFixed(2)}`;
}
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        header="Sale Complete"
        modal
        :closable="false"
        :style="{ width: '400px' }"
    >
        <div v-if="transaction" class="text-center space-y-4">
            <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900 mx-auto flex items-center justify-center">
                <i class="pi pi-check text-3xl text-green-600" />
            </div>

            <div>
                <div class="text-sm text-muted-color">Transaction</div>
                <div class="font-semibold">{{ transaction.transaction_number }}</div>
            </div>

            <div>
                <div class="text-sm text-muted-color">Total</div>
                <div class="text-2xl font-bold">{{ fmt(transaction.total) }}</div>
            </div>

            <div v-if="parseFloat(transaction.change_amount || '0') > 0" class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-3">
                <div class="text-sm text-blue-600 dark:text-blue-400">Change Due</div>
                <div class="text-xl font-bold text-blue-600 dark:text-blue-400">
                    {{ fmt(transaction.change_amount) }}
                </div>
            </div>

            <!-- Payment summary -->
            <div v-if="transaction.payments && transaction.payments.length > 0" class="text-left space-y-1">
                <div class="text-xs text-muted-color font-semibold uppercase">Payments</div>
                <div
                    v-for="payment in transaction.payments"
                    :key="payment.id"
                    class="flex justify-between text-sm"
                >
                    <span>{{ payment.payment_mode_name }}</span>
                    <span class="font-medium">{{ fmt(payment.amount) }}</span>
                </div>
            </div>

            <Button
                label="New Transaction"
                icon="pi pi-plus"
                class="w-full !py-3"
                @click="emit('new-transaction')"
            />
        </div>
    </Dialog>
</template>
