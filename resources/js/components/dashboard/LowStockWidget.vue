<script setup lang="ts">
import type { LowStockItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import Card from 'primevue/card';

defineProps<{
    data: LowStockItem[];
}>();

function stockClass(quantity: number): string {
    if (quantity === 0) return 'text-red-600 dark:text-red-400';
    if (quantity <= 2) return 'text-orange-600 dark:text-orange-400';
    return 'text-amber-600 dark:text-amber-400';
}
</script>

<template>
    <Card class="h-full">
        <template #title>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <AlertTriangle class="h-4 w-4 text-amber-500" />
                    <span class="text-sm font-semibold">Low Stock Alerts</span>
                </div>
                <Link
                    href="/products"
                    class="text-xs text-muted-foreground transition-colors hover:text-primary"
                >
                    View products
                </Link>
            </div>
        </template>
        <template #content>
            <div v-if="data.length === 0" class="py-4 text-center text-sm text-muted-foreground">
                No low stock items
            </div>
            <div v-else class="space-y-1.5">
                <div
                    v-for="item in data"
                    :key="`${item.product_id}-${item.store_name}`"
                    class="flex items-center gap-3 rounded-lg px-2 py-1.5 transition-colors hover:bg-stone-100 dark:hover:bg-stone-800"
                >
                    <div class="flex-1 min-w-0">
                        <p class="truncate text-sm font-medium">{{ item.product_name }}</p>
                        <p class="truncate text-xs text-muted-foreground">{{ item.store_name }}</p>
                    </div>
                    <div
                        class="flex-shrink-0 text-sm font-bold"
                        :class="stockClass(item.quantity)"
                    >
                        {{ item.quantity }}
                    </div>
                </div>
            </div>
        </template>
    </Card>
</template>
