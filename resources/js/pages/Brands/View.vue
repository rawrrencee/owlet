<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import Tag from 'primevue/tag';
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Brand, type BreadcrumbItem } from '@/types';

interface Props {
    brand: Brand;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Brands', href: '/brands' },
    { title: props.brand.brand_name },
];

function getInitials(): string {
    const words = props.brand.brand_name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return props.brand.brand_name.substring(0, 2).toUpperCase();
}

function navigateToEdit() {
    router.get(`/brands/${props.brand.id}/edit`);
}
</script>

<template>
    <Head :title="brand.brand_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <BackButton fallback-url="/brands" />
                    <h1 class="heading-lg">{{ brand.brand_name }}</h1>
                    <Tag
                        :value="brand.is_active ? 'Active' : 'Inactive'"
                        :severity="brand.is_active ? 'success' : 'danger'"
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
                            <!-- Brand Header -->
                            <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                                <Image
                                    v-if="brand.logo_url"
                                    :src="brand.logo_url"
                                    :alt="brand.brand_name"
                                    image-class="!h-24 !w-24 rounded-lg object-cover cursor-pointer"
                                    :pt="{ root: { class: 'rounded-lg overflow-hidden' }, previewMask: { class: 'rounded-lg' } }"
                                    preview
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials()"
                                    class="!h-24 !w-24 bg-primary/10 text-3xl text-primary"
                                />
                                <div class="flex flex-col gap-1 text-center sm:text-left">
                                    <h2 class="text-xl font-semibold">{{ brand.brand_name }}</h2>
                                    <Tag :value="brand.brand_code" severity="secondary" class="self-center sm:self-start" />
                                    <p v-if="brand.country_name" class="text-muted-foreground">{{ brand.country_name }}</p>
                                </div>
                            </div>

                            <Divider />

                            <!-- Contact Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Contact Information</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Email</span>
                                        <span>{{ brand.email ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Website</span>
                                        <a
                                            v-if="brand.website"
                                            :href="brand.website"
                                            target="_blank"
                                            class="text-primary hover:underline"
                                        >
                                            {{ brand.website }}
                                        </a>
                                        <span v-else>-</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Phone Number</span>
                                        <span>{{ brand.phone_number ?? '-' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-muted-foreground">Mobile Number</span>
                                        <span>{{ brand.mobile_number ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Address -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Address</h3>
                                <div class="flex flex-col gap-1">
                                    <span v-if="brand.address_1">{{ brand.address_1 }}</span>
                                    <span v-if="brand.address_2">{{ brand.address_2 }}</span>
                                    <span v-if="brand.country_name">{{ brand.country_name }}</span>
                                    <span v-if="!brand.address_1 && !brand.address_2 && !brand.country_name" class="text-muted-foreground">
                                        No address provided
                                    </span>
                                </div>
                            </div>

                            <!-- Description -->
                            <template v-if="brand.description">
                                <Divider />
                                <div>
                                    <h3 class="mb-4 text-lg font-medium">Description</h3>
                                    <div class="prose prose-sm max-w-none dark:prose-invert" v-html="brand.description"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
