<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useSmartBack } from '@/composables/useSmartBack';
import type { BreadcrumbItem, StocktakeTemplate } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Checkbox from 'primevue/checkbox';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { computed, ref, watch } from 'vue';

interface StoreOption {
    id: number;
    store_name: string;
    store_code: string;
}

interface ProductResult {
    id: number;
    product_name: string;
    product_number: string;
    variant_name: string | null;
    image_url: string | null;
}

interface Props {
    stores: StoreOption[];
    template: StocktakeTemplate | null;
}

const props = defineProps<Props>();

const { goBack } = useSmartBack('/stocktake-templates');
const isEditing = computed(() => !!props.template);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stocktake', href: '/stocktakes' },
    { title: 'My Templates', href: '/stocktake-templates' },
    { title: isEditing.value ? 'Edit Template' : 'Create Template' },
];

const form = useForm({
    name: props.template?.name ?? '',
    description: props.template?.description ?? '',
    store_id: props.template?.store_id ?? (null as number | null),
    product_ids: (props.template?.products?.map((p) => p.id) ?? []) as number[],
});

// Product list management
const selectedProducts = ref<ProductResult[]>(
    props.template?.products?.map((p) => ({
        id: p.id,
        product_name: p.product_name,
        product_number: p.product_number,
        variant_name: p.variant_name,
        image_url: null,
    })) ?? [],
);

const productSearchQuery = ref('');
const searchResults = ref<ProductResult[]>([]);
const checkedResultIds = ref<number[]>([]);
const searching = ref(false);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(productSearchQuery, (query) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (query.length < 2 || !form.store_id) {
        searchResults.value = [];
        checkedResultIds.value = [];
        return;
    }
    searching.value = true;
    searchTimeout = setTimeout(() => fetchProducts(query), 300);
});

async function fetchProducts(query: string) {
    try {
        const response = await fetch(
            `/stocktake-templates/search-products?q=${encodeURIComponent(query)}&store_id=${form.store_id}`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );
        const results: ProductResult[] = await response.json();
        const existingIds = new Set(form.product_ids);
        searchResults.value = results.filter((p) => !existingIds.has(p.id));
        checkedResultIds.value = [];
    } catch {
        searchResults.value = [];
    } finally {
        searching.value = false;
    }
}

function addCheckedProducts() {
    const toAdd = searchResults.value.filter((p) => checkedResultIds.value.includes(p.id));
    for (const product of toAdd) {
        if (!form.product_ids.includes(product.id)) {
            form.product_ids.push(product.id);
            selectedProducts.value.push(product);
        }
    }
    // Remove added items from search results
    searchResults.value = searchResults.value.filter((p) => !checkedResultIds.value.includes(p.id));
    checkedResultIds.value = [];
}

function addSingleProduct(product: ProductResult) {
    if (!form.product_ids.includes(product.id)) {
        form.product_ids.push(product.id);
        selectedProducts.value.push(product);
    }
    searchResults.value = searchResults.value.filter((p) => p.id !== product.id);
}

function removeProduct(productId: number) {
    form.product_ids = form.product_ids.filter((id) => id !== productId);
    selectedProducts.value = selectedProducts.value.filter(
        (p) => p.id !== productId,
    );
}

const storeOptions = props.stores.map((s) => ({
    label: `${s.store_name} (${s.store_code})`,
    value: s.id,
}));

// Clear products when store changes (products are store-specific)
watch(() => form.store_id, (newStoreId, oldStoreId) => {
    if (oldStoreId !== null && newStoreId !== oldStoreId) {
        form.product_ids = [];
        selectedProducts.value = [];
        productSearchQuery.value = '';
        searchResults.value = [];
        checkedResultIds.value = [];
    }
});

function submit() {
    if (isEditing.value && props.template) {
        form.put(`/stocktake-templates/${props.template.id}`);
    } else {
        form.post('/stocktake-templates');
    }
}
</script>

