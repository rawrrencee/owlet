<script setup lang="ts">
import AutoComplete from 'primevue/autocomplete';
import Avatar from 'primevue/avatar';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';

const props = defineProps<{
    storeId: number;
    currencyId: number;
}>();

const emit = defineEmits<{
    select: [product: any];
}>();

const toast = useToast();
const searchQuery = ref('');
const suggestions = ref<any[]>([]);
const searching = ref(false);

// Barcode scan dedup: track last scanned barcode + timestamp
let lastScanBarcode = '';
let lastScanTime = 0;

// Physical barcode scanner detection
let scanBuffer = '';
let lastKeyTime = 0;

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

async function scanBarcode(barcode: string) {
    const trimmed = barcode.trim();
    if (!trimmed) return;

    // Dedup: ignore same barcode within 3 seconds
    const now = Date.now();
    if (trimmed === lastScanBarcode && now - lastScanTime < 3000) {
        return;
    }
    lastScanBarcode = trimmed;
    lastScanTime = now;

    // Search by barcode
    try {
        const response = await fetch(
            `/pos/search-products?search=${encodeURIComponent(trimmed)}&store_id=${props.storeId}&currency_id=${props.currencyId}`,
            {
                headers: {
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
                    ),
                },
            }
        );
        const results = await response.json();
        if (results.length === 1) {
            // Auto-select single match
            emit('select', results[0]);
            searchQuery.value = '';
        } else if (results.length > 1) {
            // Show results in autocomplete
            suggestions.value = results;
            searchQuery.value = trimmed;
        } else {
            toast.add({
                severity: 'warn',
                summary: 'Product not found',
                detail: `No product found for barcode: ${trimmed}`,
                life: 3000,
            });
        }
    } catch {
        toast.add({
            severity: 'error',
            summary: 'Search failed',
            detail: 'Failed to search for barcode.',
            life: 3000,
        });
    }
}

function onKeydown(event: KeyboardEvent) {
    const now = Date.now();

    if (event.key === 'Enter') {
        if (scanBuffer.length >= 4) {
            event.preventDefault();
            scanBarcode(scanBuffer);
        }
        scanBuffer = '';
        return;
    }

    // Only buffer printable single characters
    if (event.key.length === 1) {
        if (now - lastKeyTime > 50) {
            // Too slow — reset buffer (user is typing normally)
            scanBuffer = '';
        }
        scanBuffer += event.key;
        lastKeyTime = now;
    }
}

function getProductImageUrl(product: any): string | null {
    if (product.image_path) {
        return `/products/${product.id}/image`;
    }
    return null;
}

defineExpose({ scanBarcode });
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
        :input-props="{ onKeydown }"
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
