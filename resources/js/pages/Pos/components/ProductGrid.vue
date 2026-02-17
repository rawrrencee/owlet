<script setup lang="ts">
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import { computed, onMounted, ref, watch } from 'vue';
import FilterBrowseDialog from './FilterBrowseDialog.vue';
import ProductCard from './ProductCard.vue';

interface FilterCategory {
    id: number;
    category_name: string;
    subcategories: Array<{ id: number; subcategory_name: string }>;
}

interface FilterBrand {
    id: number;
    brand_name: string;
}

interface CurrencyInfo {
    id: number;
    code: string;
    symbol: string;
    name: string;
    exchange_rate: string;
}

const props = defineProps<{
    storeId: number;
    currencyId: number;
    currencySymbol: string;
    currencies?: CurrencyInfo[];
    favouriteIds?: Set<number>;
    scrollContainer?: HTMLElement | null;
}>();

const emit = defineEmits<{
    select: [product: any];
    'toggle-favourite': [productId: number];
}>();

const products = ref<any[]>([]);
const categories = ref<any[]>([]);
const subcategories = ref<any[]>([]);
const brands = ref<any[]>([]);
const selectedCategory = ref<number | null>(null);
const selectedCategoryName = ref<string | null>(null);
const selectedSubcategory = ref<number | null>(null);
const selectedBrand = ref<number | null>(null);
const selectedBrandName = ref<string | null>(null);
const showFavourites = ref(false);
const loading = ref(false);
const currentPage = ref(1);
const hasMore = ref(false);
const sentinelRef = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;
let isLoadingMore = false;

// Full filter data from the /pos/filters endpoint
const allCategories = ref<FilterCategory[]>([]);
const allBrands = ref<FilterBrand[]>([]);
const showFilterDialog = ref(false);
const filterDialogMode = ref<'category' | 'brand'>('category');
const filtersExpanded = ref(localStorage.getItem('pos_filters_expanded') !== 'false');

function toggleFilters() {
    filtersExpanded.value = !filtersExpanded.value;
    localStorage.setItem('pos_filters_expanded', filtersExpanded.value.toString());
}

const filteredSubcategories = computed(() => {
    if (!selectedCategory.value) return [];
    return subcategories.value.filter(s => s.category_id === selectedCategory.value);
});

const displayedProducts = computed(() => {
    if (!showFavourites.value || !props.favouriteIds) return products.value;
    return products.value.filter(p => props.favouriteIds!.has(p.id));
});

// Check if the selected category/brand from dialog is already in the inline chips
const selectedCategoryInChips = computed(() => {
    if (!selectedCategory.value) return true;
    return categories.value.some(c => c.id === selectedCategory.value);
});

const selectedBrandInChips = computed(() => {
    if (!selectedBrand.value) return true;
    return brands.value.some(b => b.id === selectedBrand.value);
});

async function loadFilters() {
    try {
        const response = await fetch(`/pos/filters?store_id=${props.storeId}`, {
            headers: {
                'Accept': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
                ),
            },
        });
        const data = await response.json();
        allCategories.value = data.categories;
        allBrands.value = data.brands;
    } catch {
        // ignore
    }
}

