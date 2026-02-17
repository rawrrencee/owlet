<script setup lang="ts">
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import ProgressSpinner from 'primevue/progressspinner';
import Tag from 'primevue/tag';
import { computed, ref, watch } from 'vue';

interface OfferEntry {
    id: number;
    offer_name: string;
    type: string;
    discount_type: string;
    is_combinable: boolean;
    status: string;
    amounts?: Array<{ currency_id: number; discount_percentage: string | null; discount_amount: string | null }>;
}

interface GroupedOffers {
    [key: string]: OfferEntry[];
}

const props = defineProps<{
    visible: boolean;
    storeId?: number | null;
    companyId?: number | null;
    fetchUrl: string;
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    select: [offer: OfferEntry];
}>();

const groupedOffers = ref<GroupedOffers>({});
const loading = ref(false);

// Detail view state
const selectedOffer = ref<any>(null);
const detailLoading = ref(false);

const allOffers = computed(() => {
    const result: Array<{ type: string; typeLabel: string; offers: OfferEntry[] }> = [];
    const typeLabels: Record<string, string> = {
        product: 'Product Offers',
        category: 'Category Offers',
        brand: 'Brand Offers',
        bundle: 'Bundle Offers',
        minimum_spend: 'Minimum Spend Offers',
    };
    for (const [type, offers] of Object.entries(groupedOffers.value)) {
        if (Array.isArray(offers) && offers.length > 0) {
            result.push({ type, typeLabel: typeLabels[type] ?? type, offers });
        }
    }
    return result;
});

const totalOffers = computed(() =>
    allOffers.value.reduce((sum, g) => sum + g.offers.length, 0),
);

