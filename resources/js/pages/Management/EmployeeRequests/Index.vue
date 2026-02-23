<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    BreadcrumbItem,
    EmployeeRequest,
    PaginatedData,
} from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { ref, watch } from 'vue';

interface Props {
    requests: PaginatedData<EmployeeRequest>;
    filters: {
        search: string;
        status: string;
    };
    statuses: { label: string; value: string }[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employee Requests', href: '/management/employee-requests' },
];

const expandedRows = ref({});
const search = ref(props.filters.search);
const status = ref(props.filters.status);

const statusOptions = [
    { label: 'All Statuses', value: '' },
    ...props.statuses,
];

function loadData(page = 1) {
    router.get(
        '/management/employee-requests',
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
    router.visit(`/management/employee-requests/${event.data.id}`);
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Employee Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="heading-lg">Employee Requests</h1>
                <Link href="/management/employee-requests/settings">
                    <Button
                        label="Settings"
                        size="small"
                        severity="secondary"
                        outlined
                    />
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <InputText
                    v-model="search"
                    placeholder="Search by name or email..."
                    size="small"
                    class="w-full sm:w-64"
                />
                <Select
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Status"
                    size="small"
                    class="w-full sm:w-48"
                />
            </div>

            <DataTable
                v-model:expandedRows="expandedRows"
                :value="requests.data"
                dataKey="id"
                striped-rows
                size="small"
                lazy
                :rows="requests.per_page"
                :total-records="requests.total"
                :first="(requests.current_page - 1) * requests.per_page"
                paginator
                :rows-per-page-options="[15]"
                class="cursor-pointer overflow-hidden rounded-lg border border-border"
                @page="onPage"
                @row-click="onRowClick"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No employee requests found.
                    </div>
                </template>

                <Column
                    expander
                    :style="{ width: '3rem' }"
                    class="sm:hidden"
                />

                <Column header="Name" :style="{ minWidth: '12rem' }">
                    <template #body="{ data }">
                        <div class="font-medium">{{ data.full_name }}</div>
                    </template>
                </Column>

                <Column
                    header="Email"
                    class="hidden sm:table-cell"
                    :style="{ minWidth: '14rem' }"
                >
                    <template #body="{ data }">
                        {{ data.email }}
                    </template>
                </Column>

                <Column
                    header="Applied"
                    class="hidden md:table-cell"
                    :style="{ width: '8rem' }"
                >
                    <template #body="{ data }">
                        {{ formatDate(data.created_at) }}
                    </template>
                </Column>

                <Column header="Status" :style="{ width: '7rem' }">
                    <template #body="{ data }">
                        <Tag
                            :value="data.status_label"
                            :severity="data.status_severity"
                            class="!text-xs"
                        />
                    </template>
                </Column>

                <template #expansion="{ data }">
                    <div class="space-y-1 p-3 text-sm">
                        <div>
                            <span class="text-muted-foreground">Email: </span>
                            {{ data.email }}
                        </div>
                        <div>
                            <span class="text-muted-foreground">Applied: </span>
                            {{ formatDate(data.created_at) }}
                        </div>
                        <div v-if="data.phone">
                            <span class="text-muted-foreground">Phone: </span>
                            {{ data.phone }}
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
