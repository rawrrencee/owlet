<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Quotation, TaxMode } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Editor from 'primevue/editor';
import Image from 'primevue/image';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Panel from 'primevue/panel';
import RadioButton from 'primevue/radiobutton';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import { computed, nextTick, ref, watch } from 'vue';

interface ProductSearchResult {
    id: number;
    product_name: string;
    product_number: string;
    variant_name: string | null;
    barcode: string | null;
    image_url: string | null;
    prices: Array<{ currency_id: number; unit_price: string }>;
}

interface CustomerSearchResult {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    email: string | null;
    phone: string | null;
    discount_percentage: string | null;
}

interface LineItem {
    product_id: number;
    product_name: string;
    product_number: string;
    variant_name: string | null;
    image_url: string | null;
    currency_id: number;
    unit_price: number;
    quantity: number;
    sort_order: number;
    offer_id: number | null;
    offer_name: string | null;
    offer_discount_type: string | null;
    offer_discount_amount: number | null;
    offer_is_combinable: boolean | null;
    customer_discount_percentage: number | null;
}

interface Props {
    quotation?: Quotation | null;
    companies: Array<{ id: number; company_name: string }>;
    currencies: Array<{ id: number; code: string; name: string; symbol: string }>;
    paymentModes: Array<{ id: number; name: string; code: string | null }>;
    stores: Array<{ id: number; store_name: string; store_code: string; company_id: number; tax_percentage: string | null }>;
}

const props = withDefaults(defineProps<Props>(), {
    quotation: null,
});

const isEditing = computed(() => !!props.quotation);
const pageTitle = computed(() =>
    isEditing.value ? 'Edit Quotation' : 'Create Quotation',
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Quotations', href: '/quotations' },
    { title: isEditing.value ? 'Edit' : 'Create' },
];

// Build initial items from existing quotation
function buildInitialItems(): LineItem[] {
    if (!props.quotation?.items) return [];
    return props.quotation.items.map((item, index) => ({
        product_id: item.product_id,
        product_name: item.product?.product_name ?? '',
        product_number: item.product?.product_number ?? '',
        variant_name: item.product?.variant_name ?? null,
        image_url: item.product?.image_url ?? null,
        currency_id: item.currency_id,
        unit_price: parseFloat(item.unit_price),
        quantity: item.quantity,
        sort_order: item.sort_order ?? index,
        offer_id: item.offer_id,
        offer_name: item.offer_name,
        offer_discount_type: item.offer_discount_type,
        offer_discount_amount: item.offer_discount_amount ? parseFloat(item.offer_discount_amount) : null,
        offer_is_combinable: item.offer_is_combinable,
        customer_discount_percentage: item.customer_discount_percentage ? parseFloat(item.customer_discount_percentage) : null,
    }));
}

const form = useForm({
    company_id: props.quotation?.company_id ?? null as number | null,
    customer_id: props.quotation?.customer_id ?? null as number | null,
    show_company_logo: props.quotation?.show_company_logo ?? true,
    show_company_address: props.quotation?.show_company_address ?? true,
    show_company_uen: props.quotation?.show_company_uen ?? true,
    tax_mode: (props.quotation?.tax_mode ?? 'none') as TaxMode,
    tax_store_id: props.quotation?.tax_store_id ?? null as number | null,
    tax_percentage: props.quotation?.tax_percentage ? parseFloat(props.quotation.tax_percentage) : null as number | null,
    tax_inclusive: props.quotation?.tax_inclusive ?? false,
    terms_and_conditions: props.quotation?.terms_and_conditions ?? '',
    internal_notes: props.quotation?.internal_notes ?? '',
    external_notes: props.quotation?.external_notes ?? '',
    payment_terms: props.quotation?.payment_terms ?? '',
    validity_date: props.quotation?.validity_date ? new Date(props.quotation.validity_date) : null as Date | null,
    customer_discount_percentage: props.quotation?.customer_discount_percentage ? parseFloat(props.quotation.customer_discount_percentage) : null as number | null,
    items: buildInitialItems() as LineItem[],
    payment_mode_ids: props.quotation?.payment_modes?.map((pm) => pm.id) ?? [] as number[],
});

// Company-specific stores
const companyStores = computed(() =>
    props.stores.filter((s) => s.company_id === form.company_id),
);

