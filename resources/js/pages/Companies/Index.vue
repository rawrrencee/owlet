<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Company, type PaginatedData } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import Image from 'primevue/image';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { computed, reactive, watch } from 'vue';

interface Filters {
    search?: string;
    status?: string;
}

interface Props {
    companies: PaginatedData<Company>;
    filters?: Filters;
}

const props = defineProps<Props>();

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
});

const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];

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

watch(
    () => filters.status,
    () => {
        applyFilters();
    },
);

function applyFilters() {
    const params: Record<string, string | number> = {};
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    router.get('/companies', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    router.get('/companies', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Companies' },
];

const expandedRows = reactive({});
const hasActiveFilters = computed(() => filters.search || filters.status);
const confirm = useConfirm();

function getInitials(company: Company): string {
    const words = company.company_name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return company.company_name.substring(0, 2).toUpperCase();
}

function navigateToCreate() {
    router.get('/companies/create');
}

function navigateToEdit(company: Company) {
    router.get(`/companies/${company.id}/edit`);
}

function confirmDelete(company: Company) {
    confirm.require({
        message: `Are you sure you want to delete "${company.company_name}"?`,
        header: 'Delete Company',
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
            router.delete(`/companies/${company.id}`);
        },
    });
}

function onPage(event: { page: number }) {
    const params: Record<string, string | number> = { page: event.page + 1 };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    router.get('/companies', params, { preserveState: true });
}
</script>

<template>
    <Head title="Companies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold">Companies</h1>
                <Button
                    label="Create Company"
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
                        placeholder="Search by name, email, or phone..."
                        size="small"
                        fluid
                    />
                </IconField>
                <div class="flex items-center gap-2">
                    <Select
                        v-model="filters.status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Status"
                        size="small"
                        class="w-full sm:w-36"
                    />
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
            </div>

            <!-- Companies Table -->
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="companies.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="15"
                :total-records="companies.total"
                :first="((companies.current_page - 1) * 15)"
                @page="onPage"
                striped-rows
                size="small"
                class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No companies found.
                    </div>
                </template>
                <Column expander class="w-12 !pr-0 md:hidden" />
                <Column header="" class="w-12 !pl-4 !pr-0">
                    <template #body="{ data }">
                        <Image
                            v-if="data.logo_url"
                            :src="data.logo_url"
                            :alt="data.company_name"
                            image-class="h-8 w-8 rounded-full object-cover cursor-pointer"
                            :pt="{ root: { class: 'rounded-full overflow-hidden' }, previewMask: { class: 'rounded-full' } }"
                            preview
                        />
                        <Avatar
                            v-else
                            :label="getInitials(data)"
                            shape="circle"
                            class="!h-8 !w-8 bg-primary/10 text-primary"
                        />
                    </template>
                </Column>
                <Column field="company_name" header="Company Name" class="!pl-3">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.company_name }}</span>
                    </template>
                </Column>
                <Column field="email" header="Email" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.email ?? '-' }}
                    </template>
                </Column>
                <Column field="phone_number" header="Phone" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.phone_number ?? '-' }}
                    </template>
                </Column>
                <Column field="active" header="Status">
                    <template #body="{ data }">
                        <Tag
                            :value="data.active ? 'Active' : 'Inactive'"
                            :severity="data.active ? 'success' : 'danger'"
                        />
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
                            <span class="text-muted-foreground">Email</span>
                            <span>{{ data.email ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-sidebar-border/50 pb-2">
                            <span class="text-muted-foreground">Phone</span>
                            <span>{{ data.phone_number ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-sidebar-border/50 pb-2">
                            <span class="text-muted-foreground">Website</span>
                            <span>{{ data.website ?? '-' }}</span>
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
