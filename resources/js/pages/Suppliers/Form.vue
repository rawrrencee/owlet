<script setup lang="ts">
import BackButton from '@/components/BackButton.vue';
import ImageSelect from '@/components/ImageSelect.vue';
import ImageUpload from '@/components/ImageUpload.vue';
import {
    clearSkipPageInHistory,
    skipCurrentPageInHistory,
} from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Country, type Supplier } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Editor from 'primevue/editor';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import { computed, ref } from 'vue';

interface Props {
    supplier: Supplier | null;
    countries: Country[];
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.supplier);
const pageTitle = computed(() =>
    isEditing.value ? 'Edit Supplier' : 'Create Supplier',
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Suppliers', href: '/suppliers' },
    { title: isEditing.value ? 'Edit' : 'Create' },
];

const countryOptions = computed(() =>
    (props.countries ?? []).map((c) => ({ label: c.name, value: c.id })),
);

const form = useForm({
    supplier_name: props.supplier?.supplier_name ?? '',
    country_id: props.supplier?.country_id ?? null,
    email: props.supplier?.email ?? '',
    phone_number: props.supplier?.phone_number ?? '',
    mobile_number: props.supplier?.mobile_number ?? '',
    address_1: props.supplier?.address_1 ?? '',
    address_2: props.supplier?.address_2 ?? '',
    website: props.supplier?.website ?? '',
    description: props.supplier?.description ?? '',
    active: props.supplier?.active ?? true,
    logo: null as File | null,
});

// Logo state for edit mode
const logoUrl = ref<string | null>(props.supplier?.logo_url ?? null);

function submit() {
    if (isEditing.value) {
        skipCurrentPageInHistory();
        form.put(`/suppliers/${props.supplier!.id}`, {
            onSuccess: () => {
                router.visit(`/suppliers/${props.supplier!.id}`);
            },
            onError: () => {
                clearSkipPageInHistory();
            },
        });
    } else {
        form.post('/suppliers', {
            forceFormData: true,
        });
    }
}

