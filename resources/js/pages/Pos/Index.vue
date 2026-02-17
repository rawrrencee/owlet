<script setup lang="ts">
import OfferBrowseDialog from '@/components/offers/OfferBrowseDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Transaction } from '@/types';
import { Head } from '@inertiajs/vue3';
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref } from 'vue';
import Cart from './components/Cart.vue';
import CompletionReceipt from './components/CompletionReceipt.vue';
import CustomerSelector from './components/CustomerSelector.vue';
import PaymentDialog from './components/PaymentDialog.vue';
import ProductGrid from './components/ProductGrid.vue';
import ProductSearch from './components/ProductSearch.vue';
import ProductVariantSelector from './components/ProductVariantSelector.vue';
import RefundDialog from './components/RefundDialog.vue';
import StoreSelector from './components/StoreSelector.vue';
import SuspendedDrawer from './components/SuspendedDrawer.vue';
import TotalsPanel from './components/TotalsPanel.vue';

interface StoreOption {
    id: number;
    store_name: string;
    store_code: string;
    tax_percentage: string | null;
    include_tax: boolean;
    can_void: boolean;
    can_apply_discounts: boolean;
    currencies: Array<{ id: number; code: string; symbol: string; name: string }>;
}

interface PaymentModeOption {
    id: number;
    name: string;
    code: string | null;
}

interface Props {
    stores: StoreOption[];
    paymentModes: PaymentModeOption[];
    employeeId: number;
}

const props = defineProps<Props>();
const toast = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Point of Sale' },
];

// State
const selectedStore = ref<StoreOption | null>(null);
const selectedCurrency = ref<{ id: number; code: string; symbol: string; name: string } | null>(null);
const currentTransaction = ref<Transaction | null>(null);
const loading = ref(false);
const showPaymentDialog = ref(false);
const showSuspendedDrawer = ref(false);
const showCompletionReceipt = ref(false);
const completedTransaction = ref<Transaction | null>(null);
const showVariantSelector = ref(false);
const variantProduct = ref<any>(null);
const showRefundDialog = ref(false);
const showOfferBrowseDialog = ref(false);
const mobileView = ref<'products' | 'cart'>('products');
const favouriteIds = ref<Set<number>>(new Set());
const productScrollContainer = ref<HTMLElement | null>(null);

const hasItems = computed(() => (currentTransaction.value?.items?.length ?? 0) > 0);
const isDraft = computed(() => currentTransaction.value?.status === 'draft');
const isCompleted = computed(() => currentTransaction.value?.status === 'completed');
const itemCount = computed(() => currentTransaction.value?.items?.length ?? 0);

// Store selection with localStorage persistence
function onStoreSelected(store: StoreOption) {
    selectedStore.value = store;
    localStorage.setItem('pos_selected_store_id', store.id.toString());
    if (store.currencies.length > 0) {
        // Try to restore saved currency, otherwise use first
        const savedCurrencyId = localStorage.getItem('pos_selected_currency_id');
        const savedCurrency = savedCurrencyId
            ? store.currencies.find(c => c.id === parseInt(savedCurrencyId))
            : null;
        selectedCurrency.value = savedCurrency ?? store.currencies[0];
        localStorage.setItem('pos_selected_currency_id', selectedCurrency.value.id.toString());
    }
    currentTransaction.value = null;
    loadFavourites();
}

function onCurrencyChanged(currency: { id: number; code: string; symbol: string; name: string }) {
    selectedCurrency.value = currency;
    localStorage.setItem('pos_selected_currency_id', currency.id.toString());
}

function onBackToStoreSelection() {
    selectedStore.value = null;
    currentTransaction.value = null;
    localStorage.removeItem('pos_selected_store_id');
    localStorage.removeItem('pos_selected_currency_id');
}

// Restore saved store on mount
onMounted(() => {
    const savedStoreId = localStorage.getItem('pos_selected_store_id');
    if (savedStoreId) {
        const store = props.stores.find(s => s.id === parseInt(savedStoreId));
        if (store) {
            onStoreSelected(store);
        }
    }
});

// API helpers
async function apiCall(url: string, options: RequestInit = {}): Promise<any> {
    loading.value = true;
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
                ),
                ...options.headers,
            },
        });
        if (!response.ok) {
            const error = await response.json().catch(() => ({ message: 'Request failed' }));
            throw new Error(error.message || `HTTP ${response.status}`);
        }
        return await response.json();
    } finally {
        loading.value = false;
    }
}

// Transaction lifecycle
async function createTransaction() {
    if (!selectedStore.value || !selectedCurrency.value) return;
    const data = await apiCall('/pos/transactions', {
        method: 'POST',
        body: JSON.stringify({
            store_id: selectedStore.value.id,
            currency_id: selectedCurrency.value.id,
        }),
    });
    currentTransaction.value = data;
}