const companyOptions = computed(() =>
    props.companies.map((c) => ({ label: c.company_name, value: c.id })),
);

const taxStoreOptions = computed(() =>
    companyStores.value.map((s) => ({
        label: `${s.store_name} (${s.store_code})`,
        value: s.id,
    })),
);

const currencyOptions = computed(() =>
    props.currencies.map((c) => ({ label: `${c.code} (${c.symbol})`, value: c.id })),
);

const paymentModeOptions = computed(() =>
    props.paymentModes.map((pm) => ({ label: pm.name, value: pm.id })),
);

// Default currency (first available)
const defaultCurrencyId = computed(() => props.currencies[0]?.id ?? null);

// Customer search
const selectedCustomer = ref<CustomerSearchResult | null>(
    props.quotation?.customer
        ? {
              id: props.quotation.customer.id,
              first_name: props.quotation.customer.first_name,
              last_name: props.quotation.customer.last_name,
              full_name: props.quotation.customer.full_name,
              email: props.quotation.customer.email ?? null,
              phone: props.quotation.customer.phone ?? null,
              discount_percentage: props.quotation.customer.discount_percentage ?? null,
          }
        : null,
);
const customerSearchQuery = ref('');
const customerSearchResults = ref<CustomerSearchResult[]>([]);
let customerSearchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(customerSearchQuery, (val) => {
    if (customerSearchTimeout) clearTimeout(customerSearchTimeout);
    if (!val || val.length < 2) {
        customerSearchResults.value = [];
        return;
    }
    customerSearchTimeout = setTimeout(() => searchCustomers(val), 300);
});

async function searchCustomers(query: string) {
    try {
        const response = await fetch(`/quotations/search-customers?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) return;
        customerSearchResults.value = await response.json();
    } catch {
        customerSearchResults.value = [];
    }
}

function selectCustomer(customer: CustomerSearchResult) {
    selectedCustomer.value = customer;
    form.customer_id = customer.id;
    form.customer_discount_percentage = customer.discount_percentage ? parseFloat(customer.discount_percentage) : null;
    customerSearchQuery.value = '';
    customerSearchResults.value = [];
    // Update customer discount on all items
    form.items.forEach((item) => {
        item.customer_discount_percentage = form.customer_discount_percentage;
    });
}

function clearCustomer() {
    selectedCustomer.value = null;
    form.customer_id = null;
    form.customer_discount_percentage = null;
    form.items.forEach((item) => {
        item.customer_discount_percentage = null;
    });
}

// Product search
const productSearchQuery = ref('');
const productSearchResults = ref<ProductSearchResult[]>([]);
const productSearchLoading = ref(false);
let productSearchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(productSearchQuery, (val) => {
    if (productSearchTimeout) clearTimeout(productSearchTimeout);
    if (!val || val.length < 2) {
        productSearchResults.value = [];
        return;
    }
    productSearchTimeout = setTimeout(() => searchProducts(val), 300);
});

async function searchProducts(query: string) {
    productSearchLoading.value = true;
    try {
        const response = await fetch(`/quotations/search-products?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) return;
        productSearchResults.value = await response.json();
    } catch {
        productSearchResults.value = [];
    } finally {
        productSearchLoading.value = false;
    }
}

async function addProduct(product: ProductSearchResult) {
    // Check if product already exists - increment quantity instead of adding duplicate
    const existingIndex = form.items.findIndex((item) => item.product_id === product.id);
    if (existingIndex !== -1) {
        form.items[existingIndex].quantity += 1;
        productSearchQuery.value = '';
        productSearchResults.value = [];

        // Wait for reactivity to flush, then re-resolve offer with updated quantity
        await nextTick();
        if (form.company_id) {
            await resolveOfferForItem(existingIndex);
        }
        return;
    }

    // Default to the product's first available priced currency, fallback to global default
    const firstProductPrice = product.prices[0];
    const currencyId = firstProductPrice?.currency_id ?? defaultCurrencyId.value;
    if (!currencyId) return;

    const unitPrice = firstProductPrice ? parseFloat(firstProductPrice.unit_price) : 0;

    const newItem: LineItem = {
        product_id: product.id,
        product_name: product.product_name,
        product_number: product.product_number,
        variant_name: product.variant_name,
        image_url: product.image_url,
        currency_id: currencyId,
        unit_price: unitPrice,
        quantity: 1,
        sort_order: form.items.length,
        offer_id: null,
        offer_name: null,
        offer_discount_type: null,
        offer_discount_amount: null,
        offer_is_combinable: null,
        customer_discount_percentage: form.customer_discount_percentage,
    };

    form.items.push(newItem);
    productSearchQuery.value = '';
    productSearchResults.value = [];

    // Resolve offer for the new item
    if (form.company_id) {
        await resolveOfferForItem(form.items.length - 1);
    }
}

