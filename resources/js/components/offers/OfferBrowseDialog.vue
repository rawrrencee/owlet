<script setup lang="ts">
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
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
    if (!val) return;
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
        <div v-if="loading" class="flex justify-center p-8">
            <ProgressSpinner style="width: 40px; height: 40px" />
        </div>

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
                        @click="emit('select', offer)"
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
