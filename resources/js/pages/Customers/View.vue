<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import AuditInfo from '@/components/AuditInfo.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Customer } from '@/types';
import { type HasAuditTrail } from '@/types/audit';

interface Props {
    customer: Customer & HasAuditTrail;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customers', href: '/users?type=customers' },
    { title: `${props.customer.first_name} ${props.customer.last_name}` },
];

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

function getInitials(): string {
    const first = props.customer.first_name?.charAt(0)?.toUpperCase() ?? '';
    const last = props.customer.last_name?.charAt(0)?.toUpperCase() ?? '';
    return `${first}${last}`;
}

function navigateToEdit() {
    router.get(`/customers/${props.customer.id}/edit`);
}

function navigateBack() {
    router.get('/users?type=customers');
}
</script>

<template>
    <Head :title="`${customer.first_name} ${customer.last_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-4">
                    <Button
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        text
                        rounded
                        size="small"
                        @click="navigateBack"
                    />
                    <h1 class="heading-lg">
                        {{ customer.first_name }} {{ customer.last_name }}
                    </h1>
                </div>
                <Button
                    label="Edit"
                    icon="pi pi-pencil"
                    size="small"
                    @click="navigateToEdit"
                />
            </div>

            <div class="mx-auto w-full max-w-2xl">
                <Card>
                    <template #content>
                        <div class="flex flex-col gap-6">
                            <!-- Profile Header -->
                            <div
                                class="flex flex-col items-center gap-4 sm:flex-row sm:items-start"
                            >
                                <Image
                                    v-if="customer.image_url"
                                    :src="customer.image_url"
                                    :alt="`${customer.first_name} ${customer.last_name}`"
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
                                        {{ customer.first_name }}
                                        {{ customer.last_name }}
                                    </h2>
                                    <p
                                        v-if="customer.email"
                                        class="text-muted-foreground"
                                    >
                                        {{ customer.email }}
                                    </p>
                                    <p
                                        v-if="customer.company_name"
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ customer.company_name }}
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
                                        <span>{{ customer.email ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Phone</span
                                        >
                                        <span>{{ customer.phone ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Company</span
                                        >
                                        <span>{{
                                            customer.company_name ?? '-'
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Customer Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Customer Information
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Customer Since</span
                                        >
                                        <span>{{
                                            formatDate(customer.customer_since)
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Loyalty Points</span
                                        >
                                        <span class="font-semibold">{{
                                            customer.loyalty_points?.toLocaleString() ??
                                            0
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Audit Info -->
                            <AuditInfo
                                :created-by="customer.created_by"
                                :updated-by="customer.updated_by"
                                :previous-updated-by="
                                    customer.previous_updated_by
                                "
                                :created-at="customer.created_at"
                                :updated-at="customer.updated_at"
                                :previous-updated-at="
                                    customer.previous_updated_at
                                "
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
