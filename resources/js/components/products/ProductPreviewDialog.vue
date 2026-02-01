<script setup lang="ts">
import { usePermissions } from '@/composables/usePermissions';
import type { Product, ProductSearchResult } from '@/types';
import { router } from '@inertiajs/vue3';
import AutoComplete, {
    type AutoCompleteCompleteEvent,
} from 'primevue/autocomplete';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import Skeleton from 'primevue/skeleton';
import Tag from 'primevue/tag';
import { computed, ref, watch } from 'vue';

interface Props {
    visible: boolean;
    product: Product | null;
    loading: boolean;
    searchLoading: boolean;
    searchResults: ProductSearchResult[];
    currentMode: 'list' | 'search' | 'selection';
    canGoBack: boolean;
    canGoPrev: boolean;
    canGoNext: boolean;
    positionText: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:visible', value: boolean): void;
    (e: 'prev'): void;
    (e: 'next'): void;
    (e: 'back'): void;
    (e: 'search', query: string): void;
    (e: 'select', result: ProductSearchResult): void;
    (e: 'close'): void;
}>();

const { canAccessPage } = usePermissions();
const canViewCostPrice = computed(() =>
    canAccessPage('products.view_cost_price'),
);
const canEdit = computed(() => canAccessPage('products.edit'));

const searchQuery = ref<string | ProductSearchResult>('');

watch(
    () => props.visible,
    (newVal) => {
        if (!newVal) {
            searchQuery.value = '';
        }
    },
);

function onSearch(event: AutoCompleteCompleteEvent) {
    emit('search', event.query);
}

function onSelect(result: ProductSearchResult) {
    searchQuery.value = '';
    emit('select', result);
}

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function formatPrice(
    price: string | number | null | undefined,
    symbol: string = '',
): string {
    if (price === null || price === undefined) return '-';
    const num = typeof price === 'string' ? parseFloat(price) : price;
    return `${symbol}${num.toFixed(2)}`;
}

function navigateToView() {
    if (!props.product) return;
    emit('close');
    router.get(`/products/${props.product.id}`);
}

function navigateToEdit() {
    if (!props.product) return;
    emit('close');
    router.get(`/products/${props.product.id}/edit`);
}

