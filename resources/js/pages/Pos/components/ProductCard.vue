<script setup lang="ts">
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import { computed } from 'vue';

interface CurrencyInfo {
    id: number;
    code: string;
    symbol: string;
    name: string;
    exchange_rate: string;
}

const props = defineProps<{
    product: any;
    storeId: number;
    currencyId: number;
    currencySymbol: string;
    currencies?: CurrencyInfo[];
    isFavourite?: boolean;
}>();

const emit = defineEmits<{
    select: [product: any];
    'toggle-favourite': [];
}>();

interface PriceResult {
    price: string;
    converted: boolean;
    sourceCode?: string;
}

function getPrice(): PriceResult | null {
    // 1. Check store price in selected currency
    const ps = props.product.product_stores?.[0];
    if (ps) {
        const storePrice = ps.store_prices?.find((sp: any) => sp.currency_id === props.currencyId);
        if (storePrice?.unit_price) return { price: storePrice.unit_price, converted: false };
    }

    // 2. Check base price in selected currency
    const basePrice = props.product.prices?.find((p: any) => p.currency_id === props.currencyId);
    if (basePrice?.unit_price) return { price: basePrice.unit_price, converted: false };

    // 3. Convert base price from another currency
    if (props.currencies && props.currencies.length > 1 && props.product.prices?.length > 0) {
        const targetCurrency = props.currencies.find(c => c.id === props.currencyId);
        if (targetCurrency) {
            const targetRate = parseFloat(targetCurrency.exchange_rate);
            for (const bp of props.product.prices) {
                if (bp.unit_price && bp.currency_id !== props.currencyId) {
                    const sourceCurrency = props.currencies.find(c => c.id === bp.currency_id);
                    if (sourceCurrency) {
                        const sourceRate = parseFloat(sourceCurrency.exchange_rate);
                        if (sourceRate > 0) {
                            const converted = parseFloat(bp.unit_price) * (targetRate / sourceRate);
                            return {
                                price: converted.toFixed(2),
                                converted: true,
                                sourceCode: sourceCurrency.code,
                            };
                        }
                    }
                }
            }
        }
    }

    return null;
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

const priceResult = computed(() => getPrice());
const stockCount = computed(() => getStock());
const hasVariants = props.product.variants && props.product.variants.length > 0;
</script>

<template>
    <div
        class="relative border rounded-lg p-2 cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors active:scale-[0.98] select-none"
        @click="emit('select', product)"
    >
        <!-- Favourite star -->
        <button
            class="absolute top-1 left-1 z-10 w-6 h-6 flex items-center justify-center rounded-full bg-surface-0/80 dark:bg-surface-800/80 hover:bg-surface-200 dark:hover:bg-surface-600 transition-colors cursor-pointer"
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
        <div class="text-xs font-medium leading-tight line-clamp-2 min-h-[2.5em]">{{ product.product_name }}</div>
        <div class="text-xs text-muted-color truncate">{{ product.product_number }}</div>
        <div class="mt-1">
            <div class="text-xs font-bold text-primary">
                <template v-if="priceResult">
                    <span
                        v-if="priceResult.converted"
                        v-tooltip.top="'Converted from ' + priceResult.sourceCode"
                    >
                        {{ formatPrice(priceResult.price) }}*
                    </span>
                    <span v-else>{{ formatPrice(priceResult.price) }}</span>
                </template>
                <span v-else>N/A</span>
            </div>
            <div v-if="stockCount !== null" class="text-[10px] text-muted-color">
                {{ stockCount }} in stock
            </div>
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
