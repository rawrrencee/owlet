<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import Divider from 'primevue/divider';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import ToggleSwitch from 'primevue/toggleswitch';
import { computed, ref } from 'vue';
import ImageSelect from '@/components/ImageSelect.vue';
import ImageUpload from '@/components/ImageUpload.vue';
import StoreCurrenciesSection from '@/components/stores/StoreCurrenciesSection.vue';
import StoreEmployeesSection from '@/components/stores/StoreEmployeesSection.vue';
import { useSmartBack } from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Company, type Country, type Currency, type Store } from '@/types';

interface EmployeeOption {
    id: number;
    first_name: string;
    last_name: string;
    employee_number: string | null;
}

interface Props {
    store: Store | null;
    companies: Company[];
    employees?: EmployeeOption[];
    currencies?: Currency[];
    countries?: Country[];
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.store);
const pageTitle = computed(() => (isEditing.value ? 'Edit Store' : 'Create Store'));

const { goBack } = useSmartBack('/stores');

// Active tab for edit mode
const activeTab = ref('basic');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stores', href: '/stores' },
    { title: isEditing.value ? 'Edit' : 'Create' },
];

const companyOptions = computed(() => [
    { label: 'No Company', value: null },
    ...props.companies.map(c => ({ label: c.company_name, value: c.id })),
]);

const countryOptions = computed(() =>
    (props.countries ?? []).map((c) => ({ label: c.name, value: c.id })),
);

const form = useForm({
    store_name: props.store?.store_name ?? '',
    store_code: props.store?.store_code ?? '',
    company_id: props.store?.company_id ?? null,
    email: props.store?.email ?? '',
    phone_number: props.store?.phone_number ?? '',
    mobile_number: props.store?.mobile_number ?? '',
    address_1: props.store?.address_1 ?? '',
    address_2: props.store?.address_2 ?? '',
    country_id: props.store?.country_id ?? null,
    website: props.store?.website ?? '',
    active: props.store?.active ?? true,
    include_tax: props.store?.include_tax ?? false,
    tax_percentage: Number(props.store?.tax_percentage ?? 0),
    logo: null as File | null,
});

// Logo state for edit mode
const logoUrl = ref<string | null>(props.store?.logo_url ?? null);

function submit() {
    if (isEditing.value) {
        form.put(`/stores/${props.store!.id}`);
    } else {
        form.post('/stores', {
            forceFormData: true,
        });
    }
}

