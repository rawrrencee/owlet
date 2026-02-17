<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, LeaveBalance, LeaveRequest, PaginatedData } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { ref, watch } from 'vue';

interface Props {
    balances: LeaveBalance[];
    leaveRequests: PaginatedData<LeaveRequest>;
    filters: {
        status: string;
        search: string;
    };
    breadcrumbs: BreadcrumbItem[];
}

const props = defineProps<Props>();

const expandedRows = ref({});
const search = ref(props.filters.search);
const status = ref(props.filters.status);

const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Pending', value: 'pending' },
    { label: 'Approved', value: 'approved' },
    { label: 'Rejected', value: 'rejected' },
    { label: 'Cancelled', value: 'cancelled' },
];

function loadData(page = 1) {
    router.get(
        '/leave',
        {
            page,
            status: status.value || undefined,
            search: search.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadData(1), 300);
});

watch(status, () => loadData(1));

function onPage(event: any) {
    loadData(event.page + 1);
}

function onRowClick(event: any) {
    router.visit(`/leave/${event.data.id}`);
}

function statusSeverity(
    val: string,
): 'warn' | 'success' | 'danger' | 'secondary' {
    const map: Record<string, 'warn' | 'success' | 'danger' | 'secondary'> = {
        pending: 'warn',
        approved: 'success',
        rejected: 'danger',
        cancelled: 'secondary',
    };
    return map[val] ?? 'secondary';
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function halfDayLabel(half: string): string {
    if (half === 'am') return '(AM)';
    if (half === 'pm') return '(PM)';
    return '';
}
</script>

<template>
    <Head title="My Leave" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <h1 class="heading-lg">My Leave</h1>
                <Button
                    label="Apply for Leave"
                    icon="pi pi-plus"
                    size="small"
                    @click="router.visit('/leave/create')"
                />
            </div>

            <!-- Balance Cards -->
            <div
                v-if="balances.length > 0"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="balance in balances"
                    :key="balance.leave_type.id"
                    class="rounded-lg border border-border bg-card p-4"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <div
                            v-if="balance.leave_type.color"
                            class="h-3 w-3 shrink-0 rounded-full"
                            :style="{
                                backgroundColor: balance.leave_type.color,
                            }"
                        ></div>
                        <span class="text-sm font-medium">{{
                            balance.leave_type.name
                        }}</span>
                    </div>
                    <div
                        v-if="balance.leave_type.requires_balance"
                        class="flex items-baseline gap-4"
                    >
                        <div>
                            <div class="text-2xl font-semibold">
                                {{ balance.remaining_days }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                Remaining
                            </div>
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ balance.taken_days }} /
                            {{ balance.entitled_days }} used
                        </div>
                    </div>
                    <div v-else class="text-xs text-muted-foreground">
                        No balance tracking
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <InputText
                    v-model="search"
                    placeholder="Search by leave type..."
                    size="small"
                    class="w-full sm:w-64"
                />
                <Select
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    size="small"
                    class="w-full sm:w-48"
                />
            </div>

            <!-- Leave Requests Table -->
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="leaveRequests.data"
                dataKey="id"
                striped-rows
                size="small"
                lazy
                :rows="leaveRequests.per_page"
                :total-records="leaveRequests.total"
                :first="(leaveRequests.current_page - 1) * leaveRequests.per_page"
                paginator
                :rows-per-page-options="[15]"
                class="cursor-pointer overflow-hidden rounded-lg border border-border"
                @page="onPage"
                @row-click="onRowClick"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No leave requests found.
                    </div>
                </template>

                <Column
                    expander
                    :style="{ width: '3rem' }"
                    class="sm:hidden"
                />

                <Column header="Type">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <div
                                v-if="data.leave_type?.color"
                                class="h-2.5 w-2.5 shrink-0 rounded-full"
                                :style="{
                                    backgroundColor: data.leave_type.color,
                                }"
                            ></div>
                            <span class="font-medium">{{
                                data.leave_type?.name ?? 'Unknown'
                            }}</span>
                        </div>
                    </template>
                </Column>

                <Column
                    header="Dates"
                    class="hidden sm:table-cell"
                    :style="{ width: '30%' }"
                >
                    <template #body="{ data }">
                        <div class="text-sm">
                            {{ formatDate(data.start_date) }}
                            <span class="text-xs text-muted-foreground">{{
                                halfDayLabel(data.start_half_day)
                            }}</span>
                            <template
                                v-if="data.start_date !== data.end_date"
                            >
                                &mdash; {{ formatDate(data.end_date) }}
                                <span
                                    class="text-xs text-muted-foreground"
                                    >{{
                                        halfDayLabel(data.end_half_day)
                                    }}</span
                                >
                            </template>
                        </div>
                    </template>
                </Column>

                <Column
                    header="Days"
                    :style="{ width: '5rem' }"
                    class="hidden sm:table-cell"
                >
                    <template #body="{ data }">
                        {{ data.total_days }}
                    </template>
                </Column>

                <Column header="Status" :style="{ width: '7rem' }">
                    <template #body="{ data }">
                        <Tag
                            :value="data.status_label"
                            :severity="statusSeverity(data.status)"
                            class="!text-xs"
                        />
                    </template>
                </Column>

                <template #expansion="{ data }">
                    <div class="space-y-2 p-3 text-sm">
                        <div>
                            <span class="text-muted-foreground">Dates: </span>
                            {{ formatDate(data.start_date) }}
                            {{ halfDayLabel(data.start_half_day) }}
                            <template
                                v-if="data.start_date !== data.end_date"
                            >
                                &mdash; {{ formatDate(data.end_date) }}
                                {{ halfDayLabel(data.end_half_day) }}
                            </template>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Days: </span>
                            {{ data.total_days }}
                        </div>
                        <div v-if="data.reason">
                            <span class="text-muted-foreground"
                                >Reason: </span
                            >
                            {{ data.reason }}
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
