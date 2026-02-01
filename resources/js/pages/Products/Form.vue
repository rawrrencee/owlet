<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Chips from 'primevue/chips';
import Divider from 'primevue/divider';
import Editor from 'primevue/editor';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import { computed, ref, watch } from 'vue';
import BackButton from '@/components/BackButton.vue';
import ImageSelect from '@/components/ImageSelect.vue';
import ImageUpload from '@/components/ImageUpload.vue';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type Category,
    type Currency,
    type Product,
    type Subcategory,
    type WeightUnitOption,
} from '@/types';

interface StoreOption {
    id: number;
    store_name: string;
    store_code: string;
    store_currencies?: Array<{
        id: number;
        currency_id: number;
        is_default: boolean;
        currency?: Currency;
    }>;
}

interface Props {
    product: Product | null;
    brands: Array<{ id: number; brand_name: string; brand_code: string }>;
    categories: Array<Category & { subcategories?: Subcategory[] }>;
    suppliers: Array<{ id: number; supplier_name: string }>;
    currencies: Currency[];
    stores: StoreOption[];
    weightUnits: WeightUnitOption[];
}

const props = defineProps<Props>();

const { canAccessPage } = usePermissions();
const canViewCostPrice = computed(() => canAccessPage('products.view_cost_price'));

const isEditing = computed(() => !!props.product);
const pageTitle = computed(() => (isEditing.value ? 'Edit Product' : 'Create Product'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products', href: '/products' },
    { title: isEditing.value ? 'Edit' : 'Create' },
];

const brandOptions = computed(() =>
    props.brands.map((b) => ({ label: b.brand_name, value: b.id })),
);

const categoryOptions = computed(() =>
    props.categories.map((c) => ({ label: c.category_name, value: c.id })),
);

const subcategoryOptions = computed(() => {
    const selectedCategory = props.categories.find((c) => c.id === form.category_id);
    if (!selectedCategory?.subcategories) return [];
    return selectedCategory.subcategories.map((s) => ({
        label: s.subcategory_name,
        value: s.id,
    }));
});

const supplierOptions = computed(() =>
    props.suppliers.map((s) => ({ label: s.supplier_name, value: s.id })),
);

const currencyOptions = computed(() =>
    props.currencies.map((c) => ({ label: `${c.code} - ${c.name}`, value: c.id, currency: c })),
);

const storeOptions = computed(() =>
    props.stores.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: s.id,
        store: s,
    })),
);

// Helper to convert string price to number
function parsePrice(value: string | null | undefined): number | null {
    if (value === null || value === undefined) return null;
    const num = parseFloat(value);
    return isNaN(num) ? null : num;
}

// Initialize prices from product
const initialPrices = (props.product?.prices ?? []).map((p) => ({
    currency_id: p.currency_id,
    cost_price: parsePrice(p.cost_price),
    unit_price: parsePrice(p.unit_price),
}));

// Initialize stores from product
const initialStores = (props.product?.product_stores ?? []).map((ps) => ({
    store_id: ps.store_id,
    quantity: ps.quantity,
    is_active: ps.is_active,
    prices: (ps.store_prices ?? []).map((sp) => ({
        currency_id: sp.currency_id,
        cost_price: parsePrice(sp.cost_price),
        unit_price: parsePrice(sp.unit_price),
    })),
}));

const form = useForm({
    product_name: props.product?.product_name ?? '',
    product_number: props.product?.product_number ?? '',
    barcode: props.product?.barcode ?? '',
    brand_id: props.product?.brand_id ?? null,
    category_id: props.product?.category_id ?? null,
    subcategory_id: props.product?.subcategory_id ?? null,
    supplier_id: props.product?.supplier_id ?? null,
    supplier_number: props.product?.supplier_number ?? '',
    description: props.product?.description ?? '',
    tags: props.product?.tags ?? [],
    cost_price_remarks: props.product?.cost_price_remarks ?? '',
    weight: props.product?.weight ? parseFloat(props.product.weight) : null,
    weight_unit: props.product?.weight_unit ?? 'kg',
    is_active: props.product?.is_active ?? true,
    image: null as File | null,
    prices: initialPrices,
    stores: initialStores,
});

// Image state for edit mode
const imageUrl = ref<string | null>(props.product?.image_url ?? null);

