<script setup lang="ts">
import { useSmartBack } from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Offer } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Image from 'primevue/image';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import Textarea from 'primevue/textarea';
import { computed, ref, watch } from 'vue';

interface ProductSearchResult {
    id: number;
    product_name: string;
    product_number: string;
    variant_name: string | null;
    barcode: string | null;
    image_url: string | null;
}

interface SubcategoryOption {
    id: number;
    subcategory_name: string;
    category_id: number;
    category?: { id: number; category_name: string } | null;
}

interface BundleItem {
    product_id: number | null;
    category_id: number | null;
    subcategory_id: number | null;
    required_quantity: number;
    product_name: string;
    product_number: string;
    variant_name: string | null;
    image_url: string | null;
    category_name: string | null;
    subcategory_name: string | null;
}

interface Props {
    offer?: Offer;
    stores: Array<{ id: number; store_name: string; store_code: string }>;
    categories: Array<{ id: number; category_name: string }>;
    subcategories?: SubcategoryOption[];
    brands: Array<{ id: number; brand_name: string }>;
    currencies: Array<{ id: number; code: string; name: string; symbol: string }>;
    typeOptions: Array<{ value: string; label: string }>;
    discountTypeOptions: Array<{ value: string; label: string }>;
    statusOptions: Array<{ value: string; label: string }>;
    bundleModeOptions: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();
const isEditing = computed(() => !!props.offer);
const { goBack } = useSmartBack('/offers');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Offers', href: '/offers' },
    { title: isEditing.value ? 'Edit' : 'Create' },
];

// Build form with initial values
const form = useForm({
    name: props.offer?.name ?? '',
    code: props.offer?.code ?? '',
    type: props.offer?.type ?? 'product',
    discount_type: props.offer?.discount_type ?? 'percentage',
    discount_percentage: props.offer?.discount_percentage ? Number(props.offer.discount_percentage) : null as number | null,
    description: props.offer?.description ?? '',
    starts_at: props.offer?.starts_at ? new Date(props.offer.starts_at) : null as Date | null,
    ends_at: props.offer?.ends_at ? new Date(props.offer.ends_at) : null as Date | null,
    is_combinable: props.offer?.is_combinable ?? false,
    priority: props.offer?.priority ?? 0,
    apply_to_all_stores: props.offer?.apply_to_all_stores ?? true,
    store_ids: props.offer?.stores?.map((s) => s.id) ?? [] as number[],
    category_id: props.offer?.category?.id ?? null as number | null,
    brand_id: props.offer?.brand?.id ?? null as number | null,
    bundle_mode: props.offer?.bundle_mode ?? 'whole',

    // Per-currency amounts
    amounts: props.offer?.amounts?.map((a) => ({
        currency_id: a.currency_id,
        discount_amount: a.discount_amount ? Number(a.discount_amount) : null as number | null,
        max_discount_amount: a.max_discount_amount ? Number(a.max_discount_amount) : null as number | null,
    })) ?? [] as Array<{ currency_id: number; discount_amount: number | null; max_discount_amount: number | null }>,

    // Product IDs for type=product
    product_ids: props.offer?.products?.map((p) => p.product_id) ?? [] as number[],

    // Bundle items for type=bundle
    bundle_items: (props.offer?.bundles?.map((b): BundleItem => ({
        product_id: b.product_id,
        category_id: b.category_id,
        subcategory_id: b.subcategory_id,
        required_quantity: b.required_quantity,
        product_name: b.product?.product_name ?? '',
        product_number: b.product?.product_number ?? '',
        variant_name: b.product?.variant_name ?? null,
        image_url: b.product?.image_url ?? null,
        category_name: b.category?.category_name ?? (b.subcategory?.category?.category_name ?? null),
        subcategory_name: b.subcategory?.subcategory_name ?? null,
    })) ?? []) as BundleItem[],

    // Minimum spends for type=minimum_spend
    minimum_spends: props.offer?.minimum_spends?.map((ms) => ({
        currency_id: ms.currency_id,
        minimum_amount: ms.minimum_amount ? Number(ms.minimum_amount) : null as number | null,
    })) ?? [] as Array<{ currency_id: number; minimum_amount: number | null }>,
});

