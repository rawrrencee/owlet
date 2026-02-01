<script setup lang="ts">
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Currency } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';

interface Props {
    currency: Currency;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Currencies', href: '/currencies' },
    { title: props.currency.code },
];

function navigateToEdit() {
    router.get(`/currencies/${props.currency.id}/edit`);
}

function formatExchangeRate(): string {
    if (
        props.currency.exchange_rate === null ||
        props.currency.exchange_rate === undefined
    ) {
        return 'Not set';
    }
    const rate =
        typeof props.currency.exchange_rate === 'string'
            ? parseFloat(props.currency.exchange_rate)
            : props.currency.exchange_rate;
    return rate.toFixed(10);
}

function formatLastUpdated(): string {
    if (!props.currency.exchange_rate_updated_at) return 'Never';
    return new Date(props.currency.exchange_rate_updated_at).toLocaleString();
}

function formatDate(date: string | null): string {
    if (!date) return '-';
    return new Date(date).toLocaleString();
}
</script>

<template>
    <Head :title="`${currency.name} (${currency.code})`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-4">
                    <BackButton fallback-url="/currencies" />
                    <h1 class="heading-lg">{{ currency.name }}</h1>
                    <Tag
                        :value="currency.active ? 'Active' : 'Inactive'"
                        :severity="currency.active ? 'success' : 'danger'"
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
                            <!-- Basic Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Basic Information
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Code</span
                                        >
                                        <Tag
                                            :value="currency.code"
                                            severity="secondary"
                                            class="self-start"
                                        />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Name</span
                                        >
                                        <span class="font-medium">{{
                                            currency.name
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Symbol</span
                                        >
                                        <span class="font-mono text-lg">{{
                                            currency.symbol
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Decimal Places</span
                                        >
                                        <span>{{
                                            currency.decimal_places
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Exchange Rate Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Exchange Rate Information
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Exchange Rate (Base: SGD)</span
                                        >
                                        <span class="font-mono">{{
                                            formatExchangeRate()
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Last Updated</span
                                        >
                                        <span>{{ formatLastUpdated() }}</span>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Timestamps -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Record Information
                                </h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Created At</span
                                        >
                                        <span>{{
                                            formatDate(currency.created_at)
                                        }}</span>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Updated At</span
                                        >
                                        <span>{{
                                            formatDate(currency.updated_at)
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
