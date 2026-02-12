<script setup lang="ts">
import type { StocktakeProductSearchResult } from '@/types';
import axios from 'axios';
import AutoComplete from 'primevue/autocomplete';
import { ref } from 'vue';

const props = defineProps<{
    stocktakeId: number;
}>();

const emit = defineEmits<{
    select: [product: StocktakeProductSearchResult];
}>();

const searchQuery = ref('');
const suggestions = ref<StocktakeProductSearchResult[]>([]);
const loading = ref(false);
let lastScannedBarcode = '';

async function fetchProducts(query: string): Promise<StocktakeProductSearchResult[]> {
    const response = await axios.get(`/stocktakes/${props.stocktakeId}/search-products`, {
        params: { q: query },
    });
    return response.data;
}

async function search(event: { query: string }) {
    if (event.query.length < 2) {
        suggestions.value = [];
        return;
    }

    loading.value = true;
    try {
        suggestions.value = await fetchProducts(event.query);
    } catch {
        suggestions.value = [];
    } finally {
        loading.value = false;
    }
}

function onSelect(event: { value: StocktakeProductSearchResult }) {
    emit('select', event.value);
    searchQuery.value = '';
}

async function scanBarcode(barcode: string) {
    // Deduplicate rapid-fire scans of the same barcode
    if (barcode === lastScannedBarcode) return;
    lastScannedBarcode = barcode;
    setTimeout(() => { lastScannedBarcode = ''; }, 3000);

    searchQuery.value = barcode;
    loading.value = true;
    try {
        const products = await fetchProducts(barcode);
        if (products.length > 0) {
            emit('select', products[0]);
            searchQuery.value = '';
        } else {
            suggestions.value = products;
        }
    } catch {
        suggestions.value = [];
    } finally {
        loading.value = false;
    }
}

defineExpose({ scanBarcode });
</script>

<template>
    <AutoComplete
        v-model="searchQuery"
        :suggestions="suggestions"
        optionLabel="product_name"
        :loading="loading"
        placeholder="Search by name, number, or barcode..."
        size="small"
        fluid
        @complete="search"
        @item-select="onSelect"
    >
        <template #option="{ option }">
            <div class="flex items-center gap-3 py-1">
                <img
                    v-if="option.image_url"
                    :src="option.image_url"
                    :alt="option.product_name"
                    class="h-8 w-8 rounded object-cover"
                />
                <div
                    v-else
                    class="flex h-8 w-8 items-center justify-center rounded bg-muted"
                >
                    <i class="pi pi-box text-xs text-muted-foreground" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-medium">
                        {{ option.product_name }}
                        <span
                            v-if="option.variant_name"
                            class="text-muted-foreground"
                        >
                            - {{ option.variant_name }}
                        </span>
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ option.product_number }}
                        <span v-if="option.barcode">
                            | {{ option.barcode }}
                        </span>
                    </div>
                </div>
            </div>
        </template>
    </AutoComplete>
</template>
