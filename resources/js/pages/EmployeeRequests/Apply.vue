<script setup lang="ts">
import { relationshipOptions } from '@/constants/employee';
import type { Country } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Divider from 'primevue/divider';
import FileUpload from 'primevue/fileupload';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { computed, ref } from 'vue';

interface Props {
    enabled: boolean;
    requiresCode: boolean;
    codeVerified: boolean;
    countries: Pick<Country, 'id' | 'name' | 'nationality_name'>[];
}

const props = defineProps<Props>();

const page = usePage();
const success = computed(() => page.props.flash && (page.props.flash as any).success);

// Code verification form
const codeForm = useForm({
    access_code: '',
});

function submitCode() {
    codeForm.post('/apply/verify');
}

// Application form
const form = useForm({
    first_name: '',
    last_name: '',
    chinese_name: '',
    email: '',
    phone: '',
    mobile: '',
    date_of_birth: null as Date | null,
    gender: null as string | null,
    race: '',
    nric: '',
    nationality: '',
    nationality_id: null as number | null,
    residency_status: '',
    address_1: '',
    address_2: '',
    city: '',
    state: '',
    postal_code: '',
    country_id: null as number | null,
    emergency_name: '',
    emergency_relationship: null as string | null,
    emergency_contact: '',
    emergency_address_1: '',
    emergency_address_2: '',
    bank_name: '',
    bank_account_number: '',
    notes: '',
    profile_picture: null as File | null,
});

const genderOptions = [
    { label: 'Male', value: 'male' },
    { label: 'Female', value: 'female' },
    { label: 'Other', value: 'other' },
];

const countryOptions = computed(() =>
    props.countries.map((c) => ({ label: c.name, value: c.id })),
);

const nationalityOptions = computed(() =>
    props.countries
        .filter((c) => c.nationality_name)
        .map((c) => ({ label: c.nationality_name!, value: c.id })),
);

const profilePictureRef = ref<File | null>(null);

function onFileSelect(event: any) {
    const file = event.files?.[0];
    if (file) {
        profilePictureRef.value = file;
        form.profile_picture = file;
    }
}