async function loadProducts(reset = false) {
    if (reset) {
        currentPage.value = 1;
        products.value = [];
    }
    loading.value = true;
    try {
        const params = new URLSearchParams({
            store_id: props.storeId.toString(),
            currency_id: props.currencyId.toString(),
            page: currentPage.value.toString(),
            per_page: '40',
        });
        if (selectedCategory.value) params.set('category_id', selectedCategory.value.toString());
        if (selectedSubcategory.value) params.set('subcategory_id', selectedSubcategory.value.toString());
        if (selectedBrand.value) params.set('brand_id', selectedBrand.value.toString());

        const response = await fetch(`/pos/products?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
                ),
            },
        });
        const data = await response.json();

        if (reset) {
            products.value = data.data;
        } else {
            products.value = [...products.value, ...data.data];
        }
        hasMore.value = data.current_page < data.last_page;

        // Extract unique categories, subcategories, and brands from first page
        if (reset) {
            const catSet = new Map<number, string>();
            const subSet = new Map<number, { name: string; category_id: number }>();
            const brandSet = new Map<number, string>();
            data.data.forEach((p: any) => {
                if (p.category) catSet.set(p.category.id, p.category.category_name);
                if (p.subcategory) subSet.set(p.subcategory.id, {
                    name: p.subcategory.subcategory_name,
                    category_id: p.subcategory.category_id,
                });
                if (p.brand) brandSet.set(p.brand.id, p.brand.brand_name);
            });
            categories.value = Array.from(catSet, ([id, name]) => ({ id, name }));
            subcategories.value = Array.from(subSet, ([id, data]) => ({ id, name: data.name, category_id: data.category_id }));
            brands.value = Array.from(brandSet, ([id, name]) => ({ id, name }));
        }
    } catch {
        // ignore
    } finally {
        loading.value = false;
    }
}

function filterByCategory(categoryId: number | null) {
    selectedCategory.value = categoryId;
    selectedCategoryName.value = null;
    selectedSubcategory.value = null;
    loadProducts(true);
}

function filterBySubcategory(subcategoryId: number | null) {
    selectedSubcategory.value = subcategoryId;
    loadProducts(true);
}

function filterByBrand(brandId: number | null) {
    selectedBrand.value = brandId;
    selectedBrandName.value = null;
    loadProducts(true);
}

function clearAllCategories() {
    selectedCategory.value = null;
    selectedCategoryName.value = null;
    selectedSubcategory.value = null;
    loadProducts(true);
}

function clearAllBrands() {
    selectedBrand.value = null;
    selectedBrandName.value = null;
    loadProducts(true);
}

function openFilterDialog(mode: 'category' | 'brand') {
    filterDialogMode.value = mode;
    showFilterDialog.value = true;
}

function onDialogSelectCategory(payload: { categoryId: number; subcategoryId: number | null }) {
    selectedCategory.value = payload.categoryId;
    selectedSubcategory.value = payload.subcategoryId;
    // Store the name for display if category is not in inline chips
    const cat = allCategories.value.find(c => c.id === payload.categoryId);
    selectedCategoryName.value = cat?.category_name ?? null;
    selectedBrand.value = null;
    selectedBrandName.value = null;
    loadProducts(true);
}

function onDialogSelectBrand(payload: { brandId: number }) {
    selectedBrand.value = payload.brandId;
    const brand = allBrands.value.find(b => b.id === payload.brandId);
    selectedBrandName.value = brand?.brand_name ?? null;
    selectedCategory.value = null;
    selectedCategoryName.value = null;
    selectedSubcategory.value = null;
    loadProducts(true);
}

async function loadMore() {
    if (isLoadingMore || !hasMore.value) return;
    isLoadingMore = true;
    currentPage.value++;
    await loadProducts();
    isLoadingMore = false;
}

function setupObserver() {
    if (observer) observer.disconnect();
    if (!sentinelRef.value) return;

    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting && hasMore.value && !isLoadingMore) {
                loadMore();
            }
        },
        {
            root: props.scrollContainer ?? null,
            rootMargin: '200px',
        }
    );
    observer.observe(sentinelRef.value);
}

onMounted(() => {
    loadProducts(true);
    loadFilters();
    // Setup observer after initial load completes
    watch([sentinelRef, () => props.scrollContainer], () => {
        setupObserver();
    }, { immediate: true });
});

watch(() => [props.storeId, props.currencyId], () => {
    selectedCategory.value = null;
    selectedCategoryName.value = null;
    selectedSubcategory.value = null;
    selectedBrand.value = null;
    selectedBrandName.value = null;
    loadProducts(true);
    loadFilters();
});
</script>

<template>
    <!-- Filter header with collapse toggle -->
    <div class="mb-2 flex items-center justify-between">
        <Button
            :icon="filtersExpanded ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
            :label="filtersExpanded ? 'Hide Filters' : 'Show Filters'"
            text
            size="small"
            severity="secondary"
            class="!pl-0"
            @click="toggleFilters"
        />
        <!-- Favourites toggle (always visible) -->
        <Button
            v-if="favouriteIds && favouriteIds.size > 0"
            icon="pi pi-star-fill"
            size="small"
            :severity="showFavourites ? 'warn' : 'secondary'"
            :outlined="!showFavourites"
            v-tooltip.bottom="'Favourites'"
            @click="showFavourites = !showFavourites"
        />
    </div>

    <!-- Collapsible filter rows -->
    <div v-show="filtersExpanded">
    <!-- Categories row -->
    <div class="mb-2 flex flex-wrap gap-1.5 items-center">
        <span class="text-xs text-muted-color font-medium mr-1">Category:</span>
        <Button
            label="All"
            size="small"
            :severity="!selectedCategory ? undefined : 'secondary'"
            :outlined="!!selectedCategory"
            @click="clearAllCategories"
        />

        <!-- Show selected category from dialog if not in inline chips -->
        <Button
            v-if="selectedCategory && !selectedCategoryInChips && selectedCategoryName"
            :label="selectedCategoryName"
            size="small"
            icon="pi pi-times"
            icon-pos="right"
            @click="clearAllCategories"
        />

        <Button
            v-for="cat in categories"
            :key="'cat-' + cat.id"
            :label="cat.name"
            size="small"
            :severity="selectedCategory === cat.id ? undefined : 'secondary'"
            :outlined="selectedCategory !== cat.id"
            @click="filterByCategory(selectedCategory === cat.id ? null : cat.id)"
        />

        <!-- More categories button -->
        <Button
            v-if="allCategories.length > categories.length"
            icon="pi pi-ellipsis-h"
            severity="secondary"
            text
            size="small"
            v-tooltip.bottom="'More categories'"
            @click="openFilterDialog('category')"
        />
    </div>

    <!-- Brands row -->
    <div class="mb-3 flex flex-wrap gap-1.5 items-center">
        <span class="text-xs text-muted-color font-medium mr-1">Brand:</span>
        <Button
            label="All"
            size="small"
            :severity="!selectedBrand ? 'info' : 'secondary'"
            :outlined="!!selectedBrand"
            @click="clearAllBrands"
        />

        <!-- Show selected brand from dialog if not in inline chips -->
        <Button
            v-if="selectedBrand && !selectedBrandInChips && selectedBrandName"
            :label="selectedBrandName"
            size="small"
            severity="info"
            icon="pi pi-times"
            icon-pos="right"
            @click="clearAllBrands"
        />

        <Button
            v-for="brand in brands"
            :key="'brand-' + brand.id"
            :label="brand.name"
            size="small"
            :severity="selectedBrand === brand.id ? 'info' : 'secondary'"
            :outlined="selectedBrand !== brand.id"
            @click="filterByBrand(selectedBrand === brand.id ? null : brand.id)"
        />

        <!-- More brands button -->
        <Button
            v-if="allBrands.length > brands.length"
            icon="pi pi-ellipsis-h"
            severity="secondary"
            text
            size="small"
            v-tooltip.bottom="'More brands'"
            @click="openFilterDialog('brand')"
        />
    </div>

    <!-- Subcategory chips (when a category is selected) -->
    <div v-if="filteredSubcategories.length > 0" class="mb-3 flex flex-wrap gap-1.5">
        <Button
            v-for="sub in filteredSubcategories"
            :key="'sub-' + sub.id"
            :label="sub.name"
            size="small"
            :severity="selectedSubcategory === sub.id ? 'success' : 'secondary'"
            :outlined="selectedSubcategory !== sub.id"
            @click="filterBySubcategory(selectedSubcategory === sub.id ? null : sub.id)"
        />
    </div>
    </div>

    <!-- Product grid -->
    <div v-if="loading && products.length === 0" class="flex justify-center p-8">
        <ProgressSpinner style="width: 40px; height: 40px" />
    </div>

    <div v-else-if="displayedProducts.length === 0" class="text-center text-muted-color py-8">
        <p>{{ showFavourites ? 'No favourite products found.' : 'No products found for this store.' }}</p>
    </div>

    <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2">
        <ProductCard
            v-for="product in displayedProducts"
            :key="product.id"
            :product="product"
            :store-id="storeId"
            :currency-id="currencyId"
            :currency-symbol="currencySymbol"
            :currencies="currencies"
            :is-favourite="favouriteIds?.has(product.id) ?? false"
            @select="emit('select', product)"
            @toggle-favourite="emit('toggle-favourite', product.id)"
        />
    </div>

    <!-- Infinite scroll sentinel -->
    <div ref="sentinelRef" class="h-4">
        <div v-if="loading && products.length > 0" class="flex justify-center py-4">
            <ProgressSpinner style="width: 30px; height: 30px" />
        </div>
    </div>

    <!-- Filter browse dialog -->
    <FilterBrowseDialog
        v-model:visible="showFilterDialog"
        :mode="filterDialogMode"
        :categories="allCategories"
        :brands="allBrands"
        @select-category="onDialogSelectCategory"
        @select-brand="onDialogSelectBrand"
    />
</template>