function cancel() {
    if (isEditing.value) {
        goBack();
    } else {
        router.get('/stores');
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
                <h1 class="heading-lg">{{ pageTitle }}</h1>
            </div>

            <div class="mx-auto w-full max-w-4xl">
                <!-- Create Mode: Simple form without tabs -->
                <Card v-if="!isEditing">
                    <template #content>
                        <form @submit.prevent="submit" class="flex flex-col gap-6">
                            <!-- Logo for create mode -->
                            <ImageSelect
                                v-model="form.logo"
                                label="Store Logo"
                                placeholder-icon="pi pi-shop"
                                alt="Store logo"
                                :circular="false"
                                :preview-size="96"
                            />

                            <Divider />

                            <!-- Basic Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Basic Information</h3>
                                <div class="flex flex-col gap-4">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="store_name" class="font-medium">Store Name *</label>
                                            <InputText
                                                id="store_name"
                                                v-model="form.store_name"
                                                :invalid="!!form.errors.store_name"
                                                placeholder="Main Store"
                                                size="small"
                                                fluid
                                            />
                                            <small v-if="form.errors.store_name" class="text-red-500">
                                                {{ form.errors.store_name }}
                                            </small>
                                        </div>

                                        <div class="flex flex-col gap-2">
                                            <label for="store_code" class="font-medium">Store Code *</label>
                                            <InputText
                                                id="store_code"
                                                v-model="form.store_code"
                                                :invalid="!!form.errors.store_code"
                                                placeholder="MAIN"
                                                maxlength="4"
                                                size="small"
                                                fluid
                                                class="uppercase"
                                            />
                                            <small class="text-muted-foreground">Max 4 characters, must be unique</small>
                                            <small v-if="form.errors.store_code" class="text-red-500">
                                                {{ form.errors.store_code }}
                                            </small>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="company_id" class="font-medium">Company</label>
                                        <Select
                                            id="company_id"
                                            v-model="form.company_id"
                                            :options="companyOptions"
                                            option-label="label"
                                            option-value="value"
                                            placeholder="Select a company (optional)"
                                            :invalid="!!form.errors.company_id"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.company_id" class="text-red-500">
                                            {{ form.errors.company_id }}
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
                                                placeholder="store@company.com"
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
                                                placeholder="https://store.com"
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
                                            placeholder="#01-234 Shopping Mall"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.address_2" class="text-red-500">
                                            {{ form.errors.address_2 }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="country_id" class="font-medium">Country</label>
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
                                        <small v-if="form.errors.country_id" class="text-red-500">
                                            {{ form.errors.country_id }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Tax Settings -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Tax Settings</h3>
                                <div class="flex flex-col gap-4">
                                    <div class="flex flex-col gap-2">
                                        <label for="tax_percentage" class="font-medium">Tax Percentage (%)</label>
                                        <InputNumber
                                            id="tax_percentage"
                                            v-model="form.tax_percentage"
                                            :invalid="!!form.errors.tax_percentage"
                                            :min="0"
                                            :max="100"
                                            :minFractionDigits="2"
                                            :maxFractionDigits="2"
                                            suffix="%"
                                            size="small"
                                            class="w-full sm:w-48"
                                        />
                                        <small v-if="form.errors.tax_percentage" class="text-red-500">
                                            {{ form.errors.tax_percentage }}
                                        </small>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <ToggleSwitch v-model="form.include_tax" />
                                        <div class="flex flex-col">
                                            <span>Tax Inclusive Pricing</span>
                                            <small class="text-muted-foreground">
                                                {{ form.include_tax ? 'Prices already include tax' : 'Tax will be added to prices' }}
                                            </small>
                                        </div>
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
                                    label="Create Store"
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
                                <Tab value="employees">Assigned Employees</Tab>
                                <Tab value="currencies">Currencies</Tab>
                            </TabList>
                            <TabPanels>
                                <TabPanel value="basic">
                                    <form @submit.prevent="submit" class="flex flex-col gap-6 pt-4">
                                        <!-- Logo for edit mode -->
                                        <ImageUpload
                                            v-if="store"
                                            :image-url="logoUrl"
                                            :upload-url="`/stores/${store.id}/logo`"
                                            :delete-url="`/stores/${store.id}/logo`"
                                            field-name="logo"
                                            label="Store Logo"
                                            placeholder-icon="pi pi-shop"
                                            alt="Store logo"
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
                                                <div class="grid gap-4 sm:grid-cols-2">
                                                    <div class="flex flex-col gap-2">
                                                        <label for="edit_store_name" class="font-medium">Store Name *</label>
                                                        <InputText
                                                            id="edit_store_name"
                                                            v-model="form.store_name"
                                                            :invalid="!!form.errors.store_name"
                                                            placeholder="Main Store"
                                                            size="small"
                                                            fluid
                                                        />
                                                        <small v-if="form.errors.store_name" class="text-red-500">
                                                            {{ form.errors.store_name }}
                                                        </small>
                                                    </div>

                                                    <div class="flex flex-col gap-2">
                                                        <label for="edit_store_code" class="font-medium">Store Code *</label>
                                                        <InputText
                                                            id="edit_store_code"
                                                            v-model="form.store_code"
                                                            :invalid="!!form.errors.store_code"
                                                            placeholder="MAIN"
                                                            maxlength="4"
                                                            size="small"
                                                            fluid
                                                            class="uppercase"
                                                        />
                                                        <small class="text-muted-foreground">Max 4 characters, must be unique</small>
                                                        <small v-if="form.errors.store_code" class="text-red-500">
                                                            {{ form.errors.store_code }}
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="flex flex-col gap-2">
                                                    <label for="edit_company_id" class="font-medium">Company</label>
                                                    <Select
                                                        id="edit_company_id"
                                                        v-model="form.company_id"
                                                        :options="companyOptions"
                                                        option-label="label"
                                                        option-value="value"
                                                        placeholder="Select a company (optional)"
                                                        :invalid="!!form.errors.company_id"
                                                        size="small"
                                                        fluid
                                                    />
                                                    <small v-if="form.errors.company_id" class="text-red-500">
                                                        {{ form.errors.company_id }}
                                                    </small>
                                                </div>

                                                <div class="grid gap-4 sm:grid-cols-2">
                                                    <div class="flex flex-col gap-2">
                                                        <label for="edit_email" class="font-medium">Email</label>
                                                        <InputText
                                                            id="edit_email"
                                                            v-model="form.email"
                                                            type="email"
                                                            :invalid="!!form.errors.email"
                                                            placeholder="store@company.com"
                                                            size="small"
                                                            fluid
                                                        />
                                                        <small v-if="form.errors.email" class="text-red-500">
                                                            {{ form.errors.email }}
                                                        </small>
                                                    </div>

                                                    <div class="flex flex-col gap-2">
                                                        <label for="edit_website" class="font-medium">Website</label>
                                                        <InputText
                                                            id="edit_website"
                                                            v-model="form.website"
                                                            :invalid="!!form.errors.website"
                                                            placeholder="https://store.com"
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
                                                        <label for="edit_phone_number" class="font-medium">Phone Number</label>
                                                        <InputText
                                                            id="edit_phone_number"
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
                                                        <label for="edit_mobile_number" class="font-medium">Mobile Number</label>
                                                        <InputText
                                                            id="edit_mobile_number"
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
                                                    <label for="edit_address_1" class="font-medium">Address Line 1</label>
                                                    <InputText
                                                        id="edit_address_1"
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
                                                    <label for="edit_address_2" class="font-medium">Address Line 2</label>
                                                    <InputText
                                                        id="edit_address_2"
                                                        v-model="form.address_2"
                                                        :invalid="!!form.errors.address_2"
                                                        placeholder="#01-234 Shopping Mall"
                                                        size="small"
                                                        fluid
                                                    />
                                                    <small v-if="form.errors.address_2" class="text-red-500">
                                                        {{ form.errors.address_2 }}
                                                    </small>
                                                </div>

                                                <div class="flex flex-col gap-2">
                                                    <label for="edit_country_id" class="font-medium">Country</label>
                                                    <Select
                                                        id="edit_country_id"
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
                                                    <small v-if="form.errors.country_id" class="text-red-500">
                                                        {{ form.errors.country_id }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <Divider />

                                        <!-- Tax Settings -->
                                        <div>
                                            <h3 class="mb-4 text-lg font-medium">Tax Settings</h3>
                                            <div class="flex flex-col gap-4">
                                                <div class="flex flex-col gap-2">
                                                    <label for="edit_tax_percentage" class="font-medium">Tax Percentage (%)</label>
                                                    <InputNumber
                                                        id="edit_tax_percentage"
                                                        v-model="form.tax_percentage"
                                                        :invalid="!!form.errors.tax_percentage"
                                                        :min="0"
                                                        :max="100"
                                                        :minFractionDigits="2"
                                                        :maxFractionDigits="2"
                                                        suffix="%"
                                                        size="small"
                                                        class="w-full sm:w-48"
                                                    />
                                                    <small v-if="form.errors.tax_percentage" class="text-red-500">
                                                        {{ form.errors.tax_percentage }}
                                                    </small>
                                                </div>

                                                <div class="flex items-center gap-3">
                                                    <ToggleSwitch v-model="form.include_tax" />
                                                    <div class="flex flex-col">
                                                        <span>Tax Inclusive Pricing</span>
                                                        <small class="text-muted-foreground">
                                                            {{ form.include_tax ? 'Prices already include tax' : 'Tax will be added to prices' }}
                                                        </small>
                                                    </div>
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
                                                label="Save Changes"
                                                size="small"
                                                :loading="form.processing"
                                            />
                                        </div>
                                    </form>
                                </TabPanel>
                                <TabPanel value="employees">
                                    <div class="pt-4">
                                        <StoreEmployeesSection
                                            v-if="store && employees"
                                            :store-id="store.id"
                                            :employees="employees"
                                        />
                                    </div>
                                </TabPanel>
                                <TabPanel value="currencies">
                                    <div class="pt-4">
                                        <StoreCurrenciesSection
                                            v-if="store && currencies"
                                            :store-id="store.id"
                                            :currencies="currencies"
                                        />
                                    </div>
                                </TabPanel>
                            </TabPanels>
                        </Tabs>
                    </template>
                </Card>
            </div>
        </div>

        <ConfirmDialog />
    </AppLayout>
</template>