<template>
    <Head :title="isEditing ? 'Edit Template' : 'Create Template'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-3">
                <Button
                    icon="pi pi-arrow-left"
                    severity="secondary"
                    text
                    rounded
                    size="small"
                    @click="goBack"
                />
                <h1 class="heading-lg">
                    {{ isEditing ? 'Edit Template' : 'Create Template' }}
                </h1>
            </div>

            <Card>
                <template #content>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium">Name</label>
                                <InputText
                                    v-model="form.name"
                                    size="small"
                                    fluid
                                    placeholder="e.g. Table 22"
                                />
                                <small v-if="form.errors.name" class="text-red-500">
                                    {{ form.errors.name }}
                                </small>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Store</label>
                                <Select
                                    v-model="form.store_id"
                                    :options="storeOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Select store..."
                                    size="small"
                                    fluid
                                    :disabled="isEditing"
                                />
                                <small v-if="form.errors.store_id" class="text-red-500">
                                    {{ form.errors.store_id }}
                                </small>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Description</label>
                            <Textarea
                                v-model="form.description"
                                rows="2"
                                size="small"
                                fluid
                                placeholder="Optional description..."
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Products</label>

                            <!-- Search input -->
                            <IconField>
                                <InputIcon class="pi pi-search" />
                                <InputText
                                    v-model="productSearchQuery"
                                    :placeholder="form.store_id ? 'Search products to add...' : 'Select a store first...'"
                                    size="small"
                                    fluid
                                    :disabled="!form.store_id"
                                />
                            </IconField>
                            <small v-if="form.errors.product_ids" class="text-red-500">
                                {{ form.errors.product_ids }}
                            </small>

                            <!-- Search results with checkboxes -->
                            <div
                                v-if="searchResults.length > 0"
                                class="mt-2 rounded-md border border-border"
                            >
                                <div class="flex items-center justify-between border-b border-border bg-surface-50 dark:bg-surface-800 px-3 py-2">
                                    <span class="text-xs text-muted-foreground">
                                        {{ searchResults.length }} result(s)
                                    </span>
                                    <Button
                                        v-if="checkedResultIds.length > 0"
                                        :label="`Add ${checkedResultIds.length} selected`"
                                        icon="pi pi-plus"
                                        size="small"
                                        @click="addCheckedProducts"
                                    />
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    <div
                                        v-for="product in searchResults"
                                        :key="product.id"
                                        class="flex items-center gap-3 border-b border-border px-3 py-2 last:border-b-0 hover:bg-surface-50 dark:hover:bg-surface-800"
                                    >
                                        <Checkbox
                                            v-model="checkedResultIds"
                                            :value="product.id"
                                        />
                                        <img
                                            v-if="product.image_url"
                                            :src="product.image_url"
                                            class="h-8 w-8 shrink-0 rounded object-cover"
                                            alt=""
                                        />
                                        <div
                                            v-else
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded bg-surface-100 dark:bg-surface-700"
                                        >
                                            <i class="pi pi-box text-xs text-muted-foreground" />
                                        </div>
                                        <div class="min-w-0 flex-1 text-sm">
                                            <span class="font-medium">{{ product.product_name }}</span>
                                            <span v-if="product.variant_name" class="text-muted-foreground">
                                                - {{ product.variant_name }}
                                            </span>
                                            <div class="text-xs text-muted-foreground">
                                                {{ product.product_number }}
                                            </div>
                                        </div>
                                        <Button
                                            icon="pi pi-plus"
                                            severity="secondary"
                                            text
                                            rounded
                                            size="small"
                                            @click="addSingleProduct(product)"
                                            v-tooltip.top="'Add'"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else-if="searching"
                                class="mt-2 rounded-md border border-border p-3 text-center text-sm text-muted-foreground"
                            >
                                Searching...
                            </div>

                            <div
                                v-else-if="productSearchQuery.length >= 2 && form.store_id && !searching"
                                class="mt-2 rounded-md border border-border p-3 text-center text-sm text-muted-foreground"
                            >
                                No products found.
                            </div>

                            <!-- Selected products list -->
                            <div v-if="selectedProducts.length > 0" class="mt-3">
                                <div class="mb-1 text-xs text-muted-foreground">
                                    {{ selectedProducts.length }} product(s) added
                                </div>
                                <div class="space-y-1">
                                    <div
                                        v-for="product in selectedProducts"
                                        :key="product.id"
                                        class="flex items-center gap-3 rounded-md border border-border px-3 py-2 text-sm"
                                    >
                                        <img
                                            v-if="product.image_url"
                                            :src="product.image_url"
                                            class="h-8 w-8 shrink-0 rounded object-cover"
                                            alt=""
                                        />
                                        <div
                                            v-else
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded bg-surface-100 dark:bg-surface-700"
                                        >
                                            <i class="pi pi-box text-xs text-muted-foreground" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <span class="font-medium">{{ product.product_name }}</span>
                                            <span v-if="product.variant_name" class="text-muted-foreground">
                                                - {{ product.variant_name }}
                                            </span>
                                            <div class="text-xs text-muted-foreground">
                                                {{ product.product_number }}
                                            </div>
                                        </div>
                                        <Button
                                            icon="pi pi-times"
                                            severity="danger"
                                            text
                                            rounded
                                            size="small"
                                            @click="removeProduct(product.id)"
                                        />
                                    </div>
                                </div>
                            </div>
                            <p
                                v-else
                                class="mt-2 text-sm text-muted-foreground"
                            >
                                No products added yet.
                            </p>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <Button
                                label="Cancel"
                                severity="secondary"
                                size="small"
                                type="button"
                                @click="goBack"
                            />
                            <Button
                                :label="isEditing ? 'Update' : 'Create'"
                                icon="pi pi-check"
                                size="small"
                                type="submit"
                                :loading="form.processing"
                                :disabled="form.processing"
                            />
                        </div>
                    </form>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