function cancel() {
    if (isEditing.value) {
        router.visit(`/suppliers/${props.supplier!.id}`);
    } else {
        router.visit('/suppliers');
    }
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-4">
                <BackButton fallback-url="/suppliers" />
                <h1 class="heading-lg">{{ pageTitle }}</h1>
            </div>

            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <template #content>
                        <form
                            @submit.prevent="submit"
                            class="flex flex-col gap-6"
                        >
                            <!-- Logo for create mode -->
                            <ImageSelect
                                v-if="!isEditing"
                                v-model="form.logo"
                                label="Supplier Logo"
                                placeholder-icon="pi pi-truck"
                                alt="Supplier logo"
                                :circular="false"
                                :preview-size="96"
                            />

                            <!-- Logo for edit mode -->
                            <ImageUpload
                                v-else-if="supplier"
                                :image-url="logoUrl"
                                :upload-url="`/suppliers/${supplier.id}/logo`"
                                :delete-url="`/suppliers/${supplier.id}/logo`"
                                field-name="logo"
                                label="Supplier Logo"
                                placeholder-icon="pi pi-truck"
                                alt="Supplier logo"
                                :circular="false"
                                :preview-size="96"
                                @uploaded="(url) => (logoUrl = url)"
                                @deleted="logoUrl = null"
                            />

                            <Divider />

                            <!-- Basic Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Basic Information
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div
                                        class="flex flex-col gap-2 sm:col-span-2"
                                    >
                                        <label
                                            for="supplier_name"
                                            class="font-medium"
                                            >Supplier Name *</label
                                        >
                                        <InputText
                                            id="supplier_name"
                                            v-model="form.supplier_name"
                                            :invalid="
                                                !!form.errors.supplier_name
                                            "
                                            placeholder="ABC Supplies Inc."
                                            size="small"
                                            fluid
                                        />
                                        <small
                                            v-if="form.errors.supplier_name"
                                            class="text-red-500"
                                        >
                                            {{ form.errors.supplier_name }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Contact Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Contact Information
                                </h3>
                                <div class="flex flex-col gap-4">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label
                                                for="email"
                                                class="font-medium"
                                                >Email</label
                                            >
                                            <InputText
                                                id="email"
                                                v-model="form.email"
                                                type="email"
                                                :invalid="!!form.errors.email"
                                                placeholder="supplier@company.com"
                                                size="small"
                                                fluid
                                            />
                                            <small
                                                v-if="form.errors.email"
                                                class="text-red-500"
                                            >
                                                {{ form.errors.email }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label
                                                for="website"
                                                class="font-medium"
                                                >Website</label
                                            >
                                            <InputText
                                                id="website"
                                                v-model="form.website"
                                                :invalid="!!form.errors.website"
                                                placeholder="https://supplier.com"
                                                size="small"
                                                fluid
                                            />
                                            <small
                                                v-if="form.errors.website"
                                                class="text-red-500"
                                            >
                                                {{ form.errors.website }}
                                            </small>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label
                                                for="phone_number"
                                                class="font-medium"
                                                >Phone Number</label
                                            >
                                            <InputText
                                                id="phone_number"
                                                v-model="form.phone_number"
                                                :invalid="
                                                    !!form.errors.phone_number
                                                "
                                                placeholder="+65 6123 4567"
                                                size="small"
                                                fluid
                                            />
                                            <small
                                                v-if="form.errors.phone_number"
                                                class="text-red-500"
                                            >
                                                {{ form.errors.phone_number }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label
                                                for="mobile_number"
                                                class="font-medium"
                                                >Mobile Number</label
                                            >
                                            <InputText
                                                id="mobile_number"
                                                v-model="form.mobile_number"
                                                :invalid="
                                                    !!form.errors.mobile_number
                                                "
                                                placeholder="+65 9123 4567"
                                                size="small"
                                                fluid
                                            />
                                            <small
                                                v-if="form.errors.mobile_number"
                                                class="text-red-500"
                                            >
                                                {{ form.errors.mobile_number }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Address -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Address
                                </h3>
                                <div class="flex flex-col gap-4">
                                    <div class="flex flex-col gap-2">
                                        <label
                                            for="address_1"
                                            class="font-medium"
                                            >Address Line 1</label
                                        >
                                        <InputText
                                            id="address_1"
                                            v-model="form.address_1"
                                            :invalid="!!form.errors.address_1"
                                            placeholder="123 Business Street"
                                            size="small"
                                            fluid
                                        />
                                        <small
                                            v-if="form.errors.address_1"
                                            class="text-red-500"
                                        >
                                            {{ form.errors.address_1 }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label
                                            for="address_2"
                                            class="font-medium"
                                            >Address Line 2</label
                                        >
                                        <InputText
                                            id="address_2"
                                            v-model="form.address_2"
                                            :invalid="!!form.errors.address_2"
                                            placeholder="#01-234 Building Name"
                                            size="small"
                                            fluid
                                        />
                                        <small
                                            v-if="form.errors.address_2"
                                            class="text-red-500"
                                        >
                                            {{ form.errors.address_2 }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label
                                            for="country_id"
                                            class="font-medium"
                                            >Country</label
                                        >
                                        <Select
                                            id="country_id"
                                            v-model="form.country_id"
                                            :options="countryOptions"
                                            option-label="label"
                                            option-value="value"
                                            :invalid="!!form.errors.country_id"
                                            placeholder="Select country"
                                            filter
                                            show-clear
                                            size="small"
                                            fluid
                                        />
                                        <small
                                            v-if="form.errors.country_id"
                                            class="text-red-500"
                                        >
                                            {{ form.errors.country_id }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Description -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Description
                                </h3>
                                <div class="flex flex-col gap-2">
                                    <Editor
                                        v-model="form.description"
                                        editor-style="height: 200px"
                                        :pt="{
                                            root: { class: 'border-border' },
                                            toolbar: {
                                                class: 'border-border bg-muted/50',
                                            },
                                            content: { class: 'border-border' },
                                        }"
                                    >
                                        <template #toolbar>
                                            <span class="ql-formats">
                                                <button
                                                    class="ql-bold"
                                                    v-tooltip.bottom="'Bold'"
                                                ></button>
                                                <button
                                                    class="ql-italic"
                                                    v-tooltip.bottom="'Italic'"
                                                ></button>
                                                <button
                                                    class="ql-underline"
                                                    v-tooltip.bottom="
                                                        'Underline'
                                                    "
                                                ></button>
                                            </span>
                                            <span class="ql-formats">
                                                <button
                                                    class="ql-list"
                                                    value="ordered"
                                                    v-tooltip.bottom="
                                                        'Ordered List'
                                                    "
                                                ></button>
                                                <button
                                                    class="ql-list"
                                                    value="bullet"
                                                    v-tooltip.bottom="
                                                        'Bullet List'
                                                    "
                                                ></button>
                                            </span>
                                            <span class="ql-formats">
                                                <button
                                                    class="ql-link"
                                                    v-tooltip.bottom="'Link'"
                                                ></button>
                                            </span>
                                            <span class="ql-formats">
                                                <button
                                                    class="ql-clean"
                                                    v-tooltip.bottom="
                                                        'Clear Formatting'
                                                    "
                                                ></button>
                                            </span>
                                        </template>
                                    </Editor>
                                    <small
                                        v-if="form.errors.description"
                                        class="text-red-500"
                                    >
                                        {{ form.errors.description }}
                                    </small>
                                </div>
                            </div>

                            <Divider />

                            <!-- Status -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Status</h3>
                                <div class="flex items-center gap-3">
                                    <ToggleSwitch v-model="form.active" />
                                    <span
                                        :class="
                                            form.active
                                                ? 'text-green-600'
                                                : 'text-red-600'
                                        "
                                    >
                                        {{
                                            form.active ? 'Active' : 'Inactive'
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                            >
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
                                    :label="
                                        isEditing
                                            ? 'Save Changes'
                                            : 'Create Supplier'
                                    "
                                    size="small"
                                    :loading="form.processing"
                                />
                            </div>
                        </form>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
