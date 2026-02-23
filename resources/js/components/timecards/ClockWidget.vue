<script setup lang="ts">
import type { Timecard, TimecardStore } from '@/types/timecard';
import { router, useForm } from '@inertiajs/vue3';
import { Coffee, Play } from 'lucide-vue-next';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    currentTimecard: Timecard | null;
    isOnBreak: boolean;
    stores: TimecardStore[];
    showViewLink?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showViewLink: false,
});

const emit = defineEmits<{
    stateChanged: [];
    close: [];
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

function goToTimecards() {
    emit('close');
    router.get('/timecards');
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
        },
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
        },
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
        },
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
    <div class="flex h-full flex-col gap-3">
        <!-- Clocked In State -->
        <div v-if="currentTimecard" class="flex-1 rounded-lg border border-primary/30 bg-primary/5 p-3">
            <div class="mb-2 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <component
                        :is="isOnBreak ? Coffee : Play"
                        class="h-4 w-4"
                        :class="isOnBreak ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400'"
                    />
                    <span class="text-sm font-semibold">{{ statusText }}</span>
                </div>
                <Tag
                    :severity="statusSeverity"
                    :value="isOnBreak ? 'Break' : 'Working'"
                    class="!text-xs"
                />
            </div>
            <div class="space-y-1 text-sm">
                <div>
                    <span class="text-muted-foreground">Started: </span>
                    <span>
                        {{
                            new Date(currentTimecard.start_date).toLocaleTimeString('en-US', {
                                hour: '2-digit',
                                minute: '2-digit',
                            })
                        }}
                    </span>
                </div>
                <div>
                    <span class="text-muted-foreground">Elapsed: </span>
                    <span class="font-medium tabular-nums">{{ elapsedTime || '—' }}</span>
                </div>
            </div>
            <div class="mt-2 flex gap-2">
                <Button
                    v-if="!isOnBreak"
                    label="Break"
                    icon="pi pi-pause"
                    severity="warn"
                    outlined
                    size="small"
                    @click="startBreak"
                    class="flex-1"
                />
                <Button
                    v-else
                    label="Resume"
                    icon="pi pi-play"
                    severity="success"
                    size="small"
                    @click="endBreak"
                    class="flex-1"
                />
                <Button
                    label="Clock Out"
                    icon="pi pi-sign-out"
                    severity="danger"
                    outlined
                    size="small"
                    @click="clockOut"
                    class="flex-1"
                />
            </div>
        </div>

        <!-- Clock In Form -->
        <div v-else class="flex-1 space-y-2">
            <Select
                v-model="selectedStore"
                :options="stores"
                option-label="name"
                option-value="id"
                placeholder="Select a store..."
                size="small"
                class="w-full"
                :disabled="isProcessing"
                :pt="{ overlay: { style: 'width: 0; overflow: hidden' } }"
            >
                <template #value="{ value, placeholder }">
                    <span v-if="value" class="block truncate">
                        {{ stores.find((s) => s.id === value)?.name }}
                        ({{ stores.find((s) => s.id === value)?.code }})
                    </span>
                    <span v-else class="text-muted-foreground">{{ placeholder }}</span>
                </template>
                <template #option="{ option }">
                    <div class="truncate">{{ option.name }} ({{ option.code }})</div>
                </template>
            </Select>
            <Button
                label="Clock In"
                icon="pi pi-sign-in"
                size="small"
                :disabled="!selectedStore || isProcessing"
                :loading="isProcessing"
                @click="clockIn"
                class="w-full"
            />
        </div>

        <!-- Link to full page (only in floating panel) -->
        <Button
            v-if="showViewLink"
            label="View Timecards"
            icon="pi pi-external-link"
            severity="secondary"
            text
            size="small"
            class="w-full"
            @click="goToTimecards"
        />
    </div>
</template>
