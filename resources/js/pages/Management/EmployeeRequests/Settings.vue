<script setup lang="ts">
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import ToggleSwitch from 'primevue/toggleswitch';

interface Props {
    signupEnabled: boolean;
    hasAccessCode: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employee Requests', href: '/management/employee-requests' },
    { title: 'Settings', href: '#' },
];

const form = useForm({
    signup_enabled: props.signupEnabled,
    access_code: '',
    clear_access_code: false,
});

function save() {
    form.put('/management/employee-requests/settings', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Employee Request Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-3">
                <BackButton fallback-url="/management/employee-requests" />
                <h1 class="heading-lg">Employee Request Settings</h1>
            </div>

            <Card class="max-w-xl">
                <template #content>
                    <form @submit.prevent="save">
                        <div class="space-y-6">
                            <!-- Enable/Disable -->
                            <div class="flex items-center gap-3">
                                <ToggleSwitch v-model="form.signup_enabled" />
                                <div>
                                    <div class="font-medium">
                                        Accept Applications
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        Allow prospective employees to submit
                                        applications via /apply
                                    </div>
                                </div>
                            </div>

                            <!-- Access Code -->
                            <div>
                                <label class="mb-1 block text-sm font-medium">
                                    Access Code (optional)
                                </label>
                                <p class="mb-2 text-sm text-muted-foreground">
                                    If set, applicants must enter this code
                                    before accessing the form.
                                </p>
                                <div class="flex gap-2">
                                    <InputText
                                        v-model="form.access_code"
                                        size="small"
                                        placeholder="Set new access code"
                                        class="flex-1"
                                        :disabled="form.clear_access_code"
                                    />
                                </div>
                                <div
                                    v-if="hasAccessCode && !form.clear_access_code"
                                    class="mt-2"
                                >
                                    <Message severity="info" :closable="false">
                                        An access code is currently set.
                                    </Message>
                                    <Button
                                        label="Remove Access Code"
                                        size="small"
                                        severity="danger"
                                        text
                                        class="mt-1"
                                        @click="form.clear_access_code = true"
                                    />
                                </div>
                                <div v-if="form.clear_access_code" class="mt-2">
                                    <Message severity="warn" :closable="false">
                                        Access code will be removed on save.
                                    </Message>
                                    <Button
                                        label="Keep Access Code"
                                        size="small"
                                        text
                                        class="mt-1"
                                        @click="form.clear_access_code = false"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <Button
                                type="submit"
                                label="Save Settings"
                                size="small"
                                :loading="form.processing"
                            />
                        </div>
                    </form>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
