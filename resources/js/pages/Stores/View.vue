<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import Tag from 'primevue/tag';
import { onMounted, onUnmounted, ref } from 'vue';
import AuditInfo from '@/components/AuditInfo.vue';
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type EmployeeStoreView,
    type Store,
    type StoreCurrency,
} from '@/types';
import { type HasAuditTrail } from '@/types/audit';

interface Props {
    store: Store & HasAuditTrail;
    employeeStores: EmployeeStoreView[];
    storeCurrencies: StoreCurrency[];
}

const props = defineProps<Props>();

const expandedEmployeeRows = ref({});
const expandedCurrencyRows = ref({});

// Clear expanded rows when viewport reaches breakpoint where all columns are visible
function handleResize() {
    const width = window.innerWidth;
    // lg breakpoint (1024px) - all currency columns visible
    if (width >= 1024) {
        expandedCurrencyRows.value = {};
    }
    // md breakpoint (768px) - all employee columns visible
    if (width >= 768) {
        expandedEmployeeRows.value = {};
    }
}

onMounted(() => {
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});

function formatExchangeRate(rate: number | string | null): string {
    if (rate === null || rate === undefined) return '-';
    const num = typeof rate === 'string' ? parseFloat(rate) : rate;
    // Format with up to 4 decimal places, remove trailing zeros
    return parseFloat(num.toFixed(4)).toString();
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stores', href: '/stores' },
    { title: props.store.store_name },
];

function getInitials(): string {
    const words = props.store.store_name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return props.store.store_name.substring(0, 2).toUpperCase();
}

function formatPercentage(value: number | string | null): string {
    if (value === null || value === undefined) return '0.00%';
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return `${num.toFixed(2)}%`;
}

function navigateToEdit() {
    router.get(`/stores/${props.store.id}/edit`);
}

function navigateToEmployee(employeeId: number) {
    router.get(`/users/${employeeId}`);
}

function getEmployeeInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}
</script>