function getTotalStoreQuantity(product: Product): number {
    if (!product.product_stores || product.product_stores.length === 0)
        return 0;
    return product.product_stores.reduce(
        (sum, ps) => sum + (ps.quantity ?? 0),
        0,
    );
}
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        dismissable-mask
        :style="{ width: '50rem' }"
        :breakpoints="{ '1280px': '90vw', '640px': '100vw' }"
        :closable="false"
        :draggable="false"
        @update:visible="emit('update:visible', $event)"
    >
        <template #header>
            <div
                class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <!-- Navigation controls -->
                <div class="order-last flex items-center gap-2 sm:order-first">
                    <!-- Back button (search mode or when can go back) -->
                    <Button
                        v-if="currentMode === 'search' || canGoBack"
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        text
                        rounded
                        size="small"
                        @click="emit('back')"
                        v-tooltip.bottom="'Go Back'"
                    />

                    <!-- Prev/Next buttons (list or selection mode) -->
                    <template
                        v-if="
                            currentMode === 'list' ||
                            currentMode === 'selection'
                        "
                    >
                        <Button
                            icon="pi pi-chevron-left"
                            severity="secondary"
                            text
                            rounded
                            size="small"
                            :disabled="!canGoPrev || loading"
                            @click="emit('prev')"
                            v-tooltip.bottom="'Previous'"
                        />
                        <span
                            v-if="positionText"
                            class="text-sm whitespace-nowrap text-muted-foreground"
                        >
                            {{ positionText }}
                        </span>
                        <Button
                            icon="pi pi-chevron-right"
                            severity="secondary"
                            text
                            rounded
                            size="small"
                            :disabled="!canGoNext || loading"
                            @click="emit('next')"
                            v-tooltip.bottom="'Next'"
                        />
                    </template>
                </div>

                <!-- Search autocomplete + close button (first on mobile) -->
                <div
                    class="order-first flex items-center gap-2 sm:order-last sm:max-w-xs"
                >
                    <AutoComplete
                        v-model="searchQuery"
                        :suggestions="searchResults"
                        option-label="product_name"
                        placeholder="Search products..."
                        size="small"
                        fluid
                        :loading="searchLoading"
                        class="flex-1"
                        @complete="onSearch"
                        @item-select="onSelect($event.value)"
                    >
                        <template #option="{ option }">
                            <div class="flex items-center gap-2">
                                <img
                                    v-if="option.image_url"
                                    :src="option.image_url"
                                    :alt="option.product_name"
                                    class="h-6 w-6 rounded object-cover"
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials(option.product_name)"
                                    shape="square"
                                    class="!h-6 !w-6 rounded bg-primary/10 !text-xs text-primary"
                                />
                                <div class="flex min-w-0 flex-1 flex-col">
                                    <span
                                        class="truncate text-sm font-medium"
                                        >{{ option.product_name }}</span
                                    >
                                    <span class="text-xs text-muted-foreground">
                                        {{ option.product_number }}
                                        <template v-if="option.brand_name">
                                            &middot;
                                            {{ option.brand_name }}</template
                                        >
                                    </span>
                                </div>
                            </div>
                        </template>
                    </AutoComplete>
                    <Button
                        icon="pi pi-times"
                        severity="secondary"
                        text
                        rounded
                        size="small"
                        @click="emit('update:visible', false)"
                        v-tooltip.bottom="'Close'"
                    />
                </div>
            </div>
        </template>

        <!-- Content - Fixed height for consistent button positioning -->
        <div class="h-[400px] overflow-y-auto">
            <!-- Loading skeleton -->
            <div v-if="loading" class="flex flex-col gap-4">
                <!-- Header skeleton -->
                <div
                    class="flex flex-col items-center gap-4 sm:flex-row sm:items-start"
                >
                    <Skeleton
                        width="5rem"
                        height="5rem"
                        border-radius="0.5rem"
                    />
                    <div
                        class="flex flex-1 flex-col gap-2 text-center sm:text-left"
                    >
                        <Skeleton width="60%" height="1.5rem" />
                        <div
                            class="flex flex-wrap items-center justify-center gap-2 sm:justify-start"
                        >
                            <Skeleton
                                width="5rem"
                                height="1.25rem"
                                border-radius="1rem"
                            />
                            <Skeleton
                                width="4rem"
                                height="1.25rem"
                                border-radius="1rem"
                            />
                            <Skeleton
                                width="3.5rem"
                                height="1.25rem"
                                border-radius="1rem"
                            />
                        </div>
                        <Skeleton width="40%" height="1rem" />
                    </div>
                </div>

                <Divider class="!my-2" />

                <!-- Classification grid skeleton -->
                <div class="grid gap-3 sm:grid-cols-2">
                    <div v-for="i in 4" :key="i" class="flex flex-col gap-1">
                        <Skeleton width="4rem" height="0.75rem" />
                        <Skeleton width="70%" height="1rem" />
                    </div>
                </div>

                <Divider class="!my-2" />

                <!-- Prices skeleton -->
                <div>
                    <Skeleton width="6rem" height="1rem" class="mb-2" />
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="i in 2"
                            :key="i"
                            class="rounded-lg border border-border p-2"
                        >
                            <Skeleton width="50%" height="1rem" class="mb-2" />
                            <Skeleton
                                width="100%"
                                height="0.875rem"
                                class="mb-1"
                            />
                            <Skeleton width="100%" height="0.875rem" />
                        </div>
                    </div>
                </div>

                <Divider class="!my-2" />

                <!-- Store summary skeleton -->
                <div
                    class="flex items-center justify-between rounded-lg border border-border bg-muted/20 p-3"
                >
                    <div class="flex items-center gap-2">
                        <Skeleton shape="circle" size="1.25rem" />
                        <Skeleton width="10rem" height="1rem" />
                    </div>
                    <Skeleton width="5rem" height="1rem" />
                </div>
            </div>

            <!-- Product details -->
            <div v-else-if="product" class="flex flex-col gap-4">
                <!-- Product Header -->
                <div
                    class="flex flex-col items-center gap-4 sm:flex-row sm:items-start"
                >
                    <Image
                        v-if="product.image_url"
                        :src="product.image_url"
                        :alt="product.product_name"
                        image-class="!h-20 !w-20 rounded-lg object-cover cursor-pointer"
                        :pt="{
                            root: { class: 'rounded-lg overflow-hidden' },
                            previewMask: { class: 'rounded-lg' },
                        }"
                        preview
                    />
                    <Avatar
                        v-else
                        :label="getInitials(product.product_name)"
                        shape="square"
                        class="!h-20 !w-20 rounded-lg bg-primary/10 text-2xl text-primary"
                    />
                    <div class="flex flex-col gap-1 text-center sm:text-left">
                        <h2 class="text-lg font-semibold">
                            {{ product.product_name }}
                        </h2>
                        <div
                            class="flex flex-wrap items-center justify-center gap-2 sm:justify-start"
                        >
                            <Tag
                                :value="product.product_number"
                                severity="secondary"
                                class="!text-xs"
                            />
                            <Tag
                                v-if="product.barcode"
                                :value="product.barcode"
                                severity="info"
                                class="!text-xs"
                            />
                            <Tag
                                :value="
                                    product.is_active ? 'Active' : 'Inactive'
                                "
                                :severity="
                                    product.is_active ? 'success' : 'danger'
                                "
                                class="!text-xs"
                            />
                        </div>
                        <p
                            v-if="product.brand_name"
                            class="text-sm text-muted-foreground"
                        >
                            {{ product.brand_name }}
                        </p>
                    </div>
                </div>

                <Divider class="!my-2" />

                <!-- Classification Grid -->
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs text-muted-foreground">Brand</span>
                        <span class="text-sm">{{
                            product.brand_name ?? '-'
                        }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs text-muted-foreground"
                            >Supplier</span
                        >
                        <span class="text-sm">{{
                            product.supplier_name ?? '-'
                        }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs text-muted-foreground"
                            >Category</span
                        >
                        <span class="text-sm">{{
                            product.category_name ?? '-'
                        }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs text-muted-foreground"
                            >Subcategory</span
                        >
                        <span class="text-sm">{{
                            product.subcategory_name ?? '-'
                        }}</span>
                    </div>
                </div>

                <!-- Base Prices -->
                <template v-if="product.prices && product.prices.length > 0">
                    <Divider class="!my-2" />
                    <div>
                        <h3 class="mb-2 text-sm font-medium">Base Prices</h3>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="price in product.prices"
                                :key="price.id"
                                class="rounded-lg border border-border p-2"
                            >
                                <div class="mb-1 flex items-center gap-1.5">
                                    <span class="text-sm font-medium">{{
                                        price.currency?.code
                                    }}</span>
                                    <span class="text-xs text-muted-foreground"
                                        >({{ price.currency?.name }})</span
                                    >
                                </div>
                                <div class="flex flex-col gap-0.5 text-sm">
                                    <div
                                        v-if="canViewCostPrice"
                                        class="flex justify-between"
                                    >
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >Cost</span
                                        >
                                        <span class="text-xs">{{
                                            formatPrice(
                                                price.cost_price,
                                                price.currency?.symbol,
                                            )
                                        }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >Unit</span
                                        >
                                        <span class="text-sm font-medium">{{
                                            formatPrice(
                                                price.unit_price,
                                                price.currency?.symbol,
                                            )
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Store Summary -->
                <template
                    v-if="
                        product.product_stores &&
                        product.product_stores.length > 0
                    "
                >
                    <Divider class="!my-2" />
                    <div
                        class="flex items-center justify-between rounded-lg border border-border bg-muted/20 p-3"
                    >
                        <div class="flex items-center gap-2">
                            <i class="pi pi-shop text-muted-foreground" />
                            <span class="text-sm">
                                Assigned to
                                <strong>{{
                                    product.product_stores.length
                                }}</strong>
                                store{{
                                    product.product_stores.length === 1
                                        ? ''
                                        : 's'
                                }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-muted-foreground"
                                >Total Qty:</span
                            >
                            <span class="font-medium">{{
                                getTotalStoreQuantity(product)
                            }}</span>
                        </div>
                    </div>
                </template>

                <!-- Tags -->
                <template v-if="product.tags && product.tags.length > 0">
                    <Divider class="!my-2" />
                    <div>
                        <h3 class="mb-2 text-sm font-medium">Tags</h3>
                        <div class="flex flex-wrap gap-1.5">
                            <Tag
                                v-for="tag in product.tags"
                                :key="tag"
                                :value="tag"
                                severity="secondary"
                                class="!text-xs"
                            />
                        </div>
                    </div>
                </template>
            </div>

            <!-- No product state -->
            <div
                v-else
                class="flex h-full items-center justify-center text-muted-foreground"
            >
                Product not found
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button
                    label="View Full Details"
                    icon="pi pi-external-link"
                    severity="secondary"
                    size="small"
                    :disabled="loading || !product"
                    @click="navigateToView"
                />
                <Button
                    v-if="canEdit"
                    label="Edit"
                    icon="pi pi-pencil"
                    size="small"
                    :disabled="loading || !product"
                    @click="navigateToEdit"
                />
            </div>
        </template>
    </Dialog>
</template>
