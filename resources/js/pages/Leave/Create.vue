<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, LeaveType } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import DatePicker from 'primevue/datepicker';
import Message from 'primevue/message';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { computed, watch } from 'vue';

interface Props {
    leaveTypes: LeaveType[];
    breadcrumbs: BreadcrumbItem[];
}

const props = defineProps<Props>();

const form = useForm({
    leave_type_id: null as number | null,
    start_date: null as Date | null,
    end_date: null as Date | null,
    start_half_day: 'full',
    end_half_day: 'full',
    reason: '',
});

const halfDayOptions = [
    { label: 'Full Day', value: 'full' },
    { label: 'AM Only', value: 'am' },
    { label: 'PM Only', value: 'pm' },
];

const isSingleDay = computed(() => {
    if (!form.start_date || !form.end_date) return false;
    return formatDateForSubmit(form.start_date) === formatDateForSubmit(form.end_date);
});

const estimatedDays = computed(() => {
    if (!form.start_date || !form.end_date) return null;

    const start = form.start_date;
    const end = form.end_date;

    if (start > end) return 0;

    if (start.getTime() === end.getTime()) {
        if (start.getDay() === 0 || start.getDay() === 6) return 0;
        return form.start_half_day === 'full' ? 1 : 0.5;
    }

    let totalDays = 0;
    const current = new Date(start);
    while (current <= end) {
        if (current.getDay() !== 0 && current.getDay() !== 6) {
            totalDays += 1;
        }
        current.setDate(current.getDate() + 1);
    }

    if (
        form.start_half_day !== 'full' &&
        start.getDay() !== 0 &&
        start.getDay() !== 6
    ) {
        totalDays -= 0.5;
    }
    if (
        form.end_half_day !== 'full' &&
        end.getDay() !== 0 &&
        end.getDay() !== 6
    ) {
        totalDays -= 0.5;
    }

    return Math.max(0, totalDays);
});

function formatDateForSubmit(date: Date | null): string {
    if (!date) return '';
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// When start_date changes, auto-set end_date if empty
watch(
    () => form.start_date,
    (val) => {
        if (val && !form.end_date) {
            form.end_date = val;
        }
    },
);

// When single day, reset end_half_day
watch(isSingleDay, (val) => {
    if (val) {
        form.end_half_day = 'full';
    }
});

function submit() {
    form.transform((data) => ({
        ...data,
        start_date: formatDateForSubmit(data.start_date),
        end_date: formatDateForSubmit(data.end_date),
    })).post('/leave', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Apply for Leave" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-3">
                <Button
                    icon="pi pi-arrow-left"
                    severity="secondary"
                    text
                    rounded
                    size="small"
                    @click="router.visit('/leave')"
                />
                <h1 class="heading-lg">Apply for Leave</h1>
            </div>

            <Card class="mx-auto w-full max-w-2xl">
                <template #content>
                    <form @submit.prevent="submit" class="space-y-4">
                        <Message
                            v-if="(form.errors as any).general"
                            severity="error"
                            :closable="false"
                        >
                            {{ (form.errors as any).general }}
                        </Message>

                        <!-- Leave Type -->
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Leave Type</label
                            >
                            <Select
                                v-model="form.leave_type_id"
                                :options="leaveTypes"
                                option-label="name"
                                option-value="id"
                                placeholder="Select leave type"
                                size="small"
                                class="w-full"
                                :class="{
                                    'p-invalid': form.errors.leave_type_id,
                                }"
                            >
                                <template #option="{ option }">
                                    <div class="flex items-center gap-2">
                                        <div
                                            v-if="option.color"
                                            class="h-3 w-3 rounded-full"
                                            :style="{
                                                backgroundColor: option.color,
                                            }"
                                        ></div>
                                        <span>{{ option.name }}</span>
                                    </div>
                                </template>
                            </Select>
                            <small
                                v-if="form.errors.leave_type_id"
                                class="text-red-500"
                                >{{ form.errors.leave_type_id }}</small
                            >
                        </div>

                        <!-- Dates -->
                        <div
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                        >
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium"
                                    >Start Date</label
                                >
                                <DatePicker
                                    v-model="form.start_date"
                                    date-format="dd M yy"
                                    size="small"
                                    class="w-full"
                                    :class="{
                                        'p-invalid': form.errors.start_date,
                                    }"
                                    show-icon
                                    icon-display="input"
                                />
                                <small
                                    v-if="form.errors.start_date"
                                    class="text-red-500"
                                    >{{ form.errors.start_date }}</small
                                >
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium"
                                    >End Date</label
                                >
                                <DatePicker
                                    v-model="form.end_date"
                                    date-format="dd M yy"
                                    size="small"
                                    class="w-full"
                                    :class="{
                                        'p-invalid': form.errors.end_date,
                                    }"
                                    show-icon
                                    icon-display="input"
                                    :min-date="
                                        form.start_date
                                            ? new Date(form.start_date)
                                            : undefined
                                    "
                                />
                                <small
                                    v-if="form.errors.end_date"
                                    class="text-red-500"
                                    >{{ form.errors.end_date }}</small
                                >
                            </div>
                        </div>

                        <!-- Half-day selectors -->
                        <div
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                        >
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium"
                                    >{{
                                        isSingleDay
                                            ? 'Day Type'
                                            : 'First Day'
                                    }}</label
                                >
                                <Select
                                    v-model="form.start_half_day"
                                    :options="halfDayOptions"
                                    option-label="label"
                                    option-value="value"
                                    size="small"
                                    class="w-full"
                                />
                            </div>

                            <div v-if="!isSingleDay">
                                <label
                                    class="mb-1 block text-sm font-medium"
                                    >Last Day</label
                                >
                                <Select
                                    v-model="form.end_half_day"
                                    :options="halfDayOptions"
                                    option-label="label"
                                    option-value="value"
                                    size="small"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <!-- Estimated Days -->
                        <div
                            v-if="estimatedDays !== null"
                            class="rounded-lg border border-border bg-muted/50 p-3"
                        >
                            <span class="text-sm text-muted-foreground"
                                >Estimated working days: </span
                            >
                            <span class="font-semibold">{{
                                estimatedDays
                            }}</span>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Reason (optional)</label
                            >
                            <Textarea
                                v-model="form.reason"
                                rows="3"
                                size="small"
                                class="w-full"
                                placeholder="Provide a reason for your leave request..."
                            />
                            <small
                                v-if="form.errors.reason"
                                class="text-red-500"
                                >{{ form.errors.reason }}</small
                            >
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-2 pt-2">
                            <Button
                                label="Cancel"
                                severity="secondary"
                                size="small"
                                @click="router.visit('/leave')"
                            />
                            <Button
                                label="Submit Request"
                                icon="pi pi-check"
                                size="small"
                                type="submit"
                                :loading="form.processing"
                            />
                        </div>
                    </form>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
