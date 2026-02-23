<script setup lang="ts">
import type { ActiveOffersData } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Percent } from 'lucide-vue-next';
import Badge from 'primevue/badge';
import Card from 'primevue/card';

defineProps<{
    data: ActiveOffersData;
}>();

const typeSeverity: Record<string, 'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast'> = {
    product: 'info',
    bundle: 'success',
    minimum_spend: 'warn',
    category: 'secondary',
    brand: 'contrast',
};
</script>

<template>
    <Card class="h-full">
        <template #title>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <Percent class="h-4 w-4 text-green-500" />
                    <span class="text-sm font-semibold">Active Offers</span>
                </div>
                <Link
                    href="/offers"
                    class="text-xs text-muted-foreground transition-colors hover:text-primary"
                >
                    View all
                </Link>
            </div>
        </template>
        <template #content>
            <div v-if="data.total === 0" class="py-4 text-center text-sm text-muted-foreground">
                No active offers
            </div>
            <div v-else class="space-y-3">
                <div class="text-2xl font-bold">
                    {{ data.total }}
                    <span class="text-base font-normal text-muted-foreground">active</span>
                </div>

                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="item in data.by_type"
                        :key="item.type"
                        class="flex items-center gap-1.5"
                    >
                        <Badge
                            :value="item.count.toString()"
                            :severity="typeSeverity[item.type] ?? 'secondary'"
                            size="small"
                        />
                        <span class="text-xs text-muted-foreground">{{ item.label }}</span>
                    </div>
                </div>
            </div>
        </template>
    </Card>
</template>
