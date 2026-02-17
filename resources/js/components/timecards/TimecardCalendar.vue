<script setup lang="ts">
import type { CalendarDayData } from '@/types/timecard';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Popover from 'primevue/popover';
import { computed, ref } from 'vue';

interface Props {
    month: string;
    data: Record<string, CalendarDayData>;
    selectedDate?: string;
    loading?: boolean;
    showEmployeeCount?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    selectedDate: undefined,
    loading: false,
    showEmployeeCount: false,
});

const emit = defineEmits<{
    (e: 'dateClick', date: string): void;
    (e: 'monthChange', month: Date): void;
}>();

const currentMonth = ref(new Date(props.month));
const monthPickerPopover = ref();
const monthPickerDate = ref(new Date(props.month));

const monthName = computed(() => {
    return currentMonth.value.toLocaleString('default', {
        month: 'long',
        year: 'numeric',
    });
});

function toggleMonthPicker(event: Event) {
    monthPickerPopover.value.toggle(event);
}

function handleMonthSelect(
    value: Date | Date[] | (Date | null)[] | null | undefined,
) {
    const date = Array.isArray(value) ? value[0] : value;
    if (!date) return;
    const newMonth = new Date(date.getFullYear(), date.getMonth(), 1);
    currentMonth.value = newMonth;
    monthPickerDate.value = newMonth;
    monthPickerPopover.value.hide();
    emit('monthChange', newMonth);
}

const daysOfWeek = [
    { short: 'S', full: 'Sun' },
    { short: 'M', full: 'Mon' },
    { short: 'T', full: 'Tue' },
    { short: 'W', full: 'Wed' },
    { short: 'T', full: 'Thu' },
    { short: 'F', full: 'Fri' },
    { short: 'S', full: 'Sat' },
];

const calendarDays = computed(() => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();

    // First day of the month
    const firstDay = new Date(year, month, 1);
    // Last day of the month
    const lastDay = new Date(year, month + 1, 0);

    const days: {
        date: Date;
        dateString: string;
        isCurrentMonth: boolean;
        isToday: boolean;
        data: CalendarDayData | null;
    }[] = [];

    // Add days from previous month to fill the first week
    const startDayOfWeek = firstDay.getDay();
    for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const date = new Date(year, month, -i);
        const dateString = formatDateString(date);
        days.push({
            date,
            dateString,
            isCurrentMonth: false,
            isToday: isToday(date),
            data: props.data[dateString] || null,
        });
    }

    // Add days of the current month
    for (let day = 1; day <= lastDay.getDate(); day++) {
        const date = new Date(year, month, day);
        const dateString = formatDateString(date);
        days.push({
            date,
            dateString,
            isCurrentMonth: true,
            isToday: isToday(date),
            data: props.data[dateString] || null,
        });
    }

    // Add days from next month to complete the last week
    const remainingDays = 7 - (days.length % 7);
    if (remainingDays < 7) {
        for (let i = 1; i <= remainingDays; i++) {
            const date = new Date(year, month + 1, i);
            const dateString = formatDateString(date);
            days.push({
                date,
                dateString,
                isCurrentMonth: false,
                isToday: isToday(date),
                data: props.data[dateString] || null,
            });
        }
    }

    return days;
});

