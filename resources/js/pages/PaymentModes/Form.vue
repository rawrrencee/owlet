<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, PaymentMode } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import { computed } from 'vue';

interface Props {
    paymentMode?: PaymentMode | null;
}

const props = withDefaults(defineProps<Props>(), {
    paymentMode: null,
});

const isEditing = computed(() => !!props.paymentMode);
const pageTitle = computed(() =>
    isEditing.value ? 'Edit Payment Mode' : 'Create Payment Mode',
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Payment Modes', href: '/payment-modes' },
    { title: isEditing.value ? 'Edit' : 'Create' },
];

const form = useForm({
    name: props.paymentMode?.name ?? '',
    code: props.paymentMode?.code ?? '',
    description: props.paymentMode?.description ?? '',
    is_active: props.paymentMode?.is_active ?? true,
    sort_order: props.paymentMode?.sort_order ?? 0,
});

function submit() {
    if (isEditing.value) {
        form.put(`/payment-modes/${props.paymentMode!.id}`);
    } else {
        form.post('/payment-modes');
    }
}

function cancel() {
    router.visit('/payment-modes');
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
                    size="small"
                    @click="cancel"
                />
                <h1 class="heading-lg">{{ pageTitle }}</h1>
            </div>

            <div class="mx-auto w-full max-w-2xl">
                <Card>
                    <template #content>
                        <form @submit.prevent="submit" class="flex flex-col gap-6">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="flex flex-col gap-2">
                                    <label for="name" class="font-medium">Name *</label>
                                    <InputText
                                        id="name"
                                        v-model="form.name"
                                        :invalid="!!form.errors.name"
                                        placeholder="PayNow"
                                        size="small"
                                        fluid
                                    />
                                    <small v-if="form.errors.name" class="text-red-500">
                                        {{ form.errors.name }}
                                    </small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="code" class="font-medium">Code</label>
                                    <InputText
                                        id="code"
                                        v-model="form.code"
                                        :invalid="!!form.errors.code"
                                        placeholder="PAYNOW"
                                        maxlength="50"
                                        size="small"
                                        fluid
                                        class="uppercase"
                                    />
                                    <small class="text-muted-foreground">Optional unique identifier</small>
                                    <small v-if="form.errors.code" class="text-red-500">
                                        {{ form.errors.code }}
                                    </small>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="description" class="font-medium">Description</label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    :invalid="!!form.errors.description"
                                    placeholder="Brief description of this payment method"
                                    rows="3"
                                    size="small"
                                    fluid
                                />
                                <small v-if="form.errors.description" class="text-red-500">
                                    {{ form.errors.description }}
                                </small>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="flex flex-col gap-2">
                                    <label for="sort_order" class="font-medium">Sort Order</label>
                                    <InputNumber
                                        id="sort_order"
                                        v-model="form.sort_order"
                                        :invalid="!!form.errors.sort_order"
                                        :min="0"
                                        size="small"
                                        fluid
                                    />
                                    <small class="text-muted-foreground">Lower values appear first</small>
                                    <small v-if="form.errors.sort_order" class="text-red-500">
                                        {{ form.errors.sort_order }}
                                    </small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="font-medium">Status</label>
                                    <div class="flex items-center gap-3 pt-1">
                                        <ToggleSwitch v-model="form.is_active" />
                                        <span :class="form.is_active ? 'text-green-600' : 'text-red-600'">
                                            {{ form.is_active ? 'Active' : 'Inactive' }}
                                        </span>
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
                                    :label="isEditing ? 'Save Changes' : 'Create Payment Mode'"
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