function removeItem(index: number) {
    form.items.splice(index, 1);
    updateSortOrders();
}

// Selected items for batch operations
const selectedItems = ref<LineItem[]>([]);

function removeSelected() {
    const selectedIds = selectedItems.value.map((s) => s.product_id + '-' + s.sort_order);
    form.items = form.items.filter(
        (item) => !selectedIds.includes(item.product_id + '-' + item.sort_order),
    );
    selectedItems.value = [];
    updateSortOrders();
}

function updateSortOrders() {
    form.items.forEach((item, index) => {
        item.sort_order = index;
    });
}

function onRowReorder(event: any) {
    form.items = event.value;
    updateSortOrders();
}

// Offer resolution with confirmation dialogs
interface PendingOffer {
    index: number;
    productName: string;
    offer_id: number;
    offer_name: string;
    discount_type: string;
    discount_percentage: number | null;
    discount_amount: number;
    is_combinable: boolean;
    selected: boolean;
}

// Single offer confirmation
const showSingleOfferDialog = ref(false);
const pendingOffer = ref<PendingOffer | null>(null);

// Batch offer confirmation
const showBatchOfferDialog = ref(false);
const pendingBatchOffers = ref<PendingOffer[]>([]);

async function fetchOfferForItem(index: number): Promise<any> {
    const item = form.items[index];
    if (!item.product_id || !form.company_id || !item.currency_id || !item.unit_price) return null;

    try {
        const response = await fetch('/quotations/resolve-offer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                product_id: item.product_id,
                company_id: form.company_id,
                currency_id: item.currency_id,
                unit_price: item.unit_price,
                quantity: item.quantity,
            }),
        });
        if (!response.ok) return null;
        return await response.json();
    } catch {
        return null;
    }
}

function applyOfferToItem(index: number, offer: any) {
    const item = form.items[index];
    if (offer) {
        item.offer_id = offer.offer_id;
        item.offer_name = offer.offer_name;
        item.offer_discount_type = offer.discount_type;
        item.offer_discount_amount = parseFloat(offer.discount_amount);
        item.offer_is_combinable = offer.is_combinable;
    } else {
        item.offer_id = null;
        item.offer_name = null;
        item.offer_discount_type = null;
        item.offer_discount_amount = null;
        item.offer_is_combinable = null;
    }
}

function clearOfferFromItem(index: number) {
    applyOfferToItem(index, null);
}

function parseOfferResponse(index: number, productName: string, offer: any): PendingOffer | null {
    if (!offer.offer_id) return null;
    const amount = parseFloat(offer.discount_amount);
    return {
        index,
        productName,
        offer_id: offer.offer_id,
        offer_name: offer.offer_name,
        discount_type: offer.discount_type,
        discount_percentage: offer.discount_percentage != null ? parseFloat(offer.discount_percentage) : null,
        discount_amount: Number.isFinite(amount) ? amount : 0,
        is_combinable: offer.is_combinable,
        selected: true,
    };
}

async function resolveOfferForItem(index: number) {
    const item = form.items[index];
    const offer = await fetchOfferForItem(index);

    if (!offer) {
        // No offer found - clear silently
        clearOfferFromItem(index);
        return;
    }

    // If same offer is already applied, keep it silently
    if (item.offer_id === offer.offer_id) return;

    const parsed = parseOfferResponse(index, item.product_name, offer);
    if (!parsed) {
        clearOfferFromItem(index);
        return;
    }

    // Show confirmation dialog
    pendingOffer.value = parsed;
    showSingleOfferDialog.value = true;
}

function applySingleOffer() {
    if (!pendingOffer.value) return;
    applyOfferToItem(pendingOffer.value.index, {
        offer_id: pendingOffer.value.offer_id,
        offer_name: pendingOffer.value.offer_name,
        discount_type: pendingOffer.value.discount_type,
        discount_amount: pendingOffer.value.discount_amount,
        is_combinable: pendingOffer.value.is_combinable,
    });
    pendingOffer.value = null;
    showSingleOfferDialog.value = false;
}

