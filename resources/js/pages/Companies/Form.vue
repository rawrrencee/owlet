<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import Divider from 'primevue/divider';
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';
import { computed, ref } from 'vue';
import ImageSelect from '@/components/ImageSelect.vue';
import ImageUpload from '@/components/ImageUpload.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { type Company } from '@/types/company';

interface Props {
    company: Company | null;
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.company);
const pageTitle = computed(() => (isEditing.value ? 'Edit Company' : 'Create Company'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Companies', href: '/companies' },
    { title: isEditing.value ? 'Edit' : 'Create' },
];

const form = useForm({
    company_name: props.company?.company_name ?? '',
    email: props.company?.email ?? '',
    phone_number: props.company?.phone_number ?? '',
    mobile_number: props.company?.mobile_number ?? '',
    address_1: props.company?.address_1 ?? '',
    address_2: props.company?.address_2 ?? '',
    website: props.company?.website ?? '',
    active: props.company?.active ?? true,
    logo: null as File | null,
});

// Logo state for edit mode
const logoUrl = ref<string | null>(props.company?.logo_url ?? null);

function submit() {
    if (isEditing.value) {
        form.put(`/companies/${props.company!.id}`);
    } else {
        form.post('/companies', {
            forceFormData: true,
        });
    }
}

function cancel() {
    router.get('/companies');
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
                            <!-- Logo for create mode -->
                            <ImageSelect
                                v-if="!isEditing"
                                v-model="form.logo"
                                label="Company Logo"
                                placeholder-icon="pi pi-building"
                                alt="Company logo"
                                :circular="false"
                                :preview-size="96"
                            />

                            <!-- Logo for edit mode -->
                            <ImageUpload
                                v-if="isEditing && company"
                                :image-url="logoUrl"
                                :upload-url="`/companies/${company.id}/logo`"
                                :delete-url="`/companies/${company.id}/logo`"
                                field-name="logo"
                                label="Company Logo"
                                placeholder-icon="pi pi-building"
                                alt="Company logo"
                                :circular="false"
                                :preview-size="96"
                                @uploaded="(url) => logoUrl = url"
                                @deleted="logoUrl = null"
                            />

                            <Divider />

                            <!-- Basic Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Basic Information</h3>
                                <div class="flex flex-col gap-4">
                                    <div class="flex flex-col gap-2">
                                        <label for="company_name" class="font-medium">Company Name *</label>
                                        <InputText
                                            id="company_name"
                                            v-model="form.company_name"
                                            :invalid="!!form.errors.company_name"
                                            placeholder="Acme Corporation"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.company_name" class="text-red-500">
                                            {{ form.errors.company_name }}
                                        </small>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="email" class="font-medium">Email</label>
                                            <InputText
                                                id="email"
                                                v-model="form.email"
                                                type="email"
                                                :invalid="!!form.errors.email"
                                                placeholder="contact@company.com"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.email" class="text-red-500">
                                                {{ form.errors.email }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="website" class="font-medium">Website</label>
                                            <InputText
                                                id="website"
                                                v-model="form.website"
                                                :invalid="!!form.errors.website"
                                                placeholder="https://company.com"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.website" class="text-red-500">
                                                {{ form.errors.website }}
                                            </small>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="phone_number" class="font-medium">Phone Number</label>
                                            <InputText
                                                id="phone_number"
                                                v-model="form.phone_number"
                                                :invalid="!!form.errors.phone_number"
                                                placeholder="+65 6123 4567"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.phone_number" class="text-red-500">
                                                {{ form.errors.phone_number }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="mobile_number" class="font-medium">Mobile Number</label>
                                            <InputText
                                                id="mobile_number"
                                                v-model="form.mobile_number"
                                                :invalid="!!form.errors.mobile_number"
                                                placeholder="+65 9123 4567"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.mobile_number" class="text-red-500">
                                                {{ form.errors.mobile_number }}
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
                                            placeholder="123 Business Street"
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
                                            placeholder="#01-234 Business Tower"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.address_2" class="text-red-500">
                                            {{ form.errors.address_2 }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Status -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Status</h3>
                                <div class="flex items-center gap-3">
                                    <ToggleSwitch v-model="form.active" />
                                    <span :class="form.active ? 'text-green-600' : 'text-red-600'">
                                        {{ form.active ? 'Active' : 'Inactive' }}
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
                                    :label="isEditing ? 'Save Changes' : 'Create Company'"
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
