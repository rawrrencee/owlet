<script setup lang="ts">
import type { WeeklyTimecardData } from '@/types';
import Card from 'primevue/card';

defineProps<{
    data: WeeklyTimecardData;
}>();
</script>

<template>
    <Card class="h-full">
        <template #title>
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold">Weekly Hours</span>
                <span class="text-sm text-muted-foreground">
                    {{ data.total_hours }}h total
                </span>
            </div>
        </template>
        <template #content>
            <div class="flex items-end gap-1.5">
                <div
                    v-for="day in data.days"
                    :key="day.date"
                    class="flex flex-1 flex-col items-center gap-1"
                >
                    <span class="text-xs text-muted-foreground">
                        {{ day.hours > 0 ? `${day.hours}h` : '' }}
                    </span>
                    <div
                        class="w-full rounded-sm transition-colors"
                        :class="[
                            day.is_today
                                ? 'bg-stone-700 dark:bg-stone-300'
                                : day.hours > 0
                                  ? 'bg-stone-400 dark:bg-stone-500'
                                  : 'bg-stone-200 dark:bg-stone-700',
                        ]"
                        :style="{
                            height: `${Math.max(day.hours > 0 ? (day.hours / 12) * 80 : 8, 8)}px`,
                        }"
                    ></div>
                    <span
                        class="text-xs"
                        :class="
                            day.is_today
                                ? 'font-semibold'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ day.day_name }}
                    </span>
                </div>
            </div>
        </template>
    </Card>
</template>
