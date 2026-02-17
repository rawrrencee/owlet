<script setup lang="ts">
import type { Transaction } from '@/types';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Tag from 'primevue/tag';

const props = defineProps<{
    transaction: Transaction | null;
    currencySymbol: string;
}>();

const emit = defineEmits<{
    'update-item': [itemId: number, updates: { quantity?: number; unit_price?: string }];
    'remove-item': [itemId: number];
    back: [];
}>();

function formatPrice(amount: string | number): string {
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return `${props.currencySymbol}${num.toFixed(2)}`;
}

function onQuantityChange(itemId: number, newQty: number | null) {
    if (newQty && newQty > 0) {
        emit('update-item', itemId, { quantity: newQty });
    }
}
</script>

<template>
    <div class="p-3">
        <div v-if="!transaction || !transaction.items?.length" class="text-center text-muted-color py-8">
            <i class="pi pi-shopping-cart text-4xl mb-2" />
            <p class="text-sm">Cart is empty</p>
            <p class="text-xs">Tap a product to add it</p>
        </div>

        <div v-else class="space-y-2">
            <div
                v-for="item in transaction.items"
                :key="item.id"
                class="flex items-start gap-2 p-2 rounded border bg-surface-0 dark:bg-surface-900"
                :class="{ 'opacity-50': item.is_refunded }"
            >
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium leading-tight">
                        {{ item.product_name }}
                        <span v-if="item.variant_name" class="text-muted-color"> - {{ item.variant_name }}</span>
                    </div>
                    <div class="text-xs text-muted-color">{{ item.product_number }}</div>

                    <!-- Offer tag -->
                    <Tag
                        v-if="item.offer_name"
                        :value="item.offer_name"
                        severity="success"
                        class="mt-1 !text-[10px]"
                    />
                    <Tag
                        v-if="item.is_refunded"
                        value="Refunded"
                        severity="danger"
                        class="mt-1 !text-[10px]"
                    />

                    <div class="flex items-center gap-2 mt-1.5">
                        <InputNumber
                            v-if="!item.is_refunded"
                            :model-value="item.quantity"
                            @update:model-value="(val: any) => onQuantityChange(item.id, val)"
                            :min="1"
                            :max="9999"
                            showButtons
                            buttonLayout="horizontal"
                            size="small"
                            :input-style="{ width: '40px', textAlign: 'center' }"
                            decrementButtonClass="p-button-secondary p-button-sm"
                            incrementButtonClass="p-button-secondary p-button-sm"
                            decrementButtonIcon="pi pi-minus"
                            incrementButtonIcon="pi pi-plus"
                        />
                        <span class="text-xs text-muted-color">
                            @ {{ formatPrice(item.unit_price) }}
                        </span>
                    </div>
                </div>

                <div class="text-right flex-shrink-0">
                    <div class="text-sm font-bold">{{ formatPrice(item.line_total) }}</div>
                    <div v-if="parseFloat(item.line_discount) > 0" class="text-[10px] text-red-500 line-through">
                        {{ formatPrice(item.line_subtotal) }}
                    </div>
                    <Button
                        v-if="!item.is_refunded"
                        icon="pi pi-times"
                        severity="danger"
                        text
                        size="small"
                        class="mt-1"
                        @click="emit('remove-item', item.id)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
