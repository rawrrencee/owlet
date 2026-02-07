import type { Product, ProductAdjacentIds, ProductSearchResult } from '@/types';
import axios from 'axios';
import { computed, ref, type ComputedRef, type Ref } from 'vue';

interface NavigationEntry {
    productId: number;
    mode: 'list' | 'search' | 'selection';
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

export function useProductPreview(
    filtersRef: Ref<Filters> | ComputedRef<Filters>,
) {
    const previewVisible = ref(false);
    const currentProduct = ref<Product | null>(null);
    const loading = ref(false);
    const searchLoading = ref(false);
    const navigationStack = ref<NavigationEntry[]>([]);
    const searchResults = ref<ProductSearchResult[]>([]);
    const selectionIds = ref<number[]>([]);

    let searchDebounceTimeout: ReturnType<typeof setTimeout> | null = null;

    const currentEntry = computed(() => {
        if (navigationStack.value.length === 0) return null;
        return navigationStack.value[navigationStack.value.length - 1];
    });

    const currentMode = computed(() => currentEntry.value?.mode ?? 'list');
    const canGoBack = computed(() => navigationStack.value.length > 1);

    const adjacentIds = computed(() => currentEntry.value?.adjacentIds ?? null);

    // For selection mode, compute adjacent IDs from the client-side selection array
    const selectionAdjacentIds = computed(() => {
        if (currentMode.value !== 'selection' || !currentProduct.value)
            return null;
        const currentIndex = selectionIds.value.indexOf(
            currentProduct.value.id,
        );
        if (currentIndex === -1) return null;
        return {
            prev_id:
                currentIndex > 0 ? selectionIds.value[currentIndex - 1] : null,
            next_id:
                currentIndex < selectionIds.value.length - 1
                    ? selectionIds.value[currentIndex + 1]
                    : null,
            position: currentIndex + 1,
            total: selectionIds.value.length,
        };
    });

    const effectiveAdjacentIds = computed(() =>
        currentMode.value === 'selection'
            ? selectionAdjacentIds.value
            : adjacentIds.value,
    );

    const canGoPrev = computed(
        () =>
            (currentMode.value === 'list' ||
                currentMode.value === 'selection') &&
            effectiveAdjacentIds.value?.prev_id !== null,
    );
    const canGoNext = computed(
        () =>
            (currentMode.value === 'list' ||
                currentMode.value === 'selection') &&
            effectiveAdjacentIds.value?.next_id !== null,
    );
    const positionText = computed(() => {
        if (
            (currentMode.value !== 'list' &&
                currentMode.value !== 'selection') ||
            !effectiveAdjacentIds.value
        )
            return '';
        return `${effectiveAdjacentIds.value.position} of ${effectiveAdjacentIds.value.total}`;
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

    async function fetchAdjacentIds(
        productId: number,
    ): Promise<ProductAdjacentIds | null> {
        try {
            const response = await axios.get(
                `/products/${productId}/adjacent`,
                {
                    params: buildFilterParams(),
                },
            );
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
            adjacentIds: adjacent ?? {
                prev_id: null,
                next_id: null,
                position: null,
                total: 0,
            },
        });

        loading.value = false;
    }

    async function navigatePrev(): Promise<void> {
        if (!canGoPrev.value || !effectiveAdjacentIds.value?.prev_id) return;
        const mode = currentMode.value === 'selection' ? 'selection' : 'list';
        await navigateToProduct(effectiveAdjacentIds.value.prev_id, mode);
    }

    async function navigateNext(): Promise<void> {
        if (!canGoNext.value || !effectiveAdjacentIds.value?.next_id) return;
        const mode = currentMode.value === 'selection' ? 'selection' : 'list';
        await navigateToProduct(effectiveAdjacentIds.value.next_id, mode);
    }

    async function navigateToProduct(
        productId: number,
        mode: 'list' | 'search' | 'selection',
    ): Promise<void> {
        loading.value = true;

        const [product, adjacent] = await Promise.all([
            fetchProduct(productId),
            mode === 'list'
                ? fetchAdjacentIds(productId)
                : Promise.resolve(null),
        ]);

        if (product) {
            currentProduct.value = product;

            if (mode === 'list') {
                // Replace current entry for list navigation
                navigationStack.value[navigationStack.value.length - 1] = {
                    productId,
                    mode: 'list',
                    adjacentIds: adjacent ?? {
                        prev_id: null,
                        next_id: null,
                        position: null,
                        total: 0,
                    },
                };
            } else if (mode === 'selection') {
                // Replace current entry for selection navigation (adjacent computed client-side)
                navigationStack.value[navigationStack.value.length - 1] = {
                    productId,
                    mode: 'selection',
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

    async function openPreviewFromSelection(
        productId: number,
        selectedProductIds: number[],
    ): Promise<void> {
        loading.value = true;
        previewVisible.value = true;
        currentProduct.value = null;
        navigationStack.value = [];
        selectionIds.value = selectedProductIds;

        const fullProduct = await fetchProduct(productId);

        if (fullProduct) {
            currentProduct.value = fullProduct;
        }

        navigationStack.value.push({
            productId,
            mode: 'selection',
        });

        loading.value = false;
    }

    async function navigateToRelated(productId: number): Promise<void> {
        await navigateToProduct(productId, 'search');
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

    async function handleSearchSelect(
        result: ProductSearchResult,
    ): Promise<void> {
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
        selectionIds.value = [];
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
        openPreviewFromSelection,
        navigatePrev,
        navigateNext,
        navigateToRelated,
        searchProducts,
        handleSearchSelect,
        goBack,
        closePreview,
    };
}
