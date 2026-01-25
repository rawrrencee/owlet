<script setup lang="ts">
import { useSmartBack } from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type Employee,
    type EmployeeCompany,
    type EmployeeContract,
    type EmployeeInsurance,
    type WorkOSUser,
} from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import Tag from 'primevue/tag';
import { reactive } from 'vue';

interface Props {
    employee: Employee;
    workosUser: WorkOSUser | null;
    role?: string;
    employeeCompanies?: EmployeeCompany[];
    contracts?: EmployeeContract[];
    insurances?: EmployeeInsurance[];
}

const props = defineProps<Props>();

const { goBack } = useSmartBack('/users');

const expandedCompanyRows = reactive({});
const expandedContractRows = reactive({});
const expandedInsuranceRows = reactive({});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/users' },
    { title: `${props.employee.first_name} ${props.employee.last_name}` },
];

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function getInitials(): string {
    const first = props.employee.first_name?.charAt(0)?.toUpperCase() ?? '';
    const last = props.employee.last_name?.charAt(0)?.toUpperCase() ?? '';
    return `${first}${last}`;
}

function getStatusLabel(status: string): string {
    const labels: Record<string, string> = {
        'FT': 'Full Time',
        'PT': 'Part Time',
        'CT': 'Contract',
        'CA': 'Casual',
    };
    return labels[status] ?? status;
}

function formatCurrency(value: string | number | null): string {
    if (value === null || value === undefined) return '-';
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('en-SG', { style: 'currency', currency: 'SGD' }).format(num);
}

function getLeaveDisplay(entitled: number, taken: number): string {
    const remaining = Math.max(0, entitled - taken);
    return `${remaining}/${entitled}`;
}

function viewContractDocument(contract: EmployeeContract) {
    if (contract.external_document_url) {
        window.open(contract.external_document_url, '_blank');
    } else if (contract.document_url) {
        window.open(contract.document_url, '_blank');
    }
}

function viewInsuranceDocument(insurance: EmployeeInsurance) {
    if (insurance.external_document_url) {
        window.open(insurance.external_document_url, '_blank');
    } else if (insurance.document_url) {
        window.open(insurance.document_url, '_blank');
    }
}

function navigateToEdit() {
    router.get(`/users/${props.employee.id}/edit`);
}
</script>