function skipSingleOffer() {
    if (pendingOffer.value) {
        clearOfferFromItem(pendingOffer.value.index);
    }
    pendingOffer.value = null;
    showSingleOfferDialog.value = false;
}

function formatOfferDiscount(offer: PendingOffer): string {
    const currency = props.currencies.find((c) => c.id === form.items[offer.index]?.currency_id);
    const symbol = currency?.symbol ?? '';
    const amount = Number.isFinite(offer.discount_amount) ? offer.discount_amount.toFixed(2) : '0.00';

    if (offer.discount_type === 'percentage' && offer.discount_percentage) {
        return `${offer.discount_percentage}% off (${symbol}${amount}/unit)`;
    }
    return `${symbol}${amount} off per unit`;
}

function applyBatchOffers() {
    for (const offer of pendingBatchOffers.value) {
        if (offer.selected) {
            applyOfferToItem(offer.index, {
                offer_id: offer.offer_id,
                offer_name: offer.offer_name,
                discount_type: offer.discount_type,
                discount_amount: offer.discount_amount,
                is_combinable: offer.is_combinable,
            });
        } else {
            clearOfferFromItem(offer.index);
        }
    }
    pendingBatchOffers.value = [];
    showBatchOfferDialog.value = false;
}

function skipBatchOffers() {
    for (const offer of pendingBatchOffers.value) {
        clearOfferFromItem(offer.index);
    }
    pendingBatchOffers.value = [];
    showBatchOfferDialog.value = false;
}

// When company changes, re-resolve all offers and clear tax store if not in company
watch(() => form.company_id, async () => {
    if (form.tax_store_id) {
        const storeStillValid = companyStores.value.some((s) => s.id === form.tax_store_id);
        if (!storeStillValid) {
            form.tax_store_id = null;
        }
    }

    if (form.items.length === 0 || !form.company_id) return;

    // Re-resolve offers for all items concurrently
    const results = await Promise.all(
        form.items.map((_, index) => fetchOfferForItem(index)),
    );

    const newOffers: PendingOffer[] = [];
    results.forEach((offer, index) => {
        const item = form.items[index];
        if (!offer) {
            // No offer - clear silently
            clearOfferFromItem(index);
            return;
        }
        // If same offer already applied, keep silently
        if (item.offer_id === offer.offer_id) return;

        const parsed = parseOfferResponse(index, item.product_name, offer);
        if (!parsed) {
            clearOfferFromItem(index);
            return;
        }
        newOffers.push(parsed);
    });

    if (newOffers.length === 0) return;

    if (newOffers.length === 1) {
        // Single item - use single dialog
        pendingOffer.value = newOffers[0];
        showSingleOfferDialog.value = true;
    } else {
        // Multiple items - use batch dialog
        pendingBatchOffers.value = newOffers;
        showBatchOfferDialog.value = true;
    }
});

// When tax store changes, update tax percentage from store
watch(() => form.tax_store_id, (newVal) => {
    if (form.tax_mode === 'store' && newVal) {
        const store = props.stores.find((s) => s.id === newVal);
        if (store?.tax_percentage) {
            form.tax_percentage = parseFloat(store.tax_percentage);
        }
    }
});

// Computations for line items
function computeLineSubtotal(item: LineItem): number {
    return item.unit_price * item.quantity;
}

function computeLineDiscount(item: LineItem): number {
    const subtotal = computeLineSubtotal(item);
    const offerDiscount = item.offer_discount_amount ? item.offer_discount_amount * item.quantity : 0;
    const custDiscountPct = item.customer_discount_percentage ?? 0;
    const custDiscount = subtotal * (custDiscountPct / 100);

    if (offerDiscount && custDiscount) {
        if (item.offer_is_combinable) {
            return Math.min(offerDiscount + custDiscount, subtotal);
        }
        return Math.min(Math.max(offerDiscount, custDiscount), subtotal);
    }
    return Math.min(offerDiscount + custDiscount, subtotal);
}

function computeLineTotal(item: LineItem): number {
    return computeLineSubtotal(item) - computeLineDiscount(item);
}