// Product display data (for showing product names in the table)
const productDisplayData = ref<Array<{
    id: number;
    product_name: string;
    product_number: string;
    variant_name: string | null;
    image_url: string | null;
}>>(
    props.offer?.products?.map((p) => ({
        id: p.product_id,
        product_name: p.product?.product_name ?? '',
        product_number: p.product?.product_number ?? '',
        variant_name: p.product?.variant_name ?? null,
        image_url: p.product?.image_url ?? null,
    })) ?? [],
);

const categoryOptions = computed(() =>
    props.categories.map((c) => ({ label: c.category_name, value: c.id })),
);

const brandOptions = computed(() =>
    props.brands.map((b) => ({ label: b.brand_name, value: b.id })),
);

const storeOptions = computed(() =>
    props.stores.map((s) => ({ label: `${s.store_name} (${s.store_code})`, value: s.id })),
);

const currencyOptions = computed(() =>
    props.currencies.map((c) => ({ label: `${c.code} (${c.symbol})`, value: c.id })),
);

// Bundle entry mode: product, category, or subcategory
type BundleEntryMode = 'product' | 'category' | 'subcategory';
const bundleEntryMode = ref<BundleEntryMode>('product');
const bundleEntryModeOptions = [
    { label: 'Add Product', value: 'product' },
    { label: 'Add Category', value: 'category' },
    { label: 'Add Subcategory', value: 'subcategory' },
];

// Bundle category/subcategory add form state
const bundleCategoryId = ref<number | null>(null);
const bundleSubcategoryCategoryId = ref<number | null>(null);
const bundleSubcategoryId = ref<number | null>(null);
const bundleEntryQty = ref<number>(1);

const bundleCategoryOptions = computed(() =>
    props.categories
        .filter((c) => !form.bundle_items.some((bi) => bi.category_id === c.id))
        .map((c) => ({ label: c.category_name, value: c.id })),
);

const filteredSubcategoryOptions = computed(() => {
    const subs = props.subcategories ?? [];
    const filtered = bundleSubcategoryCategoryId.value
        ? subs.filter((s) => s.category_id === bundleSubcategoryCategoryId.value)
        : subs;
    return filtered
        .filter((s) => !form.bundle_items.some((bi) => bi.subcategory_id === s.id))
        .map((s) => ({
            label: `${s.category?.category_name ?? ''} > ${s.subcategory_name}`,
            value: s.id,
        }));
});

function addBundleCategory() {
    if (!bundleCategoryId.value) return;
    const cat = props.categories.find((c) => c.id === bundleCategoryId.value);
    if (!cat) return;

    form.bundle_items.push({
        product_id: null,
        category_id: cat.id,
        subcategory_id: null,
        required_quantity: bundleEntryQty.value || 1,
        product_name: '',
        product_number: '',
        variant_name: null,
        image_url: null,
        category_name: cat.category_name,
        subcategory_name: null,
    });
    bundleCategoryId.value = null;
    bundleEntryQty.value = 1;
}

function addBundleSubcategory() {
    if (!bundleSubcategoryId.value) return;
    const sub = (props.subcategories ?? []).find((s) => s.id === bundleSubcategoryId.value);
    if (!sub) return;

    form.bundle_items.push({
        product_id: null,
        category_id: null,
        subcategory_id: sub.id,
        required_quantity: bundleEntryQty.value || 1,
        product_name: '',
        product_number: '',
        variant_name: null,
        image_url: null,
        category_name: sub.category?.category_name ?? null,
        subcategory_name: sub.subcategory_name,
    });
    bundleSubcategoryCategoryId.value = null;
    bundleSubcategoryId.value = null;
    bundleEntryQty.value = 1;
}

// Product search
const searchQuery = ref('');
const searchResults = ref<ProductSearchResult[]>([]);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(searchQuery, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (!val || val.length < 2) {
        searchResults.value = [];
        return;
    }
    searchTimeout = setTimeout(() => searchProducts(val), 300);
});

