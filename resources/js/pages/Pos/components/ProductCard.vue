<script setup lang="ts">
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';

const props = defineProps<{
    product: any;
    storeId: number;
    currencyId: number;
    currencySymbol: string;
    isFavourite?: boolean;
}>();

const emit = defineEmits<{
    select: [product: any];
    'toggle-favourite': [];
}>();

function getPrice(): string | null {
    // Check store price first
    const ps = props.product.product_stores?.[0];
    if (ps) {
        const storePrice = ps.store_prices?.find((sp: any) => sp.currency_id === props.currencyId);
        if (storePrice?.unit_price) return storePrice.unit_price;
    }
    // Fallback to base price
    const basePrice = props.product.prices?.find((p: any) => p.currency_id === props.currencyId);
    return basePrice?.unit_price ?? null;
}

function getStock(): number | null {
    const ps = props.product.product_stores?.[0];
    return ps?.quantity ?? null;
}

function formatPrice(price: string | null): string {
    if (!price) return 'N/A';
    return `${props.currencySymbol}${parseFloat(price).toFixed(2)}`;
}

function getImageUrl(): string | null {
    if (props.product.image_path) {
        return `/products/${props.product.id}/image`;
    }
    return null;
}

const hasVariants = props.product.variants && props.product.variants.length > 0;
</script>

<template>
    <div
        class="relative border rounded-lg p-2 cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors active:scale-[0.98] select-none"
        @click="emit('select', product)"
    >
        <!-- Favourite star -->
        <button
            class="absolute top-1 left-1 z-10 w-6 h-6 flex items-center justify-center rounded-full bg-surface-0/80 dark:bg-surface-800/80 hover:bg-surface-200 dark:hover:bg-surface-600 transition-colors"
            @click.stop="emit('toggle-favourite')"
        >
            <i
                :class="[
                    isFavourite ? 'pi pi-star-fill text-yellow-500' : 'pi pi-star text-muted-color',
                    'text-xs',
                ]"
            />
        </button>

        <!-- Image -->
        <div class="aspect-square mb-1.5 rounded overflow-hidden bg-surface-100 dark:bg-surface-700 flex items-center justify-center">
            <img
                v-if="getImageUrl()"
                :src="getImageUrl()!"
                :alt="product.product_name"
                class="w-full h-full object-cover"
                loading="lazy"
            />
            <Avatar
                v-else
                :label="product.product_name?.charAt(0) ?? '?'"
                size="xlarge"
                shape="square"
                class="!w-full !h-full !text-2xl"
            />
        </div>

        <!-- Info -->
        <div class="text-xs font-medium leading-tight truncate">{{ product.product_name }}</div>
        <div class="text-xs text-muted-color truncate">{{ product.product_number }}</div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-xs font-bold text-primary">{{ formatPrice(getPrice()) }}</span>
            <span v-if="getStock() !== null" class="text-[10px] text-muted-color">
                {{ getStock() }} in stock
            </span>
        </div>

        <!-- Variant badge -->
        <Badge
            v-if="hasVariants"
            :value="product.variants.length + ' variants'"
            severity="info"
            class="!absolute top-1 right-1 !text-[10px]"
        />
    </div>
</template>
