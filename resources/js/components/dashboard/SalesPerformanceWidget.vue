<script setup lang="ts">
import type { SalesPerformanceData } from '@/types';
import Card from 'primevue/card';
import { computed } from 'vue';

const props = defineProps<{
    data: SalesPerformanceData;
}>();

const salesTrend = computed(() => {
    if (props.data.prev_total_sales === 0) return null;
    const pct =
        ((props.data.total_sales - props.data.prev_total_sales) /
            props.data.prev_total_sales) *
        100;
    return {
        value: Math.abs(pct).toFixed(1),
        positive: pct >= 0,
    };
});

function formatCurrency(value: number): string {
    return value.toLocaleString('en', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}
</script>

<template>
    <Card class="h-full">
        <template #title>
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold">Sales</span>
                <span class="text-xs text-muted-foreground">
                    {{ data.period_label }}
                </span>
            </div>
        </template>
        <template #content>
            <div class="space-y-3">
                <div>
                    <div class="text-2xl font-bold">
                        {{ data.currency_symbol
                        }}{{ formatCurrency(data.total_sales) }}
                    </div>
                    <div
                        v-if="salesTrend"
                        class="mt-0.5 text-xs"
                        :class="
                            salesTrend.positive
                                ? 'text-green-600'
                                : 'text-red-600'
                        "
                    >
                        {{ salesTrend.positive ? '+' : '-'
                        }}{{ salesTrend.value }}% vs prev period
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-xs text-muted-foreground">
                            Transactions
                        </div>
                        <div class="text-lg font-semibold">
                            {{ data.transaction_count }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">
                            Avg Order
                        </div>
                        <div class="text-lg font-semibold">
                            {{ data.currency_symbol
                            }}{{ formatCurrency(data.avg_order_value) }}
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Card>
</template>
