<script setup lang="ts">
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
import ToggleSwitch from 'primevue/toggleswitch';
import { useConfirm } from 'primevue/useconfirm';
import { computed, reactive, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type Company,
    type Customer,
    type Employee,
    type PaginatedData,
} from '@/types';

interface Filters {
    search?: string;
    status?: string;
    company?: string | number;
    show_deleted?: boolean;
    per_page?: number;
}

interface Props {
    users: PaginatedData<Employee | Customer>;
    type: 'employees' | 'customers';
    filters?: Filters;
    companies?: Company[];
}

const props = defineProps<Props>();

// Filter state
const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    company: props.filters?.company ?? '',
    showDeleted: props.filters?.show_deleted ?? false,
});

const perPage = ref(props.users.per_page ?? 15);

const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Terminated', value: 'terminated' },
];

const companyOptions = computed(() => [
    { label: 'All Companies', value: '' },
    ...(props.companies ?? []).map((c) => ({
        label: c.company_name,
        value: c.id,
    })),
]);

// Debounce timer for search
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

// Watch for filter changes and apply them
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
    const params: Record<string, string | number | boolean> = {
        type: props.type,
    };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.company) params.company = filters.company;
    if (filters.showDeleted) params.show_deleted = true;
    if (perPage.value !== 15) params.per_page = perPage.value;
    router.get('/users', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    filters.company = '';
    filters.showDeleted = false;
    router.get('/users', { type: props.type }, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users' },
];

const pageTitle = computed(() =>
    props.type === 'employees' ? 'Employees' : 'Customers',
);

const expandedEmployeeRows = ref({});
const expandedCustomerRows = ref({});

const hasActiveFilters = computed(
    () =>
        filters.search ||
        filters.status ||
        filters.company ||
        filters.showDeleted,
);

const confirm = useConfirm();

function switchType(newType: string | number) {
    // Clear filters when switching tabs
    filters.search = '';
    filters.status = '';
    filters.company = '';
    filters.showDeleted = false;
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

function isEmployeeDeleted(employee: Employee): boolean {
    return employee.is_deleted === true;
}

function isEmployee(user: Employee | Customer): user is Employee {
    return 'employee_number' in user;
}

function getFullName(user: Employee | Customer): string {
    return `${user.first_name} ${user.last_name}`;
}

function getEmployeeEmail(employee: Employee): string {
    return employee.user?.email ?? '-';
}

function getEmployeeCompanies(employee: Employee): {
    text: string;
    full: string;
} {
    if (!employee.companies || employee.companies.length === 0) {
        return { text: '-', full: '-' };
    }
    const full = employee.companies.map((c) => c.name).join(', ');
    if (employee.companies.length === 1) {
        return { text: employee.companies[0].name, full };
    }
    const othersCount = employee.companies.length - 1;
    return {
        text: `${employee.companies[0].name} ...and ${othersCount} other${othersCount > 1 ? 's' : ''}`,
        full,
    };
}

function getInitials(user: Employee | Customer): string {
    const first = user.first_name?.charAt(0)?.toUpperCase() ?? '';
    const last = user.last_name?.charAt(0)?.toUpperCase() ?? '';
    return `${first}${last}`;
}

function getProfilePictureUrl(user: Employee | Customer): string | undefined {
    if (isEmployee(user)) {
        return user.profile_picture_url ?? undefined;
    }
    return (user as Customer).image_url ?? undefined;
}

function navigateToCreate() {
    router.get('/users/create');
}

function navigateToCreateCustomer() {
    router.get('/customers/create');
}

function navigateToView(employee: Employee) {
    router.get(`/users/${employee.id}`);
}

function navigateToEdit(employee: Employee) {
    router.get(`/users/${employee.id}/edit`);
}

function navigateToViewCustomer(customer: Customer) {
    router.get(`/customers/${customer.id}`);
}

function navigateToEditCustomer(customer: Customer) {
    router.get(`/customers/${customer.id}/edit`);
}

function onEmployeeRowClick(event: { data: Employee }) {
    navigateToView(event.data);
}

function onCustomerRowClick(event: { data: Customer }) {
    navigateToViewCustomer(event.data);
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

function confirmRestoreEmployee(employee: Employee) {
    confirm.require({
        message: `Are you sure you want to restore ${employee.first_name} ${employee.last_name}?`,
        header: 'Restore User',
        icon: 'pi pi-history',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Restore',
        acceptProps: {
            severity: 'success',
            size: 'small',
        },
        accept: () => {
            router.post(`/users/${employee.id}/restore`);
        },
    });
}

function onPage(event: { page: number; rows: number }) {
    perPage.value = event.rows;
    const params: Record<string, string | number | boolean> = {
        type: props.type,
        page: event.page + 1,
    };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.company) params.company = filters.company;
    if (filters.showDeleted) params.show_deleted = true;
    if (event.rows !== 15) params.per_page = event.rows;
    router.get('/users', params, { preserveState: true });
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <h1 class="heading-lg">Users</h1>
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
            <div
                class="filter-section flex flex-col gap-3 sm:flex-row sm:items-center"
            >
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        :placeholder="
                            type === 'employees'
                                ? 'Search by name or email...'
                                : 'Search by name, email, phone, or company...'
                        "
                        size="small"
                        fluid
                    />
                </IconField>
                <div
                    v-if="type === 'employees'"
                    class="flex flex-wrap items-center gap-2"
                >
                    <Select
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
                        <span class="text-sm whitespace-nowrap"
                            >Show Deleted</span
                        >
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
                :rows="perPage"
                :rows-per-page-options="[10, 15, 25, 50]"
                :total-records="users.total"
                :first="(users.current_page - 1) * perPage"
                @page="onPage"
                @row-click="onEmployeeRowClick"
                striped-rows
                size="small"
                tableLayout="fixed"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No employees found.
                    </div>
                </template>
                <Column expander style="width: 3rem" class="!pr-0 md:hidden" />
                <Column
                    header=""
                    :style="{
                        width: '3.5rem',
                        minWidth: '3.5rem',
                        maxWidth: '3.5rem',
                    }"
                    class="!pr-0 !pl-4"
                >
                    <template #body="{ data }">
                        <div v-if="getProfilePictureUrl(data)" @click.stop>
                            <Image
                                :src="getProfilePictureUrl(data)"
                                :alt="getFullName(data)"
                                image-class="!h-8 !w-8 rounded-full object-cover cursor-pointer"
                                :pt="{
                                    root: {
                                        class: 'rounded-full overflow-hidden',
                                    },
                                    previewMask: { class: 'rounded-full' },
                                }"
                                preview
                            />
                        </div>
                        <Avatar
                            v-else
                            :label="getInitials(data)"
                            shape="circle"
                            class="!h-8 !w-8 bg-primary/10 text-primary"
                        />
                    </template>
                </Column>
                <Column
                    field="name"
                    header="Name"
                    style="width: 25%"
                    class="!pl-3"
                >
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span
                                class="block truncate font-medium"
                                :class="{
                                    'text-muted-foreground line-through':
                                        isEmployeeDeleted(data),
                                }"
                            >
                                {{ getFullName(data) }}
                            </span>
                            <Tag
                                v-if="isEmployeeDeleted(data)"
                                value="Deleted"
                                severity="danger"
                                class="!text-xs"
                            />
                        </div>
                    </template>
                </Column>
                <Column
                    field="companies"
                    header="Companies"
                    style="width: 25%"
                    class="hidden md:table-cell"
                >
                    <template #body="{ data }">
                        <span
                            class="block truncate"
                            v-tooltip.top="getEmployeeCompanies(data).full"
                        >
                            {{ getEmployeeCompanies(data).text }}
                        </span>
                    </template>
                </Column>
                <Column
                    field="email"
                    header="Email"
                    style="width: 25%"
                    class="hidden md:table-cell"
                >
                    <template #body="{ data }">
                        <span class="block truncate">{{
                            getEmployeeEmail(data)
                        }}</span>
                    </template>
                </Column>
                <Column field="status" header="Status" style="width: 6rem">
                    <template #body="{ data }">
                        <Tag
                            :value="getEmployeeStatus(data)"
                            :severity="getStatusSeverity(data)"
                        />
                    </template>
                </Column>
                <Column header="" style="width: 5.5rem" class="!pr-4">
                    <template #body="{ data }">
                        <div
                            v-if="isEmployeeDeleted(data)"
                            class="flex justify-end gap-1"
                        >
                            <Button
                                icon="pi pi-history"
                                severity="success"
                                text
                                rounded
                                size="small"
                                @click.stop="confirmRestoreEmployee(data)"
                                v-tooltip.top="'Restore'"
                            />
                        </div>
                        <div v-else class="flex justify-end gap-1">
                            <Button
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click.stop="navigateToEdit(data)"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click.stop="confirmDeleteEmployee(data)"
                            />
                        </div>
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-3 p-3 text-sm md:hidden">
                        <div
                            class="flex justify-between gap-4 border-b border-border pb-2"
                        >
                            <span class="shrink-0 text-muted-foreground"
                                >Companies</span
                            >
                            <span class="text-right">{{
                                getEmployeeCompanies(data).full
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between gap-4 border-b border-border pb-2"
                        >
                            <span class="shrink-0 text-muted-foreground"
                                >Email</span
                            >
                            <span class="truncate text-right">{{
                                getEmployeeEmail(data)
                            }}</span>
                        </div>
                        <div
                            v-if="isEmployeeDeleted(data)"
                            class="flex gap-2 pt-2"
                        >
                            <Button
                                label="Restore"
                                icon="pi pi-history"
                                severity="success"
                                size="small"
                                @click="confirmRestoreEmployee(data)"
                                class="flex-1"
                            />
                        </div>
                        <div v-else class="flex gap-2 pt-2">
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
                :rows="perPage"
                :rows-per-page-options="[10, 15, 25, 50]"
                :total-records="users.total"
                :first="(users.current_page - 1) * perPage"
                @page="onPage"
                @row-click="onCustomerRowClick"
                striped-rows
                size="small"
                tableLayout="fixed"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No customers found.
                    </div>
                </template>
                <Column expander style="width: 3rem" class="!pr-0 md:hidden" />
                <Column
                    header=""
                    :style="{
                        width: '3.5rem',
                        minWidth: '3.5rem',
                        maxWidth: '3.5rem',
                    }"
                    class="!pr-0 !pl-4"
                >
                    <template #body="{ data }">
                        <div v-if="getProfilePictureUrl(data)" @click.stop>
                            <Image
                                :src="getProfilePictureUrl(data)"
                                :alt="getFullName(data)"
                                image-class="!h-8 !w-8 rounded-full object-cover cursor-pointer"
                                :pt="{
                                    root: {
                                        class: 'rounded-full overflow-hidden',
                                    },
                                    previewMask: { class: 'rounded-full' },
                                }"
                                preview
                            />
                        </div>
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
                <Column
                    field="email"
                    header="Email"
                    class="hidden md:table-cell"
                >
                    <template #body="{ data }">
                        {{ data.email ?? '-' }}
                    </template>
                </Column>
                <Column
                    field="phone"
                    header="Phone"
                    class="hidden md:table-cell"
                >
                    <template #body="{ data }">
                        {{ data.phone ?? '-' }}
                    </template>
                </Column>
                <Column
                    field="company_name"
                    header="Company"
                    class="hidden lg:table-cell"
                >
                    <template #body="{ data }">
                        {{ data.company_name ?? '-' }}
                    </template>
                </Column>
                <Column
                    field="customer_since"
                    header="Customer Since"
                    class="hidden xl:table-cell"
                >
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
                        <div
                            class="flex justify-between border-b border-border pb-2"
                        >
                            <span class="text-muted-foreground">Email</span>
                            <span>{{ data.email ?? '-' }}</span>
                        </div>
                        <div
                            class="flex justify-between border-b border-border pb-2"
                        >
                            <span class="text-muted-foreground">Phone</span>
                            <span>{{ data.phone ?? '-' }}</span>
                        </div>
                        <div
                            class="flex justify-between border-b border-border pb-2"
                        >
                            <span class="text-muted-foreground">Company</span>
                            <span>{{ data.company_name ?? '-' }}</span>
                        </div>
                        <div
                            class="flex justify-between border-b border-border pb-2"
                        >
                            <span class="text-muted-foreground"
                                >Customer Since</span
                            >
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
