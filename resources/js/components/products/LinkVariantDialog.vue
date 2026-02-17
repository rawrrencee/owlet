<script setup lang="ts">
import type { ProductSearchResult } from '@/types';
import AutoComplete, {
    type AutoCompleteCompleteEvent,
} from 'primevue/autocomplete';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import { ref, watch } from 'vue';

interface Props {
    visible: boolean;
    productId: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:visible', value: boolean): void;
    (
        e: 'linked',
        variant: {
            id: number;
            variant_name: string;
            product_number: string;
            is_active: boolean;
        }
    ): void;
}>();

const searchQuery = ref<string | ProductSearchResult>('');
const searchResults = ref<ProductSearchResult[]>([]);
const searchLoading = ref(false);
const selectedProduct = ref<ProductSearchResult | null>(null);
const variantName = ref('');
const submitting = ref(false);
const error = ref<string | null>(null);

// Reset state when dialog opens/closes
watch(
    () => props.visible,
    (newVal) => {
        if (!newVal) {
            searchQuery.value = '';
            searchResults.value = [];
            selectedProduct.value = null;
            variantName.value = '';
            error.value = null;
        }
    }
);

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

async function onSearch(event: AutoCompleteCompleteEvent) {
    const query = event.query;
    if (query.length < 2) {
        searchResults.value = [];
        return;
    }

    searchLoading.value = true;
    try {
        const response = await fetch(
            `/products/${props.productId}/search-linkable?q=${encodeURIComponent(query)}`,
            {
                headers: {
                    Accept: 'application/json',
                },
            }
        );
        if (response.ok) {
            const data = await response.json();
            searchResults.value = data.data;
        }
    } catch {
        // Ignore errors
    } finally {
        searchLoading.value = false;
    }
}

function onSelect(product: ProductSearchResult) {
    selectedProduct.value = product;
    searchQuery.value = '';
    // Pre-fill variant name with product name as suggestion
    if (!variantName.value) {
        variantName.value = product.product_name;
    }
}

function clearSelection() {
    selectedProduct.value = null;
    variantName.value = '';
}

async function submit() {
    if (!selectedProduct.value || !variantName.value.trim()) {
        return;
    }

    submitting.value = true;
    error.value = null;

    try {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        const response = await fetch(
            `/products/${props.productId}/link-variant`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    product_id: selectedProduct.value.id,
                    variant_name: variantName.value.trim(),
                }),
            }
        );

        const data = await response.json();

        if (response.ok) {
            emit('linked', data.variant);
            emit('update:visible', false);
        } else {
            error.value = data.message || 'Failed to link product as variant.';
        }
    } catch (err) {
        console.error('Link variant error:', err);
        error.value = 'An error occurred. Please try again.';
    } finally {
        submitting.value = false;
    }
}

function close() {
    emit('update:visible', false);
}
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        dismissable-mask
        :style="{ width: '30rem' }"
        :breakpoints="{ '640px': '95vw' }"
        :draggable="false"
        header="Link Existing Product as Variant"
        @update:visible="emit('update:visible', $event)"
    >
        <div class="flex flex-col gap-4">
            <Message v-if="error" severity="error" :closable="false">
                {{ error }}
            </Message>

            <!-- Search for product -->
            <div v-if="!selectedProduct" class="flex flex-col gap-2">
                <label class="font-medium">Search Product</label>
                <AutoComplete
                    v-model="searchQuery"
                    :suggestions="searchResults"
                    option-label="product_name"
                    placeholder="Search by name, SKU, or barcode..."
                    size="small"
                    fluid
                    :loading="searchLoading"
                    :overlay-style="{ maxWidth: 'min(27.5rem, calc(100vw - 2.5rem))' }"
                    @complete="onSearch"
                    @item-select="onSelect($event.value)"
                >
                    <template #option="{ option }">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <img
                                v-if="option.image_url"
                                :src="option.image_url"
                                :alt="option.product_name"
                                class="h-8 w-8 shrink-0 rounded object-cover"
                            />
                            <Avatar
                                v-else
                                :label="getInitials(option.product_name)"
                                shape="square"
                                class="!h-8 !w-8 shrink-0 rounded bg-primary/10 !text-xs text-primary"
                            />
                            <div class="flex min-w-0 flex-1 flex-col">
                                <span class="truncate text-sm font-medium">{{
                                    option.product_name
                                }}</span>
                                <span class="truncate text-xs text-muted-foreground">
                                    {{ option.product_number }}
                                    <template v-if="option.brand_name">
                                        &middot; {{ option.brand_name }}
                                    </template>
                                </span>
                            </div>
                        </div>
                    </template>
                </AutoComplete>
                <small class="text-muted-foreground">
                    Only standalone products (not variants or parents) can be
                    linked.
                </small>
            </div>

            <!-- Selected product display -->
            <div v-else class="flex flex-col gap-2">
                <label class="font-medium">Selected Product</label>
                <div
                    class="flex items-center gap-3 rounded-lg border border-border p-3"
                >
                    <img
                        v-if="selectedProduct.image_url"
                        :src="selectedProduct.image_url"
                        :alt="selectedProduct.product_name"
                        class="h-12 w-12 rounded object-cover"
                    />
                    <Avatar
                        v-else
                        :label="getInitials(selectedProduct.product_name)"
                        shape="square"
                        class="!h-12 !w-12 rounded bg-primary/10 text-lg text-primary"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="font-medium">
                            {{ selectedProduct.product_name }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                            {{ selectedProduct.product_number }}
                            <template v-if="selectedProduct.brand_name">
                                &middot; {{ selectedProduct.brand_name }}
                            </template>
                        </div>
                    </div>
                    <Button
                        icon="pi pi-times"
                        severity="secondary"
                        text
                        rounded
                        size="small"
                        @click="clearSelection"
                        v-tooltip.left="'Remove'"
                    />
                </div>
            </div>

            <!-- Variant name input -->
            <div v-if="selectedProduct" class="flex flex-col gap-2">
                <label for="variant_name" class="font-medium"
                    >Variant Name *</label
                >
                <InputText
                    id="variant_name"
                    v-model="variantName"
                    placeholder="e.g., Blue, Large, 500ml"
                    size="small"
                    fluid
                />
                <small class="text-muted-foreground">
                    This name identifies the variant (color, size, etc.)
                </small>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button
                    label="Cancel"
                    severity="secondary"
                    size="small"
                    :disabled="submitting"
                    @click="close"
                />
                <Button
                    label="Link as Variant"
                    size="small"
                    :loading="submitting"
                    :disabled="!selectedProduct || !variantName.trim()"
                    @click="submit"
                />
            </div>
        </template>
    </Dialog>
</template>
