<script setup lang="ts">
import BackButton from '@/components/BackButton.vue';
import TimecardSummaryCard from '@/components/timecards/TimecardSummaryCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Timecard } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import { computed } from 'vue';

interface GroupedTimecard {
    employee: {
        id: number;
        name: string;
        employee_number: string | null;
        profile_picture_url: string | null;
    };
    timecards: { data: Timecard[] };
    total_hours: number;
}

interface Props {
    date: string;
    dateFormatted: string;
    groupedTimecards: GroupedTimecard[];
    totalHours: number;
    employeeCount: number;
}

const props = defineProps<Props>();

// Get highlight_employee from query params
const highlightEmployeeId = computed(() => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('highlight_employee');
    return id ? parseInt(id, 10) : null;
});

// Reorder grouped timecards to put highlighted employee first
const orderedGroupedTimecards = computed(() => {
    if (!highlightEmployeeId.value) {
        return props.groupedTimecards;
    }

    const highlighted = props.groupedTimecards.filter(
        (g) => g.employee.id === highlightEmployeeId.value,
    );
    const others = props.groupedTimecards.filter(
        (g) => g.employee.id !== highlightEmployeeId.value,
    );

    return [...highlighted, ...others];
});

function isHighlighted(employeeId: number): boolean {
    return highlightEmployeeId.value === employeeId;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Timecards', href: '/management/timecards' },
    { title: props.dateFormatted },
];

function formatHours(hours: number): string {
    if (hours === 0) return '0h';
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
}

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function navigateToEmployee(employeeId: number) {
    router.visit(`/management/timecards/employee/${employeeId}`);
}

function navigateToCreate() {
    router.visit(`/management/timecards/create?date=${props.date}`);
}
</script>

<template>
    <Head :title="`Timecards - ${dateFormatted}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <BackButton fallback-url="/management/timecards" />
                    <div>
                        <h1 class="heading-lg">{{ dateFormatted }}</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ employeeCount }} employee{{
                                employeeCount === 1 ? '' : 's'
                            }}
                            - Total: {{ formatHours(totalHours) }}
                        </p>
                    </div>
                </div>
                <Button
                    label="Add Timecard"
                    icon="pi pi-plus"
                    size="small"
                    @click="navigateToCreate"
                />
            </div>

            <!-- Grouped by Employee -->
            <div
                v-if="orderedGroupedTimecards.length > 0"
                class="flex flex-col gap-4 sm:gap-6"
            >
                <template
                    v-for="(group, index) in orderedGroupedTimecards"
                    :key="group.employee.id"
                >
                    <!-- Divider after highlighted employee -->
                    <Divider
                        v-if="highlightEmployeeId && index === 1"
                        class="my-2"
                    />

                    <div
                        class="flex flex-col gap-3 rounded-lg p-2 transition-all"
                        :class="{
                            'bg-primary/5 ring-2 ring-primary': isHighlighted(
                                group.employee.id,
                            ),
                            'opacity-75':
                                highlightEmployeeId &&
                                !isHighlighted(group.employee.id),
                        }"
                    >
                        <!-- Employee Header -->
                        <div
                            class="flex cursor-pointer items-center gap-2 rounded-lg p-2 transition-colors sm:gap-3 sm:p-3"
                            :class="{
                                'bg-primary/10 hover:bg-primary/15':
                                    isHighlighted(group.employee.id),
                                'bg-muted/50 hover:bg-muted': !isHighlighted(
                                    group.employee.id,
                                ),
                            }"
                            @click="navigateToEmployee(group.employee.id)"
                        >
                            <Avatar
                                v-if="group.employee.profile_picture_url"
                                :image="group.employee.profile_picture_url"
                                shape="circle"
                                class="shrink-0"
                            />
                            <Avatar
                                v-else
                                :label="getInitials(group.employee.name)"
                                shape="circle"
                                class="shrink-0 bg-primary/10 text-primary"
                            />
                            <div class="flex-1">
                                <p class="font-semibold">
                                    {{ group.employee.name }}
                                </p>
                                <p
                                    v-if="group.employee.employee_number"
                                    class="text-sm text-muted-foreground"
                                >
                                    #{{ group.employee.employee_number }}
                                </p>
                            </div>
                            <Tag
                                :value="formatHours(group.total_hours)"
                                severity="success"
                            />
                            <i
                                class="pi pi-chevron-right text-muted-foreground"
                            ></i>
                        </div>

                        <!-- Timecards -->
                        <div
                            class="ml-1 flex flex-col gap-3 border-l-2 pl-2 sm:ml-4 sm:pl-4"
                            :class="{
                                'border-primary': isHighlighted(
                                    group.employee.id,
                                ),
                                'border-muted': !isHighlighted(
                                    group.employee.id,
                                ),
                            }"
                        >
                            <TimecardSummaryCard
                                v-for="timecard in group.timecards.data"
                                :key="timecard.id"
                                :timecard="timecard"
                                :show-link="true"
                                :link-url="`/management/timecards/${timecard.id}`"
                            />
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <Card v-else>
                <template #content>
                    <div
                        class="flex flex-col items-center justify-center gap-4 py-8"
                    >
                        <i
                            class="pi pi-clock text-4xl text-muted-foreground"
                        ></i>
                        <div class="text-center">
                            <h3 class="font-medium">No timecards</h3>
                            <p class="text-sm text-muted-foreground">
                                No time entries recorded for this date.
                            </p>
                        </div>
                        <Button
                            label="Create Timecard"
                            icon="pi pi-plus"
                            size="small"
                            @click="navigateToCreate"
                        />
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
