<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import Image from 'primevue/image';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import { computed, reactive, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Company, type EmployeeContract, type EmployeeInsurance, type PaginatedData } from '@/types';

interface ContractWithEmployee extends EmployeeContract {
    employee?: {
        id: number;
        first_name: string;
        last_name: string;
        profile_picture_url?: string | null;
    };
    employee_is_deleted?: boolean;
}

interface InsuranceWithEmployee extends EmployeeInsurance {
    employee?: {
        id: number;
        first_name: string;
        last_name: string;
        profile_picture_url?: string | null;
    };
    employee_is_deleted?: boolean;
}

interface Filters {
    search?: string;
    status?: string;
    company?: string | number;
    show_deleted?: boolean;
}

interface Props {
    documents: PaginatedData<ContractWithEmployee | InsuranceWithEmployee>;
    type: 'contracts' | 'insurances';
    filters?: Filters;
    companies?: Company[];
}

const props = defineProps<Props>();

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    company: props.filters?.company ?? '',
    showDeleted: props.filters?.show_deleted ?? false,
});

const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Expired', value: 'expired' },
];

const companyOptions = computed(() => [
    { label: 'All Companies', value: '' },
    ...(props.companies ?? []).map((c) => ({ label: c.company_name, value: c.id })),
]);

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

watch(
    () => filters.company,
    () => {
        applyFilters();
    },
);

watch(
    () => filters.showDeleted,
    () => {
        applyFilters();
    },
);

