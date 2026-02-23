<script setup lang="ts">
import type { QuickLink } from '@/types';
import { Link } from '@inertiajs/vue3';
import Card from 'primevue/card';
import {
    BarChart3,
    CalendarDays,
    ClipboardCheck,
    Clock,
    Package,
    ShoppingBag,
    UsersRound,
} from 'lucide-vue-next';
import type { Component } from 'vue';

defineProps<{
    data: QuickLink[];
}>();

const iconMap: Record<string, Component> = {
    Clock,
    CalendarDays,
    ShoppingBag,
    Package,
    ClipboardCheck,
    BarChart3,
    UsersRound,
};
</script>

<template>
    <Card class="h-full">
        <template #title>
            <span class="text-sm font-semibold">Quick Links</span>
        </template>
        <template #content>
            <div class="grid grid-cols-2 gap-2">
                <Link
                    v-for="link in data"
                    :key="link.href"
                    :href="link.href"
                    class="flex items-center gap-2 rounded-md border border-stone-200 px-3 py-2 text-sm hover:bg-stone-100 dark:border-stone-700 dark:hover:bg-stone-800"
                >
                    <component
                        :is="iconMap[link.icon]"
                        v-if="iconMap[link.icon]"
                        class="h-4 w-4 shrink-0 text-muted-foreground"
                    />
                    <span class="truncate">{{ link.label }}</span>
                </Link>
            </div>
        </template>
    </Card>
</template>
