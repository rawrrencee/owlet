import axios from 'axios';
import { computed, ref, type ComputedRef, type Ref } from 'vue';
import type { Product, ProductAdjacentIds, ProductSearchResult } from '@/types';

interface NavigationEntry {
    productId: number;
    mode: 'list' | 'search';
    adjacentIds?: ProductAdjacentIds;
}

interface Filters {
    search?: string;
    status?: string;
    brand_id?: string | number;
    category_id?: string | number;
    supplier_id?: string | number;
    show_deleted?: boolean;
}

export function useProductPreview(filtersRef: Ref<Filters> | ComputedRef<Filters>) {
    const previewVisible = ref(false);
    const currentProduct = ref<Product | null>(null);
    const loading = ref(false);
    const searchLoading = ref(false);
    const navigationStack = ref<NavigationEntry[]>([]);
    const searchResults = ref<ProductSearchResult[]>([]);

    let searchDebounceTimeout: ReturnType<typeof setTimeout> | null = null;

    const currentEntry = computed(() => {
        if (navigationStack.value.length === 0) return null;
        return navigationStack.value[navigationStack.value.length - 1];
    });

    const currentMode = computed(() => currentEntry.value?.mode ?? 'list');
    const canGoBack = computed(() => navigationStack.value.length > 1);

    const adjacentIds = computed(() => currentEntry.value?.adjacentIds ?? null);
    const canGoPrev = computed(() => currentMode.value === 'list' && adjacentIds.value?.prev_id !== null);
    const canGoNext = computed(() => currentMode.value === 'list' && adjacentIds.value?.next_id !== null);
    const positionText = computed(() => {
        if (currentMode.value !== 'list' || !adjacentIds.value) return '';
        return `${adjacentIds.value.position} of ${adjacentIds.value.total}`;
    });

    function buildFilterParams(): Record<string, string | boolean> {
        const params: Record<string, string | boolean> = {};
        const f = filtersRef.value;
        if (f.search) params.search = f.search;
        if (f.status) params.status = f.status;
        if (f.brand_id) params.brand_id = String(f.brand_id);
        if (f.category_id) params.category_id = String(f.category_id);
        if (f.supplier_id) params.supplier_id = String(f.supplier_id);
        if (f.show_deleted) params.show_deleted = true;
        return params;
    }

    async function fetchProduct(productId: number): Promise<Product | null> {
        try {
            const response = await axios.get(`/products/${productId}/preview`);
            return response.data.data;
        } catch (error) {
            console.error('Failed to fetch product preview:', error);
            return null;
        }
    }

    async function fetchAdjacentIds(productId: number): Promise<ProductAdjacentIds | null> {
        try {
            const response = await axios.get(`/products/${productId}/adjacent`, {
                params: buildFilterParams(),
            });
            return response.data;
        } catch (error) {
            console.error('Failed to fetch adjacent IDs:', error);
            return null;
        }
    }

    async function openPreview(product: Product): Promise<void> {
        loading.value = true;
        previewVisible.value = true;
        currentProduct.value = product;
        navigationStack.value = [];

        const [fullProduct, adjacent] = await Promise.all([
            fetchProduct(product.id),
            fetchAdjacentIds(product.id),
        ]);

        if (fullProduct) {
            currentProduct.value = fullProduct;
        }

        navigationStack.value.push({
            productId: product.id,
            mode: 'list',
            adjacentIds: adjacent ?? { prev_id: null, next_id: null, position: null, total: 0 },
        });

        loading.value = false;
    }

    async function navigatePrev(): Promise<void> {
        if (!canGoPrev.value || !adjacentIds.value?.prev_id) return;
        await navigateToProduct(adjacentIds.value.prev_id, 'list');
    }

    async function navigateNext(): Promise<void> {
        if (!canGoNext.value || !adjacentIds.value?.next_id) return;
        await navigateToProduct(adjacentIds.value.next_id, 'list');
    }

    async function navigateToProduct(productId: number, mode: 'list' | 'search'): Promise<void> {
        loading.value = true;

        const [product, adjacent] = await Promise.all([
            fetchProduct(productId),
            mode === 'list' ? fetchAdjacentIds(productId) : Promise.resolve(null),
        ]);

        if (product) {
            currentProduct.value = product;

            if (mode === 'list') {
                // Replace current entry for list navigation
                navigationStack.value[navigationStack.value.length - 1] = {
                    productId,
                    mode: 'list',
                    adjacentIds: adjacent ?? { prev_id: null, next_id: null, position: null, total: 0 },
                };
            } else {
                // Push new entry for search navigation
                navigationStack.value.push({
                    productId,
                    mode: 'search',
                });
            }
        }

        loading.value = false;
    }

    async function searchProducts(query: string): Promise<void> {
        if (searchDebounceTimeout) {
            clearTimeout(searchDebounceTimeout);
        }

        if (query.length < 2) {
            searchResults.value = [];
            return;
        }

        searchDebounceTimeout = setTimeout(async () => {
            searchLoading.value = true;
            try {
                const response = await axios.get('/products/search', {
                    params: { q: query, limit: 20 },
                });
                searchResults.value = response.data.data;
            } catch (error) {
                console.error('Failed to search products:', error);
                searchResults.value = [];
            } finally {
                searchLoading.value = false;
            }
        }, 300);
    }

    async function handleSearchSelect(result: ProductSearchResult): Promise<void> {
        searchResults.value = [];
        await navigateToProduct(result.id, 'search');
    }

    async function goBack(): Promise<void> {
        if (!canGoBack.value) {
            closePreview();
            return;
        }

        navigationStack.value.pop();
        const previousEntry = currentEntry.value;
        if (!previousEntry) {
            closePreview();
            return;
        }

        loading.value = true;
        const product = await fetchProduct(previousEntry.productId);
        if (product) {
            currentProduct.value = product;
        }
        loading.value = false;
    }

    function closePreview(): void {
        previewVisible.value = false;
        navigationStack.value = [];
        currentProduct.value = null;
        searchResults.value = [];
    }

    return {
        previewVisible,
        currentProduct,
        loading,
        searchLoading,
        searchResults,
        currentMode,
        canGoBack,
        canGoPrev,
        canGoNext,
        positionText,
        openPreview,
        navigatePrev,
        navigateNext,
        searchProducts,
        handleSearchSelect,
        goBack,
        closePreview,
    };
}