<template>
    <Head :title="`${employee.first_name} ${employee.last_name}`" />

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
                    <h1 class="text-2xl font-semibold">{{ employee.first_name }} {{ employee.last_name }}</h1>
                    <Tag
                        :value="employee.termination_date ? 'Terminated' : 'Active'"
                        :severity="employee.termination_date ? 'danger' : 'success'"
                    />
                </div>
                <Button
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
                            <!-- Profile Header -->
                            <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                                <Image
                                    v-if="employee.profile_picture_url"
                                    :src="employee.profile_picture_url"
                                    :alt="`${employee.first_name} ${employee.last_name}`"
                                    image-class="!h-24 !w-24 rounded-full object-cover cursor-pointer"
                                    :pt="{ root: { class: 'rounded-full overflow-hidden' }, previewMask: { class: 'rounded-full' } }"
                                    preview
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials()"
                                    shape="circle"
                                    class="!h-24 !w-24 bg-primary/10 text-3xl text-primary"
                                />
                                <div class="flex flex-col gap-1 text-center sm:text-left">
                                    <h2 class="text-xl font-semibold">{{ employee.first_name }} {{ employee.last_name }}</h2>
                                    <p v-if="employee.chinese_name" class="text-muted-foreground">{{ employee.chinese_name }}</p>
                                    <p v-if="workosUser?.email" class="text-muted-foreground">{{ workosUser.email }}</p>
                                    <p v-if="employee.employee_number" class="text-sm text-muted-foreground">Employee #{{ employee.employee_number }}</p>
                                </div>
                            </div>

                            <Divider />

                            <!-- Basic Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Basic Information</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">NRIC</span>
                                        <span>{{ employee.nric ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Phone</span>
                                        <span>{{ employee.phone ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Mobile</span>
                                        <span>{{ employee.mobile ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Role</span>
                                        <span class="capitalize">{{ role ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Personal Details -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Personal Details</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Gender</span>
                                        <span>{{ employee.gender ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Date of Birth</span>
                                        <span>{{ formatDate(employee.date_of_birth) }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Nationality</span>
                                        <span>{{ employee.nationality ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Race</span>
                                        <span>{{ employee.race ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Residency Status</span>
                                        <span>{{ employee.residency_status ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">PR Conversion Date</span>
                                        <span>{{ formatDate(employee.pr_conversion_date) }}</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Address -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Address</h3>
                                <div class="flex flex-col gap-1">
                                    <span v-if="employee.address_1">{{ employee.address_1 }}</span>
                                    <span v-if="employee.address_2">{{ employee.address_2 }}</span>
                                    <span v-if="employee.city || employee.state || employee.postal_code">
                                        {{ [employee.city, employee.state, employee.postal_code].filter(Boolean).join(', ') }}
                                    </span>
                                    <span v-if="employee.country">{{ employee.country }}</span>
                                    <span v-if="!employee.address_1 && !employee.address_2 && !employee.city && !employee.country" class="text-muted-foreground">
                                        No address provided
                                    </span>
                                </div>
                            </div>

                            <Divider />

                            <!-- Employment Details -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Employment Details</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Hire Date</span>
                                        <span>{{ formatDate(employee.hire_date) }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Status</span>
                                        <Tag
                                            :value="employee.termination_date ? 'Terminated' : 'Active'"
                                            :severity="employee.termination_date ? 'danger' : 'success'"
                                        />
                                    </div>
                                    <div v-if="employee.termination_date" class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Termination Date</span>
                                        <span>{{ formatDate(employee.termination_date) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Company Assignments -->
                            <template v-if="employeeCompanies && employeeCompanies.length > 0">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Company Assignments</h3>
                                    <DataTable
                                        v-model:expandedRows="expandedCompanyRows"
                                        :value="employeeCompanies"
                                        dataKey="id"
                                        size="small"
                                        stripedRows
                                        class="rounded-lg border border-sidebar-border/70"
                                    >
                                        <Column expander style="width: 3rem" class="!pr-0 md:hidden" />
                                        <Column field="company.company_name" header="Company">
                                            <template #body="{ data }">
                                                {{ data.company?.company_name ?? '-' }}
                                            </template>
                                        </Column>
                                        <Column field="designation.designation_name" header="Designation" class="hidden md:table-cell">
                                            <template #body="{ data }">
                                                {{ data.designation?.designation_name ?? '-' }}
                                            </template>
                                        </Column>
                                        <Column field="status" header="Type" class="hidden sm:table-cell">
                                            <template #body="{ data }">
                                                {{ getStatusLabel(data.status) }}
                                            </template>
                                        </Column>
                                        <Column field="commencement_date" header="Start Date" class="hidden lg:table-cell">
                                            <template #body="{ data }">
                                                {{ formatDate(data.commencement_date) }}
                                            </template>
                                        </Column>
                                        <Column field="is_active" header="Status">
                                            <template #body="{ data }">
                                                <Tag
                                                    :value="data.is_active ? 'Active' : 'Inactive'"
                                                    :severity="data.is_active ? 'success' : 'secondary'"
                                                />
                                            </template>
                                        </Column>
                                        <template #expansion="{ data }">
                                            <div class="grid gap-3 p-3 text-sm md:hidden">
                                                <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                                                    <span class="shrink-0 text-muted-foreground">Designation</span>
                                                    <span class="text-right">{{ data.designation?.designation_name ?? '-' }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2 sm:hidden">
                                                    <span class="shrink-0 text-muted-foreground">Type</span>
                                                    <span class="text-right">{{ getStatusLabel(data.status) }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2 lg:hidden">
                                                    <span class="shrink-0 text-muted-foreground">Start Date</span>
                                                    <span class="text-right">{{ formatDate(data.commencement_date) }}</span>
                                                </div>
                                                <div v-if="data.left_date" class="flex justify-between gap-4">
                                                    <span class="shrink-0 text-muted-foreground">Left Date</span>
                                                    <span class="text-right">{{ formatDate(data.left_date) }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </DataTable>
                                </div>
                            </template>

                            <!-- Contracts -->
                            <template v-if="contracts && contracts.length > 0">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Contracts</h3>
                                    <DataTable
                                        v-model:expandedRows="expandedContractRows"
                                        :value="contracts"
                                        dataKey="id"
                                        size="small"
                                        stripedRows
                                        class="rounded-lg border border-sidebar-border/70"
                                    >
                                        <Column expander style="width: 3rem" class="!pr-0 sm:hidden" />
                                        <Column field="company.company_name" header="Company">
                                            <template #body="{ data }">
                                                {{ data.company?.company_name ?? '-' }}
                                            </template>
                                        </Column>
                                        <Column field="start_date" header="Start Date" class="hidden sm:table-cell">
                                            <template #body="{ data }">
                                                {{ formatDate(data.start_date) }}
                                            </template>
                                        </Column>
                                        <Column field="end_date" header="End Date" class="hidden md:table-cell">
                                            <template #body="{ data }">
                                                {{ formatDate(data.end_date) }}
                                            </template>
                                        </Column>
                                        <Column field="salary_amount" header="Salary" class="hidden lg:table-cell">
                                            <template #body="{ data }">
                                                {{ formatCurrency(data.salary_amount) }}
                                            </template>
                                        </Column>
                                        <Column header="Annual Leave" class="hidden sm:table-cell">
                                            <template #body="{ data }">
                                                {{ getLeaveDisplay(data.annual_leave_entitled, data.annual_leave_taken) }}
                                            </template>
                                        </Column>
                                        <Column header="Sick Leave" class="hidden sm:table-cell">
                                            <template #body="{ data }">
                                                {{ getLeaveDisplay(data.sick_leave_entitled, data.sick_leave_taken) }}
                                            </template>
                                        </Column>
                                        <Column header="Status">
                                            <template #body="{ data }">
                                                <Tag
                                                    :value="data.is_active ? 'Active' : 'Expired'"
                                                    :severity="data.is_active ? 'success' : 'secondary'"
                                                />
                                            </template>
                                        </Column>
                                        <Column header="Doc" class="w-12">
                                            <template #body="{ data }">
                                                <Button
                                                    v-if="data.has_document"
                                                    icon="pi pi-file"
                                                    severity="secondary"
                                                    text
                                                    rounded
                                                    size="small"
                                                    @click="viewContractDocument(data)"
                                                    v-tooltip.top="'View Document'"
                                                />
                                            </template>
                                        </Column>
                                        <template #expansion="{ data }">
                                            <div class="grid gap-3 p-3 text-sm sm:hidden">
                                                <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                                                    <span class="shrink-0 text-muted-foreground">Start Date</span>
                                                    <span class="text-right">{{ formatDate(data.start_date) }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                                                    <span class="shrink-0 text-muted-foreground">End Date</span>
                                                    <span class="text-right">{{ formatDate(data.end_date) }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                                                    <span class="shrink-0 text-muted-foreground">Salary</span>
                                                    <span class="text-right">{{ formatCurrency(data.salary_amount) }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                                                    <span class="shrink-0 text-muted-foreground">Annual Leave</span>
                                                    <span class="text-right">{{ getLeaveDisplay(data.annual_leave_entitled, data.annual_leave_taken) }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4">
                                                    <span class="shrink-0 text-muted-foreground">Sick Leave</span>
                                                    <span class="text-right">{{ getLeaveDisplay(data.sick_leave_entitled, data.sick_leave_taken) }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </DataTable>
                                </div>
                            </template>

                            <!-- Insurances -->
                            <template v-if="insurances && insurances.length > 0">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Insurances</h3>
                                    <DataTable
                                        v-model:expandedRows="expandedInsuranceRows"
                                        :value="insurances"
                                        dataKey="id"
                                        size="small"
                                        stripedRows
                                        class="rounded-lg border border-sidebar-border/70"
                                    >
                                        <Column expander style="width: 3rem" class="!pr-0 sm:hidden" />
                                        <Column field="title" header="Title">
                                            <template #body="{ data }">
                                                {{ data.title }}
                                            </template>
                                        </Column>
                                        <Column field="insurer_name" header="Insurer" class="hidden sm:table-cell">
                                            <template #body="{ data }">
                                                {{ data.insurer_name }}
                                            </template>
                                        </Column>
                                        <Column field="policy_number" header="Policy #" class="hidden md:table-cell">
                                            <template #body="{ data }">
                                                {{ data.policy_number }}
                                            </template>
                                        </Column>
                                        <Column field="start_date" header="Start Date" class="hidden lg:table-cell">
                                            <template #body="{ data }">
                                                {{ formatDate(data.start_date) }}
                                            </template>
                                        </Column>
                                        <Column field="end_date" header="End Date" class="hidden lg:table-cell">
                                            <template #body="{ data }">
                                                {{ formatDate(data.end_date) }}
                                            </template>
                                        </Column>
                                        <Column header="Status">
                                            <template #body="{ data }">
                                                <Tag
                                                    :value="data.is_active ? 'Active' : 'Expired'"
                                                    :severity="data.is_active ? 'success' : 'secondary'"
                                                />
                                            </template>
                                        </Column>
                                        <Column header="Doc" class="w-12">
                                            <template #body="{ data }">
                                                <Button
                                                    v-if="data.has_document"
                                                    icon="pi pi-file"
                                                    severity="secondary"
                                                    text
                                                    rounded
                                                    size="small"
                                                    @click="viewInsuranceDocument(data)"
                                                    v-tooltip.top="'View Document'"
                                                />
                                            </template>
                                        </Column>
                                        <template #expansion="{ data }">
                                            <div class="grid gap-3 p-3 text-sm sm:hidden">
                                                <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                                                    <span class="shrink-0 text-muted-foreground">Insurer</span>
                                                    <span class="text-right">{{ data.insurer_name }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                                                    <span class="shrink-0 text-muted-foreground">Policy #</span>
                                                    <span class="text-right">{{ data.policy_number }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4 border-b border-sidebar-border/50 pb-2">
                                                    <span class="shrink-0 text-muted-foreground">Start Date</span>
                                                    <span class="text-right">{{ formatDate(data.start_date) }}</span>
                                                </div>
                                                <div class="flex justify-between gap-4">
                                                    <span class="shrink-0 text-muted-foreground">End Date</span>
                                                    <span class="text-right">{{ formatDate(data.end_date) }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </DataTable>
                                </div>
                            </template>

                            <Divider />

                            <!-- Bank Account Details -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Bank Account Details</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Bank Name</span>
                                        <span>{{ employee.bank_name ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Account Number</span>
                                        <span>{{ employee.bank_account_number ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Emergency Contact -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Emergency Contact</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Contact Name</span>
                                        <span>{{ employee.emergency_name ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Relationship</span>
                                        <span>{{ employee.emergency_relationship ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Contact Number</span>
                                        <span>{{ employee.emergency_contact ?? '-' }}</span>
                                    </div>
                                </div>
                                <div v-if="employee.emergency_address_1 || employee.emergency_address_2" class="mt-4 flex flex-col gap-1">
                                    <span class="text-sm text-muted-foreground">Address</span>
                                    <span v-if="employee.emergency_address_1">{{ employee.emergency_address_1 }}</span>
                                    <span v-if="employee.emergency_address_2">{{ employee.emergency_address_2 }}</span>
                                </div>
                            </div>

                            <!-- Notes -->
                            <template v-if="employee.notes">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Notes</h3>
                                    <p class="whitespace-pre-wrap text-sm">{{ employee.notes }}</p>
                                </div>
                            </template>

                            <!-- WorkOS Info -->
                            <template v-if="workosUser">
                                <Divider />
                                <div>
                                    <h3 class="mb-3 text-sm font-medium text-muted-foreground">WorkOS Account Info</h3>
                                    <div class="grid gap-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-muted-foreground">WorkOS ID</span>
                                            <span class="font-mono text-xs">{{ workosUser.id }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-muted-foreground">Email Verified</span>
                                            <span>{{ workosUser.emailVerified ? 'Yes' : 'No' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