async function ensureTransaction(): Promise<Transaction> {
    if (!currentTransaction.value) {
        await createTransaction();
    }
    if (!currentTransaction.value) {
        throw new Error('Failed to create transaction. Please try again.');
    }
    return currentTransaction.value;
}

// Product actions
async function onProductSelected(product: any) {
    if (product.variants && product.variants.length > 0) {
        variantProduct.value = product;
        showVariantSelector.value = true;
        return;
    }
    await addProductToCart(product.id);
}

async function onVariantSelected(productId: number) {
    showVariantSelector.value = false;
    variantProduct.value = null;
    await addProductToCart(productId);
}

async function addProductToCart(productId: number) {
    try {
        const txn = await ensureTransaction();
        const data = await apiCall(`/pos/transactions/${txn.id}/items`, {
            method: 'POST',
            body: JSON.stringify({ product_id: productId, quantity: 1 }),
        });
        currentTransaction.value = data;
        // Auto-switch to cart on mobile when first item is added
        if (data.items?.length === 1) {
            mobileView.value = 'cart';
        }
    } catch (err: any) {
        toast.add({
            severity: 'error',
            summary: 'Failed to add item',
            detail: err.message || 'An error occurred while adding the item to cart.',
            life: 4000,
        });
    }
}

async function onUpdateItem(itemId: number, updates: { quantity?: number; unit_price?: string }) {
    if (!currentTransaction.value) return;
    try {
        const data = await apiCall(`/pos/transactions/${currentTransaction.value.id}/items/${itemId}`, {
            method: 'PUT',
            body: JSON.stringify(updates),
        });
        currentTransaction.value = data;
    } catch (err: any) {
        toast.add({
            severity: 'error',
            summary: 'Failed to update item',
            detail: err.message,
            life: 4000,
        });
    }
}

async function onRemoveItem(itemId: number) {
    if (!currentTransaction.value) return;
    try {
        const data = await apiCall(`/pos/transactions/${currentTransaction.value.id}/items/${itemId}`, {
            method: 'DELETE',
        });
        currentTransaction.value = data;
    } catch (err: any) {
        toast.add({
            severity: 'error',
            summary: 'Failed to remove item',
            detail: err.message,
            life: 4000,
        });
    }
}

// Customer
async function onCustomerSelected(customerId: number | null) {
    if (!currentTransaction.value) return;
    const data = await apiCall(`/pos/transactions/${currentTransaction.value.id}/customer`, {
        method: 'PUT',
        body: JSON.stringify({ customer_id: customerId }),
    });
    currentTransaction.value = data;
}

// Payments
async function onAddPayment(paymentModeId: number, amount: string, paymentData?: any) {
    if (!currentTransaction.value) return;
    const data = await apiCall(`/pos/transactions/${currentTransaction.value.id}/payments`, {
        method: 'POST',
        body: JSON.stringify({
            payment_mode_id: paymentModeId,
            amount,
            payment_data: paymentData,
        }),
    });
    currentTransaction.value = data;
}

async function onRemovePayment(paymentId: number) {
    if (!currentTransaction.value) return;
    const data = await apiCall(`/pos/transactions/${currentTransaction.value.id}/payments/${paymentId}`, {
        method: 'DELETE',
    });
    currentTransaction.value = data;
}

// Complete
async function onComplete() {
    if (!currentTransaction.value) return;
    const data = await apiCall(`/pos/transactions/${currentTransaction.value.id}/complete`, {
        method: 'POST',
    });
    completedTransaction.value = data;
    showCompletionReceipt.value = true;
    showPaymentDialog.value = false;
    currentTransaction.value = null;
}

// Suspend & Resume
async function onSuspend() {
    if (!currentTransaction.value) return;
    await apiCall(`/pos/transactions/${currentTransaction.value.id}/suspend`, {
        method: 'POST',
    });
    currentTransaction.value = null;
    mobileView.value = 'products';
}

async function onResume(transaction: Transaction) {
    // If there's an active draft, suspend it first
    if (currentTransaction.value && currentTransaction.value.status === 'draft') {
        await apiCall(`/pos/transactions/${currentTransaction.value.id}/suspend`, {
            method: 'POST',
        });
    }
    const data = await apiCall(`/pos/transactions/${transaction.id}/resume`, {
        method: 'POST',
    });
    currentTransaction.value = data;
    showSuspendedDrawer.value = false;
}

// Void
async function onVoid(reason?: string) {
    if (!currentTransaction.value) return;
    try {
        await apiCall(`/pos/transactions/${currentTransaction.value.id}/void`, {
            method: 'POST',
            body: JSON.stringify({ reason }),
        });
        currentTransaction.value = null;
        mobileView.value = 'products';
    } catch (err: any) {
        toast.add({
            severity: 'error',
            summary: 'Failed to void transaction',
            detail: err.message,
            life: 4000,
        });
    }
}