// Selected currencies for base prices
const selectedCurrencyIds = ref<number[]>(initialPrices.map((p) => p.currency_id));

// Selected stores
const selectedStoreIds = ref<number[]>(initialStores.map((s) => s.store_id));

// Watch category changes to reset subcategory
watch(
    () => form.category_id,
    () => {
        // Reset subcategory if current one doesn't belong to selected category
        const selectedCategory = props.categories.find((c) => c.id === form.category_id);
        const validSubcategories = selectedCategory?.subcategories ?? [];
        if (!validSubcategories.find((s) => s.id === form.subcategory_id)) {
            form.subcategory_id = null;
        }
    },
);

// Watch selected currencies to sync prices array
watch(
    selectedCurrencyIds,
    (newIds) => {
        // Add new currencies
        for (const currencyId of newIds) {
            if (!form.prices.find((p) => p.currency_id === currencyId)) {
                form.prices.push({
                    currency_id: currencyId,
                    cost_price: null,
                    unit_price: null,
                });
            }
        }
        // Remove deselected currencies
        form.prices = form.prices.filter((p) => newIds.includes(p.currency_id));
    },
    { deep: true },
);

// Watch selected stores to sync stores array
watch(
    selectedStoreIds,
    (newIds) => {
        // Add new stores
        for (const storeId of newIds) {
            if (!form.stores.find((s) => s.store_id === storeId)) {
                // Initialize with store currencies
                const store = props.stores.find((s) => s.id === storeId);
                const storePrices = (store?.store_currencies ?? []).map((sc) => ({
                    currency_id: sc.currency_id,
                    cost_price: null,
                    unit_price: null,
                }));
                form.stores.push({
                    store_id: storeId,
                    quantity: 0,
                    is_active: true,
                    prices: storePrices,
                });
            }
        }
        // Remove deselected stores
        form.stores = form.stores.filter((s) => newIds.includes(s.store_id));
    },
    { deep: true },
);

function getCurrencyById(currencyId: number): Currency | undefined {
    return props.currencies.find((c) => c.id === currencyId);
}

function getStoreById(storeId: number): StoreOption | undefined {
    return props.stores.find((s) => s.id === storeId);
}

function submit() {
    if (isEditing.value) {
        form.put(`/products/${props.product!.id}`);
    } else {
        form.post('/products', {
            forceFormData: true,
        });
    }
}

