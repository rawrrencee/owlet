<script setup lang="ts">
import TimecardCalendar from '@/components/timecards/TimecardCalendar.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    BreadcrumbItem,
    CalendarDayData,
    Timecard,
    TimecardStore,
} from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface MonthlyStats {
    total_hours: number;
    days_worked: number;
    daily_average: number;
}

interface Props {
    month: string;
    monthlyData: Record<string, CalendarDayData>;
    monthlyStats: MonthlyStats;
    currentTimecard: Timecard | null;
    isOnBreak: boolean;
    stores: TimecardStore[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Timecards' },
];

const selectedStore = ref<number | null>(null);
const clockInForm = useForm({ store_id: null as number | null });
const elapsedTime = ref('');
let timerInterval: ReturnType<typeof setInterval> | null = null;

const storeOptions = props.stores.map((s) => ({
    label: `${s.name} (${s.code})`,
    value: s.id,
}));

const statusText = computed(() => {
    if (!props.currentTimecard) return 'Not clocked in';
    const storeName = props.currentTimecard.store?.name ?? 'Unknown';
    if (props.isOnBreak) return `On break at ${storeName}`;
    return `Working at ${storeName}`;
});

function updateElapsedTime() {
    if (!props.currentTimecard?.start_date) {
        elapsedTime.value = '';
        return;
    }
    const start = new Date(props.currentTimecard.start_date);
    if (isNaN(start.getTime())) { elapsedTime.value = ''; return; }
    const diff = Math.max(0, Date.now() - start.getTime());
    const hours = Math.floor(diff / 3600000);
    const minutes = Math.floor((diff % 3600000) / 60000);
    const seconds = Math.floor((diff % 60000) / 1000);
    if (hours > 0) elapsedTime.value = `${hours}h ${minutes}m ${seconds}s`;
    else if (minutes > 0) elapsedTime.value = `${minutes}m ${seconds}s`;
    else elapsedTime.value = `${seconds}s`;
}

function clockIn() {
    if (!selectedStore.value) return;
    clockInForm.store_id = selectedStore.value;
    clockInForm.post('/timecards/clock-in', { preserveScroll: true });
}

function clockOut() {
    if (!props.currentTimecard) return;
    router.post(`/timecards/${props.currentTimecard.id}/clock-out`, {}, { preserveScroll: true });
}

function startBreak() {
    if (!props.currentTimecard) return;
    router.post(`/timecards/${props.currentTimecard.id}/start-break`, {}, { preserveScroll: true });
}

function endBreak() {
    if (!props.currentTimecard) return;
    router.post(`/timecards/${props.currentTimecard.id}/end-break`, {}, { preserveScroll: true });
}

onMounted(() => {
    updateElapsedTime();
    timerInterval = setInterval(updateElapsedTime, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

const selectedDate = ref<string | undefined>(undefined);

function handleDateClick(date: string) {
    selectedDate.value = date;
    router.visit(`/timecards/${date}`);
}

function handleMonthChange(month: Date) {
    const monthString = month.toISOString().split('T')[0];
    router.get('/timecards', { month: monthString }, { preserveState: true });
}

function formatHours(hours: number): string {
    if (hours === 0) return '0h';
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
}

const monthDisplayName = computed(() => {
    const date = new Date(props.month);
    return date.toLocaleString('default', { month: 'long', year: 'numeric' });
});
</script>

<template>
    <Head title="My Timecards" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 class="heading-lg">My Timecards</h1>

            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Clock Widget and Stats -->
                <div class="flex flex-col gap-4 lg:col-span-1">
                    <!-- Active Timecard Card -->
                    <Card v-if="currentTimecard" class="border-primary/30 bg-primary/5">
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <i class="pi pi-clock text-primary" />
                                Active Timecard
                                <Tag
                                    :value="isOnBreak ? 'Break' : 'Working'"
                                    :severity="isOnBreak ? 'warn' : 'success'"
                                    class="!text-xs"
                                />
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-3">
                                <div class="space-y-1 text-sm">
                                    <div>
                                        <span class="text-muted-foreground">Status: </span>
                                        <span class="font-medium">{{ statusText }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Started: </span>
                                        <span>
                                            {{ new Date(currentTimecard.start_date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Elapsed: </span>
                                        <span class="font-medium tabular-nums">{{ elapsedTime || '—' }}</span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
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
                        </template>
                    </Card>

                    <!-- Clock In Card -->
                    <Card v-else>
                        <template #title>
                            <span class="text-base">Clock In</span>
                        </template>
                        <template #content>
                            <div class="flex flex-col gap-3">
                                <Select
                                    v-model="selectedStore"
                                    :options="storeOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Select a store..."
                                    size="small"
                                    fluid
                                />
                                <Button
                                    label="Clock In"
                                    icon="pi pi-sign-in"
                                    size="small"
                                    :disabled="!selectedStore || clockInForm.processing"
                                    :loading="clockInForm.processing"
                                    @click="clockIn"
                                />
                            </div>
                        </template>
                    </Card>

                    <!-- Monthly Stats -->
                    <Card>
                        <template #content>
                            <div class="flex flex-col gap-3">
                                <h3
                                    class="text-sm font-medium text-muted-foreground"
                                >
                                    {{ monthDisplayName }} Summary
                                </h3>
                                <div class="grid grid-cols-3 gap-3">
                                    <div
                                        class="flex flex-col items-center rounded-lg bg-green-50 p-3 dark:bg-green-900/20"
                                    >
                                        <span
                                            class="text-xl font-bold text-green-600 dark:text-green-400"
                                        >
                                            {{
                                                formatHours(
                                                    monthlyStats.total_hours,
                                                )
                                            }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >Total</span
                                        >
                                    </div>
                                    <div
                                        class="flex flex-col items-center rounded-lg bg-muted/50 p-3"
                                    >
                                        <span class="text-xl font-bold">
                                            {{ monthlyStats.days_worked }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >Days</span
                                        >
                                    </div>
                                    <div
                                        class="flex flex-col items-center rounded-lg bg-muted/50 p-3"
                                    >
                                        <span class="text-xl font-bold">
                                            {{
                                                formatHours(
                                                    monthlyStats.daily_average,
                                                )
                                            }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >Avg/Day</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Calendar -->
                <div class="lg:col-span-2">
                    <Card>
                        <template #content>
                            <TimecardCalendar
                                :month="month"
                                :data="monthlyData"
                                :selected-date="selectedDate"
                                @date-click="handleDateClick"
                                @month-change="handleMonthChange"
                            />
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
