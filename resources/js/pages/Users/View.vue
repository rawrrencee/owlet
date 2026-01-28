<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import Message from 'primevue/message';
import OrganizationChart from 'primevue/organizationchart';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import { computed, onMounted, reactive, ref } from 'vue';
import { useSmartBack } from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type AppPageProps,
    type BreadcrumbItem,
    type Employee,
    type EmployeeCompany,
    type EmployeeContract,
    type EmployeeHierarchyData,
    type EmployeeInsurance,
    type EmployeeStore,
    type WorkOSUser,
} from '@/types';

interface Props {
    employee: Employee;
    workosUser: WorkOSUser | null;
    role?: string;
    employeeCompanies?: EmployeeCompany[];
    contracts?: EmployeeContract[];
    insurances?: EmployeeInsurance[];
    stores?: EmployeeStore[];
}

const props = defineProps<Props>();

const { goBack } = useSmartBack('/users');

// Check if current user is admin (for showing hierarchy tab)
const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

// Hierarchy data for org chart
const hierarchyLoading = ref(true);
const hierarchyData = ref<EmployeeHierarchyData | null>(null);

async function fetchHierarchyData() {
    if (!isAdmin.value) return;

    hierarchyLoading.value = true;
    try {
        const response = await fetch(`/users/${props.employee.id}/hierarchy`, {
            headers: {
                Accept: 'application/json',
            },
        });
        const data = await response.json();
        hierarchyData.value = data;
    } catch (error) {
        console.error('Failed to fetch hierarchy data:', error);
    } finally {
        hierarchyLoading.value = false;
    }
}

onMounted(() => {
    if (isAdmin.value) {
        fetchHierarchyData();
    }
});

function getHierarchyInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function getTierColor(tier: number): string {
    const colors: Record<number, string> = {
        1: 'secondary',
        2: 'info',
        3: 'warn',
        4: 'success',
        5: 'danger',
    };
    return colors[tier] || 'secondary';
}

// Pending confirmation status
const isPendingConfirmation = computed(() => !props.workosUser && !!props.employee.pending_email);
const isResendingInvitation = ref(false);

function resendInvitation() {
    if (isResendingInvitation.value) return;

    isResendingInvitation.value = true;
    router.post(`/users/${props.employee.id}/resend-invitation`, {}, {
        preserveScroll: true,
        onFinish: () => {
            isResendingInvitation.value = false;
        },
    });
}

// Active tab state
const activeTab = ref('basic');

const expandedCompanyRows = reactive({});
const expandedContractRows = reactive({});
const expandedInsuranceRows = reactive({});
const expandedStoreRows = reactive({});

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