// Multi-currency totals
interface DisplayTotal {
    currency_id: number;
    currency_code: string;
    currency_symbol: string;
    subtotal: number;
    discount: number;
    tax: number;
    total: number;
}

const currencyTotals = computed<DisplayTotal[]>(() => {
    const groups: Record<number, DisplayTotal> = {};
    for (const item of form.items) {
        const cid = item.currency_id;
        if (!groups[cid]) {
            const curr = props.currencies.find((c) => c.id === cid);
            groups[cid] = {
                currency_id: cid,
                currency_code: curr?.code ?? '',
                currency_symbol: curr?.symbol ?? '',
                subtotal: 0,
                discount: 0,
                tax: 0,
                total: 0,
            };
        }
        groups[cid].subtotal += computeLineSubtotal(item);
        groups[cid].discount += computeLineDiscount(item);
    }

    for (const g of Object.values(groups)) {
        const afterDiscount = g.subtotal - g.discount;
        if (form.tax_mode !== 'none' && form.tax_percentage) {
            const taxRate = form.tax_percentage / 100;
            if (form.tax_inclusive) {
                g.tax = afterDiscount - afterDiscount / (1 + taxRate);
                g.total = afterDiscount;
            } else {
                g.tax = afterDiscount * taxRate;
                g.total = afterDiscount + g.tax;
            }
        } else {
            g.total = afterDiscount;
        }
    }

    return Object.values(groups);
});

function formatCurrency(value: number, symbol: string): string {
    return `${symbol}${value.toFixed(2)}`;
}

// Currency change handler
function onCurrencyChange(index: number, product: LineItem) {
    // Find product price for new currency
    // We don't have product prices cached per-item, so just re-resolve the offer
    if (form.company_id) {
        resolveOfferForItem(index);
    }
}

// Submit
function submit() {
    // Update sort orders
    updateSortOrders();

    if (isEditing.value) {
        form.put(`/quotations/${props.quotation!.id}`);
    } else {
        form.post('/quotations');
    }
}

