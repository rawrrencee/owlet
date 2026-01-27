<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Clock, Coffee, Play } from 'lucide-vue-next';
import Button from 'primevue/button';
import Drawer from 'primevue/drawer';
import Tag from 'primevue/tag';
import { computed, onMounted, ref, watch } from 'vue';
import type { Timecard, TimecardStore } from '@/types/timecard';
import ClockWidget from './ClockWidget.vue';

interface PageProps {
    currentTimecard?: Timecard | null;
    isOnBreak?: boolean;
    employeeStores?: TimecardStore[];
}

const page = usePage<PageProps>();

const isOpen = ref(false);
const currentTimecard = ref<Timecard | null>(null);
const isOnBreak = ref(false);
const stores = ref<TimecardStore[]>([]);
const isLoading = ref(true);

const hasEmployee = computed(() => {
    return page.props.auth?.user?.employee_id !== null;
});

const statusBadge = computed(() => {
    if (!currentTimecard.value) return null;
    if (isOnBreak.value) {
        return { icon: Coffee, color: 'warn', label: 'Break' };
    }
    return { icon: Play, color: 'success', label: 'Working' };
});

async function fetchCurrentState() {
    if (!hasEmployee.value) return;

    try {
        const response = await fetch('/timecards/current', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            const data = await response.json();
            currentTimecard.value = data.timecard;
            isOnBreak.value = data.is_on_break;
            if (data.stores) {
                stores.value = data.stores;
            }
        }
    } catch (error) {
        console.error('Failed to fetch timecard state:', error);
    } finally {
        isLoading.value = false;
    }
}

// Watch for page prop changes (after clock in/out)
watch(
    () => page.props.currentTimecard,
    (newVal) => {
        if (newVal !== undefined) {
            currentTimecard.value = newVal;
        }
    },
    { immediate: true }
);

watch(
    () => page.props.isOnBreak,
    (newVal) => {
        if (newVal !== undefined) {
            isOnBreak.value = newVal;
        }
    },
    { immediate: true }
);

watch(
    () => page.props.employeeStores,
    (newVal) => {
        if (newVal) {
            stores.value = newVal;
        }
    },
    { immediate: true }
);

onMounted(() => {
    fetchCurrentState();
});
</script>

<template>
    <!-- Only show if user has an employee record -->
    <div v-if="hasEmployee" class="floating-clock-panel">
        <!-- Floating Action Button Container -->
        <div class="fixed bottom-6 right-6 z-[100] flex flex-col items-center gap-2">
            <!-- Status Badge above FAB -->
            <Tag
                v-if="statusBadge"
                :severity="statusBadge.color"
                :value="statusBadge.label"
                class="shadow-md"
            />

            <!-- Floating Action Button -->
            <Button
                class="!h-14 !w-14 !rounded-full shadow-xl transition-transform hover:scale-105"
                :severity="statusBadge ? statusBadge.color : 'secondary'"
                @click="isOpen = true"
                aria-label="Open clock panel"
            >
                <template #icon>
                    <component
                        :is="statusBadge ? statusBadge.icon : Clock"
                        class="h-6 w-6"
                    />
                </template>
            </Button>
        </div>

        <!-- Drawer Panel -->
        <Drawer
            v-model:visible="isOpen"
            position="right"
            :modal="true"
            class="!w-full sm:!w-[400px]"
        >
            <template #header>
                <div class="flex w-full items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                            <Clock class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold">Time Clock</h2>
                            <p class="text-xs text-muted-foreground">Track your work hours</p>
                        </div>
                    </div>
                </div>
            </template>

            <div class="flex flex-col gap-4 p-4">
                <ClockWidget
                    v-if="!isLoading"
                    :current-timecard="currentTimecard"
                    :is-on-break="isOnBreak"
                    :stores="stores"
                />
                <div v-else class="flex items-center justify-center py-12">
                    <i class="pi pi-spin pi-spinner text-3xl text-primary"></i>
                </div>
            </div>
        </Drawer>
    </div>
</template>