// Refund
async function onRefund(items: Array<{ item_id: number; quantity: number; reason?: string }>) {
    if (!currentTransaction.value) return;
    try {
        const data = await apiCall(`/pos/transactions/${currentTransaction.value.id}/refund`, {
            method: 'POST',
            body: JSON.stringify({ items }),
        });
        currentTransaction.value = data;
    } catch (err: any) {
        toast.add({
            severity: 'error',
            summary: 'Failed to process refund',
            detail: err.message,
            life: 4000,
        });
    }
}

// New transaction
function onNewTransaction() {
    showCompletionReceipt.value = false;
    completedTransaction.value = null;
    currentTransaction.value = null;
    mobileView.value = 'products';
}

// Favourites
async function loadFavourites() {
    try {
        const data = await apiCall('/pos/favourites');
        favouriteIds.value = new Set(data as number[]);
    } catch {
        // ignore
    }
}

async function onToggleFavourite(productId: number) {
    try {
        const data = await apiCall(`/pos/favourites/${productId}`, {
            method: 'POST',
        });
        if (data.favourited) {
            favouriteIds.value = new Set([...favouriteIds.value, productId]);
        } else {
            const newSet = new Set(favouriteIds.value);
            newSet.delete(productId);
            favouriteIds.value = newSet;
        }
    } catch {
        // ignore
    }
}


</script>

