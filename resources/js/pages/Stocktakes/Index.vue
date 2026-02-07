<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Stocktake, StocktakeTemplate } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { ref } from 'vue';

interface StoreOption {
    id: number;
    store_name: string;
    store_code: string;
}

interface Props {
    stores: StoreOption[];
    activeStocktake: Stocktake | null;
    templates: StocktakeTemplate[];
    recentStocktakes: Stocktake[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stocktake' },
];

const selectedStoreId = ref<number | null>(null);

const form = useForm({
    store_id: null as number | null,
});

function startStocktake() {
    if (!selectedStoreId.value) return;
    form.store_id = selectedStoreId.value;
    form.post('/stocktakes');
}

function resumeStocktake() {
    if (props.activeStocktake) {
        router.get(`/stocktakes/${props.activeStocktake.id}`);
    }
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString();
}

const storeOptions = props.stores.map((s) => ({
    label: `${s.store_name} (${s.store_code})`,
    value: s.id,
}));
</script>

<template>
    <Head title="Stocktake" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 class="heading-lg">Stocktake</h1>

            <!-- Active Stocktake Card -->
            <Card v-if="activeStocktake" class="border-primary/30 bg-primary/5">
                <template #title>
                    <div class="flex items-center gap-2 text-base">
                        <i class="pi pi-clock text-primary" />
                        Active Stocktake
                    </div>
                </template>
                <template #content>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1 text-sm">
                            <div>
                                <span class="text-muted-foreground">Store: </span>
                                <span class="font-medium">
                                    {{ activeStocktake.store?.store_name }}
                                    ({{ activeStocktake.store?.store_code }})
                                </span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Items: </span>
                                <span class="font-medium">{{ activeStocktake.items_count ?? 0 }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Started: </span>
                                <span>{{ formatDate(activeStocktake.created_at) }}</span>
                            </div>
                        </div>
                        <Button
                            label="Resume"
                            icon="pi pi-play"
                            size="small"
                            @click="resumeStocktake"
                        />
                    </div>
                </template>
            </Card>

            <!-- Start New Stocktake -->
            <Card v-if="!activeStocktake">
                <template #title>
                    <span class="text-base">Start New Stocktake</span>
                </template>
                <template #content>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <label class="mb-1 block text-sm text-muted-foreground">Select Store</label>
                            <Select
                                v-model="selectedStoreId"
                                :options="storeOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Choose a store..."
                                size="small"
                                fluid
                            />
                        </div>
                        <Button
                            label="Start Stocktake"
                            icon="pi pi-play"
                            size="small"
                            :disabled="!selectedStoreId || form.processing"
                            :loading="form.processing"
                            @click="startStocktake"
                        />
                    </div>
                    <small v-if="form.errors.store_id" class="mt-1 text-red-500">
                        {{ form.errors.store_id }}
                    </small>
                </template>
            </Card>

            <!-- Recent Stocktakes -->
            <Card v-if="recentStocktakes.length > 0">
                <template #title>
                    <span class="text-base">Recent Stocktakes</span>
                </template>
                <template #content>
                    <div class="grid gap-2">
                        <div
                            v-for="stocktake in recentStocktakes"
                            :key="stocktake.id"
                            class="flex flex-wrap items-center gap-2 rounded-md border border-border p-3 cursor-pointer hover:bg-surface-50 dark:hover:bg-surface-800"
                            @click="router.get(`/stocktakes/${stocktake.id}`)"
                        >
                            <div class="min-w-0 flex-1 space-y-0.5">
                                <div class="text-sm font-medium">
                                    {{ stocktake.store?.store_name }}
                                    ({{ stocktake.store?.store_code }})
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ formatDate(stocktake.submitted_at) }} &middot;
                                    {{ stocktake.items_count ?? 0 }} items
                                </div>
                            </div>
                            <Tag
                                :value="stocktake.has_issues ? 'Issues' : 'OK'"
                                :severity="stocktake.has_issues ? 'warn' : 'success'"
                                class="!text-xs shrink-0"
                            />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- My Templates -->
            <Card>
                <template #title>
                    <div class="flex items-center justify-between">
                        <span class="text-base">My Templates</span>
                        <div class="flex gap-1">
                            <Button
                                label="Create"
                                icon="pi pi-plus"
                                severity="secondary"
                                text
                                size="small"
                                @click="router.get('/stocktake-templates/create')"
                            />
                            <Button
                                v-if="templates.length > 0"
                                label="Manage"
                                icon="pi pi-cog"
                                severity="secondary"
                                text
                                size="small"
                                @click="router.get('/stocktake-templates')"
                            />
                        </div>
                    </div>
                </template>
                <template #content>
                    <div v-if="templates.length > 0" class="grid gap-2">
                        <div
                            v-for="template in templates"
                            :key="template.id"
                            class="flex flex-wrap items-center gap-2 rounded-md border border-border p-3"
                        >
                            <div class="min-w-0 flex-1 basis-0">
                                <div class="text-sm font-medium">{{ template.name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ template.store?.store_name }} &middot;
                                    {{ template.products_count ?? 0 }} products
                                </div>
                            </div>
                            <Tag
                                :value="`${template.products_count ?? 0} items`"
                                severity="info"
                                class="!text-xs shrink-0"
                            />
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        No templates yet. Create one to speed up stocktaking.
                    </p>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
