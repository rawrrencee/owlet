<script setup lang="ts">
import type { TransactionItem } from '@/types';
import Avatar from 'primevue/avatar';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Image from 'primevue/image';
import Tag from 'primevue/tag';
import { ref } from 'vue';

const props = defineProps<{
    items: TransactionItem[];
    currencySymbol: string;
}>();

const expandedRows = ref({});

function fmt(amount: string | number | null | undefined): string {
    if (amount === null || amount === undefined) return `${props.currencySymbol}0.00`;
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return `${props.currencySymbol}${num.toFixed(2)}`;
}

function productInitial(name: string): string {
    return name.charAt(0).toUpperCase();
}
</script>

<template>
    <DataTable
        v-model:expandedRows="expandedRows"
        :value="items"
        dataKey="id"
        striped-rows
        size="small"
        class="overflow-hidden rounded-lg border border-border"
    >
        <template #empty>
            <div class="p-4 text-center text-muted-foreground">No items.</div>
        </template>
        <Column expander class="w-[12%] sm:w-12 !pr-0 md:hidden" />
        <Column header="Product">
            <template #body="{ data }">
                <div class="flex items-center gap-2">
                    <Image
                        v-if="data.product?.image_path"
                        :src="`/storage/${data.product.image_path}`"
                        :alt="data.product_name"
                        width="32"
                        preview
                        class="shrink-0"
                    />
                    <Avatar
                        v-else
                        :label="productInitial(data.product_name)"
                        size="normal"
                        shape="square"
                        class="shrink-0"
                    />
                    <div class="min-w-0">
                        <div class="font-medium text-sm truncate">{{ data.product_name }}</div>
                        <div class="text-xs text-muted-foreground">
                            {{ data.product_number }}
                            <span v-if="data.variant_name"> &middot; {{ data.variant_name }}</span>
                        </div>
                    </div>
                </div>
            </template>
        </Column>
        <Column header="Qty" class="w-16 text-center hidden sm:table-cell">
            <template #body="{ data }">
                {{ data.quantity }}
            </template>
        </Column>
        <Column header="Unit Price" class="hidden md:table-cell">
            <template #body="{ data }">
                {{ fmt(data.unit_price) }}
            </template>
        </Column>
        <Column header="Discount" class="hidden lg:table-cell">
            <template #body="{ data }">
                <template v-if="parseFloat(data.line_discount || '0') > 0">
                    <span class="text-green-600">-{{ fmt(data.line_discount) }}</span>
                    <div v-if="data.offer_name" class="text-xs text-muted-foreground">{{ data.offer_name }}</div>
                </template>
                <span v-else class="text-muted-foreground">-</span>
            </template>
        </Column>
        <Column header="Total" class="hidden sm:table-cell">
            <template #body="{ data }">
                <div class="flex items-center gap-2">
                    <span class="font-semibold">{{ fmt(data.line_total) }}</span>
                    <Tag v-if="data.is_refunded" value="Refunded" severity="danger" class="!text-[10px]" />
                </div>
            </template>
        </Column>
        <template #expansion="{ data }">
            <div class="grid gap-3 p-3 text-sm md:hidden">
                <div class="flex justify-between border-b border-border pb-2">
                    <span class="text-muted-foreground">Quantity</span>
                    <span>{{ data.quantity }}</span>
                </div>
                <div class="flex justify-between border-b border-border pb-2">
                    <span class="text-muted-foreground">Unit Price</span>
                    <span>{{ fmt(data.unit_price) }}</span>
                </div>
                <div v-if="parseFloat(data.line_discount || '0') > 0" class="flex justify-between border-b border-border pb-2">
                    <span class="text-muted-foreground">Discount</span>
                    <span class="text-green-600">-{{ fmt(data.line_discount) }}</span>
                </div>
                <div v-if="data.offer_name" class="flex justify-between border-b border-border pb-2">
                    <span class="text-muted-foreground">Offer</span>
                    <span>{{ data.offer_name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Line Total</span>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">{{ fmt(data.line_total) }}</span>
                        <Tag v-if="data.is_refunded" value="Refunded" severity="danger" class="!text-[10px]" />
                    </div>
                </div>
                <div v-if="data.is_refunded && data.refund_reason" class="text-xs text-red-600">
                    Reason: {{ data.refund_reason }}
                </div>
            </div>
        </template>
    </DataTable>
</template>
