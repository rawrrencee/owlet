<script setup lang="ts">
import EmployeeCompaniesSection from '@/components/employees/EmployeeCompaniesSection.vue';
import ImageUpload from '@/components/ImageUpload.vue';
import {
    countries,
    countryOptions,
    nationalities,
    nationalityOptions,
    relationships,
    relationshipOptions,
} from '@/constants/employee';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type Company,
    type Designation,
    type Employee,
    type EmployeeCompany,
    type WorkOSUser,
} from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import DatePicker from 'primevue/datepicker';
import Divider from 'primevue/divider';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import ConfirmDialog from 'primevue/confirmdialog';

interface Props {
    employee: Employee | null;
    workosUser: WorkOSUser | null;
    role?: string;
    employeeCompanies?: EmployeeCompany[];
    companies?: Company[];
    designations?: Designation[];
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.employee);
const pageTitle = computed(() => (isEditing.value ? 'Edit User' : 'Create User'));
const hasWorkOSAccount = computed(() => !!props.workosUser);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/users' },
    { title: isEditing.value ? 'Edit' : 'Create' },
];

// Track active status separately for the toggle
const isActive = ref(props.employee ? !props.employee.termination_date : true);

const form = useForm({
    first_name: props.employee?.first_name ?? props.workosUser?.firstName ?? '',
    last_name: props.employee?.last_name ?? props.workosUser?.lastName ?? '',
    chinese_name: props.employee?.chinese_name ?? '',
    email: props.workosUser?.email ?? '',
    employee_number: props.employee?.employee_number ?? '',
    nric: props.employee?.nric ?? '',
    phone: props.employee?.phone ?? '',
    mobile: props.employee?.mobile ?? '',
    address_1: props.employee?.address_1 ?? '',
    address_2: props.employee?.address_2 ?? '',
    city: props.employee?.city ?? '',
    state: props.employee?.state ?? '',
    postal_code: props.employee?.postal_code ?? '',
    country: props.employee?.country ?? '',
    date_of_birth: props.employee?.date_of_birth ? new Date(props.employee.date_of_birth) : null,
    gender: props.employee?.gender ?? '',
    race: props.employee?.race ?? '',
    nationality: props.employee?.nationality ?? '',
    residency_status: props.employee?.residency_status ?? '',
    pr_conversion_date: props.employee?.pr_conversion_date ? new Date(props.employee.pr_conversion_date) : null,
    emergency_name: props.employee?.emergency_name ?? '',
    emergency_relationship: props.employee?.emergency_relationship ?? '',
    emergency_contact: props.employee?.emergency_contact ?? '',
    emergency_address_1: props.employee?.emergency_address_1 ?? '',
    emergency_address_2: props.employee?.emergency_address_2 ?? '',
    bank_name: props.employee?.bank_name ?? '',
    bank_account_number: props.employee?.bank_account_number ?? '',
    hire_date: props.employee?.hire_date ? new Date(props.employee.hire_date) : null,
    termination_date: props.employee?.termination_date ? new Date(props.employee.termination_date) : null,
    notes: props.employee?.notes ?? '',
    role: props.role ?? 'staff',
});

// Confirmation dialog for unsaved changes
const confirm = useConfirm();
let removeBeforeListener: (() => void) | null = null;
let pendingNavigation: string | null = null;

function handleBeforeUnload(e: BeforeUnloadEvent) {
    if (form.isDirty) {
        e.preventDefault();
        return '';
    }
}

function confirmLeave(targetUrl: string) {
    // Prevent multiple dialogs - if already pending, ignore
    if (pendingNavigation) return;
    pendingNavigation = targetUrl;

    confirm.require({
        group: 'unsavedChanges',
        message: 'You have unsaved changes. Are you sure you want to leave?',
        header: 'Unsaved Changes',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Stay',
        acceptLabel: 'Leave',
        accept: () => {
            removeBeforeListener?.();
            router.visit(pendingNavigation!);
            pendingNavigation = null;
        },
        onHide: () => {
            pendingNavigation = null;
        },
    });
}

