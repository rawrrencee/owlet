<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';
import { computed } from 'vue';
import BackButton from '@/components/BackButton.vue';
import {
    clearSkipPageInHistory,
    skipCurrentPageInHistory,
} from '@/composables/useSmartBack';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Currency } from '@/types';

interface Props {
    currency: Currency | null;
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.currency);
const pageTitle = computed(() =>
    isEditing.value ? 'Edit Currency' : 'Create Currency',
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Currencies', href: '/currencies' },
    { title: isEditing.value ? 'Edit' : 'Create' },
];

const form = useForm({
    code: props.currency?.code ?? '',
    name: props.currency?.name ?? '',
    symbol: props.currency?.symbol ?? '',
    decimal_places: props.currency?.decimal_places ?? 2,
    exchange_rate: props.currency?.exchange_rate
        ? typeof props.currency.exchange_rate === 'string'
            ? parseFloat(props.currency.exchange_rate)
            : props.currency.exchange_rate
        : null,
    active: props.currency?.active ?? true,
});

function submit() {
    if (isEditing.value) {
        skipCurrentPageInHistory();
        form.put(`/currencies/${props.currency!.id}`, {
            onSuccess: () => {
                router.visit(`/currencies/${props.currency!.id}`);
            },
            onError: () => {
                clearSkipPageInHistory();
            },
        });
    } else {
        form.post('/currencies');
    }
}

function cancel() {
    if (isEditing.value) {
        router.visit(`/currencies/${props.currency!.id}`);
    } else {
        router.visit('/currencies');
    }
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-4">
                <BackButton fallback-url="/currencies" />
                <h1 class="heading-lg">{{ pageTitle }}</h1>
            </div>

            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <template #content>
                        <form
                            @submit.prevent="submit"
                            class="flex flex-col gap-6"
                        >
                            <!-- Basic Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Basic Information
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-2">
                                        <label for="code" class="font-medium"
                                            >Currency Code *</label
                                        >
                                        <InputText
                                            id="code"
                                            v-model="form.code"
                                            :invalid="!!form.errors.code"
                                            placeholder="SGD"
                                            maxlength="3"
                                            size="small"
                                            fluid
                                            class="uppercase"
                                        />
                                        <small class="text-muted-foreground"
                                            >3-letter ISO currency code</small
                                        >
                                        <small
                                            v-if="form.errors.code"
                                            class="text-red-500"
                                        >
                                            {{ form.errors.code }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="name" class="font-medium"
                                            >Currency Name *</label
                                        >
                                        <InputText
                                            id="name"
                                            v-model="form.name"
                                            :invalid="!!form.errors.name"
                                            placeholder="Singapore Dollar"
                                            size="small"
                                            fluid
                                        />
                                        <small
                                            v-if="form.errors.name"
                                            class="text-red-500"
                                        >
                                            {{ form.errors.name }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="symbol" class="font-medium"
                                            >Symbol *</label
                                        >
                                        <InputText
                                            id="symbol"
                                            v-model="form.symbol"
                                            :invalid="!!form.errors.symbol"
                                            placeholder="S$"
                                            maxlength="10"
                                            size="small"
                                            fluid
                                        />
                                        <small
                                            v-if="form.errors.symbol"
                                            class="text-red-500"
                                        >
                                            {{ form.errors.symbol }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label
                                            for="decimal_places"
                                            class="font-medium"
                                            >Decimal Places *</label
                                        >
                                        <InputNumber
                                            id="decimal_places"
                                            v-model="form.decimal_places"
                                            :invalid="
                                                !!form.errors.decimal_places
                                            "
                                            :min="0"
                                            :max="4"
                                            size="small"
                                            fluid
                                            show-buttons
                                        />
                                        <small class="text-muted-foreground"
                                            >0-4 decimal places</small
                                        >
                                        <small
                                            v-if="form.errors.decimal_places"
                                            class="text-red-500"
                                        >
                                            {{ form.errors.decimal_places }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Exchange Rate -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Exchange Rate
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-2">
                                        <label
                                            for="exchange_rate"
                                            class="font-medium"
                                            >Exchange Rate (SGD Base)</label
                                        >
                                        <InputNumber
                                            id="exchange_rate"
                                            v-model="form.exchange_rate"
                                            :invalid="
                                                !!form.errors.exchange_rate
                                            "
                                            :min-fraction-digits="2"
                                            :max-fraction-digits="10"
                                            :min="0"
                                            size="small"
                                            fluid
                                        />
                                        <small class="text-muted-foreground">
                                            Rate relative to SGD (1 SGD = X
                                            currency). Use "Refresh Rates" to
                                            fetch from API.
                                        </small>
                                        <small
                                            v-if="form.errors.exchange_rate"
                                            class="text-red-500"
                                        >
                                            {{ form.errors.exchange_rate }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Status -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Status</h3>
                                <div class="flex items-center gap-3">
                                    <ToggleSwitch v-model="form.active" />
                                    <span
                                        :class="
                                            form.active
                                                ? 'text-green-600'
                                                : 'text-red-600'
                                        "
                                    >
                                        {{
                                            form.active ? 'Active' : 'Inactive'
                                        }}
                                    </span>
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
                                            : 'Create Currency'
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
