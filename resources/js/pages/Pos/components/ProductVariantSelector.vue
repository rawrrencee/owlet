<script setup lang="ts">
import Avatar from 'primevue/avatar';
import Dialog from 'primevue/dialog';

const props = defineProps<{
    visible: boolean;
    product: any;
    storeId: number;
    currencyId: number;
    currencySymbol: string;
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    select: [productId: number];
}>();

function getVariantPrice(variant: any): string {
    const ps = variant.product_stores?.[0];
    if (ps) {
        const storePrice = ps.store_prices?.find((sp: any) => sp.currency_id === props.currencyId);
        if (storePrice?.unit_price) return `${props.currencySymbol}${parseFloat(storePrice.unit_price).toFixed(2)}`;
    }
    const basePrice = variant.prices?.find((p: any) => p.currency_id === props.currencyId);
    if (basePrice?.unit_price) return `${props.currencySymbol}${parseFloat(basePrice.unit_price).toFixed(2)}`;
    return 'N/A';
}

function getVariantStock(variant: any): number | null {
    return variant.product_stores?.[0]?.quantity ?? null;
}

function selectVariant(variantId: number) {
    emit('select', variantId);
}

function getImageUrl(item: any): string | null {
    if (item.image_path) return `/products/${item.id}/image`;
    return null;
}
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        :header="product?.product_name ?? 'Select Variant'"
        modal
        :style="{ width: '400px' }"
        :content-style="{ padding: '0' }"
    >
        <div class="divide-y" v-if="product">
            <div
                v-for="variant in product.variants"
                :key="variant.id"
                class="flex items-center gap-3 p-3 cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors"
                @click="selectVariant(variant.id)"
            >
                <img
                    v-if="getImageUrl(variant)"
                    :src="getImageUrl(variant)!"
                    :alt="variant.variant_name"
                    class="w-10 h-10 rounded object-cover"
                />
                <Avatar
                    v-else
                    :label="(variant.variant_name || variant.product_name)?.charAt(0) ?? '?'"
                    shape="square"
                />
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium">{{ variant.variant_name || variant.product_name }}</div>
                    <div class="text-xs text-muted-color">{{ variant.product_number }}</div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-bold text-primary">{{ getVariantPrice(variant) }}</div>
                    <div v-if="getVariantStock(variant) !== null" class="text-[10px] text-muted-color">
                        {{ getVariantStock(variant) }} in stock
                    </div>
                </div>
            </div>
        </div>
    </Dialog>
</template>
