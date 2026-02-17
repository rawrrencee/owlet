<script setup lang="ts">
import AutoComplete from 'primevue/autocomplete';
import Avatar from 'primevue/avatar';
import { ref } from 'vue';

const props = defineProps<{
    storeId: number;
    currencyId: number;
}>();

const emit = defineEmits<{
    select: [product: any];
}>();

const searchQuery = ref('');
const suggestions = ref<any[]>([]);
const searching = ref(false);

async function searchProducts(event: any) {
    const query = event.query?.trim();
    if (!query || query.length < 1) {
        suggestions.value = [];
        return;
    }
    searching.value = true;
    try {
        const response = await fetch(
            `/pos/search-products?search=${encodeURIComponent(query)}&store_id=${props.storeId}&currency_id=${props.currencyId}`,
            {
                headers: {
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
                    ),
                },
            }
        );
        suggestions.value = await response.json();
    } catch {
        suggestions.value = [];
    } finally {
        searching.value = false;
    }
}

function onSelect(event: any) {
    const product = event.value;
    if (product && typeof product === 'object') {
        emit('select', product);
        searchQuery.value = '';
    }
}

function getProductImageUrl(product: any): string | null {
    if (product.image_path) {
        return `/products/${product.id}/image`;
    }
    return null;
}
</script>

<template>
    <AutoComplete
        v-model="searchQuery"
        :suggestions="suggestions"
        optionLabel="product_name"
        placeholder="Search products or scan barcode..."
        class="w-full"
        :input-class="'w-full'"
        :loading="searching"
        @complete="searchProducts"
        @item-select="onSelect"
    >
        <template #option="{ option }">
            <div class="flex items-center gap-2 py-1">
                <img
                    v-if="getProductImageUrl(option)"
                    :src="getProductImageUrl(option)!"
                    :alt="option.product_name"
                    class="w-8 h-8 rounded object-cover"
                />
                <Avatar
                    v-else
                    :label="option.product_name?.charAt(0) ?? '?'"
                    size="normal"
                    shape="square"
                />
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate">
                        {{ option.product_name }}
                        <span v-if="option.variant_name" class="text-muted-color"> - {{ option.variant_name }}</span>
                    </div>
                    <div class="text-xs text-muted-color">{{ option.product_number }}</div>
                </div>
                <span v-if="option.barcode" class="text-xs text-muted-color hidden sm:inline">{{ option.barcode }}</span>
            </div>
        </template>
    </AutoComplete>
</template>
