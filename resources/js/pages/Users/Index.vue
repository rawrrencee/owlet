<script setup lang="ts">
import Pagination, { type PaginationMeta } from '@/components/Pagination.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Employee {
    id: number;
    first_name: string;
    last_name: string;
    employee_number: string | null;
    phone: string | null;
    hire_date: string | null;
    termination_date: string | null;
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

interface Props {
    users: PaginatedData<Employee | Customer>;
    type: 'employees' | 'customers';
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users' },
];

const pageTitle = computed(() =>
    props.type === 'employees' ? 'Employees' : 'Customers'
);

function switchType(newType: string) {
    router.get('/users', { type: newType }, { preserveState: true });
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function getEmployeeStatus(employee: Employee): string {
    return employee.termination_date ? 'Terminated' : 'Active';
}

function isEmployee(user: Employee | Customer): user is Employee {
    return 'employee_number' in user;
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Users</h1>
            </div>

            <Tabs :default-value="type" @update:model-value="switchType">
                <TabsList>
                    <TabsTrigger value="employees">Employees</TabsTrigger>
                    <TabsTrigger value="customers">Customers</TabsTrigger>
                </TabsList>
            </Tabs>

            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <!-- Employees Table -->
                <Table v-if="type === 'employees'">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Employee #</TableHead>
                            <TableHead>Phone</TableHead>
                            <TableHead>Hire Date</TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="user in users.data"
                            :key="user.id"
                        >
                            <template v-if="isEmployee(user)">
                                <TableCell class="font-medium">
                                    {{ user.last_name }}, {{ user.first_name }}
                                </TableCell>
                                <TableCell>{{ user.employee_number ?? '-' }}</TableCell>
                                <TableCell>{{ user.phone ?? '-' }}</TableCell>
                                <TableCell>{{ formatDate(user.hire_date) }}</TableCell>
                                <TableCell>
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                            user.termination_date
                                                ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                                : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        ]"
                                    >
                                        {{ getEmployeeStatus(user) }}
                                    </span>
                                </TableCell>
                            </template>
                        </TableRow>
                        <TableRow v-if="users.data.length === 0">
                            <TableCell colspan="5" class="text-center text-muted-foreground">
                                No employees found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Customers Table -->
                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Phone</TableHead>
                            <TableHead>Company</TableHead>
                            <TableHead>Customer Since</TableHead>
                            <TableHead>Loyalty Points</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="user in users.data"
                            :key="user.id"
                        >
                            <template v-if="!isEmployee(user)">
                                <TableCell class="font-medium">
                                    {{ user.last_name }}, {{ user.first_name }}
                                </TableCell>
                                <TableCell>{{ user.email ?? '-' }}</TableCell>
                                <TableCell>{{ user.phone ?? '-' }}</TableCell>
                                <TableCell>{{ user.company_name ?? '-' }}</TableCell>
                                <TableCell>{{ formatDate(user.customer_since) }}</TableCell>
                                <TableCell>{{ user.loyalty_points.toLocaleString() }}</TableCell>
                            </template>
                        </TableRow>
                        <TableRow v-if="users.data.length === 0">
                            <TableCell colspan="6" class="text-center text-muted-foreground">
                                No customers found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <Pagination
                :meta="{
                    current_page: users.current_page,
                    last_page: users.last_page,
                    from: users.from,
                    to: users.to,
                    total: users.total,
                    links: users.links,
                }"
            />
        </div>
    </AppLayout>
</template>