function cancel() {
    if (isEditing.value) {
        router.visit(`/products/${props.product!.id}`);
    } else {
        router.visit('/products');
    }
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-4">
                <BackButton fallback-url="/products" />
                <h1 class="heading-lg">{{ pageTitle }}</h1>
            </div>

            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <template #content>
                        <form @submit.prevent="submit" class="flex flex-col gap-6">
                            <!-- Image for create mode -->
                            <ImageSelect
                                v-if="!isEditing"
                                v-model="form.image"
                                label="Product Image"
                                placeholder-icon="pi pi-box"
                                alt="Product image"
                                :circular="false"
                                :preview-size="96"
                            />

                            <!-- Image for edit mode -->
                            <ImageUpload
                                v-else-if="product"
                                :image-url="imageUrl"
                                :upload-url="`/products/${product.id}/image`"
                                :delete-url="`/products/${product.id}/image`"
                                field-name="image"
                                label="Product Image"
                                placeholder-icon="pi pi-box"
                                alt="Product image"
                                :circular="false"
                                :preview-size="96"
                                @uploaded="(url) => imageUrl = url"
                                @deleted="imageUrl = null"
                            />

                            <Divider />

                            <!-- Basic Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Basic Information</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-2">
                                        <label for="product_name" class="font-medium">Product Name *</label>
                                        <InputText
                                            id="product_name"
                                            v-model="form.product_name"
                                            :invalid="!!form.errors.product_name"
                                            placeholder="Product Name"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.product_name" class="text-red-500">
                                            {{ form.errors.product_name }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="product_number" class="font-medium">Product Number (SKU) *</label>
                                        <InputText
                                            id="product_number"
                                            v-model="form.product_number"
                                            :invalid="!!form.errors.product_number"
                                            placeholder="SKU-001"
                                            size="small"
                                            fluid
                                            class="uppercase"
                                        />
                                        <small class="text-muted-foreground">Unique identifier, auto-uppercased</small>
                                        <small v-if="form.errors.product_number" class="text-red-500">
                                            {{ form.errors.product_number }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="barcode" class="font-medium">Barcode</label>
                                        <InputText
                                            id="barcode"
                                            v-model="form.barcode"
                                            :invalid="!!form.errors.barcode"
                                            placeholder="UPC/EAN/ISBN"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.barcode" class="text-red-500">
                                            {{ form.errors.barcode }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="supplier_number" class="font-medium">Supplier Number</label>
                                        <InputText
                                            id="supplier_number"
                                            v-model="form.supplier_number"
                                            :invalid="!!form.errors.supplier_number"
                                            placeholder="Supplier's product code"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.supplier_number" class="text-red-500">
                                            {{ form.errors.supplier_number }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Classification -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Classification</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-2">
                                        <label for="brand_id" class="font-medium">Brand *</label>
                                        <Select
                                            id="brand_id"
                                            v-model="form.brand_id"
                                            :options="brandOptions"
                                            option-label="label"
                                            option-value="value"
                                            :invalid="!!form.errors.brand_id"
                                            placeholder="Select brand"
                                            filter
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.brand_id" class="text-red-500">
                                            {{ form.errors.brand_id }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="supplier_id" class="font-medium">Supplier *</label>
                                        <Select
                                            id="supplier_id"
                                            v-model="form.supplier_id"
                                            :options="supplierOptions"
                                            option-label="label"
                                            option-value="value"
                                            :invalid="!!form.errors.supplier_id"
                                            placeholder="Select supplier"
                                            filter
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.supplier_id" class="text-red-500">
                                            {{ form.errors.supplier_id }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="category_id" class="font-medium">Category *</label>
                                        <Select
                                            id="category_id"
                                            v-model="form.category_id"
                                            :options="categoryOptions"
                                            option-label="label"
                                            option-value="value"
                                            :invalid="!!form.errors.category_id"
                                            placeholder="Select category"
                                            filter
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.category_id" class="text-red-500">
                                            {{ form.errors.category_id }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="subcategory_id" class="font-medium">Subcategory *</label>
                                        <Select
                                            id="subcategory_id"
                                            v-model="form.subcategory_id"
                                            :options="subcategoryOptions"
                                            option-label="label"
                                            option-value="value"
                                            :invalid="!!form.errors.subcategory_id"
                                            placeholder="Select subcategory"
                                            :disabled="!form.category_id"
                                            filter
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="!form.category_id" class="text-muted-foreground">
                                            Select a category first
                                        </small>
                                        <small v-if="form.errors.subcategory_id" class="text-red-500">
                                            {{ form.errors.subcategory_id }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Base Prices -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Base Prices</h3>
                                <p class="mb-4 text-sm text-muted-foreground">
                                    Set base prices for currencies. These are the default prices used across all stores unless overridden.
                                </p>

                                <div class="mb-4 flex flex-col gap-2">
                                    <label class="font-medium">Select Currencies</label>
                                    <MultiSelect
                                        v-model="selectedCurrencyIds"
                                        :options="currencyOptions"
                                        option-label="label"
                                        option-value="value"
                                        placeholder="Select currencies"
                                        filter
                                        size="small"
                                        fluid
                                        display="chip"
                                    />
                                </div>

                                <div v-if="form.prices.length > 0" class="flex flex-col gap-4">
                                    <div
                                        v-for="(price, index) in form.prices"
                                        :key="price.currency_id"
                                        class="rounded-lg border border-border p-4"
                                    >
                                        <div class="mb-3 flex items-center gap-2">
                                            <span class="font-medium">
                                                {{ getCurrencyById(price.currency_id)?.code }}
                                            </span>
                                            <span class="text-sm text-muted-foreground">
                                                ({{ getCurrencyById(price.currency_id)?.name }})
                                            </span>
                                        </div>
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <div v-if="canViewCostPrice" class="flex flex-col gap-2">
                                                <label class="text-sm">Cost Price</label>
                                                <InputNumber
                                                    v-model="form.prices[index].cost_price"
                                                    :min-fraction-digits="2"
                                                    :max-fraction-digits="4"
                                                    :prefix="getCurrencyById(price.currency_id)?.symbol + ' '"
                                                    placeholder="0.00"
                                                    size="small"
                                                    fluid
                                                />
                                            </div>
                                            <div class="flex flex-col gap-2">
                                                <label class="text-sm">Unit Price</label>
                                                <InputNumber
                                                    v-model="form.prices[index].unit_price"
                                                    :min-fraction-digits="2"
                                                    :max-fraction-digits="4"
                                                    :prefix="getCurrencyById(price.currency_id)?.symbol + ' '"
                                                    placeholder="0.00"
                                                    size="small"
                                                    fluid
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-muted-foreground">
                                    No currencies selected. Select currencies above to set base prices.
                                </p>
                            </div>

                            <Divider />

                            <!-- Store Assignments -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Store Assignments</h3>
                                <p class="mb-4 text-sm text-muted-foreground">
                                    Assign this product to stores. You can set store-specific prices that override the base prices.
                                </p>

                                <div class="mb-4 flex flex-col gap-2">
                                    <label class="font-medium">Select Stores</label>
                                    <MultiSelect
                                        v-model="selectedStoreIds"
                                        :options="storeOptions"
                                        option-label="label"
                                        option-value="value"
                                        placeholder="Select stores"
                                        filter
                                        size="small"
                                        fluid
                                        display="chip"
                                    />
                                </div>

                                <div v-if="form.stores.length > 0" class="flex flex-col gap-4">
                                    <div
                                        v-for="(storeAssignment, storeIndex) in form.stores"
                                        :key="storeAssignment.store_id"
                                        class="rounded-lg border border-border p-4"
                                    >
                                        <div class="mb-4 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium">
                                                    {{ getStoreById(storeAssignment.store_id)?.store_name }}
                                                </span>
                                                <span class="text-sm text-muted-foreground">
                                                    ({{ getStoreById(storeAssignment.store_id)?.store_code }})
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <ToggleSwitch v-model="form.stores[storeIndex].is_active" />
                                                <span class="text-sm" :class="form.stores[storeIndex].is_active ? 'text-green-600' : 'text-red-600'">
                                                    {{ form.stores[storeIndex].is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mb-4 flex flex-col gap-2">
                                            <label class="text-sm font-medium">Initial Quantity</label>
                                            <InputNumber
                                                v-model="form.stores[storeIndex].quantity"
                                                :min="0"
                                                placeholder="0"
                                                size="small"
                                                class="max-w-xs"
                                            />
                                        </div>

                                        <!-- Store-specific prices -->
                                        <div v-if="storeAssignment.prices && storeAssignment.prices.length > 0">
                                            <h4 class="mb-3 text-sm font-medium">Store-Specific Prices (Optional)</h4>
                                            <p class="mb-3 text-xs text-muted-foreground">
                                                Leave blank to use base prices. Set values to override for this store.
                                            </p>
                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div
                                                    v-for="(storePrice, priceIndex) in storeAssignment.prices"
                                                    :key="storePrice.currency_id"
                                                    class="rounded border border-border/50 bg-muted/20 p-3"
                                                >
                                                    <div class="mb-2 text-sm font-medium">
                                                        {{ getCurrencyById(storePrice.currency_id)?.code }}
                                                    </div>
                                                    <div class="flex flex-col gap-2">
                                                        <div v-if="canViewCostPrice" class="flex flex-col gap-1">
                                                            <label class="text-xs text-muted-foreground">Cost Price</label>
                                                            <InputNumber
                                                                v-model="form.stores[storeIndex].prices[priceIndex].cost_price"
                                                                :min-fraction-digits="2"
                                                                :max-fraction-digits="4"
                                                                :prefix="getCurrencyById(storePrice.currency_id)?.symbol + ' '"
                                                                placeholder="Use base"
                                                                size="small"
                                                                fluid
                                                            />
                                                        </div>
                                                        <div class="flex flex-col gap-1">
                                                            <label class="text-xs text-muted-foreground">Unit Price</label>
                                                            <InputNumber
                                                                v-model="form.stores[storeIndex].prices[priceIndex].unit_price"
                                                                :min-fraction-digits="2"
                                                                :max-fraction-digits="4"
                                                                :prefix="getCurrencyById(storePrice.currency_id)?.symbol + ' '"
                                                                placeholder="Use base"
                                                                size="small"
                                                                fluid
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <p v-else class="text-sm text-muted-foreground">
                                            This store has no currencies configured.
                                        </p>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-muted-foreground">
                                    No stores selected. Select stores above to assign this product.
                                </p>
                            </div>

                            <Divider />

                            <!-- Physical Attributes -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Physical Attributes</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-2">
                                        <label for="weight" class="font-medium">Weight</label>
                                        <InputNumber
                                            id="weight"
                                            v-model="form.weight"
                                            :min-fraction-digits="0"
                                            :max-fraction-digits="3"
                                            :min="0"
                                            placeholder="0.000"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.weight" class="text-red-500">
                                            {{ form.errors.weight }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="weight_unit" class="font-medium">Weight Unit</label>
                                        <Select
                                            id="weight_unit"
                                            v-model="form.weight_unit"
                                            :options="weightUnits"
                                            option-label="label"
                                            option-value="value"
                                            :invalid="!!form.errors.weight_unit"
                                            placeholder="Select unit"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.weight_unit" class="text-red-500">
                                            {{ form.errors.weight_unit }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Tags -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Tags</h3>
                                <div class="flex flex-col gap-2">
                                    <label for="tags" class="font-medium">Product Tags</label>
                                    <Chips
                                        id="tags"
                                        v-model="form.tags"
                                        separator=","
                                        placeholder="Add tags (press Enter or comma to add)"
                                        :pt="{
                                            root: { class: 'border-border' },
                                            input: { class: 'border-0' },
                                        }"
                                    />
                                    <small class="text-muted-foreground">
                                        Tags help with filtering and categorization
                                    </small>
                                </div>
                            </div>

                            <Divider />

                            <!-- Description -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Description</h3>
                                <div class="flex flex-col gap-2">
                                    <Editor
                                        v-model="form.description"
                                        editor-style="height: 200px"
                                        :pt="{
                                            root: { class: 'border-border' },
                                            toolbar: { class: 'border-border bg-muted/50' },
                                            content: { class: 'border-border' },
                                        }"
                                    >
                                        <template #toolbar>
                                            <span class="ql-formats">
                                                <button class="ql-bold" v-tooltip.bottom="'Bold'"></button>
                                                <button class="ql-italic" v-tooltip.bottom="'Italic'"></button>
                                                <button class="ql-underline" v-tooltip.bottom="'Underline'"></button>
                                            </span>
                                            <span class="ql-formats">
                                                <button class="ql-list" value="ordered" v-tooltip.bottom="'Ordered List'"></button>
                                                <button class="ql-list" value="bullet" v-tooltip.bottom="'Bullet List'"></button>
                                            </span>
                                            <span class="ql-formats">
                                                <button class="ql-link" v-tooltip.bottom="'Link'"></button>
                                            </span>
                                            <span class="ql-formats">
                                                <button class="ql-clean" v-tooltip.bottom="'Clear Formatting'"></button>
                                            </span>
                                        </template>
                                    </Editor>
                                    <small v-if="form.errors.description" class="text-red-500">
                                        {{ form.errors.description }}
                                    </small>
                                </div>
                            </div>

                            <!-- Cost Price Remarks (only if user can view cost price) -->
                            <template v-if="canViewCostPrice">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Cost Price Notes</h3>
                                    <div class="flex flex-col gap-2">
                                        <label for="cost_price_remarks" class="font-medium">Remarks</label>
                                        <InputText
                                            id="cost_price_remarks"
                                            v-model="form.cost_price_remarks"
                                            :invalid="!!form.errors.cost_price_remarks"
                                            placeholder="Notes about cost price..."
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.cost_price_remarks" class="text-red-500">
                                            {{ form.errors.cost_price_remarks }}
                                        </small>
                                    </div>
                                </div>
                            </template>

                            <Divider />

                            <!-- Status -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Status</h3>
                                <div class="flex items-center gap-3">
                                    <ToggleSwitch v-model="form.is_active" />
                                    <span :class="form.is_active ? 'text-green-600' : 'text-red-600'">
                                        {{ form.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <Button
                                    type="button"
                                    label="Cancel"
                                    severity="secondary"
                                    size="small"
                                    @click="cancel"
                                    :disabled="form.processing"
                                />
                                <Button
                                    type="submit"
                                    :label="isEditing ? 'Save Changes' : 'Create Product'"
                                    size="small"
                                    :loading="form.processing"
                                />
                            </div>
                        </form>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
