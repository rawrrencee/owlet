<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Chips from 'primevue/chips';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import InputNumber from 'primevue/inputnumber';
import Message from 'primevue/message';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import ToggleSwitch from 'primevue/toggleswitch';
import { computed, watch } from 'vue';
import type { Category, Currency, Product, Subcategory } from '@/types';

interface Props {
    visible: boolean;
    products: Product[];
    brands: Array<{ id: number; brand_name: string; brand_code: string }>;
    categories: Array<Category & { subcategories?: Subcategory[] }>;
    suppliers: Array<{ id: number; supplier_name: string }>;
    currencies: Currency[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:visible': [value: boolean];
    'success': [];
}>();

const dialogVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});

const form = useForm({
    product_ids: [] as number[],
    // Classification
    apply_brand: false,
    brand_id: null as number | null,
    apply_category: false,
    category_id: null as number | null,
    subcategory_id: null as number | null,
    apply_supplier: false,
    supplier_id: null as number | null,
    // Status
    apply_status: false,
    is_active: true,
    // Tags
    apply_tags: false,
    tags_to_add: [] as string[],
    tags_to_remove: [] as string[],
    // Prices
    apply_prices: false,
    price_mode: 'percentage' as 'fixed' | 'percentage',
    price_adjustments: [] as Array<{
        currency_id: number;
        cost_price: number | null;
        unit_price: number | null;
    }>,
});

// Options
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

const priceModeOptions = [
    { label: 'Percentage', value: 'percentage' },
    { label: 'Fixed Value', value: 'fixed' },
];

// Get common values from selected products
const commonBrandId = computed(() => {
    if (props.products.length === 0) return null;
    const first = props.products[0].brand_id;
    return props.products.every((p) => p.brand_id === first) ? first : null;
});

const commonCategoryId = computed(() => {
    if (props.products.length === 0) return null;
    const first = props.products[0].category_id;
    return props.products.every((p) => p.category_id === first) ? first : null;
});

const commonSubcategoryId = computed(() => {
    if (props.products.length === 0) return null;
    const first = props.products[0].subcategory_id;
    return props.products.every((p) => p.subcategory_id === first) ? first : null;
});

const commonSupplierId = computed(() => {
    if (props.products.length === 0) return null;
    const first = props.products[0].supplier_id;
    return props.products.every((p) => p.supplier_id === first) ? first : null;
});

const commonIsActive = computed(() => {
    if (props.products.length === 0) return null;
    const first = props.products[0].is_active;
    return props.products.every((p) => p.is_active === first) ? first : null;
});

// Get all unique tags from selected products
const allExistingTags = computed(() => {
    const tagSet = new Set<string>();
    props.products.forEach((p) => {
        (p.tags ?? []).forEach((t) => tagSet.add(t));
    });
    return Array.from(tagSet).sort();
});

// Initialize form when dialog opens
watch(
    () => props.visible,
    (visible) => {
        if (visible) {
            initializeForm();
        }
    },
);

function initializeForm() {
    form.reset();
    form.product_ids = props.products.map((p) => p.id);
    form.brand_id = commonBrandId.value;
    form.category_id = commonCategoryId.value;
    form.subcategory_id = commonSubcategoryId.value;
    form.supplier_id = commonSupplierId.value;
    form.is_active = commonIsActive.value ?? true;

    // Initialize price adjustments for available currencies
    form.price_adjustments = props.currencies.map((c) => ({
        currency_id: c.id,
        cost_price: null,
        unit_price: null,
    }));
}

// Reset subcategory when category changes
watch(
    () => form.category_id,
    (newCategoryId) => {
        if (newCategoryId !== commonCategoryId.value) {
            form.subcategory_id = null;
        }
    },
);

function handleSubmit() {
    form.post('/products/batch-update', {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            emit('success');
        },
    });
}

function handleClose() {
    dialogVisible.value = false;
}

// Check if a field has mixed values
function isMixed(value: unknown): boolean {
    return value === null;
}
</script>

