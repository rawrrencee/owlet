<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';
import TimecardCalendar from '@/components/timecards/TimecardCalendar.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SubordinateInfo, TeamTimecardDayData } from '@/types';

interface Props {
    month: string;
    monthlyData: Record<string, TeamTimecardDayData>;
    subordinates: SubordinateInfo[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Team Timecards' },
];

const searchQuery = ref('');
const selectedSubordinate = ref<number | null>(null);

const filteredSubordinates = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.subordinates;
    }
    const query = searchQuery.value.toLowerCase();
    return props.subordinates.filter(
        (s) =>
            s.name.toLowerCase().includes(query) ||
            s.employee_number?.toLowerCase().includes(query)
    );
});

const subordinateOptions = computed(() => [
    { label: 'All Team Members', value: null },
    ...props.subordinates.map((s) => ({
        label: s.name,
        value: s.id,
    })),
]);

// Convert monthlyData to CalendarDayData format for the calendar
const calendarData = computed(() => {
    const data: Record<string, { date: string; total_hours: number; employee_count: number }> = {};
    for (const [date, dayData] of Object.entries(props.monthlyData)) {
        data[date] = {
            date: dayData.date,
            total_hours: dayData.total_hours,
            employee_count: dayData.employee_count,
        };
    }
    return data;
});

function handleDateClick(date: string) {
    if (selectedSubordinate.value) {
        router.visit(`/my-team-timecards/${selectedSubordinate.value}/${date}`);
    } else {
        // Show date overview - could navigate to a page showing all subordinates for that date
        // For now, we'll just navigate with the first subordinate if there's data
        const dayData = props.monthlyData[date];
        if (dayData?.employees?.length) {
            router.visit(`/my-team-timecards/${dayData.employees[0].id}/${date}`);
        }
    }
}

function handleMonthChange(month: Date) {
    const monthString = month.toISOString().split('T')[0];
    router.get('/my-team-timecards', { month: monthString }, { preserveState: true });
}

function handleSubordinateChange() {
    if (selectedSubordinate.value) {
        router.visit(`/my-team-timecards/${selectedSubordinate.value}`);
    }
}

function navigateToSubordinate(subordinateId: number) {
    router.visit(`/my-team-timecards/${subordinateId}`);
}

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}
</script>

<template>
    <Head title="Team Timecards" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="heading-lg">Team Timecards</h1>
                    <Tag
                        :value="`${subordinates.length} member${subordinates.length === 1 ? '' : 's'}`"
                        severity="secondary"
                    />
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section flex flex-col gap-3 sm:flex-row sm:items-center">
                <IconField class="flex-1 sm:max-w-md">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="searchQuery"
                        placeholder="Search team members..."
                        size="small"
                        fluid
                    />
                </IconField>
                <Select
                    v-model="selectedSubordinate"
                    :options="subordinateOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="View specific member"
                    size="small"
                    class="w-full sm:w-64"
                    @change="handleSubordinateChange"
                />
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Team Members List -->
                <div class="lg:col-span-1">
                    <Card>
                        <template #title>
                            <span class="text-base font-semibold">Team Members</span>
                        </template>
                        <template #content>
                            <div class="flex max-h-96 flex-col gap-2 overflow-y-auto">
                                <div
                                    v-for="member in filteredSubordinates"
                                    :key="member.id"
                                    class="flex cursor-pointer items-center gap-3 rounded-lg p-2 transition-colors hover:bg-muted/50"
                                    @click="navigateToSubordinate(member.id)"
                                >
                                    <Avatar
                                        v-if="member.profile_picture_url"
                                        :image="member.profile_picture_url"
                                        shape="circle"
                                        class="shrink-0"
                                    />
                                    <Avatar
                                        v-else
                                        :label="getInitials(member.name)"
                                        shape="circle"
                                        class="shrink-0 bg-primary/10 text-primary"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium">{{ member.name }}</p>
                                        <p
                                            v-if="member.employee_number"
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            #{{ member.employee_number }}
                                        </p>
                                    </div>
                                    <i class="pi pi-chevron-right text-muted-foreground"></i>
                                </div>

                                <div
                                    v-if="filteredSubordinates.length === 0"
                                    class="py-4 text-center text-muted-foreground"
                                >
                                    No team members found.
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
                                :data="calendarData"
                                :show-employee-count="true"
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
