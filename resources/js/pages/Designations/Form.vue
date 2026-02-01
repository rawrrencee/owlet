<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { type Designation } from '@/types/company';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import { computed } from 'vue';

interface Props {
    designation: Designation | null;
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.designation);
const pageTitle = computed(() =>
    isEditing.value ? 'Edit Designation' : 'Create Designation',
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Designations', href: '/designations' },
    { title: isEditing.value ? 'Edit' : 'Create' },
];

const form = useForm({
    designation_name: props.designation?.designation_name ?? '',
    designation_code: props.designation?.designation_code ?? '',
});

function submit() {
    if (isEditing.value) {
        form.put(`/designations/${props.designation!.id}`);
    } else {
        form.post('/designations');
    }
}

function cancel() {
    router.get('/designations');
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="heading-lg">{{ pageTitle }}</h1>
            </div>

            <div class="mx-auto w-full max-w-2xl">
                <Card>
                    <template #content>
                        <form
                            @submit.prevent="submit"
                            class="flex flex-col gap-6"
                        >
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-col gap-2">
                                    <label
                                        for="designation_name"
                                        class="font-medium"
                                        >Designation Name *</label
                                    >
                                    <InputText
                                        id="designation_name"
                                        v-model="form.designation_name"
                                        :invalid="
                                            !!form.errors.designation_name
                                        "
                                        placeholder="Senior Manager"
                                        size="small"
                                        fluid
                                    />
                                    <small
                                        v-if="form.errors.designation_name"
                                        class="text-red-500"
                                    >
                                        {{ form.errors.designation_name }}
                                    </small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label
                                        for="designation_code"
                                        class="font-medium"
                                        >Designation Code *</label
                                    >
                                    <InputText
                                        id="designation_code"
                                        v-model="form.designation_code"
                                        :invalid="
                                            !!form.errors.designation_code
                                        "
                                        placeholder="SM"
                                        size="small"
                                        fluid
                                    />
                                    <small class="text-muted-foreground">
                                        A unique code to identify this
                                        designation.
                                    </small>
                                    <small
                                        v-if="form.errors.designation_code"
                                        class="text-red-500"
                                    >
                                        {{ form.errors.designation_code }}
                                    </small>
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
                                            : 'Create Designation'
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
