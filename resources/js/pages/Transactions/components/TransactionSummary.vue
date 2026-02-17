<script setup lang="ts">
import type { Transaction } from '@/types';
import Tag from 'primevue/tag';

defineProps<{
    transaction: Transaction;
}>();

function getStatusSeverity(status: string): string {
    switch (status) {
        case 'draft': return 'info';
        case 'suspended': return 'warn';
        case 'completed': return 'success';
        case 'voided': return 'danger';
        default: return 'info';
    }
}

function getStatusLabel(status: string): string {
    switch (status) {
        case 'draft': return 'Draft';
        case 'suspended': return 'Suspended';
        case 'completed': return 'Completed';
        case 'voided': return 'Voided';
        default: return status;
    }
}

function fmt(amount: string | number | null | undefined, symbol: string): string {
    if (amount === null || amount === undefined) return `${symbol}0.00`;
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return `${symbol}${num.toFixed(2)}`;
}

function formatDateTime(dateStr: string | null | undefined): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function hasDiscount(txn: Transaction): boolean {
    return (
        parseFloat(txn.offer_discount || '0') > 0 ||
        parseFloat(txn.bundle_discount || '0') > 0 ||
        parseFloat(txn.minimum_spend_discount || '0') > 0 ||
        parseFloat(txn.customer_discount || '0') > 0 ||
        parseFloat(txn.manual_discount || '0') > 0
    );
}
</script>

<template>
    <div>
        <div class="grid gap-4 text-sm sm:grid-cols-2">
            <!-- Left column -->
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Transaction #</span>
                    <span class="font-semibold">{{ transaction.transaction_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Status</span>
                    <Tag :value="getStatusLabel(transaction.status)" :severity="getStatusSeverity(transaction.status)" />
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Store</span>
                    <span>{{ transaction.store?.store_name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Employee</span>
                    <span>{{ transaction.employee?.full_name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Customer</span>
                    <span>{{ transaction.customer?.full_name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Currency</span>
                    <span>{{ transaction.currency?.code ?? '-' }} ({{ transaction.currency?.symbol }})</span>
                </div>
                <div v-if="transaction.checkout_date" class="flex justify-between">
                    <span class="text-muted-foreground">Checkout Date</span>
                    <span>{{ formatDateTime(transaction.checkout_date) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Created</span>
                    <span>{{ formatDateTime(transaction.created_at) }}</span>
                </div>
            </div>

            <!-- Right column: Totals -->
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Subtotal</span>
                    <span>{{ fmt(transaction.subtotal, transaction.currency?.symbol ?? '$') }}</span>
                </div>

                <template v-if="hasDiscount(transaction)">
                    <div v-if="parseFloat(transaction.offer_discount || '0') > 0" class="flex justify-between text-green-600">
                        <span>Item Discounts</span>
                        <span>-{{ fmt(transaction.offer_discount, transaction.currency?.symbol ?? '$') }}</span>
                    </div>
                    <div v-if="parseFloat(transaction.bundle_discount || '0') > 0" class="flex justify-between text-green-600">
                        <span>Bundle: {{ transaction.bundle_offer_name }}</span>
                        <span>-{{ fmt(transaction.bundle_discount, transaction.currency?.symbol ?? '$') }}</span>
                    </div>
                    <div v-if="parseFloat(transaction.minimum_spend_discount || '0') > 0" class="flex justify-between text-green-600">
                        <span>Min Spend: {{ transaction.minimum_spend_offer_name }}</span>
                        <span>-{{ fmt(transaction.minimum_spend_discount, transaction.currency?.symbol ?? '$') }}</span>
                    </div>
                    <div v-if="parseFloat(transaction.customer_discount || '0') > 0" class="flex justify-between text-green-600">
                        <span>Customer Discount</span>
                        <span>-{{ fmt(transaction.customer_discount, transaction.currency?.symbol ?? '$') }}</span>
                    </div>
                    <div v-if="parseFloat(transaction.manual_discount || '0') > 0" class="flex justify-between text-green-600">
                        <span>Manual Discount</span>
                        <span>-{{ fmt(transaction.manual_discount, transaction.currency?.symbol ?? '$') }}</span>
                    </div>
                </template>

                <div v-if="parseFloat(transaction.tax_amount || '0') > 0" class="flex justify-between">
                    <span class="text-muted-foreground">
                        Tax ({{ transaction.tax_percentage }}%{{ transaction.tax_inclusive ? ' incl.' : '' }})
                    </span>
                    <span>{{ fmt(transaction.tax_amount, transaction.currency?.symbol ?? '$') }}</span>
                </div>

                <div class="flex justify-between border-t pt-2 font-bold text-base">
                    <span>Total</span>
                    <span>{{ fmt(transaction.total, transaction.currency?.symbol ?? '$') }}</span>
                </div>

                <div v-if="parseFloat(transaction.amount_paid || '0') > 0" class="flex justify-between">
                    <span class="text-muted-foreground">Amount Paid</span>
                    <span>{{ fmt(transaction.amount_paid, transaction.currency?.symbol ?? '$') }}</span>
                </div>
                <div v-if="parseFloat(transaction.refund_amount || '0') > 0" class="flex justify-between text-orange-600">
                    <span>Refunded</span>
                    <span>{{ fmt(transaction.refund_amount, transaction.currency?.symbol ?? '$') }}</span>
                </div>
                <div v-if="parseFloat(transaction.balance_due || '0') > 0" class="flex justify-between text-red-600 font-semibold">
                    <span>Balance Due</span>
                    <span>{{ fmt(transaction.balance_due, transaction.currency?.symbol ?? '$') }}</span>
                </div>
                <div v-if="parseFloat(transaction.change_amount || '0') > 0" class="flex justify-between font-semibold" :class="transaction.status === 'completed' ? 'text-orange-600' : 'text-blue-600'">
                    <span>{{ transaction.status === 'completed' ? 'Refund Due' : 'Change' }}</span>
                    <span>{{ fmt(transaction.change_amount, transaction.currency?.symbol ?? '$') }}</span>
                </div>
            </div>
        </div>

        <div v-if="transaction.comments" class="mt-4 border-t pt-3">
            <div class="text-xs text-muted-foreground font-semibold uppercase mb-1">Comments</div>
            <p class="text-sm">{{ transaction.comments }}</p>
        </div>
    </div>
</template>
