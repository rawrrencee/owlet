<script setup lang="ts">
import type { Transaction } from '@/types';

const props = defineProps<{
    transaction: Transaction | null;
    currencySymbol: string;
}>();

function fmt(amount: string | number | null | undefined): string {
    if (amount === null || amount === undefined) return `${props.currencySymbol}0.00`;
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return `${props.currencySymbol}${num.toFixed(2)}`;
}

function hasDiscount(): boolean {
    if (!props.transaction) return false;
    return (
        parseFloat(props.transaction.offer_discount || '0') > 0 ||
        parseFloat(props.transaction.bundle_discount || '0') > 0 ||
        parseFloat(props.transaction.minimum_spend_discount || '0') > 0 ||
        parseFloat(props.transaction.customer_discount || '0') > 0 ||
        parseFloat(props.transaction.manual_discount || '0') > 0
    );
}
</script>

<template>
    <div v-if="transaction" class="px-3 py-2 text-sm space-y-1">
        <div class="flex justify-between">
            <span class="text-muted-color">Subtotal</span>
            <span>{{ fmt(transaction.subtotal) }}</span>
        </div>

        <template v-if="hasDiscount()">
            <div v-if="parseFloat(transaction.offer_discount || '0') > 0" class="flex justify-between text-green-600">
                <span>Item Discounts</span>
                <span>-{{ fmt(transaction.offer_discount) }}</span>
            </div>
            <div v-if="parseFloat(transaction.bundle_discount || '0') > 0" class="flex justify-between text-green-600">
                <span>Bundle: {{ transaction.bundle_offer_name }}</span>
                <span>-{{ fmt(transaction.bundle_discount) }}</span>
            </div>
            <div v-if="parseFloat(transaction.minimum_spend_discount || '0') > 0" class="flex justify-between text-green-600">
                <span>Min Spend: {{ transaction.minimum_spend_offer_name }}</span>
                <span>-{{ fmt(transaction.minimum_spend_discount) }}</span>
            </div>
            <div v-if="parseFloat(transaction.customer_discount || '0') > 0" class="flex justify-between text-green-600">
                <span>Customer Discount</span>
                <span>-{{ fmt(transaction.customer_discount) }}</span>
            </div>
            <div v-if="parseFloat(transaction.manual_discount || '0') > 0" class="flex justify-between text-green-600">
                <span>Manual Discount</span>
                <span>-{{ fmt(transaction.manual_discount) }}</span>
            </div>
        </template>

        <div v-if="parseFloat(transaction.tax_amount || '0') > 0" class="flex justify-between">
            <span class="text-muted-color">
                Tax ({{ transaction.tax_percentage }}%{{ transaction.tax_inclusive ? ' incl.' : '' }})
            </span>
            <span>{{ fmt(transaction.tax_amount) }}</span>
        </div>

        <div class="flex justify-between font-bold text-base border-t pt-1">
            <span>Total</span>
            <span>{{ fmt(transaction.total) }}</span>
        </div>

        <div v-if="parseFloat(transaction.amount_paid || '0') > 0" class="flex justify-between text-muted-color">
            <span>Paid</span>
            <span>{{ fmt(transaction.amount_paid) }}</span>
        </div>
        <div v-if="parseFloat(transaction.balance_due || '0') > 0" class="flex justify-between text-red-600 font-semibold">
            <span>Balance Due</span>
            <span>{{ fmt(transaction.balance_due) }}</span>
        </div>
        <div v-if="parseFloat(transaction.change_amount || '0') > 0" class="flex justify-between text-blue-600 font-semibold">
            <span>Change</span>
            <span>{{ fmt(transaction.change_amount) }}</span>
        </div>
    </div>
</template>