function formatBoolean(value: boolean): string {
    return value ? 'Yes' : 'No';
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
    router.get(`/users/${props.employee.id}/edit`, { tab: activeTab.value });
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
                    <h1 class="heading-lg">{{ employee.first_name }} {{ employee.last_name }}</h1>
                    <Tag v-if="employee.is_deleted" value="Deleted" severity="danger" />
                    <Tag
                        v-else
                        :value="employee.termination_date ? 'Terminated' : 'Active'"
                        :severity="employee.termination_date ? 'danger' : 'success'"
                    />
                </div>
                <Button
                    v-if="!employee.is_deleted"
                    label="Edit"
                    icon="pi pi-pencil"
                    size="small"
                    @click="navigateToEdit"
                />
            </div>

            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <template #content>
                        <Tabs v-model:value="activeTab" scrollable>
                            <TabList>
                                <Tab value="basic">Basic Info</Tab>
                                <Tab value="companies">Companies</Tab>
                                <Tab value="contracts">Contracts</Tab>
                                <Tab value="insurances">Insurances</Tab>
                                <Tab value="stores">Stores</Tab>
                                <Tab v-if="isAdmin" value="hierarchy">Hierarchy</Tab>
                            </TabList>
                            <TabPanels>
                                <TabPanel value="basic">
                                    <div class="flex flex-col gap-6 pt-4">
                                        <!-- Pending Confirmation Banner -->
                                        <Message v-if="isPendingConfirmation" severity="warn" :closable="false">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                <span>
                                                    This account is pending confirmation. An invitation was sent to
                                                    <strong>{{ employee.pending_email }}</strong>.
                                                </span>
                                                <Button
                                                    label="Resend Invitation"
                                                    icon="pi pi-send"
                                                    size="small"
                                                    severity="warn"
                                                    :loading="isResendingInvitation"
                                                    @click="resendInvitation"
                                                />
                                            </div>
                                        </Message>

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
                                                <p v-if="workosUser?.email || employee.pending_email" class="text-muted-foreground">
                                                    {{ workosUser?.email ?? employee.pending_email }}
                                                </p>
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
                                                        class="w-fit"
                                                    />
                                                </div>
                                                <div v-if="employee.termination_date" class="flex flex-col gap-1">
                                                    <span class="text-sm text-muted-foreground">Termination Date</span>
                                                    <span>{{ formatDate(employee.termination_date) }}</span>
                                                </div>
                                            </div>
                                        </div>

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
                                                <div class="prose prose-sm dark:prose-invert max-w-none" v-html="employee.notes"></div>
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
                                </TabPanel>

                                <TabPanel value="companies">
                                    <div class="pt-4">
                                        <h3 class="mb-4 text-lg font-medium">Company Assignments</h3>
                                        <template v-if="employeeCompanies && employeeCompanies.length > 0">
                                            <DataTable
                                                v-model:expandedRows="expandedCompanyRows"
                                                :value="employeeCompanies"
                                                dataKey="id"
                                                size="small"
                                                stripedRows
                                                class="rounded-lg border border-border"
                                            >
                                                <Column expander style="width: 3rem" class="!pr-0 lg:hidden" />
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
                                                        {{ data.status_label ?? getStatusLabel(data.status) }}
                                                    </template>
                                                </Column>
                                                <Column field="levy_amount" header="Levy" class="hidden xl:table-cell">
                                                    <template #body="{ data }">
                                                        {{ formatCurrency(data.levy_amount) }}
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
                                                <Column field="is_active" header="Status">
                                                    <template #body="{ data }">
                                                        <Tag
                                                            :value="data.is_active ? 'Active' : 'Inactive'"
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
                                                            <span class="text-right">{{ data.status_label ?? getStatusLabel(data.status) }}</span>
                                                        </div>
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2 xl:hidden">
                                                            <span class="shrink-0 text-muted-foreground">Levy Amount</span>
                                                            <span class="text-right">{{ formatCurrency(data.levy_amount) }}</span>
                                                        </div>
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2">
                                                            <span class="shrink-0 text-muted-foreground">Start Date</span>
                                                            <span class="text-right">{{ formatDate(data.commencement_date) }}</span>
                                                        </div>
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2 xl:hidden">
                                                            <span class="shrink-0 text-muted-foreground">End Date</span>
                                                            <span class="text-right">{{ formatDate(data.left_date) }}</span>
                                                        </div>
                                                        <div class="flex justify-between gap-4">
                                                            <span class="shrink-0 text-muted-foreground">Include SHG Donations</span>
                                                            <span class="text-right">{{ formatBoolean(data.include_shg_donations) }}</span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </DataTable>
                                        </template>
                                        <p v-else class="text-muted-foreground">No company assignments.</p>
                                    </div>
                                </TabPanel>

                                <TabPanel value="contracts">
                                    <div class="pt-4">
                                        <h3 class="mb-4 text-lg font-medium">Contracts</h3>
                                        <template v-if="contracts && contracts.length > 0">
                                            <DataTable
                                                v-model:expandedRows="expandedContractRows"
                                                :value="contracts"
                                                dataKey="id"
                                                size="small"
                                                stripedRows
                                                class="rounded-lg border border-border"
                                            >
                                                <Column expander style="width: 3rem" class="!pr-0 lg:hidden" />
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
                                                <Column header="Annual Leave" class="hidden xl:table-cell">
                                                    <template #body="{ data }">
                                                        {{ getLeaveDisplay(data.annual_leave_entitled, data.annual_leave_taken) }}
                                                    </template>
                                                </Column>
                                                <Column header="Sick Leave" class="hidden xl:table-cell">
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
                                                    <div class="grid gap-3 p-3 text-sm lg:hidden">
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2 sm:hidden">
                                                            <span class="shrink-0 text-muted-foreground">Start Date</span>
                                                            <span class="text-right">{{ formatDate(data.start_date) }}</span>
                                                        </div>
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2 md:hidden">
                                                            <span class="shrink-0 text-muted-foreground">End Date</span>
                                                            <span class="text-right">{{ formatDate(data.end_date) }}</span>
                                                        </div>
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2">
                                                            <span class="shrink-0 text-muted-foreground">Salary</span>
                                                            <span class="text-right">{{ formatCurrency(data.salary_amount) }}</span>
                                                        </div>
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2 xl:hidden">
                                                            <span class="shrink-0 text-muted-foreground">Annual Leave</span>
                                                            <span class="text-right">{{ getLeaveDisplay(data.annual_leave_entitled, data.annual_leave_taken) }}</span>
                                                        </div>
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2 xl:hidden">
                                                            <span class="shrink-0 text-muted-foreground">Sick Leave</span>
                                                            <span class="text-right">{{ getLeaveDisplay(data.sick_leave_entitled, data.sick_leave_taken) }}</span>
                                                        </div>
                                                        <div v-if="data.comments" class="flex flex-col gap-1">
                                                            <span class="shrink-0 text-muted-foreground">Comments</span>
                                                            <div class="prose prose-sm dark:prose-invert max-w-none text-sm" v-html="data.comments"></div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </DataTable>
                                        </template>
                                        <p v-else class="text-muted-foreground">No contracts.</p>
                                    </div>
                                </TabPanel>

                                <TabPanel value="insurances">
                                    <div class="pt-4">
                                        <h3 class="mb-4 text-lg font-medium">Insurances</h3>
                                        <template v-if="insurances && insurances.length > 0">
                                            <DataTable
                                                v-model:expandedRows="expandedInsuranceRows"
                                                :value="insurances"
                                                dataKey="id"
                                                size="small"
                                                stripedRows
                                                class="rounded-lg border border-border"
                                            >
                                                <Column expander style="width: 3rem" class="!pr-0 lg:hidden" />
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
                                                <Column field="end_date" header="End Date" class="hidden xl:table-cell">
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
                                                    <div class="grid gap-3 p-3 text-sm lg:hidden">
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2 sm:hidden">
                                                            <span class="shrink-0 text-muted-foreground">Insurer</span>
                                                            <span class="text-right">{{ data.insurer_name }}</span>
                                                        </div>
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2 md:hidden">
                                                            <span class="shrink-0 text-muted-foreground">Policy #</span>
                                                            <span class="text-right">{{ data.policy_number }}</span>
                                                        </div>
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2">
                                                            <span class="shrink-0 text-muted-foreground">Start Date</span>
                                                            <span class="text-right">{{ formatDate(data.start_date) }}</span>
                                                        </div>
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2 xl:hidden">
                                                            <span class="shrink-0 text-muted-foreground">End Date</span>
                                                            <span class="text-right">{{ formatDate(data.end_date) }}</span>
                                                        </div>
                                                        <div v-if="data.comments" class="flex flex-col gap-1">
                                                            <span class="shrink-0 text-muted-foreground">Comments</span>
                                                            <div class="prose prose-sm dark:prose-invert max-w-none text-sm" v-html="data.comments"></div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </DataTable>
                                        </template>
                                        <p v-else class="text-muted-foreground">No insurances.</p>
                                    </div>
                                </TabPanel>

                                <TabPanel value="stores">
                                    <div class="pt-4">
                                        <h3 class="mb-4 text-lg font-medium">Store Assignments</h3>
                                        <template v-if="stores && stores.length > 0">
                                            <DataTable
                                                v-model:expandedRows="expandedStoreRows"
                                                :value="stores"
                                                dataKey="id"
                                                size="small"
                                                stripedRows
                                                class="rounded-lg border border-border"
                                            >
                                                <Column expander style="width: 3rem" class="!pr-0 md:hidden" />
                                                <Column field="store.store_name" header="Store">
                                                    <template #body="{ data }">
                                                        <div class="flex items-center gap-2">
                                                            <span>{{ data.store?.store_name ?? '-' }}</span>
                                                            <Tag v-if="data.store?.store_code" :value="data.store.store_code" severity="secondary" class="!text-xs" />
                                                        </div>
                                                    </template>
                                                </Column>
                                                <Column field="permissions" header="Permissions" class="hidden md:table-cell">
                                                    <template #body="{ data }">
                                                        <div class="flex flex-wrap gap-1">
                                                            <Tag
                                                                v-for="perm in (data.permissions_with_labels || []).slice(0, 3)"
                                                                :key="perm.key"
                                                                :value="perm.label"
                                                                severity="secondary"
                                                                class="!text-xs"
                                                            />
                                                            <Tag
                                                                v-if="(data.permissions_with_labels || []).length > 3"
                                                                :value="`+${(data.permissions_with_labels || []).length - 3}`"
                                                                severity="info"
                                                                class="!text-xs"
                                                                v-tooltip.top="(data.permissions_with_labels || []).slice(3).map((p: any) => p.label).join(', ')"
                                                            />
                                                            <span v-if="!(data.permissions_with_labels || []).length" class="text-muted-foreground text-sm">
                                                                No permissions
                                                            </span>
                                                        </div>
                                                    </template>
                                                </Column>
                                                <Column field="active" header="Status">
                                                    <template #body="{ data }">
                                                        <Tag
                                                            :value="data.active ? 'Active' : 'Inactive'"
                                                            :severity="data.active ? 'success' : 'secondary'"
                                                        />
                                                    </template>
                                                </Column>
                                                <template #expansion="{ data }">
                                                    <div class="grid gap-3 p-3 text-sm md:hidden">
                                                        <div class="flex flex-col gap-2">
                                                            <span class="shrink-0 text-muted-foreground">Permissions</span>
                                                            <div class="flex flex-wrap gap-1">
                                                                <Tag
                                                                    v-for="perm in (data.permissions_with_labels || [])"
                                                                    :key="perm.key"
                                                                    :value="perm.label"
                                                                    severity="secondary"
                                                                    class="!text-xs"
                                                                />
                                                                <span v-if="!(data.permissions_with_labels || []).length" class="text-muted-foreground text-sm">
                                                                    No permissions
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </DataTable>
                                        </template>
                                        <p v-else class="text-muted-foreground">No store assignments.</p>
                                    </div>
                                </TabPanel>

                                <TabPanel v-if="isAdmin" value="hierarchy">
                                    <div class="pt-4">
                                        <div class="flex flex-col gap-4">
                                            <div>
                                                <h3 class="text-lg font-medium">Organization Chart</h3>
                                                <p class="text-sm text-muted-foreground">
                                                    Visual representation of this employee's position in the hierarchy.
                                                </p>
                                            </div>
                                            <div v-if="hierarchyLoading" class="flex items-center justify-center p-8">
                                                <i class="pi pi-spin pi-spinner text-2xl text-muted-foreground"></i>
                                            </div>
                                            <div v-else-if="hierarchyData?.subtree?.length" class="overflow-x-auto rounded-lg border border-border p-4">
                                                <OrganizationChart
                                                    v-for="node in hierarchyData.subtree"
                                                    :key="node.key"
                                                    :value="node"
                                                    collapsible
                                                >
                                                    <template #employee="{ node: chartNode }">
                                                        <div class="flex flex-col items-center gap-2 p-2">
                                                            <Avatar
                                                                v-if="chartNode.data.profile_picture_url"
                                                                :image="chartNode.data.profile_picture_url"
                                                                shape="circle"
                                                                size="normal"
                                                            />
                                                            <Avatar
                                                                v-else
                                                                :label="getHierarchyInitials(chartNode.data.name)"
                                                                shape="circle"
                                                                size="normal"
                                                                class="bg-primary/10 text-primary"
                                                            />
                                                            <div class="text-center">
                                                                <div class="text-sm font-medium">{{ chartNode.data.name }}</div>
                                                                <div v-if="chartNode.data.designation" class="text-xs text-muted-foreground">
                                                                    {{ chartNode.data.designation }}
                                                                </div>
                                                            </div>
                                                            <Tag
                                                                :value="`Tier ${chartNode.data.tier}`"
                                                                :severity="getTierColor(chartNode.data.tier)"
                                                                class="!text-xs"
                                                            />
                                                        </div>
                                                    </template>
                                                </OrganizationChart>
                                            </div>
                                            <div v-else class="rounded-lg border border-dashed border-border p-6 text-center text-muted-foreground">
                                                <i class="pi pi-sitemap mb-2 text-2xl"></i>
                                                <p>No subordinates in hierarchy.</p>
                                            </div>
                                        </div>
                                    </div>
                                </TabPanel>
                            </TabPanels>
                        </Tabs>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
