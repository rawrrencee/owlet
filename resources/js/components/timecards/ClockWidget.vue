<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Clock, Coffee, Play } from 'lucide-vue-next';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import type { Timecard, TimecardStore } from '@/types/timecard';

interface Props {
    currentTimecard: Timecard | null;
    isOnBreak: boolean;
    stores: TimecardStore[];
    compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    compact: false,
});

const emit = defineEmits<{
    stateChanged: [];
}>();

const selectedStore = ref<number | null>(null);
const elapsedTime = ref('');
let timerInterval: ReturnType<typeof setInterval> | null = null;

const clockInForm = useForm({
    store_id: null as number | null,
});

const isProcessing = computed(() => clockInForm.processing);

const storeName = computed(() => {
    return props.currentTimecard?.store?.name ?? 'Unknown';
});

const statusText = computed(() => {
    if (!props.currentTimecard) {
        return 'Not clocked in';
    }
    if (props.isOnBreak) {
        return `On break at ${storeName.value}`;
    }
    return `Working at ${storeName.value}`;
});

const statusSeverity = computed(() => {
    if (!props.currentTimecard) return 'secondary';
    if (props.isOnBreak) return 'warn';
    return 'success';
});

function updateElapsedTime() {
    if (!props.currentTimecard || !props.currentTimecard.start_date) {
        elapsedTime.value = '';
        return;
    }

    const start = new Date(props.currentTimecard.start_date);
    if (isNaN(start.getTime())) {
        elapsedTime.value = '';
        return;
    }

    const now = new Date();
    const diff = now.getTime() - start.getTime();

    if (diff < 0) {
        elapsedTime.value = '0s';
        return;
    }

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    if (hours > 0) {
        elapsedTime.value = `${hours}h ${minutes}m ${seconds}s`;
    } else if (minutes > 0) {
        elapsedTime.value = `${minutes}m ${seconds}s`;
    } else {
        elapsedTime.value = `${seconds}s`;
    }
}

function clockIn() {
    if (!selectedStore.value) return;

    clockInForm.store_id = selectedStore.value;
    clockInForm.post('/timecards/clock-in', {
        preserveScroll: true,
        onFinish: () => emit('stateChanged'),
    });
}

function clockOut() {
    if (!props.currentTimecard) return;

    router.post(
        `/timecards/${props.currentTimecard.id}/clock-out`,
        {},
        {
            preserveScroll: true,
            onFinish: () => emit('stateChanged'),
        }
    );
}

function startBreak() {
    if (!props.currentTimecard) return;

    router.post(
        `/timecards/${props.currentTimecard.id}/start-break`,
        {},
        {
            preserveScroll: true,
            onFinish: () => emit('stateChanged'),
        }
    );
}

function endBreak() {
    if (!props.currentTimecard) return;

    router.post(
        `/timecards/${props.currentTimecard.id}/end-break`,
        {},
        {
            preserveScroll: true,
            onFinish: () => emit('stateChanged'),
        }
    );
}

onMounted(() => {
    updateElapsedTime();
    timerInterval = setInterval(updateElapsedTime, 1000);
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});
</script>

<template>
    <Card class="clock-widget">
        <template #content>
            <div class="flex flex-col gap-4">
                <!-- Status Header -->
                <div class="flex flex-wrap items-start gap-2">
                    <div class="flex min-w-0 flex-1 basis-48 items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                            :class="{
                                'bg-muted': !currentTimecard,
                                'bg-green-100 dark:bg-green-900/30': currentTimecard && !isOnBreak,
                                'bg-amber-100 dark:bg-amber-900/30': currentTimecard && isOnBreak,
                            }"
                        >
                            <Clock
                                v-if="!currentTimecard"
                                class="h-5 w-5 text-muted-foreground"
                            />
                            <Play
                                v-else-if="!isOnBreak"
                                class="h-5 w-5 text-green-600 dark:text-green-400"
                            />
                            <Coffee v-else class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ statusText }}</p>
                            <p v-if="currentTimecard" class="text-xs text-muted-foreground">
                                Started {{ new Date(currentTimecard.start_date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) }}
                            </p>
                        </div>
                    </div>
                    <Tag
                        :severity="statusSeverity"
                        :value="currentTimecard ? (isOnBreak ? 'Break' : 'Working') : 'Off'"
                    />
                </div>

                <!-- Elapsed Time Display -->
                <div
                    v-if="currentTimecard"
                    class="rounded-xl bg-muted/50 py-6 text-center"
                >
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        Time Elapsed
                    </p>
                    <p class="mt-1 text-3xl font-bold tabular-nums tracking-tight">
                        {{ elapsedTime || '—' }}
                    </p>
                </div>

                <!-- Clock In Form (when not clocked in) -->
                <div v-if="!currentTimecard" class="flex flex-col gap-3">
                    <Select
                        v-model="selectedStore"
                        :options="stores"
                        option-label="name"
                        option-value="id"
                        placeholder="Select a store"
                        class="w-full"
                        :disabled="isProcessing"
                    />
                    <Button
                        label="Clock In"
                        icon="pi pi-sign-in"
                        :disabled="!selectedStore || isProcessing"
                        :loading="isProcessing"
                        @click="clockIn"
                        class="w-full"
                    />
                </div>

                <!-- Clock Actions (when clocked in) -->
                <div v-else class="flex gap-2">
                    <!-- Break Toggle -->
                    <Button
                        v-if="!isOnBreak"
                        label="Break"
                        icon="pi pi-pause"
                        severity="warn"
                        outlined
                        @click="startBreak"
                        class="flex-1"
                    />
                    <Button
                        v-else
                        label="Resume"
                        icon="pi pi-play"
                        severity="success"
                        @click="endBreak"
                        class="flex-1"
                    />

                    <!-- Clock Out -->
                    <Button
                        label="Clock Out"
                        icon="pi pi-sign-out"
                        severity="danger"
                        outlined
                        @click="clockOut"
                        class="flex-1"
                    />
                </div>
            </div>
        </template>
    </Card>
</template>