function cancel() {
    router.visit('/quotations');
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-4">
                <Button
                    icon="pi pi-arrow-left"
                    severity="secondary"
                    text
                    size="small"
                    @click="cancel"
                />
                <h1 class="heading-lg">{{ pageTitle }}</h1>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <!-- Company Selection -->
                <div class="rounded-lg border border-border p-4">
                    <h3 class="mb-3 font-medium">Company</h3>
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium">Company *</label>
                            <Select
                                v-model="form.company_id"
                                :options="companyOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Select company"
                                size="small"
                                filter
                                :invalid="!!form.errors.company_id"
                                class="w-full sm:w-80"
                            />
                            <small v-if="form.errors.company_id" class="text-red-500">{{ form.errors.company_id }}</small>
                        </div>
                        <div v-if="form.company_id" class="flex flex-wrap items-center gap-4">
                            <label class="flex items-center gap-2 text-sm">
                                <Checkbox v-model="form.show_company_logo" :binary="true" />
                                Show Logo
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <Checkbox v-model="form.show_company_address" :binary="true" />
                                Show Address
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <Checkbox v-model="form.show_company_uen" :binary="true" />
                                Show UEN
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Customer Selection -->
                <div class="rounded-lg border border-border p-4">
                    <h3 class="mb-3 font-medium">Customer</h3>
                    <div v-if="selectedCustomer" class="flex items-center justify-between rounded-md bg-muted/50 px-3 py-2">
                        <div>
                            <div class="font-medium">{{ selectedCustomer.full_name }}</div>
                            <div class="text-xs text-muted-foreground">
                                <span v-if="selectedCustomer.email">{{ selectedCustomer.email }}</span>
                                <span v-if="selectedCustomer.email && selectedCustomer.phone"> &middot; </span>
                                <span v-if="selectedCustomer.phone">{{ selectedCustomer.phone }}</span>
                            </div>
                            <div v-if="selectedCustomer.discount_percentage" class="mt-1">
                                <Tag :value="`${selectedCustomer.discount_percentage}% discount`" severity="success" />
                            </div>
                        </div>
                        <Button icon="pi pi-times" severity="secondary" text rounded size="small" @click="clearCustomer" />
                    </div>
                    <div v-else class="relative">
                        <InputText
                            v-model="customerSearchQuery"
                            placeholder="Search customers by name, email, or phone..."
                            size="small"
                            fluid
                        />
                        <div
                            v-if="customerSearchResults.length > 0"
                            class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-border bg-background shadow-lg"
                        >
                            <div
                                v-for="customer in customerSearchResults"
                                :key="customer.id"
                                class="flex cursor-pointer items-center justify-between px-3 py-2 hover:bg-muted"
                                @click="selectCustomer(customer)"
                            >
                                <div>
                                    <div class="font-medium">{{ customer.full_name }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        <span v-if="customer.email">{{ customer.email }}</span>
                                        <span v-if="customer.phone"> &middot; {{ customer.phone }}</span>
                                    </div>
                                </div>
                                <Tag v-if="customer.discount_percentage" :value="`${customer.discount_percentage}%`" severity="success" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Line Items Section -->
                <div class="rounded-lg border border-border p-4">
                    <h3 class="mb-3 font-medium">Line Items *</h3>

                    <!-- Product Search -->
                    <div class="relative mb-3">
                        <InputText
                            v-model="productSearchQuery"
                            placeholder="Search products by name, number, or barcode..."
                            size="small"
                            fluid
                        />
                        <div
                            v-if="productSearchResults.length > 0"
                            class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-border bg-background shadow-lg"
                        >
                            <div
                                v-for="product in productSearchResults"
                                :key="product.id"
                                class="flex cursor-pointer items-center gap-2 px-3 py-2 hover:bg-muted"
                                @click="addProduct(product)"
                            >
                                <img v-if="product.image_url" :src="product.image_url" class="h-8 w-8 flex-shrink-0 rounded object-cover" alt="" />
                                <Avatar v-else :label="product.product_name?.charAt(0)" shape="square" class="!h-8 !w-8 flex-shrink-0 rounded bg-primary/10 text-primary" />
                                <div class="flex-1">
                                    <div class="font-medium">{{ product.product_name }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ product.product_number }}
                                        <span v-if="product.variant_name"> - {{ product.variant_name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <small v-if="form.errors.items" class="mb-2 block text-red-500">{{ form.errors.items }}</small>

                    <!-- Batch Actions -->
                    <div v-if="selectedItems.length > 0" class="mb-2 flex items-center gap-2 rounded-md bg-muted/50 px-3 py-2">
                        <span class="text-sm">{{ selectedItems.length }} selected</span>
                        <Button
                            label="Remove Selected"
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            size="small"
                            @click="removeSelected"
                        />
                    </div>

                    <!-- Items Table -->
                    <div v-if="form.items.length > 0">
                        <DataTable
                            v-model:selection="selectedItems"
                            :value="form.items"
                            dataKey="sort_order"
                            size="small"
                            striped-rows
                            @row-reorder="onRowReorder"
                            class="overflow-hidden rounded-lg border border-border"
                        >
                            <Column rowReorder headerStyle="width: 3rem" :reorderableColumn="false" />
                            <Column selectionMode="multiple" class="w-10" />
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
                            <Column header="Currency" class="hidden sm:table-cell w-32">
                                <template #body="{ data, index }">
                                    <Select
                                        v-model="data.currency_id"
                                        :options="currencyOptions"
                                        option-label="label"
                                        option-value="value"
                                        size="small"
                                        filter
                                        fluid
                                        @change="onCurrencyChange(index, data)"
                                    />
                                </template>
                            </Column>
                            <Column header="Unit Price" class="w-32">
                                <template #body="{ data, index }">
                                    <InputNumber
                                        v-model="data.unit_price"
                                        :min="0"
                                        :min-fraction-digits="2"
                                        :max-fraction-digits="2"
                                        size="small"
                                        fluid
                                        @update:modelValue="resolveOfferForItem(index)"
                                    />
                                </template>
                            </Column>
                            <Column header="Qty" class="w-20">
                                <template #body="{ data, index }">
                                    <InputNumber
                                        v-model="data.quantity"
                                        :min="1"
                                        size="small"
                                        fluid
                                        @update:modelValue="resolveOfferForItem(index)"
                                    />
                                </template>
                            </Column>
                            <Column header="Offer" class="hidden md:table-cell">
                                <template #body="{ data }">
                                    <Tag v-if="data.offer_name" :value="data.offer_name" severity="success" />
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </template>
                            </Column>
                            <Column header="Total" class="w-28 text-right">
                                <template #body="{ data }">
                                    <div class="text-right">
                                        <div class="font-medium">
                                            {{ formatCurrency(computeLineTotal(data), currencies.find(c => c.id === data.currency_id)?.symbol ?? '') }}
                                        </div>
                                        <div v-if="computeLineDiscount(data) > 0" class="text-xs text-green-600">
                                            -{{ formatCurrency(computeLineDiscount(data), currencies.find(c => c.id === data.currency_id)?.symbol ?? '') }}
                                        </div>
                                    </div>
                                </template>
                            </Column>
                            <Column header="" class="w-12">
                                <template #body="{ index }">
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        size="small"
                                        @click="removeItem(index)"
                                    />
                                </template>
                            </Column>
                        </DataTable>

                    </div>
                    <div v-else class="rounded-md border border-dashed border-border p-8 text-center text-muted-foreground">
                        Search and add products above to start building the quotation.
                    </div>
                </div>

                <!-- Multi-Currency Totals -->
                <div v-if="currencyTotals.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="total in currencyTotals"
                        :key="total.currency_id"
                        class="rounded-lg border border-border p-4"
                    >
                        <h4 class="mb-2 font-medium">{{ total.currency_code }}</h4>
                        <div class="flex flex-col gap-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Subtotal</span>
                                <span>{{ formatCurrency(total.subtotal, total.currency_symbol) }}</span>
                            </div>
                            <div v-if="total.discount > 0" class="flex justify-between text-green-600">
                                <span>Discount</span>
                                <span>-{{ formatCurrency(total.discount, total.currency_symbol) }}</span>
                            </div>
                            <div v-if="total.tax > 0" class="flex justify-between">
                                <span class="text-muted-foreground">Tax{{ form.tax_inclusive ? ' (incl.)' : '' }}</span>
                                <span>{{ formatCurrency(total.tax, total.currency_symbol) }}</span>
                            </div>
                            <Divider class="!my-1" />
                            <div class="flex justify-between text-base font-semibold">
                                <span>Total</span>
                                <span>{{ formatCurrency(total.total, total.currency_symbol) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tax Configuration -->
                <div class="rounded-lg border border-border p-4">
                    <h3 class="mb-3 font-medium">Tax Configuration</h3>
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-wrap items-center gap-4">
                            <label class="flex items-center gap-2 text-sm">
                                <RadioButton v-model="form.tax_mode" value="none" />
                                No Tax
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <RadioButton v-model="form.tax_mode" value="store" />
                                From Store
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <RadioButton v-model="form.tax_mode" value="manual" />
                                Manual
                            </label>
                        </div>

                        <div v-if="form.tax_mode === 'store'" class="flex flex-col gap-2">
                            <label class="text-sm font-medium">Tax Store</label>
                            <Select
                                v-model="form.tax_store_id"
                                :options="taxStoreOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Select store for tax rate"
                                size="small"
                                :invalid="!!form.errors.tax_store_id"
                                class="w-full sm:w-80"
                            />
                            <small v-if="form.tax_store_id && form.tax_percentage" class="text-muted-foreground">
                                Tax rate: {{ form.tax_percentage }}%
                            </small>
                            <small v-if="form.errors.tax_store_id" class="text-red-500">{{ form.errors.tax_store_id }}</small>
                        </div>

                        <div v-if="form.tax_mode === 'manual'" class="flex flex-col gap-2">
                            <label class="text-sm font-medium">Tax Percentage</label>
                            <InputNumber
                                v-model="form.tax_percentage"
                                :min="0"
                                :max="100"
                                suffix="%"
                                :min-fraction-digits="2"
                                :max-fraction-digits="2"
                                size="small"
                                :invalid="!!form.errors.tax_percentage"
                                class="w-full sm:w-40"
                            />
                            <small v-if="form.errors.tax_percentage" class="text-red-500">{{ form.errors.tax_percentage }}</small>
                        </div>

                        <div v-if="form.tax_mode !== 'none'" class="flex items-center gap-2">
                            <ToggleSwitch v-model="form.tax_inclusive" />
                            <span class="text-sm">Tax inclusive (prices already include tax)</span>
                        </div>
                    </div>
                </div>

                <!-- Optional Sections -->
                <Panel header="Terms & Conditions" toggleable collapsed class="border-border">
                    <Editor
                        v-model="form.terms_and_conditions"
                        editor-style="height: 150px"
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
                                <button class="ql-clean" v-tooltip.bottom="'Clear Formatting'"></button>
                            </span>
                        </template>
                    </Editor>
                </Panel>

                <Panel header="External Notes (visible to customer)" toggleable collapsed class="border-border">
                    <Textarea
                        v-model="form.external_notes"
                        rows="3"
                        placeholder="Notes visible to the customer..."
                        size="small"
                        fluid
                    />
                </Panel>

                <Panel toggleable collapsed class="border-border">
                    <template #header>
                        <div class="flex items-center gap-2">
                            <span>Internal Notes</span>
                            <Tag value="Staff Only" severity="warn" />
                        </div>
                    </template>
                    <Textarea
                        v-model="form.internal_notes"
                        rows="3"
                        placeholder="Internal notes (staff only)..."
                        size="small"
                        fluid
                    />
                </Panel>

                <Panel header="Payment Terms" toggleable collapsed class="border-border">
                    <Textarea
                        v-model="form.payment_terms"
                        rows="3"
                        placeholder="Payment terms and conditions..."
                        size="small"
                        fluid
                    />
                </Panel>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border border-border p-4">
                        <h3 class="mb-3 font-medium">Validity Date</h3>
                        <DatePicker
                            v-model="form.validity_date"
                            date-format="dd/mm/yy"
                            placeholder="Select expiry date"
                            size="small"
                            :min-date="new Date()"
                            show-icon
                            fluid
                        />
                    </div>

                    <div class="rounded-lg border border-border p-4">
                        <h3 class="mb-3 font-medium">Accepted Payment Modes</h3>
                        <MultiSelect
                            v-model="form.payment_mode_ids"
                            :options="paymentModeOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select payment modes"
                            size="small"
                            display="chip"
                            fluid
                        />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
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
                        :label="isEditing ? 'Save Changes' : 'Save Draft'"
                        icon="pi pi-save"
                        size="small"
                        :loading="form.processing"
                        :disabled="form.items.length === 0"
                    />
                </div>
            </form>
        </div>

        <!-- Single Offer Confirmation Dialog -->
        <Dialog
            v-model:visible="showSingleOfferDialog"
            header="Offer Available"
            modal
            :closable="false"
            :style="{ width: '400px' }"
        >
            <div v-if="pendingOffer" class="flex flex-col gap-3">
                <p class="text-sm">
                    An offer is available for <span class="font-medium">{{ pendingOffer.productName }}</span>:
                </p>
                <div class="rounded-md bg-green-50 p-3">
                    <div class="flex items-center gap-2">
                        <Tag :value="pendingOffer.offer_name" severity="success" />
                        <span class="text-sm text-green-700">{{ formatOfferDiscount(pendingOffer) }}</span>
                    </div>
                </div>
                <p class="text-sm text-muted-foreground">Would you like to apply this offer?</p>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Skip" severity="secondary" size="small" @click="skipSingleOffer" />
                    <Button label="Apply Offer" icon="pi pi-check" size="small" @click="applySingleOffer" />
                </div>
            </template>
        </Dialog>

        <!-- Batch Offer Confirmation Dialog -->
        <Dialog
            v-model:visible="showBatchOfferDialog"
            header="Offers Available"
            modal
            :closable="false"
            :style="{ width: '500px' }"
        >
            <div class="flex flex-col gap-3">
                <p class="text-sm text-muted-foreground">
                    The following offers were found for your line items. Select which ones to apply:
                </p>
                <div class="flex flex-col gap-2">
                    <label
                        v-for="offer in pendingBatchOffers"
                        :key="offer.index"
                        class="flex items-center gap-3 rounded-md border border-border p-3 hover:bg-muted/50 cursor-pointer"
                    >
                        <Checkbox v-model="offer.selected" :binary="true" />
                        <div class="flex-1">
                            <div class="text-sm font-medium">{{ offer.productName }}</div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <Tag :value="offer.offer_name" severity="success" />
                                <span class="text-xs text-green-700">{{ formatOfferDiscount(offer) }}</span>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Skip All" severity="secondary" size="small" @click="skipBatchOffers" />
                    <Button label="Apply Selected" icon="pi pi-check" size="small" @click="applyBatchOffers" />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
