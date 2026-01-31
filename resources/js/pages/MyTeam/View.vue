<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import { computed, reactive, ref } from 'vue';
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type Employee,
    type EmployeeCompany,
    type EmployeeStore,
} from '@/types';

interface Props {
    employee: Employee;
    email: string | null;
    employeeCompanies?: EmployeeCompany[];
    stores?: EmployeeStore[];
    visibleSections: string[];
}

const props = defineProps<Props>();


// Active tab state
const activeTab = ref('basic');

const expandedCompanyRows = reactive({});
const expandedStoreRows = reactive({});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Team', href: '/my-team' },
    { title: `${props.employee.first_name} ${props.employee.last_name}` },
];

// Computed property to check which tabs should be visible
const showCompaniesTab = computed(() => props.visibleSections.includes('companies'));
const showStoresTab = computed(() => props.visibleSections.includes('stores'));

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
</script>

<template>
    <Head :title="`${employee.first_name} ${employee.last_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <BackButton fallback-url="/my-team" />
                    <h1 class="heading-lg">{{ employee.first_name }} {{ employee.last_name }}</h1>
                    <Tag
                        :value="employee.termination_date ? 'Terminated' : 'Active'"
                        :severity="employee.termination_date ? 'danger' : 'success'"
                    />
                </div>
            </div>

            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <template #content>
                        <Tabs v-model:value="activeTab" scrollable>
                            <TabList>
                                <Tab value="basic">Basic Info</Tab>
                                <Tab v-if="showCompaniesTab" value="companies">Companies</Tab>
                                <Tab v-if="showStoresTab" value="stores">Stores</Tab>
                            </TabList>
                            <TabPanels>
                                <TabPanel value="basic">
                                    <div class="flex flex-col gap-6 pt-4">
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
                                                <p v-if="email" class="text-muted-foreground">{{ email }}</p>
                                                <p v-if="employee.employee_number" class="text-sm text-muted-foreground">Employee #{{ employee.employee_number }}</p>
                                            </div>
                                        </div>

                                        <Divider />

                                        <!-- Basic Information -->
                                        <div>
                                            <h3 class="mb-4 text-lg font-medium">Basic Information</h3>
                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div class="flex flex-col gap-1">
                                                    <span class="text-sm text-muted-foreground">Phone</span>
                                                    <span>{{ employee.phone ?? '-' }}</span>
                                                </div>
                                                <div class="flex flex-col gap-1">
                                                    <span class="text-sm text-muted-foreground">Mobile</span>
                                                    <span>{{ employee.mobile ?? '-' }}</span>
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
                                        </div>
                                    </div>
                                </TabPanel>

                                <TabPanel v-if="showCompaniesTab" value="companies">
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
                                                    <div class="grid gap-3 p-3 text-sm lg:hidden">
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2 md:hidden">
                                                            <span class="shrink-0 text-muted-foreground">Designation</span>
                                                            <span class="text-right">{{ data.designation?.designation_name ?? '-' }}</span>
                                                        </div>
                                                        <div class="flex justify-between gap-4 border-b border-border pb-2 sm:hidden">
                                                            <span class="shrink-0 text-muted-foreground">Type</span>
                                                            <span class="text-right">{{ data.status_label ?? getStatusLabel(data.status) }}</span>
                                                        </div>
                                                        <div class="flex justify-between gap-4">
                                                            <span class="shrink-0 text-muted-foreground">Start Date</span>
                                                            <span class="text-right">{{ formatDate(data.commencement_date) }}</span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </DataTable>
                                        </template>
                                        <p v-else class="text-muted-foreground">No company assignments.</p>
                                    </div>
                                </TabPanel>

                                <TabPanel v-if="showStoresTab" value="stores">
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
                            </TabPanels>
                        </Tabs>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
