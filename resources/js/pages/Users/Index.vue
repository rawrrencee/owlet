<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
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
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { computed, reactive, watch } from 'vue';

interface Employee {
    id: number;
    first_name: string;
    last_name: string;
    employee_number: string | null;
    phone: string | null;
    hire_date: string | null;
    termination_date: string | null;
    profile_picture_url: string | null;
}

interface Customer {
    id: number;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
    company_name: string | null;
    customer_since: string | null;
    loyalty_points: number;
    image_url: string | null;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Filters {
    search?: string;
    status?: string;
}

interface Props {
    users: PaginatedData<Employee | Customer>;
    type: 'employees' | 'customers';
    filters?: Filters;
}

const props = defineProps<Props>();

// Filter state
const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
});

const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Terminated', value: 'terminated' },
];

// Debounce timer for search
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

// Watch for filter changes and apply them
watch(
    () => filters.search,
    (newValue) => {
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
    const params: Record<string, string | number> = { type: props.type };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    router.get('/users', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    router.get('/users', { type: props.type }, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users' },
];

const pageTitle = computed(() =>
    props.type === 'employees' ? 'Employees' : 'Customers',
);

const expandedEmployeeRows = reactive({});
const expandedCustomerRows = reactive({});

const hasActiveFilters = computed(() => filters.search || filters.status);

const confirm = useConfirm();

function switchType(newType: string | number) {
    // Clear filters when switching tabs
    filters.search = '';
    filters.status = '';
    router.get('/users', { type: newType }, { preserveState: true });
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function getEmployeeStatus(employee: Employee): 'Active' | 'Terminated' {
    return employee.termination_date ? 'Terminated' : 'Active';
}

function getStatusSeverity(employee: Employee): 'success' | 'danger' {
    return employee.termination_date ? 'danger' : 'success';
}

function isEmployee(user: Employee | Customer): user is Employee {
    return 'employee_number' in user;
}

function getFullName(user: Employee | Customer): string {
    return `${user.last_name}, ${user.first_name}`;
}

function getInitials(user: Employee | Customer): string {
    const first = user.first_name?.charAt(0)?.toUpperCase() ?? '';
    const last = user.last_name?.charAt(0)?.toUpperCase() ?? '';
    return `${first}${last}`;
}

function getProfilePictureUrl(user: Employee | Customer): string | null {
    if (isEmployee(user)) {
        return user.profile_picture_url;
    }
    return (user as Customer).image_url;
}

function navigateToCreate() {
    router.get('/users/create');
}

function navigateToCreateCustomer() {
    router.get('/customers/create');
}

function navigateToEdit(employee: Employee) {
    router.get(`/users/${employee.id}/edit`);
}

function navigateToEditCustomer(customer: Customer) {
    router.get(`/customers/${customer.id}/edit`);
}

function confirmDeleteEmployee(employee: Employee) {
    confirm.require({
        message: `Are you sure you want to delete ${employee.first_name} ${employee.last_name}? This will also remove their WorkOS account if synced.`,
        header: 'Delete User',
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
            router.delete(`/users/${employee.id}`);
        },
    });
}

function confirmDeleteCustomer(customer: Customer) {
    confirm.require({
        message: `Are you sure you want to delete ${customer.first_name} ${customer.last_name}?`,
        header: 'Delete Customer',
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
            router.delete(`/customers/${customer.id}`);
        },
    });
}

function onPage(event: { page: number }) {
    const params: Record<string, string | number> = {
        type: props.type,
        page: event.page + 1,
    };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    router.get('/users', params, { preserveState: true });
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold">Users</h1>
                <Button
                    v-if="type === 'employees'"
                    label="Create User"
                    icon="pi pi-plus"
                    size="small"
                    @click="navigateToCreate"
                />
                <Button
                    v-else
                    label="Create Customer"
                    icon="pi pi-plus"
                    size="small"
                    @click="navigateToCreateCustomer"
                />
            </div>

            <Tabs :value="type" @update:value="switchType">
                <TabList>
                    <Tab value="employees">Employees</Tab>
                    <Tab value="customers">Customers</Tab>
                </TabList>
            </Tabs>

            <!-- Filter Section -->
            <div class="flex flex-col gap-3 rounded-lg border border-sidebar-border/70 bg-surface-50 p-3 dark:border-sidebar-border dark:bg-surface-900 sm:flex-row sm:items-center">
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        :placeholder="type === 'employees' ? 'Search by name, employee #, or phone...' : 'Search by name, email, phone, or company...'"
                        size="small"
                        fluid
                    />
                </IconField>
                <div v-if="type === 'employees'" class="flex items-center gap-2">
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
                <Button
                    v-else-if="hasActiveFilters"
                    icon="pi pi-times"
                    severity="secondary"
                    text
                    size="small"
                    @click="clearFilters"
                    v-tooltip.top="'Clear filters'"
                />
            </div>

            <!-- Employees Table -->
            <DataTable
                v-if="type === 'employees'"
                v-model:expandedRows="expandedEmployeeRows"
                :value="users.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="15"
                :total-records="users.total"
                :first="((users.current_page - 1) * 15)"
                @page="onPage"
                striped-rows
                size="small"
                class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No employees found.
                    </div>
                </template>
                <Column expander class="w-12 !pr-0 md:hidden" />
                <Column header="" class="w-12 !pl-4 !pr-0">
                    <template #body="{ data }">
                        <Image
                            v-if="getProfilePictureUrl(data)"
                            :src="getProfilePictureUrl(data)"
                            :alt="getFullName(data)"
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
                <Column field="name" header="Name" class="!pl-3">
                    <template #body="{ data }">
                        <span class="font-medium">{{ getFullName(data) }}</span>
                    </template>
                </Column>
                <Column field="employee_number" header="Employee #" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.employee_number ?? '-' }}
                    </template>
                </Column>
                <Column field="phone" header="Phone" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.phone ?? '-' }}
                    </template>
                </Column>
                <Column field="hire_date" header="Hire Date" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        {{ formatDate(data.hire_date) }}
                    </template>
                </Column>
                <Column field="status" header="Status">
                    <template #body="{ data }">
                        <Tag
                            :value="getEmployeeStatus(data)"
                            :severity="getStatusSeverity(data)"
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
                                @click="confirmDeleteEmployee(data)"
                            />
                        </div>
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm md:hidden">
                        <div class="flex justify-between border-b border-sidebar-border/50 pb-2">
                            <span class="text-muted-foreground">Employee #</span>
                            <span>{{ data.employee_number ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-sidebar-border/50 pb-2">
                            <span class="text-muted-foreground">Phone</span>
                            <span>{{ data.phone ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-sidebar-border/50 pb-2">
                            <span class="text-muted-foreground">Hire Date</span>
                            <span>{{ formatDate(data.hire_date) }}</span>
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
                                @click="confirmDeleteEmployee(data)"
                                class="flex-1"
                            />
                        </div>
                    </div>
                </template>
            </DataTable>

            <!-- Customers Table -->
            <DataTable
                v-else
                v-model:expandedRows="expandedCustomerRows"
                :value="users.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="15"
                :total-records="users.total"
                :first="((users.current_page - 1) * 15)"
                @page="onPage"
                striped-rows
                size="small"
                class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No customers found.
                    </div>
                </template>
                <Column expander class="w-12 !pr-0 md:hidden" />
                <Column header="" class="w-12 !pl-4 !pr-0">
                    <template #body="{ data }">
                        <Image
                            v-if="getProfilePictureUrl(data)"
                            :src="getProfilePictureUrl(data)"
                            :alt="getFullName(data)"
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
                <Column field="name" header="Name" class="!pl-3">
                    <template #body="{ data }">
                        <span class="font-medium">{{ getFullName(data) }}</span>
                    </template>
                </Column>
                <Column field="email" header="Email" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.email ?? '-' }}
                    </template>
                </Column>
                <Column field="phone" header="Phone" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.phone ?? '-' }}
                    </template>
                </Column>
                <Column field="company_name" header="Company" class="hidden lg:table-cell">
                    <template #body="{ data }">
                        {{ data.company_name ?? '-' }}
                    </template>
                </Column>
                <Column field="customer_since" header="Customer Since" class="hidden xl:table-cell">
                    <template #body="{ data }">
                        {{ formatDate(data.customer_since) }}
                    </template>
                </Column>
                <Column field="loyalty_points" header="Points">
                    <template #body="{ data }">
                        {{ data.loyalty_points?.toLocaleString() ?? 0 }}
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
                                @click="navigateToEditCustomer(data)"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="confirmDeleteCustomer(data)"
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
                            <span>{{ data.phone ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-sidebar-border/50 pb-2">
                            <span class="text-muted-foreground">Company</span>
                            <span>{{ data.company_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-sidebar-border/50 pb-2">
                            <span class="text-muted-foreground">Customer Since</span>
                            <span>{{ formatDate(data.customer_since) }}</span>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <Button
                                label="Edit"
                                icon="pi pi-pencil"
                                severity="secondary"
                                size="small"
                                @click="navigateToEditCustomer(data)"
                                class="flex-1"
                            />
                            <Button
                                label="Delete"
                                icon="pi pi-trash"
                                severity="danger"
                                size="small"
                                @click="confirmDeleteCustomer(data)"
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
