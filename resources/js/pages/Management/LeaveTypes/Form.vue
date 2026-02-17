<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { LeaveType } from '@/types/leave';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import ColorPicker from 'primevue/colorpicker';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

interface Props {
    leaveType?: LeaveType;
    breadcrumbs: BreadcrumbItem[];
}

const props = defineProps<Props>();

const isEditing = !!props.leaveType;

const form = useForm({
    name: props.leaveType?.name ?? '',
    code: props.leaveType?.code ?? '',
    description: props.leaveType?.description ?? '',
    color: props.leaveType?.color?.replace('#', '') ?? '4CAF50',
    requires_balance: props.leaveType?.requires_balance ?? true,
    is_active: props.leaveType?.is_active ?? true,
    sort_order: props.leaveType?.sort_order ?? 0,
});

function submit() {
    const data = {
        ...form.data(),
        color: form.color ? `#${form.color}` : null,
    };

    if (isEditing) {
        router.put(`/management/leave-types/${props.leaveType!.id}`, data, {
            onError: (errors) => {
                form.clearErrors();
                Object.entries(errors).forEach(([key, value]) => {
                    form.setError(key as keyof typeof form.data, value as string);
                });
            },
        });
    } else {
        router.post('/management/leave-types', data, {
            onError: (errors) => {
                form.clearErrors();
                Object.entries(errors).forEach(([key, value]) => {
                    form.setError(key as keyof typeof form.data, value as string);
                });
            },
        });
    }
}
</script>

<template>
    <Head :title="isEditing ? 'Edit Leave Type' : 'Create Leave Type'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-4">
                <Button
                    icon="pi pi-arrow-left"
                    severity="secondary"
                    text
                    rounded
                    size="small"
                    @click="router.visit('/management/leave-types')"
                />
                <h1 class="heading-lg">
                    {{ isEditing ? 'Edit Leave Type' : 'Create Leave Type' }}
                </h1>
            </div>

            <form
                @submit.prevent="submit"
                class="mx-auto flex w-full max-w-2xl flex-col gap-4"
            >
                <div class="flex flex-col gap-2">
                    <label for="name" class="font-medium">Name *</label>
                    <InputText
                        id="name"
                        v-model="form.name"
                        :invalid="!!form.errors.name"
                        size="small"
                        fluid
                        placeholder="e.g. Annual Leave"
                    />
                    <small v-if="form.errors.name" class="text-red-500">
                        {{ form.errors.name }}
                    </small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="code" class="font-medium">Code *</label>
                    <InputText
                        id="code"
                        v-model="form.code"
                        :invalid="!!form.errors.code"
                        size="small"
                        fluid
                        placeholder="e.g. annual"
                    />
                    <small class="text-muted-foreground"
                        >Unique identifier (lowercase, no spaces)</small
                    >
                    <small v-if="form.errors.code" class="text-red-500">
                        {{ form.errors.code }}
                    </small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="description" class="font-medium"
                        >Description</label
                    >
                    <Textarea
                        id="description"
                        v-model="form.description"
                        :invalid="!!form.errors.description"
                        size="small"
                        fluid
                        rows="3"
                        placeholder="Optional description"
                    />
                    <small
                        v-if="form.errors.description"
                        class="text-red-500"
                    >
                        {{ form.errors.description }}
                    </small>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="font-medium">Color</label>
                    <div class="flex items-center gap-3">
                        <ColorPicker v-model="form.color" />
                        <InputText
                            v-model="form.color"
                            size="small"
                            class="w-32"
                            placeholder="4CAF50"
                        />
                        <div
                            v-if="form.color"
                            class="h-6 w-6 rounded-full border border-border"
                            :style="{
                                backgroundColor: `#${form.color}`,
                            }"
                        ></div>
                    </div>
                    <small v-if="form.errors.color" class="text-red-500">
                        {{ form.errors.color }}
                    </small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="sort_order" class="font-medium"
                        >Sort Order *</label
                    >
                    <InputNumber
                        id="sort_order"
                        v-model="form.sort_order"
                        :invalid="!!form.errors.sort_order"
                        :min="0"
                        :max="65535"
                        size="small"
                        fluid
                    />
                    <small v-if="form.errors.sort_order" class="text-red-500">
                        {{ form.errors.sort_order }}
                    </small>
                </div>

                <div class="flex items-center gap-3">
                    <InputSwitch v-model="form.requires_balance" />
                    <label class="font-medium">Requires Balance</label>
                </div>
                <small class="-mt-2 text-muted-foreground"
                    >When enabled, employees must have sufficient balance to
                    apply for this leave type</small
                >

                <div class="flex items-center gap-3">
                    <InputSwitch v-model="form.is_active" />
                    <label class="font-medium">Active</label>
                </div>
                <small class="-mt-2 text-muted-foreground"
                    >Inactive leave types won't appear in leave request
                    forms</small
                >

                <div class="mt-4 flex justify-end gap-2">
                    <Button
                        type="button"
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="router.visit('/management/leave-types')"
                    />
                    <Button
                        type="submit"
                        :label="isEditing ? 'Update' : 'Create'"
                        size="small"
                        :loading="form.processing"
                    />
                </div>
            </form>
        </div>
    </AppLayout>
</template>
