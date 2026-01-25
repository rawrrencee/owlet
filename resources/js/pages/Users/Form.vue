<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import DatePicker from 'primevue/datepicker';
import Divider from 'primevue/divider';
import Editor from 'primevue/editor';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import ToggleSwitch from 'primevue/toggleswitch';
import { useConfirm } from 'primevue/useconfirm';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import EmployeeCompaniesSection from '@/components/employees/EmployeeCompaniesSection.vue';
import EmployeeContractsSection from '@/components/employees/EmployeeContractsSection.vue';
import EmployeeHierarchySection from '@/components/employees/EmployeeHierarchySection.vue';
import EmployeeInsurancesSection from '@/components/employees/EmployeeInsurancesSection.vue';
import EmployeeStoresSection from '@/components/employees/EmployeeStoresSection.vue';
import ImageSelect from '@/components/ImageSelect.vue';
import ImageUpload from '@/components/ImageUpload.vue';
import { useSmartBack } from '@/composables/useSmartBack';
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
    type AppPageProps,
    type BreadcrumbItem,
    type Company,
    type Designation,
    type Employee,
    type EmployeeCompany,
    type EmployeeContract,
    type EmployeeInsurance,
    type Store,
    type WorkOSUser,
} from '@/types';

interface Props {
    employee: Employee | null;
    workosUser: WorkOSUser | null;
    role?: string;
    employeeCompanies?: EmployeeCompany[];
    contracts?: EmployeeContract[];
    insurances?: EmployeeInsurance[];
    companies?: Company[];
    designations?: Designation[];
    stores?: Store[];
}

const props = defineProps<Props>();

// Smart back navigation for edit mode (can be accessed from View page or Index page)
const { goBack } = useSmartBack('/users');

const isEditing = computed(() => !!props.employee);
const pageTitle = computed(() => (isEditing.value ? 'Edit User' : 'Create User'));
const hasWorkOSAccount = computed(() => !!props.workosUser);
const isPendingConfirmation = computed(() => !props.workosUser && !!props.employee?.pending_email);
const isResendingInvitation = ref(false);

function resendInvitation() {
    if (!props.employee || isResendingInvitation.value) return;

    isResendingInvitation.value = true;
    router.post(`/users/${props.employee.id}/resend-invitation`, {}, {
        preserveScroll: true,
        onFinish: () => {
            isResendingInvitation.value = false;
        },
    });
}

// Check if current user is admin (for showing hierarchy tab)
const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

// Active tab for edit mode - initialize from URL query param if provided
const urlParams = new URLSearchParams(window.location.search);
const initialTab = urlParams.get('tab') ?? 'basic';
const validTabs = ['basic', 'companies', 'contracts', 'insurances', 'stores', 'hierarchy'];
const activeTab = ref(validTabs.includes(initialTab) ? initialTab : 'basic');

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
    email: props.workosUser?.email ?? props.employee?.pending_email ?? '',
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
    profile_picture: null as File | null,
});

// Confirmation dialog for unsaved changes
const confirm = useConfirm();
let removeBeforeListener: (() => void) | null = null;
let pendingNavigation: string | null = null;
let isSubmitting = false;