<template>
    <Dialog
        v-model:visible="dialogVisible"
        modal
        :closable="!form.processing"
        :draggable="false"
        header="Batch Edit Products"
        class="w-full max-w-xl"
        :pt="{
            content: { class: 'p-0' },
        }"
    >
        <form @submit.prevent="handleSubmit" class="flex flex-col gap-4 p-4">
            <!-- Selection Summary -->
            <Message severity="info" :closable="false" class="!m-0">
                Editing {{ products.length }} product(s). Only checked fields will be updated.
            </Message>

            <!-- Classification Section -->
            <div class="rounded-lg border border-border p-4">
                <h3 class="mb-3 text-sm font-semibold text-foreground">Classification</h3>
                <div class="flex flex-col gap-3">
                    <!-- Brand -->
                    <div class="flex items-center gap-3">
                        <Checkbox v-model="form.apply_brand" :binary="true" input-id="apply_brand" />
                        <label for="apply_brand" class="w-24 text-sm">Brand</label>
                        <Select
                            v-model="form.brand_id"
                            :options="brandOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="isMixed(commonBrandId) ? '(Mixed)' : 'Select brand'"
                            :disabled="!form.apply_brand"
                            filter
                            size="small"
                            class="flex-1"
                        />
                    </div>

                    <!-- Category -->
                    <div class="flex items-center gap-3">
                        <Checkbox v-model="form.apply_category" :binary="true" input-id="apply_category" />
                        <label for="apply_category" class="w-24 text-sm">Category</label>
                        <div class="flex flex-1 gap-2">
                            <Select
                                v-model="form.category_id"
                                :options="categoryOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="isMixed(commonCategoryId) ? '(Mixed)' : 'Select category'"
                                :disabled="!form.apply_category"
                                filter
                                size="small"
                                class="flex-1"
                            />
                            <Select
                                v-model="form.subcategory_id"
                                :options="subcategoryOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="subcategoryOptions.length === 0 ? 'No subcategories' : 'Subcategory'"
                                :disabled="!form.apply_category || subcategoryOptions.length === 0"
                                filter
                                size="small"
                                class="flex-1"
                            />
                        </div>
                    </div>

                    <!-- Supplier -->
                    <div class="flex items-center gap-3">
                        <Checkbox v-model="form.apply_supplier" :binary="true" input-id="apply_supplier" />
                        <label for="apply_supplier" class="w-24 text-sm">Supplier</label>
                        <Select
                            v-model="form.supplier_id"
                            :options="supplierOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="isMixed(commonSupplierId) ? '(Mixed)' : 'Select supplier'"
                            :disabled="!form.apply_supplier"
                            filter
                            size="small"
                            class="flex-1"
                        />
                    </div>
                </div>
            </div>

            <!-- Status Section -->
            <div class="rounded-lg border border-border p-4">
                <h3 class="mb-3 text-sm font-semibold text-foreground">Status</h3>
                <div class="flex items-center gap-3">
                    <Checkbox v-model="form.apply_status" :binary="true" input-id="apply_status" />
                    <label for="apply_status" class="w-24 text-sm">Active</label>
                    <div class="flex items-center gap-2">
                        <ToggleSwitch v-model="form.is_active" :disabled="!form.apply_status" />
                        <span class="text-sm text-muted-foreground">
                            {{ form.is_active ? 'Active' : 'Inactive' }}
                            <template v-if="isMixed(commonIsActive)"> (Mixed)</template>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tags Section -->
            <div class="rounded-lg border border-border p-4">
                <h3 class="mb-3 text-sm font-semibold text-foreground">Tags</h3>
                <div class="flex flex-col gap-3">
                    <div class="flex items-start gap-3">
                        <Checkbox v-model="form.apply_tags" :binary="true" input-id="apply_tags" class="mt-2" />
                        <div class="flex flex-1 flex-col gap-3">
                            <div>
                                <label class="mb-1 block text-sm text-muted-foreground">Add tags</label>
                                <Chips
                                    v-model="form.tags_to_add"
                                    :disabled="!form.apply_tags"
                                    separator=","
                                    add-on-blur
                                    class="w-full"
                                    :pt="{ input: { class: 'w-full' } }"
                                />
                            </div>
                            <div v-if="allExistingTags.length > 0">
                                <label class="mb-1 block text-sm text-muted-foreground">Remove tags</label>
                                <div class="flex flex-wrap gap-1">
                                    <Button
                                        v-for="tag in allExistingTags"
                                        :key="tag"
                                        :label="tag"
                                        size="small"
                                        :severity="form.tags_to_remove.includes(tag) ? 'danger' : 'secondary'"
                                        :outlined="!form.tags_to_remove.includes(tag)"
                                        :disabled="!form.apply_tags"
                                        @click="
                                            form.tags_to_remove.includes(tag)
                                                ? (form.tags_to_remove = form.tags_to_remove.filter((t) => t !== tag))
                                                : form.tags_to_remove.push(tag)
                                        "
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prices Section -->
            <div class="rounded-lg border border-border p-4">
                <h3 class="mb-3 text-sm font-semibold text-foreground">Base Prices</h3>
                <div class="flex flex-col gap-3">
                    <div class="flex items-center gap-3">
                        <Checkbox v-model="form.apply_prices" :binary="true" input-id="apply_prices" />
                        <label for="apply_prices" class="w-24 text-sm">Mode</label>
                        <SelectButton
                            v-model="form.price_mode"
                            :options="priceModeOptions"
                            option-label="label"
                            option-value="value"
                            :disabled="!form.apply_prices"
                            :allow-empty="false"
                            size="small"
                        />
                    </div>

                    <div v-if="form.apply_prices" class="mt-2 flex flex-col gap-2">
                        <div
                            v-for="(adjustment, index) in form.price_adjustments"
                            :key="adjustment.currency_id"
                            class="grid grid-cols-3 items-center gap-2"
                        >
                            <span class="text-sm font-medium">
                                {{ currencies.find((c) => c.id === adjustment.currency_id)?.code ?? 'Unknown' }}
                            </span>
                            <div>
                                <label class="mb-1 block text-xs text-muted-foreground">
                                    Cost {{ form.price_mode === 'percentage' ? '(%)' : '' }}
                                </label>
                                <InputNumber
                                    v-model="form.price_adjustments[index].cost_price"
                                    :min="form.price_mode === 'fixed' ? 0 : undefined"
                                    :min-fraction-digits="2"
                                    :max-fraction-digits="2"
                                    :prefix="form.price_mode === 'percentage' ? '' : currencies.find((c) => c.id === adjustment.currency_id)?.symbol"
                                    :suffix="form.price_mode === 'percentage' ? '%' : ''"
                                    size="small"
                                    fluid
                                    :placeholder="form.price_mode === 'percentage' ? '+/-' : ''"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs text-muted-foreground">
                                    Unit {{ form.price_mode === 'percentage' ? '(%)' : '' }}
                                </label>
                                <InputNumber
                                    v-model="form.price_adjustments[index].unit_price"
                                    :min="form.price_mode === 'fixed' ? 0 : undefined"
                                    :min-fraction-digits="2"
                                    :max-fraction-digits="2"
                                    :prefix="form.price_mode === 'percentage' ? '' : currencies.find((c) => c.id === adjustment.currency_id)?.symbol"
                                    :suffix="form.price_mode === 'percentage' ? '%' : ''"
                                    size="small"
                                    fluid
                                    :placeholder="form.price_mode === 'percentage' ? '+/-' : ''"
                                />
                            </div>
                        </div>
                    </div>

                    <Message v-if="form.apply_prices" severity="warn" :closable="false" class="!mt-2">
                        Store-specific prices cannot be batch edited. Only base prices will be updated.
                    </Message>
                </div>
            </div>

            <!-- Errors -->
            <Message v-if="Object.keys(form.errors).length > 0" severity="error" :closable="false">
                <ul class="list-inside list-disc">
                    <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                </ul>
            </Message>

            <Divider class="!my-0" />

            <!-- Actions -->
            <div class="flex justify-end gap-2">
                <Button
                    type="button"
                    label="Cancel"
                    severity="secondary"
                    size="small"
                    :disabled="form.processing"
                    @click="handleClose"
                />
                <Button
                    type="submit"
                    :label="`Update ${products.length} product(s)`"
                    size="small"
                    :loading="form.processing"
                    :disabled="!form.apply_brand && !form.apply_category && !form.apply_supplier && !form.apply_status && !form.apply_tags && !form.apply_prices"
                />
            </div>
        </form>
    </Dialog>
</template>
