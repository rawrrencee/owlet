<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import Tag from 'primevue/tag';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit } from '@/routes/profile';
import { type BreadcrumbItem, type Employee, type WorkOSUser } from '@/types';

interface Props {
    employee: Employee | null;
    workosUser: Pick<WorkOSUser, 'id' | 'email' | 'emailVerified'> | null;
    role: string;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile',
        href: edit().url,
    },
];

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function getInitials(): string {
    if (!props.employee) return '?';
    const first = props.employee.first_name?.charAt(0)?.toUpperCase() ?? '';
    const last = props.employee.last_name?.charAt(0)?.toUpperCase() ?? '';
    return `${first}${last}`;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile" />

        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <template #content>
                        <div v-if="employee" class="flex flex-col gap-6">
                            <!-- Profile Header -->
                            <div
                                class="flex flex-col items-center gap-4 sm:flex-row sm:items-start"
                            >
                                <Image
                                    v-if="employee.profile_picture_url"
                                    :src="employee.profile_picture_url"
                                    :alt="`${employee.first_name} ${employee.last_name}`"
                                    image-class="!h-24 !w-24 rounded-full object-cover cursor-pointer"
                                    :pt="{
                                        root: {
                                            class: 'rounded-full overflow-hidden',
                                        },
                                        previewMask: { class: 'rounded-full' },
                                    }"
                                    preview
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials()"
                                    shape="circle"
                                    class="!h-24 !w-24 bg-primary/10 text-3xl text-primary"
                                />
                                <div
                                    class="flex flex-col gap-1 text-center sm:text-left"
                                >
                                    <h2 class="text-xl font-semibold">
                                        {{ employee.first_name }}
                                        {{ employee.last_name }}
                                    </h2>
                                    <p
                                        v-if="employee.chinese_name"
                                        class="text-muted-foreground"
                                    >
                                        {{ employee.chinese_name }}
                                    </p>
                                    <p
                                        v-if="workosUser?.email"
                                        class="text-muted-foreground"
                                    >
                                        {{ workosUser.email }}
                                    </p>
                                    <p
                                        v-if="employee.employee_number"
                                        class="text-sm text-muted-foreground"
                                    >
                                        Employee #{{ employee.employee_number }}
                                    </p>
                                </div>
                            </div>

                            <Divider />

                            <!-- Basic Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Basic Information
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >NRIC</span
                                        >
                                        <span>{{ employee.nric ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Phone</span
                                        >
                                        <span>{{ employee.phone ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Mobile</span
                                        >
                                        <span>{{
                                            employee.mobile ?? '-'
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Role</span
                                        >
                                        <span class="capitalize">{{
                                            role ?? '-'
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Personal Details -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Personal Details
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Gender</span
                                        >
                                        <span>{{
                                            employee.gender ?? '-'
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Date of Birth</span
                                        >
                                        <span>{{
                                            formatDate(employee.date_of_birth)
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Nationality</span
                                        >
                                        <span>{{
                                            employee.nationality_name ??
                                            employee.nationality ??
                                            '-'
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Race</span
                                        >
                                        <span>{{ employee.race ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Residency Status</span
                                        >
                                        <span>{{
                                            employee.residency_status ?? '-'
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >PR Conversion Date</span
                                        >
                                        <span>{{
                                            formatDate(
                                                employee.pr_conversion_date,
                                            )
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Address -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Address
                                </h3>
                                <div class="flex flex-col gap-1">
                                    <span v-if="employee.address_1">{{
                                        employee.address_1
                                    }}</span>
                                    <span v-if="employee.address_2">{{
                                        employee.address_2
                                    }}</span>
                                    <span
                                        v-if="
                                            employee.city ||
                                            employee.state ||
                                            employee.postal_code
                                        "
                                    >
                                        {{
                                            [
                                                employee.city,
                                                employee.state,
                                                employee.postal_code,
                                            ]
                                                .filter(Boolean)
                                                .join(', ')
                                        }}
                                    </span>
                                    <span
                                        v-if="
                                            employee.country_name ||
                                            employee.country
                                        "
                                        >{{
                                            employee.country_name ??
                                            employee.country
                                        }}</span
                                    >
                                    <span
                                        v-if="
                                            !employee.address_1 &&
                                            !employee.address_2 &&
                                            !employee.city &&
                                            !employee.country_name &&
                                            !employee.country
                                        "
                                        class="text-muted-foreground"
                                    >
                                        No address provided
                                    </span>
                                </div>
                            </div>

                            <Divider />

                            <!-- Employment Details -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Employment Details
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Hire Date</span
                                        >
                                        <span>{{
                                            formatDate(employee.hire_date)
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Status</span
                                        >
                                        <Tag
                                            :value="
                                                employee.termination_date
                                                    ? 'Terminated'
                                                    : 'Active'
                                            "
                                            :severity="
                                                employee.termination_date
                                                    ? 'danger'
                                                    : 'success'
                                            "
                                            class="w-fit"
                                        />
                                    </div>
                                    <div
                                        v-if="employee.termination_date"
                                        class="flex flex-col gap-1"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Termination Date</span
                                        >
                                        <span>{{
                                            formatDate(
                                                employee.termination_date,
                                            )
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Bank Account Details -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Bank Account Details
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Bank Name</span
                                        >
                                        <span>{{
                                            employee.bank_name ?? '-'
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Account Number</span
                                        >
                                        <span>{{
                                            employee.bank_account_number ?? '-'
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Emergency Contact -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Emergency Contact
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Contact Name</span
                                        >
                                        <span>{{
                                            employee.emergency_name ?? '-'
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Relationship</span
                                        >
                                        <span>{{
                                            employee.emergency_relationship ??
                                            '-'
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Contact Number</span
                                        >
                                        <span>{{
                                            employee.emergency_contact ?? '-'
                                        }}</span>
                                    </div>
                                </div>
                                <div
                                    v-if="
                                        employee.emergency_address_1 ||
                                        employee.emergency_address_2
                                    "
                                    class="mt-4 flex flex-col gap-1"
                                >
                                    <span class="text-sm text-muted-foreground"
                                        >Address</span
                                    >
                                    <span v-if="employee.emergency_address_1">{{
                                        employee.emergency_address_1
                                    }}</span>
                                    <span v-if="employee.emergency_address_2">{{
                                        employee.emergency_address_2
                                    }}</span>
                                </div>
                            </div>

                            <!-- WorkOS Info -->
                            <template v-if="workosUser">
                                <Divider />
                                <div>
                                    <h3
                                        class="mb-3 text-sm font-medium text-muted-foreground"
                                    >
                                        Account Info
                                    </h3>
                                    <div class="grid gap-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-muted-foreground"
                                                >Email Verified</span
                                            >
                                            <span>{{
                                                workosUser.emailVerified
                                                    ? 'Yes'
                                                    : 'No'
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- No employee data -->
                        <div
                            v-else
                            class="py-8 text-center text-muted-foreground"
                        >
                            <p>No profile information available.</p>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
