<script setup lang="ts">
import AuditInfo from '@/components/AuditInfo.vue';
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Category, type Subcategory } from '@/types';
import { type HasAuditTrail } from '@/types/audit';
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import { ref } from 'vue';

interface Props {
    category: Category & HasAuditTrail;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Categories', href: '/categories' },
    { title: props.category.category_name },
];

const expandedRows = ref({});

function navigateToEdit() {
    router.get(`/categories/${props.category.id}/edit`);
}

function isSubcategoryDeleted(subcategory: Subcategory): boolean {
    return subcategory.is_deleted === true;
}
</script>

<template>
    <Head :title="category.category_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-4">
                    <BackButton fallback-url="/categories" />
                    <h1 class="heading-lg">{{ category.category_name }}</h1>
                    <Tag
                        :value="category.is_active ? 'Active' : 'Inactive'"
                        :severity="category.is_active ? 'success' : 'danger'"
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
                            <!-- Category Header -->
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-col gap-1">
                                    <h2 class="text-xl font-semibold">
                                        {{ category.category_name }}
                                    </h2>
                                    <Tag
                                        :value="category.category_code"
                                        severity="secondary"
                                        class="self-start"
                                    />
                                </div>
                            </div>

                            <!-- Description -->
                            <template v-if="category.description">
                                <Divider />
                                <div>
                                    <h3 class="mb-2 text-lg font-medium">
                                        Description
                                    </h3>
                                    <p class="text-muted-foreground">
                                        {{ category.description }}
                                    </p>
                                </div>
                            </template>

                            <Divider />

                            <!-- Subcategories -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">
                                    Subcategories
                                </h3>
                                <DataTable
                                    v-model:expandedRows="expandedRows"
                                    :value="category.subcategories"
                                    dataKey="id"
                                    striped-rows
                                    size="small"
                                    class="overflow-hidden rounded-lg border border-border"
                                >
                                    <template #empty>
                                        <div
                                            class="p-4 text-center text-muted-foreground"
                                        >
                                            No subcategories found.
                                        </div>
                                    </template>
                                    <Column
                                        expander
                                        class="w-10 !pr-0 md:hidden"
                                    />
                                    <Column
                                        field="subcategory_name"
                                        header="Name"
                                        class="!pl-3 md:w-48"
                                    >
                                        <template #body="{ data }">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    :class="{
                                                        'text-muted-foreground line-through':
                                                            isSubcategoryDeleted(
                                                                data,
                                                            ),
                                                    }"
                                                >
                                                    {{ data.subcategory_name }}
                                                </span>
                                                <Tag
                                                    v-if="data.is_default"
                                                    value="Default"
                                                    severity="info"
                                                    class="!text-xs"
                                                />
                                                <Tag
                                                    v-if="
                                                        isSubcategoryDeleted(
                                                            data,
                                                        )
                                                    "
                                                    value="Deleted"
                                                    severity="danger"
                                                    class="!text-xs"
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                    <Column
                                        field="subcategory_code"
                                        header="Code"
                                        class="w-20 !pl-0"
                                    >
                                        <template #body="{ data }">
                                            <Tag
                                                :value="data.subcategory_code"
                                                severity="secondary"
                                            />
                                        </template>
                                    </Column>
                                    <Column
                                        field="description"
                                        header="Description"
                                        class="hidden md:table-cell"
                                    >
                                        <template #body="{ data }">
                                            <span
                                                class="text-muted-foreground"
                                                >{{
                                                    data.description ?? '-'
                                                }}</span
                                            >
                                        </template>
                                    </Column>
                                    <Column
                                        field="is_active"
                                        header="Status"
                                        class="w-24"
                                    >
                                        <template #body="{ data }">
                                            <Tag
                                                :value="
                                                    data.is_active
                                                        ? 'Active'
                                                        : 'Inactive'
                                                "
                                                :severity="
                                                    data.is_active
                                                        ? 'success'
                                                        : 'danger'
                                                "
                                            />
                                        </template>
                                    </Column>
                                    <template #expansion="{ data }">
                                        <div
                                            class="grid gap-2 p-3 text-sm md:hidden"
                                        >
                                            <div
                                                v-if="data.description"
                                                class="flex flex-col gap-1"
                                            >
                                                <span
                                                    class="text-muted-foreground"
                                                    >Description</span
                                                >
                                                <span>{{
                                                    data.description
                                                }}</span>
                                            </div>
                                            <div
                                                v-else
                                                class="text-muted-foreground"
                                            >
                                                No description
                                            </div>
                                        </div>
                                    </template>
                                </DataTable>
                            </div>

                            <Divider />

                            <!-- Audit Info -->
                            <AuditInfo
                                :created-by="category.created_by"
                                :updated-by="category.updated_by"
                                :previous-updated-by="
                                    category.previous_updated_by
                                "
                                :created-at="category.created_at"
                                :updated-at="category.updated_at"
                                :previous-updated-at="
                                    category.previous_updated_at
                                "
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