onMounted(() => {
    // Browser beforeunload (handles refresh, close tab, external navigation)
    window.addEventListener('beforeunload', handleBeforeUnload);

    // Inertia navigation (handles in-app navigation like links, router.visit)
    removeBeforeListener = router.on('before', (event) => {
        if (form.isDirty && !pendingNavigation) {
            event.preventDefault();
            confirmLeave(event.detail.visit.url.toString());
            return false;
        }
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
    removeBeforeListener?.();
});

const genderOptions = [
    { label: 'Male', value: 'Male' },
    { label: 'Female', value: 'Female' },
];

const residencyStatusOptions = [
    { label: 'Citizen', value: 'Citizen' },
    { label: 'Permanent Resident', value: 'PR' },
    { label: 'Employment Pass', value: 'EP' },
    { label: 'S Pass', value: 'S Pass' },
    { label: 'Work Permit', value: 'WP' },
    { label: 'Dependant Pass', value: 'DP' },
    { label: 'Student Pass', value: 'Student' },
];

// Helper to check if value is in predefined list or is a custom "Other" value
const isOtherValue = (value: string | null | undefined, list: readonly string[]) => {
    return value && !list.includes(value);
};

// Track custom "Other" values for fields with Other option
const nationalitySelection = ref(
    isOtherValue(props.employee?.nationality, nationalities) ? 'Other' : (props.employee?.nationality ?? ''),
);
const nationalityOther = ref(isOtherValue(props.employee?.nationality, nationalities) ? props.employee?.nationality : '');

const countrySelection = ref(
    isOtherValue(props.employee?.country, countries) ? 'Other' : (props.employee?.country ?? ''),
);
const countryOther = ref(isOtherValue(props.employee?.country, countries) ? props.employee?.country : '');

const relationshipSelection = ref(
    isOtherValue(props.employee?.emergency_relationship, relationships)
        ? 'Other'
        : (props.employee?.emergency_relationship ?? ''),
);
const relationshipOther = ref(
    isOtherValue(props.employee?.emergency_relationship, relationships) ? props.employee?.emergency_relationship : '',
);

// Watch isActive toggle and update termination_date accordingly
watch(isActive, (active) => {
    if (active) {
        form.termination_date = null;
    } else if (!form.termination_date) {
        form.termination_date = new Date();
    }
});

const roleOptions = [
    { label: 'Staff', value: 'staff' },
    { label: 'Admin', value: 'admin' },
];

// Profile picture state
const profilePictureUrl = ref<string | null>(props.employee?.profile_picture_url ?? null);

function submit() {
    // Transform dates to ISO strings for the backend
    // Use custom "Other" values when selected
    const data = {
        ...form.data(),
        nationality: nationalitySelection.value === 'Other' ? nationalityOther.value : nationalitySelection.value,
        country: countrySelection.value === 'Other' ? countryOther.value : countrySelection.value,
        emergency_relationship:
            relationshipSelection.value === 'Other' ? relationshipOther.value : relationshipSelection.value,
        date_of_birth: form.date_of_birth ? formatDateForBackend(form.date_of_birth) : null,
        pr_conversion_date: form.pr_conversion_date ? formatDateForBackend(form.pr_conversion_date) : null,
        hire_date: form.hire_date ? formatDateForBackend(form.hire_date) : null,
        termination_date: isActive.value ? null : (form.termination_date ? formatDateForBackend(form.termination_date) : null),
    };

    if (isEditing.value) {
        router.put(`/users/${props.employee!.id}`, data);
    } else {
        router.post('/users', data);
    }
}

function formatDateForBackend(date: Date): string {
    return date.toISOString().split('T')[0];
}

function cancel() {
    router.get('/users');
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-4">
                <Button
                    icon="pi pi-arrow-left"
                    severity="secondary"
                    text
                    rounded
                    size="small"
                    @click="cancel"
                />
                <h1 class="text-2xl font-semibold">{{ pageTitle }}</h1>
            </div>

            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <template #content>
                        <form @submit.prevent="submit" class="flex flex-col gap-6">
                            <Message v-if="isEditing && hasWorkOSAccount" severity="info" :closable="false">
                                This user is managed via WorkOS. Changes to name and role will sync to WorkOS.
                            </Message>

                            <Message v-if="isEditing && !hasWorkOSAccount" severity="warn" :closable="false">
                                This employee does not have a WorkOS account. Only local employee data will be updated.
                            </Message>

                            <p v-if="!isEditing" class="text-surface-500 dark:text-surface-400 text-sm">
                                Create a new user in WorkOS. They will receive an email to set their password.
                            </p>

                            <!-- Basic Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Basic Information</h3>
                                <div class="flex flex-col gap-4">
                                    <!-- Profile Picture -->
                                    <ImageUpload
                                        v-if="isEditing && employee"
                                        :image-url="profilePictureUrl"
                                        :upload-url="`/users/${employee.id}/profile-picture`"
                                        :delete-url="`/users/${employee.id}/profile-picture`"
                                        field-name="profile_picture"
                                        label="Profile Picture"
                                        placeholder-icon="pi pi-user"
                                        alt="Profile picture"
                                        :circular="true"
                                        :preview-size="96"
                                        @uploaded="(url) => profilePictureUrl = url"
                                        @deleted="profilePictureUrl = null"
                                    />

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="first_name" class="font-medium">First Name</label>
                                            <InputText
                                                id="first_name"
                                                v-model="form.first_name"
                                                :invalid="!!form.errors.first_name"
                                                placeholder="John"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.first_name" class="text-red-500">
                                                {{ form.errors.first_name }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="last_name" class="font-medium">Last Name</label>
                                            <InputText
                                                id="last_name"
                                                v-model="form.last_name"
                                                :invalid="!!form.errors.last_name"
                                                placeholder="Doe"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.last_name" class="text-red-500">
                                                {{ form.errors.last_name }}
                                            </small>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="chinese_name" class="font-medium">Chinese Name</label>
                                            <InputText
                                                id="chinese_name"
                                                v-model="form.chinese_name"
                                                :invalid="!!form.errors.chinese_name"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.chinese_name" class="text-red-500">
                                                {{ form.errors.chinese_name }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="nric" class="font-medium">NRIC</label>
                                            <InputText
                                                id="nric"
                                                v-model="form.nric"
                                                :invalid="!!form.errors.nric"
                                                placeholder="S1234567A"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.nric" class="text-red-500">
                                                {{ form.errors.nric }}
                                            </small>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="email" class="font-medium">Email</label>
                                            <InputText
                                                id="email"
                                                v-model="form.email"
                                                type="email"
                                                :invalid="!!form.errors.email"
                                                :disabled="isEditing"
                                                placeholder="john.doe@example.com"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="isEditing" class="text-muted-foreground">
                                                Email cannot be changed after account creation.
                                            </small>
                                            <small v-if="form.errors.email" class="text-red-500">
                                                {{ form.errors.email }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="employee_number" class="font-medium">Employee Number</label>
                                            <InputText
                                                id="employee_number"
                                                v-model="form.employee_number"
                                                :invalid="!!form.errors.employee_number"
                                                placeholder="EMP001"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.employee_number" class="text-red-500">
                                                {{ form.errors.employee_number }}
                                            </small>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="phone" class="font-medium">Phone</label>
                                            <InputText
                                                id="phone"
                                                v-model="form.phone"
                                                :invalid="!!form.errors.phone"
                                                placeholder="(555) 123-4567"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.phone" class="text-red-500">
                                                {{ form.errors.phone }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="mobile" class="font-medium">Mobile</label>
                                            <InputText
                                                id="mobile"
                                                v-model="form.mobile"
                                                :invalid="!!form.errors.mobile"
                                                placeholder="(555) 987-6543"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.mobile" class="text-red-500">
                                                {{ form.errors.mobile }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Personal Details -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Personal Details</h3>
                                <div class="flex flex-col gap-4">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="gender" class="font-medium">Gender</label>
                                            <Select
                                                id="gender"
                                                v-model="form.gender"
                                                :options="genderOptions"
                                                option-label="label"
                                                option-value="value"
                                                :invalid="!!form.errors.gender"
                                                placeholder="Select gender"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.gender" class="text-red-500">
                                                {{ form.errors.gender }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="date_of_birth" class="font-medium">Date of Birth</label>
                                            <DatePicker
                                                id="date_of_birth"
                                                v-model="form.date_of_birth"
                                                :invalid="!!form.errors.date_of_birth"
                                                dateFormat="yy-mm-dd"
                                                showIcon
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.date_of_birth" class="text-red-500">
                                                {{ form.errors.date_of_birth }}
                                            </small>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="nationality" class="font-medium">Nationality</label>
                                            <Select
                                                id="nationality"
                                                v-model="nationalitySelection"
                                                :options="nationalityOptions"
                                                option-label="label"
                                                option-value="value"
                                                :invalid="!!form.errors.nationality"
                                                placeholder="Select nationality"
                                                filter
                                                size="small"
                                                fluid
                                            />
                                            <InputText
                                                v-if="nationalitySelection === 'Other'"
                                                v-model="nationalityOther"
                                                :invalid="!!form.errors.nationality"
                                                placeholder="Enter nationality"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.nationality" class="text-red-500">
                                                {{ form.errors.nationality }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="race" class="font-medium">Race</label>
                                            <InputText
                                                id="race"
                                                v-model="form.race"
                                                :invalid="!!form.errors.race"
                                                placeholder="Chinese"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.race" class="text-red-500">
                                                {{ form.errors.race }}
                                            </small>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="residency_status" class="font-medium">Residency Status</label>
                                            <Select
                                                id="residency_status"
                                                v-model="form.residency_status"
                                                :options="residencyStatusOptions"
                                                option-label="label"
                                                option-value="value"
                                                :invalid="!!form.errors.residency_status"
                                                placeholder="Select status"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.residency_status" class="text-red-500">
                                                {{ form.errors.residency_status }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="pr_conversion_date" class="font-medium">PR Conversion Date</label>
                                            <DatePicker
                                                id="pr_conversion_date"
                                                v-model="form.pr_conversion_date"
                                                :invalid="!!form.errors.pr_conversion_date"
                                                dateFormat="yy-mm-dd"
                                                showIcon
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.pr_conversion_date" class="text-red-500">
                                                {{ form.errors.pr_conversion_date }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Address -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Address</h3>
                                <div class="flex flex-col gap-4">
                                    <div class="flex flex-col gap-2">
                                        <label for="address_1" class="font-medium">Address Line 1</label>
                                        <InputText
                                            id="address_1"
                                            v-model="form.address_1"
                                            :invalid="!!form.errors.address_1"
                                            placeholder="Block 123 Street Name"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.address_1" class="text-red-500">
                                            {{ form.errors.address_1 }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="address_2" class="font-medium">Address Line 2</label>
                                        <InputText
                                            id="address_2"
                                            v-model="form.address_2"
                                            :invalid="!!form.errors.address_2"
                                            placeholder="#01-234"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.address_2" class="text-red-500">
                                            {{ form.errors.address_2 }}
                                        </small>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="city" class="font-medium">City</label>
                                            <InputText
                                                id="city"
                                                v-model="form.city"
                                                :invalid="!!form.errors.city"
                                                placeholder="Singapore"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.city" class="text-red-500">
                                                {{ form.errors.city }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="state" class="font-medium">State</label>
                                            <InputText
                                                id="state"
                                                v-model="form.state"
                                                :invalid="!!form.errors.state"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.state" class="text-red-500">
                                                {{ form.errors.state }}
                                            </small>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="postal_code" class="font-medium">Postal Code</label>
                                            <InputText
                                                id="postal_code"
                                                v-model="form.postal_code"
                                                :invalid="!!form.errors.postal_code"
                                                placeholder="123456"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.postal_code" class="text-red-500">
                                                {{ form.errors.postal_code }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="country" class="font-medium">Country</label>
                                            <Select
                                                id="country"
                                                v-model="countrySelection"
                                                :options="countryOptions"
                                                option-label="label"
                                                option-value="value"
                                                :invalid="!!form.errors.country"
                                                placeholder="Select country"
                                                filter
                                                size="small"
                                                fluid
                                            />
                                            <InputText
                                                v-if="countrySelection === 'Other'"
                                                v-model="countryOther"
                                                :invalid="!!form.errors.country"
                                                placeholder="Enter country"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.country" class="text-red-500">
                                                {{ form.errors.country }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Employment Details -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Employment Details</h3>
                                <div class="flex flex-col gap-4">
                                    <div class="flex flex-col gap-2">
                                        <label for="role" class="font-medium">Role</label>
                                        <Select
                                            id="role"
                                            v-model="form.role"
                                            :options="roleOptions"
                                            option-label="label"
                                            option-value="value"
                                            :invalid="!!form.errors.role"
                                            :disabled="!hasWorkOSAccount && isEditing"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="!hasWorkOSAccount && isEditing" class="text-muted-foreground">
                                            Role can only be changed for users with WorkOS accounts.
                                        </small>
                                        <small v-if="form.errors.role" class="text-red-500">
                                            {{ form.errors.role }}
                                        </small>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="hire_date" class="font-medium">Hire Date</label>
                                            <DatePicker
                                                id="hire_date"
                                                v-model="form.hire_date"
                                                :invalid="!!form.errors.hire_date"
                                                dateFormat="yy-mm-dd"
                                                showIcon
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.hire_date" class="text-red-500">
                                                {{ form.errors.hire_date }}
                                            </small>
                                        </div>

                                        <div v-if="isEditing" class="flex flex-col gap-2">
                                            <label class="font-medium">Status</label>
                                            <div class="flex h-[30px] items-center gap-3">
                                                <ToggleSwitch v-model="isActive" />
                                                <span :class="isActive ? 'text-green-600' : 'text-red-600'">
                                                    {{ isActive ? 'Active' : 'Terminated' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="isEditing && !isActive" class="flex flex-col gap-2">
                                        <label for="termination_date" class="font-medium">Termination Date</label>
                                        <DatePicker
                                            id="termination_date"
                                            v-model="form.termination_date"
                                            :invalid="!!form.errors.termination_date"
                                            dateFormat="yy-mm-dd"
                                            showIcon
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.termination_date" class="text-red-500">
                                            {{ form.errors.termination_date }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Company Assignments (only shown when editing) -->
                            <template v-if="isEditing && employee">
                                <Divider />
                                <EmployeeCompaniesSection
                                    :employee-id="employee.id"
                                    :employee-companies="employeeCompanies ?? []"
                                    :companies="companies ?? []"
                                    :designations="designations ?? []"
                                />
                            </template>

                            <Divider />

                            <!-- Bank Account Details -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Bank Account Details</h3>
                                <div class="flex flex-col gap-4">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="bank_name" class="font-medium">Bank Name</label>
                                            <InputText
                                                id="bank_name"
                                                v-model="form.bank_name"
                                                :invalid="!!form.errors.bank_name"
                                                placeholder="DBS"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.bank_name" class="text-red-500">
                                                {{ form.errors.bank_name }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="bank_account_number" class="font-medium">Bank Account Number</label>
                                            <InputText
                                                id="bank_account_number"
                                                v-model="form.bank_account_number"
                                                :invalid="!!form.errors.bank_account_number"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.bank_account_number" class="text-red-500">
                                                {{ form.errors.bank_account_number }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Emergency Contact -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Emergency Contact</h3>
                                <div class="flex flex-col gap-4">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="emergency_name" class="font-medium">Contact Name</label>
                                            <InputText
                                                id="emergency_name"
                                                v-model="form.emergency_name"
                                                :invalid="!!form.errors.emergency_name"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.emergency_name" class="text-red-500">
                                                {{ form.errors.emergency_name }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="emergency_relationship" class="font-medium">Relationship</label>
                                            <Select
                                                id="emergency_relationship"
                                                v-model="relationshipSelection"
                                                :options="relationshipOptions"
                                                option-label="label"
                                                option-value="value"
                                                :invalid="!!form.errors.emergency_relationship"
                                                placeholder="Select relationship"
                                                size="small"
                                                fluid
                                            />
                                            <InputText
                                                v-if="relationshipSelection === 'Other'"
                                                v-model="relationshipOther"
                                                :invalid="!!form.errors.emergency_relationship"
                                                placeholder="Enter relationship"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.emergency_relationship" class="text-red-500">
                                                {{ form.errors.emergency_relationship }}
                                            </small>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="emergency_contact" class="font-medium">Contact Number</label>
                                        <InputText
                                            id="emergency_contact"
                                            v-model="form.emergency_contact"
                                            :invalid="!!form.errors.emergency_contact"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.emergency_contact" class="text-red-500">
                                            {{ form.errors.emergency_contact }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="emergency_address_1" class="font-medium">Address Line 1</label>
                                        <InputText
                                            id="emergency_address_1"
                                            v-model="form.emergency_address_1"
                                            :invalid="!!form.errors.emergency_address_1"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.emergency_address_1" class="text-red-500">
                                            {{ form.errors.emergency_address_1 }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="emergency_address_2" class="font-medium">Address Line 2</label>
                                        <InputText
                                            id="emergency_address_2"
                                            v-model="form.emergency_address_2"
                                            :invalid="!!form.errors.emergency_address_2"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.emergency_address_2" class="text-red-500">
                                            {{ form.errors.emergency_address_2 }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Comments/Notes -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Notes</h3>
                                <div class="flex flex-col gap-2">
                                    <Textarea
                                        id="notes"
                                        v-model="form.notes"
                                        :invalid="!!form.errors.notes"
                                        rows="4"
                                        autoResize
                                        fluid
                                    />
                                    <small v-if="form.errors.notes" class="text-red-500">
                                        {{ form.errors.notes }}
                                    </small>
                                </div>
                            </div>

                            <!-- WorkOS Info (if applicable) -->
                            <div v-if="workosUser">
                                <Divider />
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

                            <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <Button
                                    type="button"
                                    label="Cancel"
                                    severity="secondary"
                                    size="small"
                                    @click="cancel"
                                    :disabled="form.processing"
                                />
                                <Button
                                    type="submit"
                                    :label="isEditing ? 'Save Changes' : 'Create User'"
                                    size="small"
                                    :loading="form.processing"
                                />
                            </div>
                        </form>
                    </template>
                </Card>
            </div>
        </div>

        <ConfirmDialog group="unsavedChanges" />
    </AppLayout>
</template>
