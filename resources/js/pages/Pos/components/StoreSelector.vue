<script setup lang="ts">
import Card from 'primevue/card';

interface StoreOption {
    id: number;
    store_name: string;
    store_code: string;
    tax_percentage: string | null;
    include_tax: boolean;
    can_void: boolean;
    can_apply_discounts: boolean;
    currencies: Array<{ id: number; code: string; symbol: string; name: string; exchange_rate: string }>;
}

defineProps<{
    stores: StoreOption[];
}>();

const emit = defineEmits<{
    select: [store: StoreOption];
}>();
</script>

<template>
    <div class="w-full max-w-lg">
        <h2 class="text-xl font-bold mb-4 text-center">Select Store</h2>
        <div v-if="stores.length === 0" class="text-center text-muted-color">
            <p>No stores available. You need <strong>Process Sales</strong> permission for at least one store.</p>
        </div>
        <div class="grid gap-3 max-h-[60vh] overflow-y-auto">
            <Card
                v-for="store in stores"
                :key="store.id"
                class="cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors"
                @click="emit('select', store)"
            >
                <template #content>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-bold text-sm">
                            {{ store.store_code }}
                        </div>
                        <div class="flex-1">
                            <div class="font-semibold text-sm">{{ store.store_name }}</div>
                            <div class="text-xs text-muted-color">
                                {{ store.currencies.map(c => c.code).join(', ') }}
                                <span v-if="store.tax_percentage"> &middot; Tax {{ store.tax_percentage }}%</span>
                            </div>
                        </div>
                        <i class="pi pi-chevron-right text-muted-color" />
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
