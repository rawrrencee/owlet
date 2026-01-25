<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Customer } from '@/types';

interface Props {
    customer: Customer | null;
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.customer);
const pageTitle = computed(() => (isEditing.value ? 'Edit Customer' : 'Create Customer'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/users?type=customers' },
    { title: isEditing.value ? 'Edit' : 'Create' },
];

const form = useForm({
    first_name: props.customer?.first_name ?? '',
    last_name: props.customer?.last_name ?? '',
    email: props.customer?.email ?? '',
    phone: props.customer?.phone ?? '',
    company_name: props.customer?.company_name ?? '',
});

function submit() {
    if (isEditing.value) {
        form.put(`/customers/${props.customer!.id}`);
    } else {
        form.post('/customers');
    }
}

function cancel() {
    router.get('/users', { type: 'customers' });
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

            <div class="mx-auto w-full max-w-2xl">
                <Card>
                    <template #content>
                        <form @submit.prevent="submit" class="flex flex-col gap-4">
                            <p v-if="!isEditing" class="text-surface-500 dark:text-surface-400 text-sm">
                                Add a new customer to the system.
                            </p>

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

                            <div class="flex flex-col gap-2">
                                <label for="email" class="font-medium">Email</label>
                                <InputText
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    :invalid="!!form.errors.email"
                                    placeholder="john.doe@example.com"
                                    size="small"
                                    fluid
                                />
                                <small v-if="form.errors.email" class="text-red-500">
                                    {{ form.errors.email }}
                                </small>
                            </div>

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
                                <label for="company_name" class="font-medium">Company Name</label>
                                <InputText
                                    id="company_name"
                                    v-model="form.company_name"
                                    :invalid="!!form.errors.company_name"
                                    placeholder="Acme Inc."
                                    size="small"
                                    fluid
                                />
                                <small v-if="form.errors.company_name" class="text-red-500">
                                    {{ form.errors.company_name }}
                                </small>
                            </div>

                            <div v-if="isEditing && customer" class="border-t border-sidebar-border/70 pt-4">
                                <h3 class="mb-3 text-sm font-medium text-muted-foreground">Customer Info</h3>
                                <div class="grid gap-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Customer Since</span>
                                        <span>{{ customer.customer_since ? new Date(customer.customer_since).toLocaleDateString() : '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Loyalty Points</span>
                                        <span>{{ customer.loyalty_points?.toLocaleString() ?? 0 }}</span>
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
                                    :label="isEditing ? 'Save Changes' : 'Create Customer'"
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