function handleBeforeUnload(e: BeforeUnloadEvent) {
    // Only warn about unsaved changes when on Basic Info tab (or in create mode)
    if (form.isDirty && (activeTab.value === 'basic' || !isEditing.value)) {
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
        // Skip dirty check if form is being submitted
        if (isSubmitting) {
            return;
        }
        // Only warn about unsaved changes when on Basic Info tab (or in create mode)
        if (form.isDirty && !pendingNavigation && (activeTab.value === 'basic' || !isEditing.value)) {
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

// Profile picture - use computed to always reflect the current state from props
// This ensures the WorkOS avatar fallback is shown after deleting a local profile picture
const profilePictureUrl = computed(() => props.employee?.profile_picture_url ?? null);

function submit() {
    isSubmitting = true;

    // Transform dates to ISO strings for the backend
    // Use custom "Other" values when selected
    form.transform((data) => ({
        ...data,
        nationality: nationalitySelection.value === 'Other' ? nationalityOther.value : nationalitySelection.value,
        country: countrySelection.value === 'Other' ? countryOther.value : countrySelection.value,
        emergency_relationship:
            relationshipSelection.value === 'Other' ? relationshipOther.value : relationshipSelection.value,
        date_of_birth: data.date_of_birth ? formatDateForBackend(data.date_of_birth as Date) : null,
        pr_conversion_date: data.pr_conversion_date ? formatDateForBackend(data.pr_conversion_date as Date) : null,
        hire_date: data.hire_date ? formatDateForBackend(data.hire_date as Date) : null,
        termination_date: isActive.value ? null : (data.termination_date ? formatDateForBackend(data.termination_date as Date) : null),
    }));

    const options = {
        onFinish: () => {
            isSubmitting = false;
        },
    };

    if (isEditing.value) {
        form.put(`/users/${props.employee!.id}`, options);
    } else {
        form.post('/users', {
            ...options,
            forceFormData: true,
        });
    }
}

function formatDateForBackend(date: Date): string {
    return date.toISOString().split('T')[0];
}

function cancel() {
    if (isEditing.value) {
        // Edit mode: use smart back to return to previous page (View or Index)
        goBack();
    } else {
        // Create mode: always go to Index page
        router.get('/users');
    }
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
                <!-- Create Mode: Simple form without tabs -->
                <Card v-if="!isEditing">
                    <template #content>
                        <form @submit.prevent="submit" class="flex flex-col gap-6">
                            <p class="text-surface-500 dark:text-surface-400 text-sm">
                                Create a new employee. They will receive an invitation email to set up their account.
                            </p>

                            <!-- Basic Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Basic Information</h3>
                                <div class="flex flex-col gap-4">
                                    <!-- Profile Picture -->
                                    <ImageSelect
                                        v-model="form.profile_picture"
                                        label="Profile Picture"
                                        placeholder-icon="pi pi-user"
                                        alt="Profile picture"
                                        :circular="true"
                                        :preview-size="96"
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
                                            <label for="email" class="font-medium">Email <span class="text-red-500">*</span></label>
                                            <InputText
                                                id="email"
                                                v-model="form.email"
                                                type="email"
                                                :invalid="!!form.errors.email"
                                                placeholder="john.doe@example.com"
                                                size="small"
                                                fluid
                                                required
                                            />
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
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.role" class="text-red-500">
                                            {{ form.errors.role }}
                                        </small>
                                    </div>

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
                                </div>
                            </div>

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
                                    <Editor
                                        id="notes"
                                        v-model="form.notes"
                                        editorStyle="height: 200px"
                                        :class="{ 'border-red-500 rounded-lg': !!form.errors.notes }"
                                    />
                                    <small v-if="form.errors.notes" class="text-red-500">
                                        {{ form.errors.notes }}
                                    </small>
                                </div>
                            </div>

                            <Divider />

                            <!-- Available After Creation Notice -->
                            <div class="rounded-lg border border-dashed border-surface-300 bg-surface-50 p-4 dark:border-surface-600 dark:bg-surface-800/50">
                                <h3 class="mb-3 text-sm font-medium text-muted-foreground">Available After Creation</h3>
                                <p class="mb-4 text-sm text-muted-foreground">
                                    The following sections will be available once the employee is created:
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-surface-200 px-2.5 py-1 text-sm text-muted-foreground dark:bg-surface-700">
                                        <i class="pi pi-building text-xs"></i>
                                        Companies
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-surface-200 px-2.5 py-1 text-sm text-muted-foreground dark:bg-surface-700">
                                        <i class="pi pi-file text-xs"></i>
                                        Contracts
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-surface-200 px-2.5 py-1 text-sm text-muted-foreground dark:bg-surface-700">
                                        <i class="pi pi-shield text-xs"></i>
                                        Insurances
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-surface-200 px-2.5 py-1 text-sm text-muted-foreground dark:bg-surface-700">
                                        <i class="pi pi-shop text-xs"></i>
                                        Stores
                                    </span>
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
                                    label="Create User"
                                    size="small"
                                    :loading="form.processing"
                                />
                            </div>
                        </form>
                    </template>
                </Card>

                <!-- Edit Mode: Tab-based layout -->
                <Card v-else>
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
                                    <form @submit.prevent="submit" class="flex flex-col gap-6 pt-4">
                                        <Message v-if="hasWorkOSAccount" severity="info" :closable="false">
                                            This user is managed via WorkOS. Changes to name and role will sync to WorkOS.
                                        </Message>

                                        <Message v-if="isPendingConfirmation" severity="warn" :closable="false">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                <span>
                                                    This account is pending confirmation. An invitation was sent to
                                                    <strong>{{ employee?.pending_email }}</strong>.
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

                                        <Message v-else-if="!hasWorkOSAccount" severity="warn" :closable="false">
                                            This employee does not have a WorkOS account. Only local employee data will be updated.
                                        </Message>

                                        <!-- Basic Information -->
                                        <div>
                                            <h3 class="mb-4 text-lg font-medium">Basic Information</h3>
                                            <div class="flex flex-col gap-4">
                                                <!-- Profile Picture for edit mode -->
                                                <ImageUpload
                                                    v-if="employee"
                                                    :image-url="profilePictureUrl"
                                                    :upload-url="`/users/${employee.id}/profile-picture`"
                                                    :delete-url="`/users/${employee.id}/profile-picture`"
                                                    field-name="profile_picture"
                                                    label="Profile Picture"
                                                    placeholder-icon="pi pi-user"
                                                    alt="Profile picture"
                                                    :circular="true"
                                                    :preview-size="96"
                                                />

                                                <div class="grid gap-4 sm:grid-cols-2">
                                                    <div class="flex flex-col gap-2">
                                                        <label for="edit_first_name" class="font-medium">First Name</label>
                                                        <InputText
                                                            id="edit_first_name"
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
                                                        <label for="edit_last_name" class="font-medium">Last Name</label>
                                                        <InputText
                                                            id="edit_last_name"
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
                                                        <label for="edit_chinese_name" class="font-medium">Chinese Name</label>
                                                        <InputText
                                                            id="edit_chinese_name"
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
                                                        <label for="edit_nric" class="font-medium">NRIC</label>
                                                        <InputText
                                                            id="edit_nric"
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
                                                        <label for="edit_email" class="font-medium">Email</label>
                                                        <InputText
                                                            id="edit_email"
                                                            v-model="form.email"
                                                            type="email"
                                                            :invalid="!!form.errors.email"
                                                            disabled
                                                            placeholder="john.doe@example.com"
                                                            size="small"
                                                            fluid
                                                        />
                                                        <small class="text-muted-foreground">
                                                            Email cannot be changed after account creation.
                                                        </small>
                                                        <small v-if="form.errors.email" class="text-red-500">
                                                            {{ form.errors.email }}
                                                        </small>
                                                    </div>

                                                    <div class="flex flex-col gap-2">
                                                        <label for="edit_employee_number" class="font-medium">Employee Number</label>
                                                        <InputText
                                                            id="edit_employee_number"
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
                                                        <label for="edit_phone" class="font-medium">Phone</label>
                                                        <InputText
                                                            id="edit_phone"
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
                                                        <label for="edit_mobile" class="font-medium">Mobile</label>
                                                        <InputText
                                                            id="edit_mobile"
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
                                                        <label for="edit_gender" class="font-medium">Gender</label>
                                                        <Select
                                                            id="edit_gender"
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
                                                        <label for="edit_date_of_birth" class="font-medium">Date of Birth</label>
                                                        <DatePicker
                                                            id="edit_date_of_birth"
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
                                                        <label for="edit_nationality" class="font-medium">Nationality</label>
                                                        <Select
                                                            id="edit_nationality"
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
                                                        <label for="edit_race" class="font-medium">Race</label>
                                                        <InputText
                                                            id="edit_race"
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
                                                        <label for="edit_residency_status" class="font-medium">Residency Status</label>
                                                        <Select
                                                            id="edit_residency_status"
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
                                                        <label for="edit_pr_conversion_date" class="font-medium">PR Conversion Date</label>
                                                        <DatePicker
                                                            id="edit_pr_conversion_date"
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
                                                    <label for="edit_address_1" class="font-medium">Address Line 1</label>
                                                    <InputText
                                                        id="edit_address_1"
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
                                                    <label for="edit_address_2" class="font-medium">Address Line 2</label>
                                                    <InputText
                                                        id="edit_address_2"
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
                                                        <label for="edit_city" class="font-medium">City</label>
                                                        <InputText
                                                            id="edit_city"
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
                                                        <label for="edit_state" class="font-medium">State</label>
                                                        <InputText
                                                            id="edit_state"
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
                                                        <label for="edit_postal_code" class="font-medium">Postal Code</label>
                                                        <InputText
                                                            id="edit_postal_code"
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
                                                        <label for="edit_country" class="font-medium">Country</label>
                                                        <Select
                                                            id="edit_country"
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
                                                    <label for="edit_role" class="font-medium">Role</label>
                                                    <Select
                                                        id="edit_role"
                                                        v-model="form.role"
                                                        :options="roleOptions"
                                                        option-label="label"
                                                        option-value="value"
                                                        :invalid="!!form.errors.role"
                                                        :disabled="!hasWorkOSAccount"
                                                        size="small"
                                                        fluid
                                                    />
                                                    <small v-if="!hasWorkOSAccount" class="text-muted-foreground">
                                                        Role can only be changed for users with WorkOS accounts.
                                                    </small>
                                                    <small v-if="form.errors.role" class="text-red-500">
                                                        {{ form.errors.role }}
                                                    </small>
                                                </div>

                                                <div class="grid gap-4 sm:grid-cols-2">
                                                    <div class="flex flex-col gap-2">
                                                        <label for="edit_hire_date" class="font-medium">Hire Date</label>
                                                        <DatePicker
                                                            id="edit_hire_date"
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

                                                    <div class="flex flex-col gap-2">
                                                        <label class="font-medium">Status</label>
                                                        <div class="flex h-[30px] items-center gap-3">
                                                            <ToggleSwitch v-model="isActive" />
                                                            <span :class="isActive ? 'text-green-600' : 'text-red-600'">
                                                                {{ isActive ? 'Active' : 'Terminated' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div v-if="!isActive" class="flex flex-col gap-2">
                                                    <label for="edit_termination_date" class="font-medium">Termination Date</label>
                                                    <DatePicker
                                                        id="edit_termination_date"
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

                                        <Divider />

                                        <!-- Bank Account Details -->
                                        <div>
                                            <h3 class="mb-4 text-lg font-medium">Bank Account Details</h3>
                                            <div class="flex flex-col gap-4">
                                                <div class="grid gap-4 sm:grid-cols-2">
                                                    <div class="flex flex-col gap-2">
                                                        <label for="edit_bank_name" class="font-medium">Bank Name</label>
                                                        <InputText
                                                            id="edit_bank_name"
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
                                                        <label for="edit_bank_account_number" class="font-medium">Bank Account Number</label>
                                                        <InputText
                                                            id="edit_bank_account_number"
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
                                                        <label for="edit_emergency_name" class="font-medium">Contact Name</label>
                                                        <InputText
                                                            id="edit_emergency_name"
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
                                                        <label for="edit_emergency_relationship" class="font-medium">Relationship</label>
                                                        <Select
                                                            id="edit_emergency_relationship"
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
                                                    <label for="edit_emergency_contact" class="font-medium">Contact Number</label>
                                                    <InputText
                                                        id="edit_emergency_contact"
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
                                                    <label for="edit_emergency_address_1" class="font-medium">Address Line 1</label>
                                                    <InputText
                                                        id="edit_emergency_address_1"
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
                                                    <label for="edit_emergency_address_2" class="font-medium">Address Line 2</label>
                                                    <InputText
                                                        id="edit_emergency_address_2"
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
                                                <Editor
                                                    id="edit_notes"
                                                    v-model="form.notes"
                                                    editorStyle="height: 200px"
                                                    :class="{ 'border-red-500 rounded-lg': !!form.errors.notes }"
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
                                                label="Save Changes"
                                                size="small"
                                                :loading="form.processing"
                                            />
                                        </div>
                                    </form>
                                </TabPanel>

                                <TabPanel value="companies">
                                    <div class="pt-4">
                                        <EmployeeCompaniesSection
                                            v-if="employee"
                                            :employee-id="employee.id"
                                            :employee-companies="employeeCompanies ?? []"
                                            :companies="companies ?? []"
                                            :designations="designations ?? []"
                                        />
                                    </div>
                                </TabPanel>

                                <TabPanel value="contracts">
                                    <div class="pt-4">
                                        <EmployeeContractsSection
                                            v-if="employee"
                                            :employee-id="employee.id"
                                            :contracts="contracts ?? []"
                                            :companies="companies ?? []"
                                        />
                                    </div>
                                </TabPanel>

                                <TabPanel value="insurances">
                                    <div class="pt-4">
                                        <EmployeeInsurancesSection
                                            v-if="employee"
                                            :employee-id="employee.id"
                                            :insurances="insurances ?? []"
                                        />
                                    </div>
                                </TabPanel>

                                <TabPanel value="stores">
                                    <div class="pt-4">
                                        <EmployeeStoresSection
                                            v-if="employee"
                                            :employee-id="employee.id"
                                            :stores="stores ?? []"
                                        />
                                    </div>
                                </TabPanel>

                                <TabPanel v-if="isAdmin" value="hierarchy">
                                    <div class="pt-4">
                                        <EmployeeHierarchySection
                                            v-if="employee"
                                            :employee-id="employee.id"
                                        />
                                    </div>
                                </TabPanel>
                            </TabPanels>
                        </Tabs>
                    </template>
                </Card>
            </div>
        </div>

        <ConfirmDialog group="unsavedChanges" />
        <ConfirmDialog />
    </AppLayout>
</template>
