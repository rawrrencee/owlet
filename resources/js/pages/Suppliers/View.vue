<script setup lang="ts">
import AuditInfo from '@/components/AuditInfo.vue';
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Supplier } from '@/types';
import { type HasAuditTrail } from '@/types/audit';
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import Tag from 'primevue/tag';

interface Props {
    supplier: Supplier & HasAuditTrail;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Suppliers', href: '/suppliers' },
    { title: props.supplier.supplier_name },
];

function getInitials(): string {
    const words = props.supplier.supplier_name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return props.supplier.supplier_name.substring(0, 2).toUpperCase();
}

function navigateToEdit() {
    router.get(`/suppliers/${props.supplier.id}/edit`);
}
</script>

<template>
    <Head :title="supplier.supplier_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-4">
                    <BackButton fallback-url="/suppliers" />
                    <h1 class="heading-lg">{{ supplier.supplier_name }}</h1>
                    <Tag
                        :value="supplier.active ? 'Active' : 'Inactive'"
                        :severity="supplier.active ? 'success' : 'danger'"
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
                            <!-- Supplier Header -->
                            <div
                                class="flex flex-col items-center gap-4 sm:flex-row sm:items-start"
                            >
                                <Image
                                    v-if="supplier.logo_url"
                                    :src="supplier.logo_url"
                                    :alt="supplier.supplier_name"
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
                                        {{ supplier.supplier_name }}
                                    </h2>
                                    <p
                                        v-if="supplier.country_name"
                                        class="text-muted-foreground"
                                    >
                                        {{ supplier.country_name }}
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
                                        <span>{{ supplier.email ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Website</span
                                        >
                                        <a
                                            v-if="supplier.website"
                                            :href="supplier.website"
                                            target="_blank"
                                            class="text-primary hover:underline"
                                        >
                                            {{ supplier.website }}
                                        </a>
                                        <span v-else>-</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Phone Number</span
                                        >
                                        <span>{{
                                            supplier.phone_number ?? '-'
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Mobile Number</span
                                        >
                                        <span>{{
                                            supplier.mobile_number ?? '-'
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
                                    <span v-if="supplier.address_1">{{
                                        supplier.address_1
                                    }}</span>
                                    <span v-if="supplier.address_2">{{
                                        supplier.address_2
                                    }}</span>
                                    <span v-if="supplier.country_name">{{
                                        supplier.country_name
                                    }}</span>
                                    <span
                                        v-if="
                                            !supplier.address_1 &&
                                            !supplier.address_2 &&
                                            !supplier.country_name
                                        "
                                        class="text-muted-foreground"
                                    >
                                        No address provided
                                    </span>
                                </div>
                            </div>

                            <!-- Description -->
                            <template v-if="supplier.description">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">
                                        Description
                                    </h3>
                                    <div
                                        class="prose prose-sm dark:prose-invert max-w-none"
                                        v-html="supplier.description"
                                    ></div>
                                </div>
                            </template>

                            <Divider />

                            <!-- Audit Info -->
                            <AuditInfo
                                :created-by="supplier.created_by"
                                :updated-by="supplier.updated_by"
                                :previous-updated-by="
                                    supplier.previous_updated_by
                                "
                                :created-at="supplier.created_at"
                                :updated-at="supplier.updated_at"
                                :previous-updated-at="
                                    supplier.previous_updated_at
                                "
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
