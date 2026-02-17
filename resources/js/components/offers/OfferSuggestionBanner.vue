<script setup lang="ts">
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';
import { ref } from 'vue';

export interface OfferSuggestion {
    offer_id: number;
    offer_name: string;
    discount_type: string;
    discount_percentage: number | null;
    discount_amount: number;
    is_combinable: boolean;
    product_name?: string;
    index?: number;
}

defineProps<{
    suggestions: OfferSuggestion[];
}>();

const emit = defineEmits<{
    apply: [offers: OfferSuggestion[]];
    dismiss: [offerId: number, permanent: boolean];
    'dismiss-all': [permanent: boolean];
}>();

const dontShowAgain = ref(false);

function formatDiscount(offer: OfferSuggestion): string {
    if (offer.discount_type === 'percentage' && offer.discount_percentage) {
        return `${offer.discount_percentage}% off`;
    }
    return `$${offer.discount_amount.toFixed(2)} off`;
}

function dismissAll() {
    emit('dismiss-all', dontShowAgain.value);
}
</script>

<template>
    <Message v-if="suggestions.length > 0" severity="success" :closable="false" class="mb-2">
        <div class="flex flex-col gap-2 w-full">
            <div class="flex items-center gap-2">
                <i class="pi pi-tag text-green-600" />
                <span class="text-sm font-semibold">
                    {{ suggestions.length === 1 ? 'Offer Available!' : `${suggestions.length} Offers Available!` }}
                </span>
            </div>

            <div v-for="offer in suggestions" :key="offer.offer_id" class="flex items-center justify-between text-sm">
                <div>
                    <span class="font-medium">{{ offer.offer_name }}</span>
                    <span class="text-muted-color ml-1">({{ formatDiscount(offer) }})</span>
                    <span v-if="offer.product_name" class="text-xs text-muted-color ml-1">
                        for {{ offer.product_name }}
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-between mt-1">
                <div class="flex items-center gap-2">
                    <Checkbox v-model="dontShowAgain" binary input-id="dont-show" />
                    <label for="dont-show" class="text-xs text-muted-color cursor-pointer">Don't show again this session</label>
                </div>
                <div class="flex gap-2">
                    <Button label="Dismiss" text size="small" severity="secondary" @click="dismissAll" />
                    <Button label="Apply" size="small" @click="$emit('apply', suggestions)" />
                </div>
            </div>
        </div>
    </Message>
</template>