<template>
    <Head title="Point of Sale" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col h-[calc(100vh-4rem)]">
            <!-- No store selected -->
            <template v-if="!selectedStore">
                <div class="flex-1 flex items-center justify-center p-4">
                    <StoreSelector :stores="stores" @select="onStoreSelected" />
                </div>
            </template>

            <!-- POS Interface -->
            <template v-else>
                <!-- Top bar -->
                <div class="flex items-center gap-2 px-3 py-2 border-b bg-surface-0 dark:bg-surface-900 flex-shrink-0">
                    <Button
                        icon="pi pi-arrow-left"
                        text
                        size="small"
                        @click="onBackToStoreSelection"
                    />
                    <span class="font-semibold text-sm">{{ selectedStore.store_name }}</span>

                    <!-- Currency toggle -->
                    <div v-if="selectedStore.currencies.length > 1" class="flex gap-1 ml-2">
                        <Button
                            v-for="curr in selectedStore.currencies"
                            :key="curr.id"
                            :label="curr.code"
                            :severity="selectedCurrency?.id === curr.id ? undefined : 'secondary'"
                            size="small"
                            :outlined="selectedCurrency?.id !== curr.id"
                            @click="onCurrencyChanged(curr)"
                        />
                    </div>
                    <span v-else class="text-sm text-muted-color ml-2">{{ selectedCurrency?.code }}</span>

                    <div class="flex-1" />

                    <!-- Transaction number -->
                    <span v-if="currentTransaction" class="text-xs text-muted-color hidden sm:inline">
                        {{ currentTransaction.transaction_number }}
                    </span>

                    <!-- Customer selector -->
                    <CustomerSelector
                        :customer="currentTransaction?.customer ?? null"
                        :disabled="!currentTransaction"
                        @select="onCustomerSelected"
                        @clear="onCustomerSelected(null)"
                    />

                    <!-- Suspended transactions -->
                    <Button
                        icon="pi pi-pause"
                        text
                        size="small"
                        v-tooltip.bottom="'Suspended'"
                        @click="showSuspendedDrawer = true"
                    />

                    <!-- Suspend current -->
                    <Button
                        v-if="currentTransaction && isDraft && hasItems"
                        label="Park"
                        icon="pi pi-pause"
                        severity="warn"
                        size="small"
                        @click="onSuspend"
                    />

                    <!-- Offers -->
                    <Button
                        icon="pi pi-tag"
                        text
                        size="small"
                        v-tooltip.bottom="'Offers'"
                        @click="showOfferBrowseDialog = true"
                    />

                    <!-- Refund (only for completed, requires void permission) -->
                    <Button
                        v-if="isCompleted && selectedStore.can_void"
                        icon="pi pi-replay"
                        text
                        size="small"
                        severity="warn"
                        v-tooltip.bottom="'Refund'"
                        @click="showRefundDialog = true"
                    />

                    <!-- Void (requires void permission) -->
                    <Button
                        v-if="currentTransaction && (isDraft || isCompleted) && selectedStore.can_void"
                        icon="pi pi-trash"
                        severity="danger"
                        text
                        size="small"
                        v-tooltip.bottom="'Void'"
                        @click="onVoid()"
                    />
                </div>

                <!-- Main content -->
                <div class="flex flex-1 overflow-hidden">
                    <!-- Left: Products -->
                    <div
                        class="flex-1 flex flex-col overflow-hidden md:w-[60%]"
                        :class="{ 'hidden md:flex': mobileView === 'cart' }"
                    >
                        <div class="p-3 pb-0 flex-shrink-0">
                            <ProductSearch
                                :store-id="selectedStore.id"
                                :currency-id="selectedCurrency?.id ?? 0"
                                @select="onProductSelected"
                            />
                        </div>
                        <div ref="productScrollContainer" class="flex-1 overflow-y-auto p-3">
                            <ProductGrid
                                :store-id="selectedStore.id"
                                :currency-id="selectedCurrency?.id ?? 0"
                                :currency-symbol="selectedCurrency?.symbol ?? '$'"
                                :favourite-ids="favouriteIds"
                                :scroll-container="productScrollContainer"
                                @select="onProductSelected"
                                @toggle-favourite="onToggleFavourite"
                            />
                        </div>
                    </div>

                    <!-- Right: Cart -->
                    <div
                        class="flex flex-col border-l bg-surface-50 dark:bg-surface-800 overflow-hidden"
                        :class="{
                            'w-full md:w-[40%]': mobileView === 'cart',
                            'hidden md:flex md:w-[40%]': mobileView === 'products',
                        }"
                    >
                        <div class="flex-1 overflow-y-auto">
                            <Cart
                                :transaction="currentTransaction"
                                :currency-symbol="selectedCurrency?.symbol ?? '$'"
                                @update-item="onUpdateItem"
                                @remove-item="onRemoveItem"
                                @back="mobileView = 'products'"
                            />
                        </div>
                        <div class="flex-shrink-0 border-t">
                            <TotalsPanel
                                :transaction="currentTransaction"
                                :currency-symbol="selectedCurrency?.symbol ?? '$'"
                            />
                            <div class="p-3 flex gap-2">
                                <!-- Back to products on mobile -->
                                <Button
                                    v-if="currentTransaction && hasItems"
                                    icon="pi pi-arrow-left"
                                    severity="secondary"
                                    size="small"
                                    class="md:hidden"
                                    @click="mobileView = 'products'"
                                />
                                <Button
                                    label="Pay"
                                    icon="pi pi-wallet"
                                    class="flex-1"
                                    size="small"
                                    :disabled="!currentTransaction || !hasItems"
                                    @click="showPaymentDialog = true"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Floating cart button on mobile (when viewing products and cart has items) -->
                <div
                    v-if="mobileView === 'products' && hasItems"
                    class="fixed bottom-6 right-6 md:hidden z-50"
                >
                    <Button
                        icon="pi pi-shopping-cart"
                        rounded
                        size="large"
                        @click="mobileView = 'cart'"
                    >
                        <template #icon>
                            <i class="pi pi-shopping-cart text-lg" />
                            <Badge
                                :value="itemCount"
                                severity="danger"
                                class="!absolute -top-1 -right-1 !text-[10px]"
                            />
                        </template>
                    </Button>
                </div>
            </template>
        </div>

        <!-- Variant selector dialog -->
        <ProductVariantSelector
            v-model:visible="showVariantSelector"
            :product="variantProduct"
            :store-id="selectedStore?.id ?? 0"
            :currency-id="selectedCurrency?.id ?? 0"
            :currency-symbol="selectedCurrency?.symbol ?? '$'"
            @select="onVariantSelected"
        />

        <!-- Payment dialog -->
        <PaymentDialog
            v-model:visible="showPaymentDialog"
            :transaction="currentTransaction"
            :payment-modes="paymentModes"
            :currency-symbol="selectedCurrency?.symbol ?? '$'"
            @add-payment="onAddPayment"
            @remove-payment="onRemovePayment"
            @complete="onComplete"
        />

        <!-- Suspended drawer -->
        <SuspendedDrawer
            v-model:visible="showSuspendedDrawer"
            :store-id="selectedStore?.id ?? 0"
            :currency-symbol="selectedCurrency?.symbol ?? '$'"
            @resume="onResume"
        />

        <!-- Completion receipt -->
        <CompletionReceipt
            v-model:visible="showCompletionReceipt"
            :transaction="completedTransaction"
            :currency-symbol="selectedCurrency?.symbol ?? '$'"
            @new-transaction="onNewTransaction"
        />

        <!-- Refund dialog -->
        <RefundDialog
            v-model:visible="showRefundDialog"
            :transaction="currentTransaction"
            :currency-symbol="selectedCurrency?.symbol ?? '$'"
            @refund="onRefund($event); showRefundDialog = false"
        />

        <!-- Offer browse dialog -->
        <OfferBrowseDialog
            v-model:visible="showOfferBrowseDialog"
            :store-id="selectedStore?.id ?? null"
            fetch-url="/pos/offers"
        />
    </AppLayout>
</template>
