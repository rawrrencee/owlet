<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Checkbox from 'primevue/checkbox';
import DatePicker from 'primevue/datepicker';
import Divider from 'primevue/divider';
import Select from 'primevue/select';
import { ref, watch } from 'vue';

interface EmployeeOption {
    id: number;
    name: string;
    employee_number: string | null;
}

interface StoreOption {
    id: number;
    name: string;
    code: string;
}

interface StatusOption {
    value: string;
    label: string;
}

interface Props {
    employees: EmployeeOption[];
    stores: StoreOption[];
    preselectedEmployee: EmployeeOption | null;
    preselectedDate: string | null;
    statuses: StatusOption[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Timecards', href: '/management/timecards' },
    { title: 'Create' },
];

interface BreakEntry {
    start_time: Date | null;
    end_time: Date | null;
}

const includeTimeEntries = ref(false);
const breaks = ref<BreakEntry[]>([]);

const form = useForm({
    employee_id: props.preselectedEmployee?.id || (null as number | null),
    store_id: null as number | null,
    date: props.preselectedDate ? new Date(props.preselectedDate) : new Date(),
    status: 'COMPLETED',
    start_time: null as Date | null,
    end_time: null as Date | null,
    breaks: [] as { start_time: string; end_time: string }[],
});

function addBreak() {
    breaks.value.push({ start_time: null, end_time: null });
}

function removeBreak(index: number) {
    breaks.value.splice(index, 1);
}

// Reset time fields when toggling
watch(includeTimeEntries, (value) => {
    if (!value) {
        form.start_time = null;
        form.end_time = null;
        breaks.value = [];
    }
});

const employeeOptions = props.employees.map((e) => ({
    label: `${e.name}${e.employee_number ? ` (#${e.employee_number})` : ''}`,
    value: e.id,
}));

const storeOptions = props.stores.map((s) => ({
    label: `${s.name} (${s.code})`,
    value: s.id,
}));

const statusOptions = props.statuses.map((s) => ({
    label: s.label,
    value: s.value,
}));

function handleSubmit() {
    // Convert date to string format
    const dateString =
        form.date instanceof Date
            ? form.date.toISOString().split('T')[0]
            : form.date;

    // Build breaks array with ISO strings
    const breaksData = breaks.value
        .filter((b) => b.start_time && b.end_time)
        .map((b) => ({
            start_time: b.start_time!.toISOString(),
            end_time: b.end_time!.toISOString(),
        }));

    form.transform((data) => ({
        ...data,
        date: dateString,
        start_time:
            includeTimeEntries.value && data.start_time
                ? (data.start_time as Date).toISOString()
                : null,
        end_time:
            includeTimeEntries.value && data.end_time
                ? (data.end_time as Date).toISOString()
                : null,
        breaks: includeTimeEntries.value ? breaksData : [],
    })).post('/management/timecards');
}

function navigateBack() {
    router.visit('/management/timecards');
}
</script>

<template>
    <Head title="Create Timecard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-3">
                <Button
                    icon="pi pi-arrow-left"
                    text
                    size="small"
                    severity="secondary"
                    @click="navigateBack"
                    v-tooltip.top="'Back'"
                />
                <h1 class="heading-lg">Create Timecard</h1>
            </div>

            <Card class="max-w-2xl">
                <template #content>
                    <form
                        @submit.prevent="handleSubmit"
                        class="flex flex-col gap-4"
                    >
                        <!-- Employee -->
                        <div class="flex flex-col gap-2">
                            <label
                                for="employee_id"
                                class="text-sm font-medium"
                            >
                                Employee <span class="text-red-500">*</span>
                            </label>
                            <Select
                                id="employee_id"
                                v-model="form.employee_id"
                                :options="employeeOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Select an employee"
                                filter
                                size="small"
                                :invalid="!!form.errors.employee_id"
                            />
                            <small
                                v-if="form.errors.employee_id"
                                class="text-red-500"
                            >
                                {{ form.errors.employee_id }}
                            </small>
                        </div>

                        <!-- Store -->
                        <div class="flex flex-col gap-2">
                            <label for="store_id" class="text-sm font-medium">
                                Store <span class="text-red-500">*</span>
                            </label>
                            <Select
                                id="store_id"
                                v-model="form.store_id"
                                :options="storeOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Select a store"
                                filter
                                size="small"
                                :invalid="!!form.errors.store_id"
                            />
                            <small
                                v-if="form.errors.store_id"
                                class="text-red-500"
                            >
                                {{ form.errors.store_id }}
                            </small>
                        </div>

                        <!-- Date -->
                        <div class="flex flex-col gap-2">
                            <label for="date" class="text-sm font-medium">
                                Date <span class="text-red-500">*</span>
                            </label>
                            <DatePicker
                                id="date"
                                v-model="form.date"
                                date-format="yy-mm-dd"
                                size="small"
                                :invalid="!!form.errors.date"
                            />
                            <small v-if="form.errors.date" class="text-red-500">
                                {{ form.errors.date }}
                            </small>
                        </div>

                        <!-- Status -->
                        <div class="flex flex-col gap-2">
                            <label for="status" class="text-sm font-medium">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <Select
                                id="status"
                                v-model="form.status"
                                :options="statusOptions"
                                option-label="label"
                                option-value="value"
                                size="small"
                                :invalid="!!form.errors.status"
                            />
                            <small
                                v-if="form.errors.status"
                                class="text-red-500"
                            >
                                {{ form.errors.status }}
                            </small>
                        </div>

                        <Divider />

                        <!-- Include Time Entries Toggle -->
                        <div class="flex items-center gap-2">
                            <Checkbox
                                v-model="includeTimeEntries"
                                input-id="include_time_entries"
                                :binary="true"
                            />
                            <label
                                for="include_time_entries"
                                class="cursor-pointer text-sm font-medium"
                            >
                                Add time entries now
                            </label>
                        </div>

                        <!-- Time Entry Fields -->
                        <template v-if="includeTimeEntries">
                            <div
                                class="rounded-lg border border-muted bg-muted/20 p-4"
                            >
                                <h4 class="mb-4 text-sm font-medium">
                                    Work Time
                                </h4>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <!-- Start Time -->
                                    <div class="flex flex-col gap-2">
                                        <label
                                            for="start_time"
                                            class="text-sm font-medium"
                                        >
                                            Start Time
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <DatePicker
                                            id="start_time"
                                            v-model="form.start_time"
                                            show-time
                                            hour-format="12"
                                            size="small"
                                            :invalid="!!form.errors.start_time"
                                        />
                                        <small
                                            v-if="form.errors.start_time"
                                            class="text-red-500"
                                        >
                                            {{ form.errors.start_time }}
                                        </small>
                                    </div>

                                    <!-- End Time -->
                                    <div class="flex flex-col gap-2">
                                        <label
                                            for="end_time"
                                            class="text-sm font-medium"
                                        >
                                            End Time
                                        </label>
                                        <DatePicker
                                            id="end_time"
                                            v-model="form.end_time"
                                            show-time
                                            hour-format="12"
                                            size="small"
                                            :invalid="!!form.errors.end_time"
                                        />
                                        <small
                                            v-if="form.errors.end_time"
                                            class="text-red-500"
                                        >
                                            {{ form.errors.end_time }}
                                        </small>
                                        <small class="text-muted-foreground">
                                            Leave empty for in-progress timecard
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Breaks Section -->
                            <div
                                class="rounded-lg border border-muted bg-muted/20 p-4"
                            >
                                <div
                                    class="mb-4 flex items-center justify-between"
                                >
                                    <h4 class="text-sm font-medium">Breaks</h4>
                                    <Button
                                        label="Add Break"
                                        icon="pi pi-plus"
                                        size="small"
                                        text
                                        @click="addBreak"
                                    />
                                </div>

                                <div
                                    v-if="breaks.length === 0"
                                    class="py-4 text-center text-sm text-muted-foreground"
                                >
                                    No breaks added
                                </div>

                                <div v-else class="flex flex-col gap-4">
                                    <div
                                        v-for="(breakEntry, index) in breaks"
                                        :key="index"
                                        class="flex items-end gap-4 rounded-lg bg-background p-3"
                                    >
                                        <div class="flex-1">
                                            <label
                                                class="text-xs text-muted-foreground"
                                                >Break Start</label
                                            >
                                            <DatePicker
                                                v-model="breakEntry.start_time"
                                                show-time
                                                hour-format="12"
                                                size="small"
                                                class="w-full"
                                            />
                                        </div>
                                        <div class="flex-1">
                                            <label
                                                class="text-xs text-muted-foreground"
                                                >Break End</label
                                            >
                                            <DatePicker
                                                v-model="breakEntry.end_time"
                                                show-time
                                                hour-format="12"
                                                size="small"
                                                class="w-full"
                                            />
                                        </div>
                                        <Button
                                            icon="pi pi-trash"
                                            severity="danger"
                                            text
                                            size="small"
                                            @click="removeBreak(index)"
                                            v-tooltip.top="'Remove break'"
                                        />
                                    </div>
                                </div>
                            </div>
                        </template>

                        <Divider />

                        <div class="flex justify-end gap-2">
                            <Button
                                label="Cancel"
                                severity="secondary"
                                size="small"
                                type="button"
                                @click="navigateBack"
                            />
                            <Button
                                label="Create"
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
