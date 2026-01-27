<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import DatePicker from 'primevue/datepicker';
import Divider from 'primevue/divider';
import Select from 'primevue/select';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

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
    { title: 'Management' },
    { title: 'Timecards', href: '/management/timecards' },
    { title: 'Create' },
];

const form = useForm({
    employee_id: props.preselectedEmployee?.id || null as number | null,
    store_id: null as number | null,
    date: props.preselectedDate ? new Date(props.preselectedDate) : new Date(),
    status: 'COMPLETED',
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
    const dateString = form.date instanceof Date
        ? form.date.toISOString().split('T')[0]
        : form.date;

    form.transform((data) => ({
        ...data,
        date: dateString,
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
                    <form @submit.prevent="handleSubmit" class="flex flex-col gap-4">
                        <!-- Employee -->
                        <div class="flex flex-col gap-2">
                            <label for="employee_id" class="text-sm font-medium">
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
                            <small v-if="form.errors.employee_id" class="text-red-500">
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
                            <small v-if="form.errors.store_id" class="text-red-500">
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
                            <small v-if="form.errors.status" class="text-red-500">
                                {{ form.errors.status }}
                            </small>
                        </div>

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