function formatDateString(date: Date): string {
    // Format in local timezone to avoid UTC conversion issues
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function isToday(date: Date): boolean {
    const today = new Date();
    return (
        date.getDate() === today.getDate() &&
        date.getMonth() === today.getMonth() &&
        date.getFullYear() === today.getFullYear()
    );
}

function previousMonth() {
    const newMonth = new Date(
        currentMonth.value.getFullYear(),
        currentMonth.value.getMonth() - 1,
        1,
    );
    currentMonth.value = newMonth;
    emit('monthChange', newMonth);
}

function nextMonth() {
    const newMonth = new Date(
        currentMonth.value.getFullYear(),
        currentMonth.value.getMonth() + 1,
        1,
    );
    currentMonth.value = newMonth;
    emit('monthChange', newMonth);
}

function handleDayClick(day: (typeof calendarDays.value)[0]) {
    if (day.isCurrentMonth) {
        emit('dateClick', day.dateString);
    }
}

function formatHours(hours: number): string {
    if (hours === 0) return '';
    if (hours < 1) return `${Math.round(hours * 60)}m`;
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
}
</script>

<template>
    <div class="timecard-calendar">
        <!-- Header with month navigation -->
        <div class="mb-4 flex items-center justify-between">
            <Button
                icon="pi pi-chevron-left"
                text
                size="small"
                @click="previousMonth"
                aria-label="Previous month"
            />
            <Button
                :label="monthName"
                text
                size="small"
                class="text-lg font-semibold"
                @click="toggleMonthPicker"
                aria-label="Select month"
            />
            <Popover ref="monthPickerPopover">
                <DatePicker
                    v-model="monthPickerDate"
                    view="month"
                    inline
                    @update:model-value="handleMonthSelect"
                />
            </Popover>
            <Button
                icon="pi pi-chevron-right"
                text
                size="small"
                @click="nextMonth"
                aria-label="Next month"
            />
        </div>

        <!-- Days of week header -->
        <div class="mb-2 grid grid-cols-7 gap-px sm:gap-1">
            <div
                v-for="day in daysOfWeek"
                :key="day.full"
                class="p-1 text-center text-xs font-medium text-muted-foreground sm:p-2"
            >
                <span class="sm:hidden">{{ day.short }}</span>
                <span class="hidden sm:inline">{{ day.full }}</span>
            </div>
        </div>

        <!-- Calendar grid -->
        <div class="grid grid-cols-7 gap-px sm:gap-1">
            <div
                v-for="day in calendarDays"
                :key="day.dateString"
                class="min-h-[60px] cursor-pointer rounded-lg border p-1 transition-colors sm:min-h-[80px] sm:p-2"
                :class="{
                    'border-primary bg-primary/5':
                        selectedDate === day.dateString,
                    'bg-muted/30': !day.isCurrentMonth,
                    'border-primary/50 ring-1 ring-primary/30': day.isToday,
                    'hover:bg-muted/50': day.isCurrentMonth,
                    'opacity-50': !day.isCurrentMonth,
                }"
                @click="handleDayClick(day)"
            >
                <div class="flex flex-col gap-1">
                    <span
                        class="text-xs font-medium sm:text-sm"
                        :class="{
                            'text-primary': day.isToday,
                            'text-muted-foreground': !day.isCurrentMonth,
                        }"
                    >
                        {{ day.date.getDate() }}
                    </span>
                    <template v-if="day.data && day.isCurrentMonth">
                        <span
                            v-if="day.data.total_hours > 0"
                            class="text-xs font-semibold text-green-600 dark:text-green-400"
                        >
                            {{ formatHours(day.data.total_hours) }}
                        </span>
                        <span
                            v-if="showEmployeeCount && day.data.employee_count"
                            class="text-xs text-muted-foreground"
                        >
                            <span class="sm:hidden" :title="day.data.employee_count + (day.data.employee_count === 1 ? ' person' : ' people')">
                                <i class="pi pi-user" style="font-size: 0.625rem"></i>
                                {{ day.data.employee_count }}
                            </span>
                            <span class="hidden sm:inline">
                                {{ day.data.employee_count }}
                                {{ day.data.employee_count === 1 ? 'person' : 'people' }}
                            </span>
                        </span>
                        <!-- Leave indicators -->
                        <div
                            v-if="day.data.leave && day.data.leave.length > 0"
                            class="flex flex-wrap gap-0.5"
                        >
                            <span
                                v-for="leave in day.data.leave"
                                :key="leave.id"
                                class="inline-flex items-center rounded-full px-1 text-[10px] leading-tight text-white"
                                :class="{
                                    'border border-dashed opacity-70': leave.status === 'pending',
                                }"
                                :style="{ backgroundColor: leave.color || '#9E9E9E' }"
                                :title="`${leave.leave_type} (${leave.status})${leave.is_half_day ? ` - ${leave.half_day_type?.toUpperCase()}` : ''}`"
                            >
                                {{ leave.is_half_day ? leave.half_day_type?.toUpperCase() : leave.leave_type.charAt(0) }}
                            </span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