function applyFilters() {
    const params: Record<string, string | number | boolean> = { type: props.type };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.company) params.company = filters.company;
    if (filters.showDeleted) params.show_deleted = true;
    router.get('/documents', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    filters.company = '';
    filters.showDeleted = false;
    router.get('/documents', { type: props.type }, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Documents' }];

const pageTitle = computed(() => (props.type === 'contracts' ? 'Contracts' : 'Insurances'));

const expandedContractRows = ref({});
const expandedInsuranceRows = ref({});

const hasActiveFilters = computed(() => filters.search || filters.status || filters.company || filters.showDeleted);

function switchType(newType: string | number) {
    filters.search = '';
    filters.status = '';
    filters.company = '';
    filters.showDeleted = false;
    router.get('/documents', { type: newType }, { preserveState: true });
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function getEmployeeName(doc: ContractWithEmployee | InsuranceWithEmployee): string {
    if (!doc.employee) return '-';
    return `${doc.employee.first_name} ${doc.employee.last_name}`;
}

function isEmployeeDeleted(doc: ContractWithEmployee | InsuranceWithEmployee): boolean {
    return doc.employee_is_deleted === true;
}

function getInitials(doc: ContractWithEmployee | InsuranceWithEmployee): string {
    if (!doc.employee) return '?';
    const first = doc.employee.first_name?.charAt(0)?.toUpperCase() ?? '';
    const last = doc.employee.last_name?.charAt(0)?.toUpperCase() ?? '';
    return `${first}${last}`;
}

function getProfilePictureUrl(doc: ContractWithEmployee | InsuranceWithEmployee): string | undefined {
    return doc.employee?.profile_picture_url ?? undefined;
}

function onContractRowClick(event: { data: ContractWithEmployee }) {
    router.get(`/documents/contracts/${event.data.id}`);
}

function onInsuranceRowClick(event: { data: InsuranceWithEmployee }) {
    router.get(`/documents/insurances/${event.data.id}`);
}

function navigateToContractEdit(contract: ContractWithEmployee) {
    router.get(`/documents/contracts/${contract.id}/edit`);
}

function navigateToInsuranceEdit(insurance: InsuranceWithEmployee) {
    router.get(`/documents/insurances/${insurance.id}/edit`);
}

function onPage(event: { page: number }) {
    const params: Record<string, string | number | boolean> = {
        type: props.type,
        page: event.page + 1,
    };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.company) params.company = filters.company;
    if (filters.showDeleted) params.show_deleted = true;
    router.get('/documents', params, { preserveState: true });
}

function navigateToCreate() {
    if (props.type === 'contracts') {
        router.get('/documents/contracts/create');
    } else {
        router.get('/documents/insurances/create');
    }
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold">Documents</h1>
                <Button
                    :label="type === 'contracts' ? 'Create Contract' : 'Create Insurance'"
                    icon="pi pi-plus"
                    size="small"
                    @click="navigateToCreate"
                />
            </div>

            <Tabs :value="type" @update:value="switchType">
                <TabList>
                    <Tab value="contracts">Contracts</Tab>
                    <Tab value="insurances">Insurances</Tab>
                </TabList>
            </Tabs>

            <!-- Filter Section -->
            <div
                class="flex flex-col gap-3 rounded-lg border border-sidebar-border/70 bg-surface-50 p-3 dark:border-sidebar-border dark:bg-surface-900 sm:flex-row sm:items-center"
            >
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        :placeholder="
                            type === 'contracts' ? 'Search by employee name or company...' : 'Search by employee name, title, insurer, or policy...'
                        "
                        size="small"
                        fluid
                    />
                </IconField>
                <div class="flex flex-wrap items-center gap-2">
                    <Select
                        v-if="type === 'contracts'"
                        v-model="filters.company"
                        :options="companyOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Company"
                        size="small"
                        class="w-full sm:w-44"
                    />
                    <Select
                        v-model="filters.status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Status"
                        size="small"
                        class="w-full sm:w-36"
                    />
                    <label class="flex cursor-pointer items-center gap-2">
                        <ToggleSwitch v-model="filters.showDeleted" />
                        <span class="whitespace-nowrap text-sm">Show Deleted</span>
                    </label>
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

            <!-- Contracts Table -->
            <DataTable
                v-if="type === 'contracts'"
                v-model:expandedRows="expandedContractRows"
                :value="documents.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="15"
                :total-records="documents.total"
                :first="(documents.current_page - 1) * 15"
                @page="onPage"
                @row-click="onContractRowClick"
                striped-rows
                size="small"
                tableLayout="fixed"
                class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">No contracts found.</div>
                </template>
                <Column expander style="width: 3rem" class="!pr-0 sm:hidden" />
                <Column header="" :style="{ width: '3.5rem', minWidth: '3.5rem', maxWidth: '3.5rem' }" class="!pl-4 !pr-0">
                    <template #body="{ data }">
                        <Image
                            v-if="getProfilePictureUrl(data)"
                            :src="getProfilePictureUrl(data)"
                            :alt="getEmployeeName(data)"
                            image-class="!h-8 !w-8 rounded-full object-cover cursor-pointer"
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
                <Column field="employee" header="Employee" style="width: 20%" class="!pl-3">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span
                                class="font-medium"
                                :class="{ 'text-muted-foreground line-through': isEmployeeDeleted(data) }"
                            >
                                {{ getEmployeeName(data) }}
                            </span>
                            <Tag v-if="isEmployeeDeleted(data)" value="Deleted" severity="danger" class="!text-xs" />
                        </div>
                    </template>
                </Column>
                <Column field="company" header="Company" style="width: 20%" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.company?.company_name ?? '-' }}
                    </template>
                </Column>
                <Column field="start_date" header="Start Date" style="width: 12%" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        {{ formatDate(data.start_date) }}
                    </template>
                </Column>
                <Column field="end_date" header="End Date" style="width: 12%" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        {{ formatDate(data.end_date) }}
                    </template>
                </Column>
                <Column header="Status" style="width: 6rem">
                    <template #body="{ data }">
                        <Tag :value="data.is_active ? 'Active' : 'Expired'" :severity="data.is_active ? 'success' : 'secondary'" />
                    </template>
                </Column>
                <Column header="Doc" style="width: 3rem" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        <i v-if="data.has_document" class="pi pi-file text-muted-foreground" v-tooltip.top="'Has document'" />
                    </template>
                </Column>
                <Column header="" style="width: 3.5rem" class="!pr-4">
                    <template #body="{ data }">
                        <div class="flex justify-end">
                            <Button
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click.stop="navigateToContractEdit(data)"
                                v-tooltip.top="'Edit'"
                            />
                        </div>
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm sm:hidden">
                        <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                            <span class="shrink-0 text-muted-foreground">Company</span>
                            <span class="text-right">{{ data.company?.company_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                            <span class="shrink-0 text-muted-foreground">Start Date</span>
                            <span class="text-right">{{ formatDate(data.start_date) }}</span>
                        </div>
                        <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                            <span class="shrink-0 text-muted-foreground">End Date</span>
                            <span class="text-right">{{ formatDate(data.end_date) }}</span>
                        </div>
                        <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                            <span class="shrink-0 text-muted-foreground">Document</span>
                            <span class="text-right">
                                <i v-if="data.has_document" class="pi pi-file text-primary" />
                                <span v-else>-</span>
                            </span>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <Button
                                label="Edit"
                                icon="pi pi-pencil"
                                severity="secondary"
                                size="small"
                                @click="navigateToContractEdit(data)"
                                class="flex-1"
                            />
                        </div>
                    </div>
                </template>
            </DataTable>

            <!-- Insurances Table -->
            <DataTable
                v-else
                v-model:expandedRows="expandedInsuranceRows"
                :value="documents.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="15"
                :total-records="documents.total"
                :first="(documents.current_page - 1) * 15"
                @page="onPage"
                @row-click="onInsuranceRowClick"
                striped-rows
                size="small"
                tableLayout="fixed"
                class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">No insurances found.</div>
                </template>
                <Column expander style="width: 3rem" class="!pr-0 sm:hidden" />
                <Column header="" :style="{ width: '3.5rem', minWidth: '3.5rem', maxWidth: '3.5rem' }" class="!pl-4 !pr-0">
                    <template #body="{ data }">
                        <Image
                            v-if="getProfilePictureUrl(data)"
                            :src="getProfilePictureUrl(data)"
                            :alt="getEmployeeName(data)"
                            image-class="!h-8 !w-8 rounded-full object-cover cursor-pointer"
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
                <Column field="employee" header="Employee" style="width: 20%" class="!pl-3">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span
                                class="font-medium"
                                :class="{ 'text-muted-foreground line-through': isEmployeeDeleted(data) }"
                            >
                                {{ getEmployeeName(data) }}
                            </span>
                            <Tag v-if="isEmployeeDeleted(data)" value="Deleted" severity="danger" class="!text-xs" />
                        </div>
                    </template>
                </Column>
                <Column field="title" header="Title" style="width: 20%" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        {{ data.title }}
                    </template>
                </Column>
                <Column field="insurer_name" header="Insurer" style="width: 15%" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.insurer_name }}
                    </template>
                </Column>
                <Column field="start_date" header="Start Date" style="width: 12%" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        {{ formatDate(data.start_date) }}
                    </template>
                </Column>
                <Column field="end_date" header="End Date" style="width: 12%" class="hidden xl:table-cell">
                    <template #body="{ data }">
                        {{ formatDate(data.end_date) }}
                    </template>
                </Column>
                <Column header="Status" style="width: 6rem">
                    <template #body="{ data }">
                        <Tag :value="data.is_active ? 'Active' : 'Expired'" :severity="data.is_active ? 'success' : 'secondary'" />
                    </template>
                </Column>
                <Column header="Doc" style="width: 3rem" class="hidden sm:table-cell">
                    <template #body="{ data }">
                        <i v-if="data.has_document" class="pi pi-file text-muted-foreground" v-tooltip.top="'Has document'" />
                    </template>
                </Column>
                <Column header="" style="width: 3.5rem" class="!pr-4">
                    <template #body="{ data }">
                        <div class="flex justify-end">
                            <Button
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click.stop="navigateToInsuranceEdit(data)"
                                v-tooltip.top="'Edit'"
                            />
                        </div>
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm sm:hidden">
                        <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                            <span class="shrink-0 text-muted-foreground">Title</span>
                            <span class="text-right">{{ data.title }}</span>
                        </div>
                        <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                            <span class="shrink-0 text-muted-foreground">Insurer</span>
                            <span class="text-right">{{ data.insurer_name }}</span>
                        </div>
                        <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                            <span class="shrink-0 text-muted-foreground">Start Date</span>
                            <span class="text-right">{{ formatDate(data.start_date) }}</span>
                        </div>
                        <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                            <span class="shrink-0 text-muted-foreground">End Date</span>
                            <span class="text-right">{{ formatDate(data.end_date) }}</span>
                        </div>
                        <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                            <span class="shrink-0 text-muted-foreground">Document</span>
                            <span class="text-right">
                                <i v-if="data.has_document" class="pi pi-file text-primary" />
                                <span v-else>-</span>
                            </span>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <Button
                                label="Edit"
                                icon="pi pi-pencil"
                                severity="secondary"
                                size="small"
                                @click="navigateToInsuranceEdit(data)"
                                class="flex-1"
                            />
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