function submitForm() {
    form.post('/apply', {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Apply for a Position" />

    <div
        class="min-h-screen bg-stone-50 text-stone-900 dark:bg-stone-950 dark:text-stone-100"
    >
        <!-- Header -->
        <header
            class="mx-auto flex max-w-3xl items-center justify-between px-6 py-6"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-md bg-stone-800 text-sm font-bold text-white dark:bg-stone-200 dark:text-stone-900"
                >
                    O
                </div>
                <span class="text-lg font-semibold tracking-tight"
                    >Owlet</span
                >
            </div>
            <a
                href="/"
                class="text-sm text-stone-500 hover:text-stone-700 dark:hover:text-stone-300"
            >
                Back to Home
            </a>
        </header>

        <main class="mx-auto max-w-3xl px-6 pb-16">
            <!-- Disabled state -->
            <div
                v-if="!enabled"
                class="rounded-lg border border-stone-200 bg-white p-8 text-center dark:border-stone-700 dark:bg-stone-900"
            >
                <h1 class="text-2xl font-bold">Applications Closed</h1>
                <p class="mt-2 text-stone-600 dark:text-stone-400">
                    We are not accepting applications at this time. Please
                    check back later.
                </p>
            </div>

            <!-- Code gate -->
            <div
                v-else-if="requiresCode && !codeVerified"
                class="rounded-lg border border-stone-200 bg-white p-8 dark:border-stone-700 dark:bg-stone-900"
            >
                <h1 class="text-2xl font-bold">Access Code Required</h1>
                <p class="mt-2 text-stone-600 dark:text-stone-400">
                    Enter the access code provided to you to continue.
                </p>
                <form class="mt-6" @submit.prevent="submitCode">
                    <div class="flex gap-3">
                        <InputText
                            v-model="codeForm.access_code"
                            placeholder="Access code"
                            size="small"
                            class="flex-1"
                            :invalid="!!codeForm.errors.access_code"
                        />
                        <Button
                            type="submit"
                            label="Verify"
                            size="small"
                            :loading="codeForm.processing"
                        />
                    </div>
                    <small
                        v-if="codeForm.errors.access_code"
                        class="mt-1 text-red-500"
                    >
                        {{ codeForm.errors.access_code }}
                    </small>
                </form>
            </div>

            <!-- Success confirmation -->
            <div
                v-else-if="success"
                class="rounded-lg border border-stone-200 bg-white p-8 text-center dark:border-stone-700 dark:bg-stone-900"
            >
                <h1 class="text-2xl font-bold">Application Submitted</h1>
                <p class="mt-2 text-stone-600 dark:text-stone-400">
                    Thank you for your application. We will review it and
                    get back to you shortly.
                </p>
            </div>

            <!-- Application form -->
            <div
                v-else
                class="rounded-lg border border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-900"
            >
                <div class="border-b border-stone-200 p-6 dark:border-stone-700">
                    <h1 class="text-2xl font-bold">Apply for a Position</h1>
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                        Fill out the form below to submit your application. Fields
                        marked with * are required.
                    </p>
                </div>

                <form class="p-6" @submit.prevent="submitForm">
                    <!-- Personal Information -->
                    <h2 class="text-lg font-semibold">Personal Information</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >First Name *</label
                            >
                            <InputText
                                v-model="form.first_name"
                                size="small"
                                class="w-full"
                                :invalid="!!form.errors.first_name"
                            />
                            <small
                                v-if="form.errors.first_name"
                                class="text-red-500"
                                >{{ form.errors.first_name }}</small
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Last Name *</label
                            >
                            <InputText
                                v-model="form.last_name"
                                size="small"
                                class="w-full"
                                :invalid="!!form.errors.last_name"
                            />
                            <small
                                v-if="form.errors.last_name"
                                class="text-red-500"
                                >{{ form.errors.last_name }}</small
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Chinese Name</label
                            >
                            <InputText
                                v-model="form.chinese_name"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Email *</label
                            >
                            <InputText
                                v-model="form.email"
                                type="email"
                                size="small"
                                class="w-full"
                                :invalid="!!form.errors.email"
                            />
                            <small
                                v-if="form.errors.email"
                                class="text-red-500"
                                >{{ form.errors.email }}</small
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Phone</label
                            >
                            <InputText
                                v-model="form.phone"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Mobile</label
                            >
                            <InputText
                                v-model="form.mobile"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Gender</label
                            >
                            <Select
                                v-model="form.gender"
                                :options="genderOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Select"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Date of Birth</label
                            >
                            <DatePicker
                                v-model="form.date_of_birth"
                                size="small"
                                class="w-full"
                                date-format="dd/mm/yy"
                                :max-date="new Date()"
                                show-icon
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Race</label
                            >
                            <InputText
                                v-model="form.race"
                                size="small"
                                class="w-full"
                            />
                        </div>
                    </div>

                    <Divider />

                    <!-- Identity -->
                    <h2 class="text-lg font-semibold">Identity</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >NRIC</label
                            >
                            <InputText
                                v-model="form.nric"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Nationality</label
                            >
                            <Select
                                v-model="form.nationality_id"
                                :options="nationalityOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Select"
                                size="small"
                                class="w-full"
                                filter
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Residency Status</label
                            >
                            <InputText
                                v-model="form.residency_status"
                                size="small"
                                class="w-full"
                            />
                        </div>
                    </div>

                    <Divider />

                    <!-- Address -->
                    <h2 class="text-lg font-semibold">Address</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium"
                                >Address Line 1</label
                            >
                            <InputText
                                v-model="form.address_1"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium"
                                >Address Line 2</label
                            >
                            <InputText
                                v-model="form.address_2"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >City</label
                            >
                            <InputText
                                v-model="form.city"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >State</label
                            >
                            <InputText
                                v-model="form.state"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Postal Code</label
                            >
                            <InputText
                                v-model="form.postal_code"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Country</label
                            >
                            <Select
                                v-model="form.country_id"
                                :options="countryOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Select"
                                size="small"
                                class="w-full"
                                filter
                            />
                        </div>
                    </div>

                    <Divider />

                    <!-- Emergency Contact -->
                    <h2 class="text-lg font-semibold">Emergency Contact</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Name</label
                            >
                            <InputText
                                v-model="form.emergency_name"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Relationship</label
                            >
                            <Select
                                v-model="form.emergency_relationship"
                                :options="relationshipOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="Select"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Contact Number</label
                            >
                            <InputText
                                v-model="form.emergency_contact"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium"
                                >Address Line 1</label
                            >
                            <InputText
                                v-model="form.emergency_address_1"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium"
                                >Address Line 2</label
                            >
                            <InputText
                                v-model="form.emergency_address_2"
                                size="small"
                                class="w-full"
                            />
                        </div>
                    </div>

                    <Divider />

                    <!-- Banking -->
                    <h2 class="text-lg font-semibold">Banking</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Bank Name</label
                            >
                            <InputText
                                v-model="form.bank_name"
                                size="small"
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Account Number</label
                            >
                            <InputText
                                v-model="form.bank_account_number"
                                size="small"
                                class="w-full"
                            />
                        </div>
                    </div>

                    <Divider />

                    <!-- Additional -->
                    <h2 class="text-lg font-semibold">Additional</h2>
                    <div class="mt-4 grid gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Notes</label
                            >
                            <Textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full"
                                auto-resize
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Profile Picture</label
                            >
                            <FileUpload
                                mode="basic"
                                accept="image/*"
                                :max-file-size="5242880"
                                choose-label="Choose Photo"
                                auto
                                custom-upload
                                @select="onFileSelect"
                            />
                            <small
                                v-if="form.errors.profile_picture"
                                class="text-red-500"
                                >{{ form.errors.profile_picture }}</small
                            >
                        </div>
                    </div>

                    <!-- General errors -->
                    <Message
                        v-if="Object.keys(form.errors).length > 0"
                        severity="error"
                        class="mt-6"
                    >
                        Please correct the errors above and try again.
                    </Message>

                    <!-- Submit -->
                    <div class="mt-6 flex justify-end">
                        <Button
                            type="submit"
                            label="Submit Application"
                            size="small"
                            :loading="form.processing"
                        />
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>
