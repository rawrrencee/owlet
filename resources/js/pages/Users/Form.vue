<script setup lang="ts">
import {
    countries,
    countryOptions,
    nationalities,
    nationalityOptions,
    relationships,
    relationshipOptions,
} from '@/constants/employee';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import DatePicker from 'primevue/datepicker';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref, watch } from 'vue';

interface Employee {
    id: number;
    first_name: string;
    last_name: string;
    chinese_name: string | null;
    employee_number: string | null;
    nric: string | null;
    phone: string | null;
    mobile: string | null;
    address_1: string | null;
    address_2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country: string | null;
    date_of_birth: string | null;
    gender: string | null;
    race: string | null;
    nationality: string | null;
    residency_status: string | null;
    pr_conversion_date: string | null;
    emergency_name: string | null;
    emergency_relationship: string | null;
    emergency_contact: string | null;
    emergency_address_1: string | null;
    emergency_address_2: string | null;
    bank_name: string | null;
    bank_account_number: string | null;
    hire_date: string | null;
    termination_date: string | null;
    notes: string | null;
    profile_picture_url: string | null;
    user?: {
        id: number;
        email: string;
        role: string;
        workos_id: string;
    } | null;
}

interface WorkOSUser {
    id: string;
    email: string;
    firstName: string;
    lastName: string;
    emailVerified: boolean;
    profilePictureUrl: string | null;
    createdAt: string;
    updatedAt: string;
}

interface Props {
    employee: Employee | null;
    workosUser: WorkOSUser | null;
    role?: string;
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
const isOtherValue = (value: string | null, list: readonly string[]) => {
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
const profilePictureFile = ref<File | null>(null);
const profilePicturePreview = ref<string | null>(null);
const uploadingPicture = ref(false);
const profilePictureError = ref<string | null>(null);

const confirm = useConfirm();

function onProfilePictureSelect(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
        profilePictureError.value = 'Please select an image file.';
        return;
    }

    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        profilePictureError.value = 'Image must be less than 5MB.';
        return;
    }

    profilePictureError.value = null;
    profilePictureFile.value = file;

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
        profilePicturePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
}

function uploadProfilePicture() {
    if (!profilePictureFile.value || !props.employee) return;

    uploadingPicture.value = true;
    profilePictureError.value = null;

    const formData = new FormData();
    formData.append('profile_picture', profilePictureFile.value);

    router.post(`/users/${props.employee.id}/profile-picture`, formData, {
        preserveScroll: true,
        onSuccess: () => {
            profilePictureUrl.value = `/users/${props.employee!.id}/profile-picture?t=${Date.now()}`;
            profilePictureFile.value = null;
            profilePicturePreview.value = null;
        },
        onError: (errors) => {
            profilePictureError.value = errors.profile_picture || 'Failed to upload image.';
        },
        onFinish: () => {
            uploadingPicture.value = false;
        },
    });
}

function confirmDeleteProfilePicture() {
    confirm.require({
        message: 'Are you sure you want to remove the profile picture?',
        header: 'Remove Profile Picture',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Remove',
        acceptProps: {
            severity: 'danger',
            size: 'small',
        },
        accept: deleteProfilePicture,
    });
}

function deleteProfilePicture() {
    if (!props.employee) return;

    uploadingPicture.value = true;

    router.delete(`/users/${props.employee.id}/profile-picture`, {
        preserveScroll: true,
        onSuccess: () => {
            profilePictureUrl.value = null;
            profilePictureFile.value = null;
            profilePicturePreview.value = null;
        },
        onFinish: () => {
            uploadingPicture.value = false;
        },
    });
}

function cancelProfilePictureSelection() {
    profilePictureFile.value = null;
    profilePicturePreview.value = null;
    profilePictureError.value = null;
}

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
            <div class="flex items-center justify-between">
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
                                    <div v-if="isEditing" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="relative h-24 w-24 overflow-hidden rounded-full bg-surface-200 dark:bg-surface-700">
                                                <Image
                                                    v-if="profilePicturePreview || profilePictureUrl"
                                                    :src="profilePicturePreview || profilePictureUrl || ''"
                                                    alt="Profile picture"
                                                    image-class="h-24 w-24 rounded-full object-cover cursor-pointer"
                                                    :pt="{ root: { class: 'rounded-full overflow-hidden' }, previewMask: { class: 'rounded-full' } }"
                                                    preview
                                                />
                                                <div v-else class="flex h-full w-full items-center justify-center">
                                                    <i class="pi pi-user text-3xl text-surface-400"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-1 flex-col gap-2">
                                            <label class="font-medium">Profile Picture</label>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <label class="cursor-pointer">
                                                    <input
                                                        type="file"
                                                        accept="image/*"
                                                        class="hidden"
                                                        @change="onProfilePictureSelect"
                                                        :disabled="uploadingPicture"
                                                    />
                                                    <Button
                                                        as="span"
                                                        :label="profilePictureUrl ? 'Change' : 'Upload'"
                                                        icon="pi pi-upload"
                                                        size="small"
                                                        severity="secondary"
                                                        :disabled="uploadingPicture"
                                                    />
                                                </label>
                                                <Button
                                                    v-if="profilePictureFile"
                                                    label="Save"
                                                    icon="pi pi-check"
                                                    size="small"
                                                    :loading="uploadingPicture"
                                                    @click="uploadProfilePicture"
                                                />
                                                <Button
                                                    v-if="profilePictureFile"
                                                    label="Cancel"
                                                    icon="pi pi-times"
                                                    size="small"
                                                    severity="secondary"
                                                    text
                                                    :disabled="uploadingPicture"
                                                    @click="cancelProfilePictureSelection"
                                                />
                                                <Button
                                                    v-if="profilePictureUrl && !profilePictureFile"
                                                    label="Remove"
                                                    icon="pi pi-trash"
                                                    size="small"
                                                    severity="danger"
                                                    text
                                                    :disabled="uploadingPicture"
                                                    @click="confirmDeleteProfilePicture"
                                                />
                                            </div>
                                            <small class="text-muted-foreground">
                                                Accepts JPG, PNG, GIF. Max 5MB.
                                            </small>
                                            <small v-if="profilePictureError" class="text-red-500">
                                                {{ profilePictureError }}
                                            </small>
                                        </div>
                                    </div>

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

        <ConfirmDialog />
    </AppLayout>
</template>