async function searchProducts(query: string) {
    try {
        const response = await fetch(`/offers/search-products?q=${encodeURIComponent(query)}`);
        const data = await response.json();

        // Exclude already selected products
        let excludeIds: number[] = [];
        if (form.type === 'product') {
            excludeIds = form.product_ids;
        } else if (form.type === 'bundle') {
            excludeIds = form.bundle_items
                .filter((i) => i.product_id !== null)
                .map((i) => i.product_id as number);
        }

        searchResults.value = (data as ProductSearchResult[]).filter(
            (p) => !excludeIds.includes(p.id),
        );
    } catch {
        searchResults.value = [];
    }
}

function addProduct(product: ProductSearchResult) {
    if (form.type === 'product') {
        form.product_ids.push(product.id);
        productDisplayData.value.push({
            id: product.id,
            product_name: product.product_name,
            product_number: product.product_number,
            variant_name: product.variant_name,
            image_url: product.image_url,
        });
    } else if (form.type === 'bundle') {
        form.bundle_items.push({
            product_id: product.id,
            category_id: null,
            subcategory_id: null,
            required_quantity: 1,
            product_name: product.product_name,
            product_number: product.product_number,
            variant_name: product.variant_name,
            image_url: product.image_url,
            category_name: null,
            subcategory_name: null,
        });
    }
    searchQuery.value = '';
    searchResults.value = [];
}

function removeProduct(index: number) {
    form.product_ids.splice(index, 1);
    productDisplayData.value.splice(index, 1);
}

function removeBundleItem(index: number) {
    form.bundle_items.splice(index, 1);
}

// Amount management
function addAmount() {
    const usedCurrencyIds = form.amounts.map((a) => a.currency_id);
    const availableCurrency = props.currencies.find((c) => !usedCurrencyIds.includes(c.id));
    if (availableCurrency) {
        form.amounts.push({
            currency_id: availableCurrency.id,
            discount_amount: null,
            max_discount_amount: null,
        });
    }
}

function removeAmount(index: number) {
    form.amounts.splice(index, 1);
}

// Minimum spend management
function addMinimumSpend() {
    const usedCurrencyIds = form.minimum_spends.map((ms) => ms.currency_id);
    const availableCurrency = props.currencies.find((c) => !usedCurrencyIds.includes(c.id));
    if (availableCurrency) {
        form.minimum_spends.push({
            currency_id: availableCurrency.id,
            minimum_amount: null,
        });
    }
}

function removeMinimumSpend(index: number) {
    form.minimum_spends.splice(index, 1);
}

// Watch type changes to reset type-specific data
watch(() => form.type, () => {
    form.product_ids = [];
    productDisplayData.value = [];
    form.bundle_items = [];
    form.minimum_spends = [];
    form.category_id = null;
    form.brand_id = null;
    form.bundle_mode = 'whole';
    bundleEntryMode.value = 'product';
    bundleCategoryId.value = null;
    bundleSubcategoryCategoryId.value = null;
    bundleSubcategoryId.value = null;
    bundleEntryQty.value = 1;
});

function getBundleItemLabel(item: BundleItem): string {
    if (item.product_id) {
        return item.product_name;
    }
    if (item.category_id) {
        return `Any product from ${item.category_name}`;
    }
    if (item.subcategory_id) {
        return `Any product from ${item.category_name} > ${item.subcategory_name}`;
    }
    return '-';
}

function submit() {
    if (isEditing.value) {
        form.put(`/offers/${props.offer!.id}`);
    } else {
        form.post('/offers');
    }
}
</script>

