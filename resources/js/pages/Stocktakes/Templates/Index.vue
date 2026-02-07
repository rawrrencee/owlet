<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StocktakeTemplate } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';

interface Props {
    templates: StocktakeTemplate[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stocktake', href: '/stocktakes' },
    { title: 'My Templates' },
];

const confirm = useConfirm();
const expandedRows = ref({});

function confirmDelete(template: StocktakeTemplate) {
    confirm.require({
        message: `Delete template "${template.name}"?`,
        header: 'Delete Template',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: { severity: 'secondary', size: 'small' },
        acceptLabel: 'Delete',
        acceptProps: { severity: 'danger', size: 'small' },
        accept: () => {
            router.delete(`/stocktake-templates/${template.id}`);
        },
    });
}
</script>

<template>
    <Head title="My Templates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <h1 class="heading-lg">My Templates</h1>
                <Button
                    label="Create Template"
                    icon="pi pi-plus"
                    size="small"
                    @click="router.get('/stocktake-templates/create')"
                />
            </div>

            <DataTable
                v-model:expandedRows="expandedRows"
                :value="templates"
                dataKey="id"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No templates yet. Create one to speed up stocktaking.
                    </div>
                </template>
                <Column
                    expander
                    style="width: 3rem"
                    class="!pr-0 sm:hidden"
                />
                <Column field="name" header="Name">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.name }}</span>
                    </template>
                </Column>
                <Column
                    header="Store"
                    class="hidden sm:table-cell"
                >
                    <template #body="{ data }">
                        {{ data.store?.store_name }} ({{ data.store?.store_code }})
                    </template>
                </Column>
                <Column header="Products" :style="{ width: '6rem' }">
                    <template #body="{ data }">
                        {{ data.products_count ?? 0 }}
                    </template>
                </Column>
                <Column header="" :style="{ width: '6rem' }">
                    <template #body="{ data }">
                        <div class="flex justify-end gap-1">
                            <Button
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click="router.get(`/stocktake-templates/${data.id}/edit`)"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="confirmDelete(data)"
                            />
                        </div>
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="grid gap-2 p-3 text-sm sm:hidden">
                        <div class="flex justify-between border-b border-border pb-2">
                            <span class="text-muted-foreground">Store</span>
                            <span>{{ data.store?.store_name }} ({{ data.store?.store_code }})</span>
                        </div>
                    </div>
                </template>
            </DataTable>

            <ConfirmDialog />
        </div>
    </AppLayout>
</template>
