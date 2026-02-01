<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { AlertTriangle, Clock } from 'lucide-vue-next';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import Message from 'primevue/message';
import { computed, ref, watch } from 'vue';
import type { AppPageProps } from '@/types';
import type { Timecard } from '@/types/timecard';

type PageProps = AppPageProps<{
    incompleteTimecards?: Timecard[];
}>;

const page = usePage<PageProps>();

const visible = ref(false);
const currentIndex = ref(0);
const selectedEndTime = ref<Date | null>(null);
const isSubmitting = ref(false);

const incompleteTimecards = computed(
    () => page.props.incompleteTimecards ?? [],
);

const currentTimecard = computed(() => {
    if (currentIndex.value >= incompleteTimecards.value.length) return null;
    return incompleteTimecards.value[currentIndex.value];
});

const hasMore = computed(
    () => currentIndex.value < incompleteTimecards.value.length - 1,
);

const minDate = computed(() => {
    if (!currentTimecard.value) return undefined;
    return new Date(currentTimecard.value.start_date);
});

const maxDate = computed(() => {
    if (!currentTimecard.value) return undefined;
    const startDate = new Date(currentTimecard.value.start_date);
    const endOfDay = new Date(startDate);
    endOfDay.setHours(23, 59, 59, 999);
    return endOfDay;
});

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function formatTime(dateString: string): string {
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function handleSubmit() {
    if (!currentTimecard.value || !selectedEndTime.value) return;

    isSubmitting.value = true;

    router.post(
        `/timecards/${currentTimecard.value.id}/resolve-incomplete`,
        { end_time: selectedEndTime.value.toISOString() },
        {
            preserveScroll: true,
            onSuccess: () => {
                if (hasMore.value) {
                    currentIndex.value++;
                    selectedEndTime.value = null;
                } else {
                    visible.value = false;
                    currentIndex.value = 0;
                    selectedEndTime.value = null;
                }
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}

function handleSkip() {
    if (hasMore.value) {
        currentIndex.value++;
        selectedEndTime.value = null;
    } else {
        visible.value = false;
    }
}

// Show dialog when there are incomplete timecards
watch(
    incompleteTimecards,
    (timecards) => {
        if (timecards.length > 0 && !visible.value) {
            currentIndex.value = 0;
            selectedEndTime.value = null;
            visible.value = true;
        }
    },
    { immediate: true },
);
</script>

<template>
    <Dialog
        v-model:visible="visible"
        :modal="true"
        :closable="false"
        :style="{ width: '450px' }"
        header="Incomplete Timecard"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30"
                >
                    <AlertTriangle
                        class="h-5 w-5 text-amber-600 dark:text-amber-400"
                    />
                </div>
                <div>
                    <h2 class="text-lg font-semibold">Incomplete Timecard</h2>
                    <p
                        v-if="incompleteTimecards.length > 1"
                        class="text-xs text-muted-foreground"
                    >
                        {{ currentIndex + 1 }} of
                        {{ incompleteTimecards.length }}
                    </p>
                </div>
            </div>
        </template>

        <div v-if="currentTimecard" class="flex flex-col gap-4">
            <Message severity="warn" :closable="false">
                You have a timecard that was not clocked out. Please provide the
                time you finished working.
            </Message>

            <!-- Timecard Info -->
            <div class="rounded-lg bg-muted/50 p-4">
                <div class="mb-3 flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10"
                    >
                        <Clock class="h-4 w-4 text-primary" />
                    </div>
                    <div>
                        <p class="font-medium">
                            {{ currentTimecard.store?.name }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ formatDate(currentTimecard.start_date) }}
                        </p>
                    </div>
                </div>
                <div class="text-sm">
                    <span class="text-muted-foreground">Clocked in at: </span>
                    <span class="font-medium">{{
                        formatTime(currentTimecard.start_date)
                    }}</span>
                </div>
            </div>

            <!-- End Time Input -->
            <div class="flex flex-col gap-2">
                <label for="end_time" class="text-sm font-medium">
                    What time did you finish working?
                    <span class="text-red-500">*</span>
                </label>
                <DatePicker
                    id="end_time"
                    v-model="selectedEndTime"
                    show-time
                    hour-format="12"
                    size="small"
                    :min-date="minDate"
                    :max-date="maxDate"
                    placeholder="Select clock-out time"
                    class="w-full"
                />
                <small class="text-muted-foreground">
                    This timecard will be marked as having user-provided data.
                </small>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between gap-2">
                <Button
                    label="Skip for now"
                    severity="secondary"
                    text
                    size="small"
                    @click="handleSkip"
                    :disabled="isSubmitting"
                />
                <Button
                    label="Save"
                    icon="pi pi-check"
                    size="small"
                    @click="handleSubmit"
                    :disabled="!selectedEndTime"
                    :loading="isSubmitting"
                />
            </div>
        </template>
    </Dialog>
</template>