<template>
    <Head :title="isEditing ? 'Edit Offer' : 'Create Offer'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="heading-lg">{{ isEditing ? 'Edit Offer' : 'Create Offer' }}</h1>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <!-- General Info -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Name *</label>
                        <InputText
                            v-model="form.name"
                            placeholder="Offer name"
                            size="small"
                            :invalid="!!form.errors.name"
                        />
                        <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Code</label>
                        <InputText
                            v-model="form.code"
                            placeholder="Internal reference code"
                            size="small"
                            :invalid="!!form.errors.code"
                        />
                        <small v-if="form.errors.code" class="text-red-500">{{ form.errors.code }}</small>
                    </div>
                    <div class="flex flex-col gap-1 sm:col-span-2">
                        <label class="text-sm font-medium">Description</label>
                        <Textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Optional description..."
                            class="w-full"
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Priority</label>
                        <InputNumber
                            v-model="form.priority"
                            :min="0"
                            size="small"
                            fluid
                        />
                        <small class="text-muted-foreground">Lower = higher priority (for tie-breaking)</small>
                    </div>
                    <div class="flex items-center gap-2 pt-5">
                        <Checkbox
                            v-model="form.is_combinable"
                            :binary="true"
                            inputId="is_combinable"
                        />
                        <label for="is_combinable" class="text-sm">Can stack with customer discount</label>
                    </div>
                </div>

                <!-- Offer Type -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Offer Type *</label>
                        <Select
                            v-model="form.type"
                            :options="typeOptions"
                            option-label="label"
                            option-value="value"
                            size="small"
                            :invalid="!!form.errors.type"
                        />
                        <small v-if="form.errors.type" class="text-red-500">{{ form.errors.type }}</small>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Discount Type *</label>
                        <Select
                            v-model="form.discount_type"
                            :options="discountTypeOptions"
                            option-label="label"
                            option-value="value"
                            size="small"
                            :invalid="!!form.errors.discount_type"
                        />
                        <small v-if="form.errors.discount_type" class="text-red-500">{{ form.errors.discount_type }}</small>
                    </div>
                </div>

                <!-- Discount Value -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div v-if="form.discount_type === 'percentage'" class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Discount Percentage *</label>
                        <InputNumber
                            v-model="form.discount_percentage"
                            :min="0.01"
                            :max="100"
                            :minFractionDigits="2"
                            :maxFractionDigits="2"
                            suffix="%"
                            size="small"
                            fluid
                            :invalid="!!form.errors.discount_percentage"
                        />
                        <small v-if="form.errors.discount_percentage" class="text-red-500">{{ form.errors.discount_percentage }}</small>
                    </div>
                </div>

                <!-- Per-Currency Amounts -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium">
                            {{ form.discount_type === 'fixed' ? 'Fixed Discount Amounts *' : 'Maximum Discount Caps (per currency)' }}
                        </label>
                        <Button
                            v-if="form.amounts.length < currencies.length"
                            label="Add Currency"
                            icon="pi pi-plus"
                            size="small"
                            severity="secondary"
                            text
                            @click="addAmount"
                        />
                    </div>
                    <small v-if="form.errors.amounts" class="text-red-500">{{ form.errors.amounts }}</small>
                    <DataTable
                        v-if="form.amounts.length > 0"
                        :value="form.amounts"
                        dataKey="currency_id"
                        size="small"
                        class="overflow-hidden rounded-lg border border-border"
                    >
                        <Column header="Currency" class="w-40">
                            <template #body="{ index }">
                                <Select
                                    v-model="form.amounts[index].currency_id"
                                    :options="currencyOptions"
                                    option-label="label"
                                    option-value="value"
                                    size="small"
                                    fluid
                                />
                            </template>
                        </Column>
                        <Column v-if="form.discount_type === 'fixed'" header="Discount Amount">
                            <template #body="{ index }">
                                <InputNumber
                                    v-model="form.amounts[index].discount_amount"
                                    :min="0"
                                    :minFractionDigits="2"
                                    :maxFractionDigits="4"
                                    size="small"
                                    fluid
                                />
                            </template>
                        </Column>
                        <Column v-if="form.discount_type === 'percentage'" header="Max Discount Cap">
                            <template #body="{ index }">
                                <InputNumber
                                    v-model="form.amounts[index].max_discount_amount"
                                    :min="0"
                                    :minFractionDigits="2"
                                    :maxFractionDigits="4"
                                    size="small"
                                    fluid
                                    placeholder="No cap"
                                />
                            </template>
                        </Column>
                        <Column header="" class="w-16">
                            <template #body="{ index }">
                                <Button
                                    icon="pi pi-trash"
                                    severity="danger"
                                    text
                                    rounded
                                    size="small"
                                    @click="removeAmount(index)"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <!-- Date Range -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Starts At</label>
                        <DatePicker
                            v-model="form.starts_at"
                            showTime
                            hourFormat="24"
                            dateFormat="dd M yy"
                            placeholder="Select start date"
                            size="small"
                            showButtonBar
                            fluid
                        />
                        <small v-if="form.errors.starts_at" class="text-red-500">{{ form.errors.starts_at }}</small>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Ends At</label>
                        <DatePicker
                            v-model="form.ends_at"
                            showTime
                            hourFormat="24"
                            dateFormat="dd M yy"
                            placeholder="Select end date"
                            size="small"
                            showButtonBar
                            fluid
                        />
                        <small v-if="form.errors.ends_at" class="text-red-500">{{ form.errors.ends_at }}</small>
                    </div>
                </div>

                <!-- Store Scope -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <Checkbox
                            v-model="form.apply_to_all_stores"
                            :binary="true"
                            inputId="apply_to_all_stores"
                        />
                        <label for="apply_to_all_stores" class="text-sm font-medium">Apply to all stores</label>
                    </div>
                    <div v-if="!form.apply_to_all_stores" class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Select Stores</label>
                        <MultiSelect
                            v-model="form.store_ids"
                            :options="storeOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select stores"
                            size="small"
                            filter
                            display="chip"
                            class="w-full"
                        />
                    </div>
                </div>

                <!-- Type-specific: Product Picker -->
                <template v-if="form.type === 'product'">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Products *</label>
                        <div class="relative">
                            <InputText
                                v-model="searchQuery"
                                placeholder="Search products by name, number, or barcode..."
                                size="small"
                                fluid
                            />
                            <div
                                v-if="searchResults.length > 0"
                                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-border bg-background shadow-lg"
                            >
                                <div
                                    v-for="product in searchResults"
                                    :key="product.id"
                                    class="flex cursor-pointer items-center gap-2 px-3 py-2 hover:bg-muted"
                                    @click="addProduct(product)"
                                >
                                    <img v-if="product.image_url" :src="product.image_url" class="h-8 w-8 flex-shrink-0 rounded object-cover" alt="" />
                                    <Avatar v-else :label="product.product_name?.charAt(0)" shape="square" class="!h-8 !w-8 flex-shrink-0 rounded bg-primary/10 text-primary" />
                                    <div>
                                        <div class="font-medium">{{ product.product_name }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ product.product_number }}
                                            <span v-if="product.variant_name"> - {{ product.variant_name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small v-if="form.errors.product_ids" class="text-red-500">{{ form.errors.product_ids }}</small>

                        <DataTable
                            v-if="productDisplayData.length > 0"
                            :value="productDisplayData"
                            dataKey="id"
                            size="small"
                            striped-rows
                            class="overflow-hidden rounded-lg border border-border"
                        >
                            <Column header="Product">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <div @click.stop>
                                            <Image v-if="data.image_url" :src="data.image_url" alt="" image-class="h-8 w-8 rounded object-cover cursor-pointer" :pt="{ root: { class: 'rounded overflow-hidden flex-shrink-0' }, previewMask: { class: 'rounded' } }" preview />
                                            <Avatar v-else :label="data.product_name?.charAt(0)" shape="square" class="!h-8 !w-8 flex-shrink-0 rounded bg-primary/10 text-primary" />
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ data.product_name }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ data.product_number }}
                                                <span v-if="data.variant_name"> - {{ data.variant_name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Column>
                            <Column header="" class="w-16">
                                <template #body="{ index }">
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        size="small"
                                        @click="removeProduct(index)"
                                    />
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>

                <!-- Type-specific: Bundle -->
                <template v-if="form.type === 'bundle'">
                    <div class="flex flex-col gap-2">
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Bundle Mode *</label>
                            <Select
                                v-model="form.bundle_mode"
                                :options="bundleModeOptions"
                                option-label="label"
                                option-value="value"
                                size="small"
                                class="w-full sm:w-72"
                                :invalid="!!form.errors.bundle_mode"
                            />
                            <small v-if="form.errors.bundle_mode" class="text-red-500">{{ form.errors.bundle_mode }}</small>
                        </div>

                        <label class="text-sm font-medium">Bundle Items *</label>

                        <!-- Entry mode selector -->
                        <SelectButton
                            v-model="bundleEntryMode"
                            :options="bundleEntryModeOptions"
                            option-label="label"
                            option-value="value"
                            size="small"
                        />

                        <!-- Product search (when mode = product) -->
                        <div v-if="bundleEntryMode === 'product'" class="relative">
                            <InputText
                                v-model="searchQuery"
                                placeholder="Search products to add to bundle..."
                                size="small"
                                fluid
                            />
                            <div
                                v-if="searchResults.length > 0"
                                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-border bg-background shadow-lg"
                            >
                                <div
                                    v-for="product in searchResults"
                                    :key="product.id"
                                    class="flex cursor-pointer items-center gap-2 px-3 py-2 hover:bg-muted"
                                    @click="addProduct(product)"
                                >
                                    <img v-if="product.image_url" :src="product.image_url" class="h-8 w-8 flex-shrink-0 rounded object-cover" alt="" />
                                    <Avatar v-else :label="product.product_name?.charAt(0)" shape="square" class="!h-8 !w-8 flex-shrink-0 rounded bg-primary/10 text-primary" />
                                    <div>
                                        <div class="font-medium">{{ product.product_name }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ product.product_number }}
                                            <span v-if="product.variant_name"> - {{ product.variant_name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Category add (when mode = category) -->
                        <div v-if="bundleEntryMode === 'category'" class="flex items-end gap-2">
                            <div class="flex flex-1 flex-col gap-1">
                                <label class="text-xs text-muted-foreground">Category</label>
                                <Select
                                    v-model="bundleCategoryId"
                                    :options="bundleCategoryOptions"
                                    option-label="label"
                                    option-value="value"
                                    placeholder="Select category"
                                    size="small"
                                    filter
                                    fluid
                                />
                            </div>
                            <div class="flex w-28 flex-col gap-1">
                                <label class="text-xs text-muted-foreground">Required Qty</label>
                                <InputNumber v-model="bundleEntryQty" :min="1" size="small" fluid />
                            </div>
                            <Button
                                label="Add"
                                icon="pi pi-plus"
                                size="small"
                                :disabled="!bundleCategoryId"
                                @click="addBundleCategory"
                            />
                        </div>

                        <!-- Subcategory add (when mode = subcategory) -->
                        <div v-if="bundleEntryMode === 'subcategory'" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                            <div class="flex flex-1 flex-col gap-1">
                                <label class="text-xs text-muted-foreground">Filter by Category</label>
                                <Select
                                    v-model="bundleSubcategoryCategoryId"
                                    :options="categoryOptions"
                                    option-label="label"
                                    option-value="value"
                                    placeholder="All categories"
                                    size="small"
                                    filter
                                    showClear
                                    fluid
                                />
                            </div>
                            <div class="flex flex-1 flex-col gap-1">
                                <label class="text-xs text-muted-foreground">Subcategory</label>
                                <Select
                                    v-model="bundleSubcategoryId"
                                    :options="filteredSubcategoryOptions"
                                    option-label="label"
                                    option-value="value"
                                    placeholder="Select subcategory"
                                    size="small"
                                    filter
                                    fluid
                                />
                            </div>
                            <div class="flex w-28 flex-col gap-1">
                                <label class="text-xs text-muted-foreground">Required Qty</label>
                                <InputNumber v-model="bundleEntryQty" :min="1" size="small" fluid />
                            </div>
                            <Button
                                label="Add"
                                icon="pi pi-plus"
                                size="small"
                                :disabled="!bundleSubcategoryId"
                                @click="addBundleSubcategory"
                            />
                        </div>

                        <small v-if="form.errors.bundle_items" class="text-red-500">{{ form.errors.bundle_items }}</small>

                        <DataTable
                            v-if="form.bundle_items.length > 0"
                            :value="form.bundle_items"
                            size="small"
                            striped-rows
                            class="overflow-hidden rounded-lg border border-border"
                        >
                            <Column header="Item">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <!-- Product entry -->
                                        <template v-if="data.product_id">
                                            <div @click.stop>
                                                <Image v-if="data.image_url" :src="data.image_url" alt="" image-class="h-8 w-8 rounded object-cover cursor-pointer" :pt="{ root: { class: 'rounded overflow-hidden flex-shrink-0' }, previewMask: { class: 'rounded' } }" preview />
                                                <Avatar v-else :label="data.product_name?.charAt(0)" shape="square" class="!h-8 !w-8 flex-shrink-0 rounded bg-primary/10 text-primary" />
                                            </div>
                                            <div>
                                                <div class="font-medium">{{ data.product_name }}</div>
                                                <div class="text-xs text-muted-foreground">
                                                    {{ data.product_number }}
                                                    <span v-if="data.variant_name"> - {{ data.variant_name }}</span>
                                                </div>
                                            </div>
                                        </template>
                                        <!-- Category / Subcategory entry -->
                                        <template v-else>
                                            <i class="pi pi-folder text-lg text-muted-foreground"></i>
                                            <div class="font-medium">{{ getBundleItemLabel(data) }}</div>
                                        </template>
                                    </div>
                                </template>
                            </Column>
                            <Column header="Required Qty" class="w-32">
                                <template #body="{ index }">
                                    <InputNumber
                                        v-model="form.bundle_items[index].required_quantity"
                                        :min="1"
                                        size="small"
                                        fluid
                                    />
                                </template>
                            </Column>
                            <Column header="" class="w-16">
                                <template #body="{ index }">
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        size="small"
                                        @click="removeBundleItem(index)"
                                    />
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>

                <!-- Type-specific: Minimum Spend -->
                <template v-if="form.type === 'minimum_spend'">
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-medium">Minimum Spend Thresholds *</label>
                            <Button
                                v-if="form.minimum_spends.length < currencies.length"
                                label="Add Currency"
                                icon="pi pi-plus"
                                size="small"
                                severity="secondary"
                                text
                                @click="addMinimumSpend"
                            />
                        </div>
                        <small v-if="form.errors.minimum_spends" class="text-red-500">{{ form.errors.minimum_spends }}</small>

                        <DataTable
                            v-if="form.minimum_spends.length > 0"
                            :value="form.minimum_spends"
                            dataKey="currency_id"
                            size="small"
                            class="overflow-hidden rounded-lg border border-border"
                        >
                            <Column header="Currency" class="w-40">
                                <template #body="{ index }">
                                    <Select
                                        v-model="form.minimum_spends[index].currency_id"
                                        :options="currencyOptions"
                                        option-label="label"
                                        option-value="value"
                                        size="small"
                                        fluid
                                    />
                                </template>
                            </Column>
                            <Column header="Minimum Amount">
                                <template #body="{ index }">
                                    <InputNumber
                                        v-model="form.minimum_spends[index].minimum_amount"
                                        :min="0.01"
                                        :minFractionDigits="2"
                                        :maxFractionDigits="4"
                                        size="small"
                                        fluid
                                    />
                                </template>
                            </Column>
                            <Column header="" class="w-16">
                                <template #body="{ index }">
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        size="small"
                                        @click="removeMinimumSpend(index)"
                                    />
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>

                <!-- Type-specific: Category -->
                <template v-if="form.type === 'category'">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Category *</label>
                        <Select
                            v-model="form.category_id"
                            :options="categoryOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select category"
                            size="small"
                            filter
                            class="w-full sm:w-72"
                            :invalid="!!form.errors.category_id"
                        />
                        <small v-if="form.errors.category_id" class="text-red-500">{{ form.errors.category_id }}</small>
                    </div>
                </template>

                <!-- Type-specific: Brand -->
                <template v-if="form.type === 'brand'">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Brand *</label>
                        <Select
                            v-model="form.brand_id"
                            :options="brandOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select brand"
                            size="small"
                            filter
                            class="w-full sm:w-72"
                            :invalid="!!form.errors.brand_id"
                        />
                        <small v-if="form.errors.brand_id" class="text-red-500">{{ form.errors.brand_id }}</small>
                    </div>
                </template>

                <!-- Actions -->
                <div class="flex gap-2">
                    <Button
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="isEditing ? goBack() : router.visit('/offers')"
                    />
                    <Button
                        :label="isEditing ? 'Update Offer' : 'Create Offer'"
                        icon="pi pi-save"
                        size="small"
                        type="submit"
                        :loading="form.processing"
                    />
                </div>
            </form>
        </div>
    </AppLayout>
</template>
