<script setup lang="ts">
import type { PendingOrdersData } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Package, Truck } from 'lucide-vue-next';
import Card from 'primevue/card';

defineProps<{
    data: PendingOrdersData;
}>();
</script>

<template>
    <Card class="h-full">
        <template #title>
            <span class="text-sm font-semibold">Pending Orders</span>
        </template>
        <template #content>
            <div class="grid grid-cols-2 gap-4">
                <!-- Delivery Orders -->
                <Link href="/delivery-orders" class="block">
                    <div
                        class="flex flex-col gap-2 rounded-xl border border-stone-200 p-3 transition-colors hover:border-blue-300 hover:bg-blue-50 dark:border-stone-700 dark:hover:border-blue-700 dark:hover:bg-blue-950/30"
                    >
                        <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                            <Truck class="h-4 w-4" />
                            <span class="text-xs font-medium">Delivery</span>
                        </div>
                        <div class="text-2xl font-bold">{{ data.delivery_orders }}</div>
                        <div class="text-xs text-muted-foreground">Submitted</div>
                    </div>
                </Link>

                <!-- Purchase Orders -->
                <Link href="/purchase-orders" class="block">
                    <div
                        class="flex flex-col gap-2 rounded-xl border border-stone-200 p-3 transition-colors hover:border-purple-300 hover:bg-purple-50 dark:border-stone-700 dark:hover:border-purple-700 dark:hover:bg-purple-950/30"
                    >
                        <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400">
                            <Package class="h-4 w-4" />
                            <span class="text-xs font-medium">Purchase</span>
                        </div>
                        <div class="text-2xl font-bold">{{ data.purchase_orders }}</div>
                        <div class="text-xs text-muted-foreground">Submitted</div>
                    </div>
                </Link>
            </div>
        </template>
    </Card>
</template>
