<script setup lang="ts">
import type { Transaction } from '@/types';
import ToggleSwitch from 'primevue/toggleswitch';
import { computed } from 'vue';

const props = defineProps<{
    transaction: Transaction | null;
    currencySymbol: string;
    canApplyDiscounts?: boolean;
}>();

const emit = defineEmits<{
    'toggle-customer-discount': [];
    'open-manual-discount': [];
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

const customerDiscountActive = computed(() => {
    if (!props.transaction) return false;
    return !!props.transaction.customer_discount_percentage &&
        parseFloat(props.transaction.customer_discount_percentage) > 0;
});

const customerHasDiscount = computed(() => {
    if (!props.transaction?.customer) return false;
    return !!props.transaction.customer.discount_percentage &&
        parseFloat(props.transaction.customer.discount_percentage) > 0;
});

const isDraft = computed(() => props.transaction?.status === 'draft');

const hasManualDiscount = computed(() => {
    if (!props.transaction) return false;
    return parseFloat(props.transaction.manual_discount || '0') > 0;
});
</script>

<template>
    <div v-if="transaction" class="px-3 py-2 text-sm space-y-1">
        <div class="flex justify-between">
            <span class="text-muted-color">Subtotal</span>
            <span>{{ fmt(transaction.subtotal) }}</span>
        </div>

        <template v-if="hasDiscount() || customerHasDiscount">
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

            <!-- Customer discount row with toggle -->
            <div v-if="customerHasDiscount" class="flex justify-between items-center text-green-600">
                <span class="flex items-center gap-1.5">
                    Customer ({{ transaction.customer?.discount_percentage }}%)
                    <ToggleSwitch
                        v-if="canApplyDiscounts && isDraft"
                        :modelValue="customerDiscountActive"
                        class="!scale-75"
                        @update:modelValue="emit('toggle-customer-discount')"
                    />
                </span>
                <span v-if="customerDiscountActive">-{{ fmt(transaction.customer_discount) }}</span>
                <span v-else class="text-muted-color line-through">off</span>
            </div>

            <!-- Manual discount row -->
            <div
                v-if="hasManualDiscount"
                class="flex justify-between items-center text-green-600"
                :class="{ 'cursor-pointer hover:text-green-700': canApplyDiscounts && isDraft }"
                @click="canApplyDiscounts && isDraft ? emit('open-manual-discount') : undefined"
            >
                <span class="flex items-center gap-1">
                    Manual Discount
                    <i v-if="canApplyDiscounts && isDraft" class="pi pi-pencil text-[10px]" />
                </span>
                <span>-{{ fmt(transaction.manual_discount) }}</span>
            </div>
        </template>

        <!-- Add discount button -->
        <div v-if="canApplyDiscounts && isDraft && !hasManualDiscount" class="flex justify-end">
            <button
                class="text-xs text-primary hover:underline flex items-center gap-1"
                @click="emit('open-manual-discount')"
            >
                <i class="pi pi-percentage !text-[8px]" />
                Add Discount
            </button>
        </div>

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
