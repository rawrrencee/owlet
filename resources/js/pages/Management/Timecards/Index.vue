<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import { ref } from 'vue';
import TimecardCalendar from '@/components/timecards/TimecardCalendar.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, CalendarDayData } from '@/types';

interface EmployeeOption {
    id: number;
    name: string;
    employee_number: string | null;
}

interface Props {
    month: string;
    monthlyData: Record<string, CalendarDayData>;
    employees: EmployeeOption[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Timecards' },
];

const activeTab = ref(0);
const selectedEmployee = ref<number | null>(null);

const employeeOptions = props.employees.map((e) => ({
    label: `${e.name}${e.employee_number ? ` (#${e.employee_number})` : ''}`,
    value: e.id,
}));

function handleDateClick(date: string) {
    router.visit(`/management/timecards/date/${date}`);
}

function handleMonthChange(month: Date) {
    // Format date in local timezone to avoid UTC conversion issues
    const year = month.getFullYear();
    const monthNum = String(month.getMonth() + 1).padStart(2, '0');
    const day = String(month.getDate()).padStart(2, '0');
    const monthString = `${year}-${monthNum}-${day}`;
    router.get('/management/timecards', { month: monthString }, { preserveState: false });
}

function handleEmployeeSearch() {
    if (selectedEmployee.value) {
        router.visit(`/management/timecards/employee/${selectedEmployee.value}`);
    }
}

function navigateToCreate() {
    router.visit('/management/timecards/create');
}
</script>

<template>
    <Head title="Manage Timecards" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="heading-lg">Manage Timecards</h1>
                <Button
                    label="Create Timecard"
                    icon="pi pi-plus"
                    size="small"
                    @click="navigateToCreate"
                />
            </div>

            <Tabs v-model:value="activeTab">
                <TabList>
                    <Tab :value="0">Calendar</Tab>
                    <Tab :value="1">Search by Employee</Tab>
                </TabList>
                <TabPanels>
                    <!-- Calendar Tab -->
                    <TabPanel :value="0">
                        <Card class="mt-4">
                            <template #content>
                                <TimecardCalendar
                                    :month="month"
                                    :data="monthlyData"
                                    :show-employee-count="true"
                                    @date-click="handleDateClick"
                                    @month-change="handleMonthChange"
                                />
                            </template>
                        </Card>
                    </TabPanel>

                    <!-- Search Tab -->
                    <TabPanel :value="1">
                        <Card class="mt-4">
                            <template #content>
                                <div class="flex flex-col gap-4">
                                    <p class="text-muted-foreground">
                                        Search for an employee to view their timecards.
                                    </p>
                                    <div class="flex flex-col gap-3 sm:flex-row">
                                        <Select
                                            v-model="selectedEmployee"
                                            :options="employeeOptions"
                                            option-label="label"
                                            option-value="value"
                                            placeholder="Select an employee"
                                            filter
                                            size="small"
                                            class="w-full sm:w-80"
                                        />
                                        <Button
                                            label="View Timecards"
                                            icon="pi pi-search"
                                            size="small"
                                            :disabled="!selectedEmployee"
                                            @click="handleEmployeeSearch"
                                        />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </AppLayout>
</template>
