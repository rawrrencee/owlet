<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { useConfirm } from 'primevue/useconfirm';
import { computed, reactive, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Designation, type PaginatedData } from '@/types';

interface Filters {
    search?: string;
}

interface Props {
    designations: PaginatedData<Designation>;
    filters?: Filters;
}

const props = defineProps<Props>();

const filters = reactive({
    search: props.filters?.search ?? '',
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(
    () => filters.search,
    () => {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 300);
    },
);

function applyFilters() {
    const params: Record<string, string | number> = {};
    if (filters.search) params.search = filters.search;
    router.get('/designations', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    router.get('/designations', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Designations' },
];

const expandedRows = ref({});
const hasActiveFilters = computed(() => filters.search);
const confirm = useConfirm();

function navigateToCreate() {
    router.get('/designations/create');
}

function navigateToEdit(designation: Designation) {
    router.get(`/designations/${designation.id}/edit`);
}

function confirmDelete(designation: Designation) {
    confirm.require({
        message: `Are you sure you want to delete "${designation.designation_name}"?`,
        header: 'Delete Designation',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Delete',
        acceptProps: {
            severity: 'danger',
            size: 'small',
        },
        accept: () => {
            router.delete(`/designations/${designation.id}`);
        },
    });
}

function onPage(event: { page: number }) {
    const params: Record<string, string | number> = { page: event.page + 1 };
    if (filters.search) params.search = filters.search;
    router.get('/designations', params, { preserveState: true });
}
</script>

<template>
    <Head title="Designations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold">Designations</h1>
                <Button
                    label="Create Designation"
                    icon="pi pi-plus"
                    size="small"
                    @click="navigateToCreate"
                />
            </div>

            <!-- Filter Section -->
            <div class="flex flex-col gap-3 rounded-lg border border-sidebar-border/70 bg-surface-50 p-3 dark:border-sidebar-border dark:bg-surface-900 sm:flex-row sm:items-center">
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        placeholder="Search by name or code..."
                        size="small"
                        fluid
                    />
                </IconField>
                <Button
                    v-if="hasActiveFilters"
                    icon="pi pi-times"
                    severity="secondary"
                    text
                    size="small"
                    @click="clearFilters"
                    v-tooltip.top="'Clear filters'"
                />
            </div>

            <!-- Designations Table -->
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="designations.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="15"
                :total-records="designations.total"
                :first="((designations.current_page - 1) * 15)"
                @page="onPage"
                striped-rows
                size="small"
                class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No designations found.
                    </div>
                </template>
                <Column expander class="w-12 !pr-0 md:hidden" />
                <Column field="designation_name" header="Name" class="!pl-4">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.designation_name }}</span>
                    </template>
                </Column>
                <Column field="designation_code" header="Code" class="hidden md:table-cell">
                    <template #body="{ data }">
                        <code class="rounded bg-surface-100 px-2 py-1 text-sm dark:bg-surface-800">
                            {{ data.designation_code }}
                        </code>
                    </template>
                </Column>
                <Column header="" class="w-24 !pr-4">
                    <template #body="{ data }">
                        <div class="flex justify-end gap-1">
                            <Button
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click="navigateToEdit(data)"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="confirmDelete(data)"
                            />
                        </div>
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm md:hidden">
                        <div class="flex justify-between border-b border-sidebar-border/50 pb-2">
                            <span class="text-muted-foreground">Code</span>
                            <code class="rounded bg-surface-100 px-2 py-0.5 text-sm dark:bg-surface-800">
                                {{ data.designation_code }}
                            </code>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <Button
                                label="Edit"
                                icon="pi pi-pencil"
                                severity="secondary"
                                size="small"
                                @click="navigateToEdit(data)"
                                class="flex-1"
                            />
                            <Button
                                label="Delete"
                                icon="pi pi-trash"
                                severity="danger"
                                size="small"
                                @click="confirmDelete(data)"
                                class="flex-1"
                            />
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>

        <ConfirmDialog />
    </AppLayout>
</template>