<template>
    <Head :title="store.store_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-4">
                    <BackButton fallback-url="/stores" />
                    <h1 class="heading-lg">{{ store.store_name }}</h1>
                    <Tag
                        :value="store.active ? 'Active' : 'Inactive'"
                        :severity="store.active ? 'success' : 'danger'"
                    />
                </div>
                <Button
                    label="Edit"
                    icon="pi pi-pencil"
                    size="small"
                    @click="navigateToEdit"
                />
            </div>

            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <template #content>
                        <div class="flex flex-col gap-6">
                            <!-- Store Header -->
                            <div
                                class="flex flex-col items-center gap-4 sm:flex-row sm:items-start"
                            >
                                <Image
                                    v-if="store.logo_url"
                                    :src="store.logo_url"
                                    :alt="store.store_name"
                                    image-class="!h-24 !w-24 rounded-lg object-cover cursor-pointer"
                                    :pt="{
                                        root: {
                                            class: 'rounded-lg overflow-hidden',
                                        },
                                        previewMask: { class: 'rounded-lg' },
                                    }"
                                    preview
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials()"
                                    class="!h-24 !w-24 bg-primary/10 text-3xl text-primary"
                                />
                                <div
                                    class="flex flex-col gap-1 text-center sm:text-left"
                                >
                                    <h2 class="text-xl font-semibold">
                                        {{ store.store_name }}
                                    </h2>
                                    <Tag
                                        :value="store.store_code"
                                        severity="secondary"
                                        class="self-center sm:self-start"
                                    />
                                    <p
                                        v-if="store.company"
                                        class="text-muted-foreground"
                                    >
                                        {{ store.company.company_name }}
                                    </p>
                                </div>
                            </div>

                            <Divider />

                            <!-- Contact Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Contact Information
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Email</span
                                        >
                                        <span>{{ store.email ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Website</span
                                        >
                                        <a
                                            v-if="store.website"
                                            :href="store.website"
                                            target="_blank"
                                            class="text-primary hover:underline"
                                        >
                                            {{ store.website }}
                                        </a>
                                        <span v-else>-</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Phone Number</span
                                        >
                                        <span>{{
                                            store.phone_number ?? '-'
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Mobile Number</span
                                        >
                                        <span>{{
                                            store.mobile_number ?? '-'
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
                                    <span v-if="store.address_1">{{
                                        store.address_1
                                    }}</span>
                                    <span v-if="store.address_2">{{
                                        store.address_2
                                    }}</span>
                                    <span v-if="store.country_name">{{
                                        store.country_name
                                    }}</span>
                                    <span
                                        v-if="
                                            !store.address_1 &&
                                            !store.address_2 &&
                                            !store.country_name
                                        "
                                        class="text-muted-foreground"
                                    >
                                        No address provided
                                    </span>
                                </div>
                            </div>

                            <Divider />

                            <!-- Tax Settings -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Tax Settings
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Tax Percentage</span
                                        >
                                        <span>{{
                                            formatPercentage(
                                                store.tax_percentage,
                                            )
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Tax Inclusive Pricing</span
                                        >
                                        <span>{{
                                            store.include_tax
                                                ? 'Prices include tax'
                                                : 'Tax added to prices'
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Store Currencies -->
                            <template
                                v-if="
                                    storeCurrencies &&
                                    storeCurrencies.length > 0
                                "
                            >
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">
                                        Currencies
                                    </h3>
                                    <DataTable
                                        v-model:expandedRows="
                                            expandedCurrencyRows
                                        "
                                        :value="storeCurrencies"
                                        dataKey="id"
                                        size="small"
                                        stripedRows
                                        class="rounded-lg border border-border"
                                    >
                                        <Column
                                            expander
                                            style="width: 3rem"
                                            class="!pr-0 lg:hidden"
                                        />
                                        <Column
                                            field="currency.code"
                                            header="Code"
                                        >
                                            <template #body="{ data }">
                                                <span class="font-medium">{{
                                                    data.currency?.code
                                                }}</span>
                                            </template>
                                        </Column>
                                        <Column
                                            field="currency.name"
                                            header="Name"
                                            class="hidden sm:table-cell"
                                        >
                                            <template #body="{ data }">
                                                {{ data.currency?.name ?? '-' }}
                                            </template>
                                        </Column>
                                        <Column
                                            field="currency.symbol"
                                            header="Symbol"
                                            class="hidden md:table-cell"
                                        >
                                            <template #body="{ data }">
                                                {{
                                                    data.currency?.symbol ?? '-'
                                                }}
                                            </template>
                                        </Column>
                                        <Column
                                            field="exchange_rate"
                                            class="hidden lg:table-cell"
                                        >
                                            <template #header>
                                                <span
                                                    v-tooltip.top="
                                                        'Exchange rate relative to SGD (base currency)'
                                                    "
                                                >
                                                    Exchange Rate
                                                    <i
                                                        class="pi pi-info-circle ml-1 text-xs text-muted-foreground"
                                                    ></i>
                                                </span>
                                            </template>
                                            <template #body="{ data }">
                                                {{
                                                    formatExchangeRate(
                                                        data.currency
                                                            ?.exchange_rate,
                                                    )
                                                }}
                                            </template>
                                        </Column>
                                        <template #expansion="{ data }">
                                            <div
                                                class="grid gap-3 p-3 text-sm lg:hidden"
                                            >
                                                <div
                                                    class="flex justify-between gap-4 border-b border-border pb-2 sm:hidden"
                                                >
                                                    <span
                                                        class="shrink-0 text-muted-foreground"
                                                        >Name</span
                                                    >
                                                    <span class="text-right">{{
                                                        data.currency?.name ??
                                                        '-'
                                                    }}</span>
                                                </div>
                                                <div
                                                    class="flex justify-between gap-4 border-b border-border pb-2 md:hidden"
                                                >
                                                    <span
                                                        class="shrink-0 text-muted-foreground"
                                                        >Symbol</span
                                                    >
                                                    <span class="text-right">{{
                                                        data.currency?.symbol ??
                                                        '-'
                                                    }}</span>
                                                </div>
                                                <div
                                                    class="flex justify-between gap-4"
                                                >
                                                    <span
                                                        class="shrink-0 text-muted-foreground"
                                                    >
                                                        Exchange Rate
                                                        <span class="text-xs"
                                                            >(vs SGD)</span
                                                        >
                                                    </span>
                                                    <span class="text-right">{{
                                                        formatExchangeRate(
                                                            data.currency
                                                                ?.exchange_rate,
                                                        )
                                                    }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </DataTable>
                                </div>
                            </template>

                            <template v-else>
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">
                                        Currencies
                                    </h3>
                                    <p class="text-muted-foreground">
                                        No currencies assigned to this store.
                                    </p>
                                </div>
                            </template>

                            <!-- Assigned Employees -->
                            <template
                                v-if="
                                    employeeStores && employeeStores.length > 0
                                "
                            >
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">
                                        Assigned Employees
                                    </h3>
                                    <DataTable
                                        v-model:expandedRows="
                                            expandedEmployeeRows
                                        "
                                        :value="employeeStores"
                                        dataKey="id"
                                        size="small"
                                        stripedRows
                                        class="rounded-lg border border-border"
                                    >
                                        <Column
                                            expander
                                            style="width: 3rem"
                                            class="!pr-0 md:hidden"
                                        />
                                        <Column
                                            field="employee_name"
                                            header="Name"
                                        >
                                            <template #body="{ data }">
                                                <div
                                                    class="flex items-center gap-3"
                                                >
                                                    <Image
                                                        v-if="
                                                            data.profile_picture_url
                                                        "
                                                        :src="
                                                            data.profile_picture_url
                                                        "
                                                        :alt="
                                                            data.employee_name
                                                        "
                                                        image-class="!h-8 !w-8 rounded-full object-cover cursor-pointer"
                                                        :pt="{
                                                            root: {
                                                                class: 'rounded-full overflow-hidden shrink-0',
                                                            },
                                                            previewMask: {
                                                                class: 'rounded-full',
                                                            },
                                                        }"
                                                        preview
                                                    />
                                                    <Avatar
                                                        v-else
                                                        :label="
                                                            getEmployeeInitials(
                                                                data.employee_name,
                                                            )
                                                        "
                                                        shape="circle"
                                                        class="!h-8 !w-8 shrink-0 bg-primary/10 text-primary"
                                                    />
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <button
                                                            type="button"
                                                            class="text-left font-medium text-primary hover:underline"
                                                            @click="
                                                                navigateToEmployee(
                                                                    data.employee_id,
                                                                )
                                                            "
                                                        >
                                                            {{
                                                                data.employee_name
                                                            }}
                                                        </button>
                                                        <Tag
                                                            v-if="
                                                                data.is_creator
                                                            "
                                                            value="Creator"
                                                            severity="info"
                                                            class="!text-xs"
                                                        />
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>
                                        <Column
                                            field="employee_number"
                                            header="Employee #"
                                            class="hidden sm:table-cell"
                                        >
                                            <template #body="{ data }">
                                                {{
                                                    data.employee_number ?? '-'
                                                }}
                                            </template>
                                        </Column>
                                        <Column
                                            field="permissions"
                                            header="Permissions"
                                            class="hidden md:table-cell"
                                        >
                                            <template #body="{ data }">
                                                <div
                                                    class="flex flex-wrap gap-1"
                                                >
                                                    <Tag
                                                        v-for="perm in data.permissions_with_labels.slice(
                                                            0,
                                                            3,
                                                        )"
                                                        :key="perm.key"
                                                        :value="perm.label"
                                                        severity="secondary"
                                                        class="!text-xs"
                                                    />
                                                    <Tag
                                                        v-if="
                                                            data
                                                                .permissions_with_labels
                                                                .length > 3
                                                        "
                                                        :value="`+${data.permissions_with_labels.length - 3}`"
                                                        severity="info"
                                                        class="!text-xs"
                                                        v-tooltip.top="
                                                            data.permissions_with_labels
                                                                .slice(3)
                                                                .map(
                                                                    (p: any) =>
                                                                        p.label,
                                                                )
                                                                .join(', ')
                                                        "
                                                    />
                                                    <span
                                                        v-if="
                                                            !data
                                                                .permissions_with_labels
                                                                .length
                                                        "
                                                        class="text-muted-foreground"
                                                    >
                                                        No permissions
                                                    </span>
                                                </div>
                                            </template>
                                        </Column>
                                        <Column field="active" header="Status">
                                            <template #body="{ data }">
                                                <Tag
                                                    :value="
                                                        data.active
                                                            ? 'Active'
                                                            : 'Inactive'
                                                    "
                                                    :severity="
                                                        data.active
                                                            ? 'success'
                                                            : 'secondary'
                                                    "
                                                />
                                            </template>
                                        </Column>
                                        <template #expansion="{ data }">
                                            <div
                                                class="grid gap-3 p-3 text-sm md:hidden"
                                            >
                                                <div
                                                    class="flex justify-between gap-4 border-b border-border pb-2 sm:hidden"
                                                >
                                                    <span
                                                        class="shrink-0 text-muted-foreground"
                                                        >Employee #</span
                                                    >
                                                    <span class="text-right">{{
                                                        data.employee_number ??
                                                        '-'
                                                    }}</span>
                                                </div>
                                                <div
                                                    class="flex flex-col gap-2"
                                                >
                                                    <span
                                                        class="shrink-0 text-muted-foreground"
                                                        >Permissions</span
                                                    >
                                                    <div
                                                        class="flex flex-wrap gap-1"
                                                    >
                                                        <Tag
                                                            v-for="perm in data.permissions_with_labels"
                                                            :key="perm.key"
                                                            :value="perm.label"
                                                            severity="secondary"
                                                            class="!text-xs"
                                                        />
                                                        <span
                                                            v-if="
                                                                !data
                                                                    .permissions_with_labels
                                                                    .length
                                                            "
                                                            class="text-muted-foreground"
                                                        >
                                                            No permissions
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </DataTable>
                                </div>
                            </template>

                            <template v-else>
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">
                                        Assigned Employees
                                    </h3>
                                    <p class="text-muted-foreground">
                                        No employees assigned to this store.
                                    </p>
                                </div>
                            </template>

                            <Divider />

                            <!-- Audit Info -->
                            <AuditInfo
                                :created-by="store.created_by"
                                :updated-by="store.updated_by"
                                :previous-updated-by="store.previous_updated_by"
                                :created-at="store.created_at"
                                :updated-at="store.updated_at"
                                :previous-updated-at="store.previous_updated_at"
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
