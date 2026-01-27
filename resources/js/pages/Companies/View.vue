<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import Tag from 'primevue/tag';
import { ref } from 'vue';
import { useSmartBack } from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { type Company, type EmployeeCompany } from '@/types/company';

interface EmployeeCompanyWithEmployee extends EmployeeCompany {
    employee?: {
        id: number;
        first_name: string;
        last_name: string;
        profile_picture_url?: string | null;
    };
}

interface Props {
    company: Company;
    companyEmployees?: EmployeeCompanyWithEmployee[];
}

const props = defineProps<Props>();

const { goBack } = useSmartBack('/companies');

const expandedEmployeeRows = ref({});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Companies', href: '/companies' },
    { title: props.company.company_name },
];

function getInitials(): string {
    const words = props.company.company_name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return props.company.company_name.substring(0, 2).toUpperCase();
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function getEmployeeName(ec: EmployeeCompanyWithEmployee): string {
    if (ec.employee) {
        return `${ec.employee.first_name} ${ec.employee.last_name}`;
    }
    return '-';
}

function getEmployeeInitials(ec: EmployeeCompanyWithEmployee): string {
    if (ec.employee) {
        return (ec.employee.first_name.charAt(0) + ec.employee.last_name.charAt(0)).toUpperCase();
    }
    return '?';
}

function navigateToEdit() {
    router.get(`/companies/${props.company.id}/edit`);
}

function navigateToEmployee(employeeId: number) {
    router.get(`/users/${employeeId}`);
}
</script>

<template>
    <Head :title="company.company_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <Button
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        text
                        rounded
                        size="small"
                        @click="goBack"
                    />
                    <h1 class="heading-lg">{{ company.company_name }}</h1>
                    <Tag v-if="company.is_deleted" value="Deleted" severity="danger" />
                    <Tag
                        v-else
                        :value="company.active ? 'Active' : 'Inactive'"
                        :severity="company.active ? 'success' : 'danger'"
                    />
                </div>
                <Button
                    v-if="!company.is_deleted"
                    label="Edit"
                    icon="pi pi-pencil"
                    size="small"
                    @click="navigateToEdit"
                />
            </div>

            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <template #content>
                        <div class="flex flex-col gap-6">
                            <!-- Company Header -->
                            <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                                <Image
                                    v-if="company.logo_url"
                                    :src="company.logo_url"
                                    :alt="company.company_name"
                                    image-class="!h-24 !w-24 rounded-lg object-cover cursor-pointer"
                                    :pt="{ root: { class: 'rounded-lg overflow-hidden' }, previewMask: { class: 'rounded-lg' } }"
                                    preview
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials()"
                                    class="!h-24 !w-24 rounded-lg bg-primary/10 text-3xl text-primary"
                                />
                                <div class="flex flex-col gap-1 text-center sm:text-left">
                                    <h2 class="text-xl font-semibold">{{ company.company_name }}</h2>
                                    <p v-if="company.email" class="text-muted-foreground">{{ company.email }}</p>
                                    <p v-if="company.website" class="text-muted-foreground">
                                        <a :href="company.website" target="_blank" class="hover:underline">{{ company.website }}</a>
                                    </p>
                                </div>
                            </div>

                            <Divider />

                            <!-- Contact Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Contact Information</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Email</span>
                                        <span>{{ company.email ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Phone Number</span>
                                        <span>{{ company.phone_number ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Mobile Number</span>
                                        <span>{{ company.mobile_number ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Website</span>
                                        <span v-if="company.website">
                                            <a :href="company.website" target="_blank" class="text-primary hover:underline">{{ company.website }}</a>
                                        </span>
                                        <span v-else>-</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Address -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Address</h3>
                                <div class="flex flex-col gap-1">
                                    <span v-if="company.address_1">{{ company.address_1 }}</span>
                                    <span v-if="company.address_2">{{ company.address_2 }}</span>
                                    <span v-if="!company.address_1 && !company.address_2" class="text-muted-foreground">
                                        No address provided
                                    </span>
                                </div>
                            </div>

                            <Divider />

                            <!-- Employees -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Employees</h3>
                                <template v-if="companyEmployees && companyEmployees.length > 0">
                                    <DataTable
                                        v-model:expandedRows="expandedEmployeeRows"
                                        :value="companyEmployees"
                                        dataKey="id"
                                        size="small"
                                        stripedRows
                                        class="cursor-pointer rounded-lg border border-border"
                                        @row-click="(e) => e.data.employee && navigateToEmployee(e.data.employee.id)"
                                    >
                                        <Column expander style="width: 3rem" class="!pr-0 lg:hidden" />
                                        <Column header="Employee">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-2">
                                                    <Avatar
                                                        v-if="data.employee?.profile_picture_url"
                                                        :image="data.employee.profile_picture_url"
                                                        shape="circle"
                                                        size="normal"
                                                    />
                                                    <Avatar
                                                        v-else
                                                        :label="getEmployeeInitials(data)"
                                                        shape="circle"
                                                        size="normal"
                                                        class="bg-primary/10 text-primary"
                                                    />
                                                    <span class="font-medium">{{ getEmployeeName(data) }}</span>
                                                </div>
                                            </template>
                                        </Column>
                                        <Column field="designation.designation_name" header="Designation" class="hidden md:table-cell">
                                            <template #body="{ data }">
                                                {{ data.designation?.designation_name ?? '-' }}
                                            </template>
                                        </Column>
                                        <Column field="status_label" header="Type" class="hidden sm:table-cell">
                                            <template #body="{ data }">
                                                {{ data.status_label }}
                                            </template>
                                        </Column>
                                        <Column field="commencement_date" header="Start Date" class="hidden lg:table-cell">
                                            <template #body="{ data }">
                                                {{ formatDate(data.commencement_date) }}
                                            </template>
                                        </Column>
                                        <Column field="left_date" header="End Date" class="hidden xl:table-cell">
                                            <template #body="{ data }">
                                                {{ formatDate(data.left_date) }}
                                            </template>
                                        </Column>
                                        <Column header="Status">
                                            <template #body="{ data }">
                                                <Tag
                                                    :value="data.is_active ? 'Active' : 'Ended'"
                                                    :severity="data.is_active ? 'success' : 'secondary'"
                                                />
                                            </template>
                                        </Column>
                                        <template #expansion="{ data }">
                                            <div class="grid gap-3 p-3 text-sm lg:hidden">
                                                <div class="flex justify-between gap-4 border-b border-border pb-2 md:hidden">
                                                    <span class="shrink-0 text-muted-foreground">Designation</span>
                                                    <span class="text-right">{{ data.designation?.designation_name ?? '-' }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4 border-b border-border pb-2 sm:hidden">
                                                    <span class="shrink-0 text-muted-foreground">Type</span>
                                                    <span class="text-right">{{ data.status_label }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4 border-b border-border pb-2">
                                                    <span class="shrink-0 text-muted-foreground">Start Date</span>
                                                    <span class="text-right">{{ formatDate(data.commencement_date) }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4 xl:hidden">
                                                    <span class="shrink-0 text-muted-foreground">End Date</span>
                                                    <span class="text-right">{{ formatDate(data.left_date) }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </DataTable>
                                </template>
                                <p v-else class="text-muted-foreground">No employees assigned to this company.</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