watch(() => props.visible, async (val) => {
    if (!val) {
        selectedOffer.value = null;
        return;
    }
    if (!props.storeId && !props.companyId) return;

    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (props.storeId) params.set('store_id', String(props.storeId));
        if (props.companyId) params.set('company_id', String(props.companyId));

        const response = await fetch(`${props.fetchUrl}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
                ),
            },
        });
        groupedOffers.value = await response.json();
    } catch {
        groupedOffers.value = {};
    } finally {
        loading.value = false;
    }
});

function getTypeSeverity(type: string): string {
    switch (type) {
        case 'product': return 'info';
        case 'bundle': return 'warn';
        case 'minimum_spend': return 'success';
        case 'category': return 'secondary';
        case 'brand': return 'contrast';
        default: return 'info';
    }
}

function formatDiscount(offer: OfferEntry): string {
    if (!offer.amounts || offer.amounts.length === 0) return '-';
    const firstAmount = offer.amounts[0];
    if (offer.discount_type === 'percentage' && firstAmount.discount_percentage) {
        return `${parseFloat(firstAmount.discount_percentage)}% off`;
    }
    if (firstAmount.discount_amount) {
        return `$${parseFloat(firstAmount.discount_amount).toFixed(2)} off`;
    }
    return '-';
}

async function onOfferClick(offer: OfferEntry) {
    selectedOffer.value = null;
    detailLoading.value = true;
    try {
        const response = await fetch(`${props.fetchUrl}/${offer.id}`, {
            headers: {
                'Accept': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
                ),
            },
        });
        selectedOffer.value = await response.json();
    } catch {
        selectedOffer.value = null;
    } finally {
        detailLoading.value = false;
    }
}

function backToList() {
    selectedOffer.value = null;
}

function formatDetailDiscount(offer: any): string {
    if (offer.discount_type === 'percentage') {
        return `${offer.discount_percentage}% off`;
    }
    if (offer.amounts && offer.amounts.length > 0) {
        return offer.amounts
            .filter((a: any) => a.discount_amount)
            .map((a: any) => `${a.currency?.symbol ?? ''}${Number(a.discount_amount).toFixed(2)} off`)
            .join(', ');
    }
    return '-';
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        header="Browse Offers"
        modal
        :style="{ width: '540px' }"
        :breakpoints="{ '640px': '95vw' }"
    >
        <!-- Loading state -->
        <div v-if="loading || detailLoading" class="flex justify-center p-8">
            <ProgressSpinner style="width: 40px; height: 40px" />
        </div>

        <!-- Detail view -->
        <div v-else-if="selectedOffer" class="max-h-[500px] overflow-y-auto">
            <div class="flex items-center gap-2 mb-3">
                <Button
                    icon="pi pi-arrow-left"
                    text
                    rounded
                    size="small"
                    severity="secondary"
                    @click="backToList"
                />
                <h3 class="font-semibold text-sm">{{ selectedOffer.name }}</h3>
                <Tag :value="selectedOffer.type_label" :severity="(getTypeSeverity(selectedOffer.type) as any)" class="!text-[10px]" />
            </div>

            <div class="space-y-3 text-sm">
                <!-- Core info -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <span class="text-muted-color text-xs">Discount</span>
                        <p class="font-medium">{{ formatDetailDiscount(selectedOffer) }}</p>
                    </div>
                    <div>
                        <span class="text-muted-color text-xs">Combinable</span>
                        <p class="font-medium">{{ selectedOffer.is_combinable ? 'Yes' : 'No' }}</p>
                    </div>
                    <div>
                        <span class="text-muted-color text-xs">Starts</span>
                        <p>{{ formatDate(selectedOffer.starts_at) }}</p>
                    </div>
                    <div>
                        <span class="text-muted-color text-xs">Ends</span>
                        <p>{{ formatDate(selectedOffer.ends_at) }}</p>
                    </div>
                    <div>
                        <span class="text-muted-color text-xs">Stores</span>
                        <p v-if="selectedOffer.apply_to_all_stores">All Stores</p>
                        <p v-else-if="selectedOffer.stores?.length > 0">
                            {{ selectedOffer.stores.map((s: any) => s.store_name).join(', ') }}
                        </p>
                        <p v-else>-</p>
                    </div>
                    <div v-if="selectedOffer.code">
                        <span class="text-muted-color text-xs">Code</span>
                        <p>{{ selectedOffer.code }}</p>
                    </div>
                </div>

                <p v-if="selectedOffer.description" class="text-muted-color">
                    {{ selectedOffer.description }}
                </p>

                <!-- Per-currency amounts -->
                <template v-if="selectedOffer.amounts?.length > 0">
                    <Divider />
                    <div class="text-xs font-semibold text-muted-color uppercase mb-1">
                        {{ selectedOffer.discount_type === 'fixed' ? 'Discount Amounts' : 'Max Discount Caps' }}
                    </div>
                    <div class="space-y-1">
                        <div
                            v-for="amount in selectedOffer.amounts"
                            :key="amount.id"
                            class="flex justify-between text-sm border rounded px-2 py-1"
                        >
                            <span>{{ amount.currency?.code ?? '-' }}</span>
                            <span v-if="selectedOffer.discount_type === 'fixed'">
                                {{ amount.currency?.symbol }}{{ amount.discount_amount ? Number(amount.discount_amount).toFixed(2) : '-' }}
                            </span>
                            <span v-else>
                                {{ amount.max_discount_amount ? `${amount.currency?.symbol}${Number(amount.max_discount_amount).toFixed(2)}` : 'No cap' }}
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Product offers -->
                <template v-if="selectedOffer.type === 'product' && selectedOffer.products?.length > 0">
                    <Divider />
                    <div class="text-xs font-semibold text-muted-color uppercase mb-1">Applicable Products</div>
                    <div class="space-y-1 max-h-[200px] overflow-y-auto">
                        <div
                            v-for="op in selectedOffer.products"
                            :key="op.id"
                            class="flex items-center gap-2 border rounded px-2 py-1.5"
                        >
                            <img
                                v-if="op.product?.image_url"
                                :src="op.product.image_url"
                                :alt="op.product?.product_name"
                                class="w-6 h-6 rounded object-cover flex-shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium truncate">{{ op.product?.product_name }}</div>
                                <div class="text-xs text-muted-color">
                                    {{ op.product?.product_number }}
                                    <span v-if="op.product?.variant_name"> - {{ op.product.variant_name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Bundle offers -->
                <template v-if="selectedOffer.type === 'bundle' && selectedOffer.bundles?.length > 0">
                    <Divider />
                    <div class="text-xs font-semibold text-muted-color uppercase mb-1">
                        Bundle Items
                        <Tag v-if="selectedOffer.bundle_mode_label" :value="selectedOffer.bundle_mode_label" severity="info" class="!text-[10px] ml-1" />
                    </div>
                    <div class="space-y-1 max-h-[200px] overflow-y-auto">
                        <div
                            v-for="bundle in selectedOffer.bundles"
                            :key="bundle.id"
                            class="flex items-center justify-between border rounded px-2 py-1.5"
                        >
                            <div class="min-w-0 flex-1">
                                <template v-if="bundle.product_id && bundle.product">
                                    <div class="text-sm font-medium truncate">{{ bundle.product.product_name }}</div>
                                    <div class="text-xs text-muted-color">{{ bundle.product.product_number }}</div>
                                </template>
                                <template v-else-if="bundle.category_id && bundle.category">
                                    <div class="text-sm font-medium">Any from {{ bundle.category.category_name }}</div>
                                </template>
                                <template v-else-if="bundle.subcategory_id && bundle.subcategory">
                                    <div class="text-sm font-medium">Any from {{ bundle.subcategory.category?.category_name }} &gt; {{ bundle.subcategory.subcategory_name }}</div>
                                </template>
                            </div>
                            <span class="text-xs text-muted-color ml-2">x{{ bundle.required_quantity }}</span>
                        </div>
                    </div>
                </template>

                <!-- Minimum spend -->
                <template v-if="selectedOffer.type === 'minimum_spend' && selectedOffer.minimum_spends?.length > 0">
                    <Divider />
                    <div class="text-xs font-semibold text-muted-color uppercase mb-1">Minimum Spend Thresholds</div>
                    <div class="space-y-1">
                        <div
                            v-for="ms in selectedOffer.minimum_spends"
                            :key="ms.id"
                            class="flex justify-between border rounded px-2 py-1"
                        >
                            <span>{{ ms.currency?.code ?? '-' }}</span>
                            <span>{{ ms.currency?.symbol }}{{ Number(ms.minimum_amount).toFixed(2) }}</span>
                        </div>
                    </div>
                </template>

                <!-- Category offer -->
                <template v-if="selectedOffer.type === 'category' && selectedOffer.category">
                    <Divider />
                    <div class="text-xs font-semibold text-muted-color uppercase mb-1">Category</div>
                    <div class="border rounded px-2 py-1.5 font-medium text-sm">
                        {{ selectedOffer.category.category_name }}
                    </div>
                </template>

                <!-- Brand offer -->
                <template v-if="selectedOffer.type === 'brand' && selectedOffer.brand">
                    <Divider />
                    <div class="text-xs font-semibold text-muted-color uppercase mb-1">Brand</div>
                    <div class="border rounded px-2 py-1.5 font-medium text-sm">
                        {{ selectedOffer.brand.brand_name }}
                    </div>
                </template>
            </div>
        </div>

        <!-- Offer list -->
        <div v-else-if="totalOffers === 0" class="text-center text-muted-color py-8">
            <i class="pi pi-tag text-2xl mb-2 block opacity-40" />
            <p class="text-sm">No active offers for this store.</p>
        </div>

        <div v-else class="space-y-4 max-h-[500px] overflow-y-auto">
            <div v-for="group in allOffers" :key="group.type">
                <div class="text-xs text-muted-color font-semibold uppercase mb-2">{{ group.typeLabel }}</div>
                <div class="space-y-1">
                    <div
                        v-for="offer in group.offers"
                        :key="offer.id"
                        class="rounded border p-3 hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors cursor-pointer"
                        @click="onOfferClick(offer)"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="font-medium text-sm">{{ offer.offer_name }}</div>
                                <div class="flex flex-wrap items-center gap-1 mt-1">
                                    <Tag :value="group.typeLabel.replace(' Offers', '')" :severity="(getTypeSeverity(group.type) as any)" class="!text-[10px]" />
                                    <span class="text-xs text-muted-color">{{ formatDiscount(offer) }}</span>
                                    <Tag v-if="offer.is_combinable" value="Combinable" severity="secondary" class="!text-[10px]" />
                                </div>
                            </div>
                            <Button icon="pi pi-chevron-right" text size="small" severity="secondary" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Dialog>
</template>
