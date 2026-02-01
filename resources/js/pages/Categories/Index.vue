<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import { useConfirm } from 'primevue/useconfirm';
import { computed, reactive, ref, watch } from 'vue';
import PagePermissionsSplitButton from '@/components/admin/PagePermissionsSplitButton.vue';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type Category,
    type PaginatedData,
    type Subcategory,
} from '@/types';

interface Filters {
    search?: string;
    status?: string;
    show_deleted?: boolean;
    search_subcategories?: boolean;
    per_page?: number;
}

interface Props {
    categories: PaginatedData<Category>;
    filters?: Filters;
}

const props = defineProps<Props>();

// Permission checks
const { canAccessPage } = usePermissions();
const canManage = computed(() => canAccessPage('categories.manage'));

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    showDeleted: props.filters?.show_deleted ?? false,
    searchSubcategories: props.filters?.search_subcategories ?? false,
});

const perPage = ref(props.categories.per_page ?? 15);

const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(
    () => filters.search,
    () => {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 300);
    },
);

watch(
    () => filters.status,
    () => {
        applyFilters();
    },
);

watch(
    () => filters.showDeleted,
    () => {
        applyFilters();
    },
);

watch(
    () => filters.searchSubcategories,
    () => {
        applyFilters();
    },
);

function applyFilters() {
    const params: Record<string, string | number | boolean> = {};
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.showDeleted) params.show_deleted = true;
    if (filters.searchSubcategories) params.search_subcategories = true;
    if (perPage.value !== 15) params.per_page = perPage.value;
    router.get('/categories', params, { preserveState: true });
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    filters.showDeleted = false;
    filters.searchSubcategories = false;
    router.get('/categories', {}, { preserveState: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Categories' },
];

const expandedRows = ref({});
const hasActiveFilters = computed(
    () =>
        filters.search ||
        filters.status ||
        filters.showDeleted ||
        filters.searchSubcategories,
);
const confirm = useConfirm();

function isDeleted(category: Category): boolean {
    return category.is_deleted === true;
}

function isSubcategoryDeleted(subcategory: Subcategory): boolean {
    return subcategory.is_deleted === true;
}

function navigateToView(category: Category) {
    router.get(`/categories/${category.id}`);
}

function navigateToEdit(category: Category) {
    router.get(`/categories/${category.id}/edit`);
}

function confirmDelete(category: Category) {
    confirm.require({
        message: `Are you sure you want to delete "${category.category_name}"?`,
        header: 'Delete Category',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Delete',
        acceptProps: {
            severity: 'danger',
            size: 'small',
        },
        accept: () => {
            router.delete(`/categories/${category.id}`);
        },
    });
}

function confirmRestore(category: Category) {
    confirm.require({
        message: `Are you sure you want to restore "${category.category_name}"?`,
        header: 'Restore Category',
        icon: 'pi pi-history',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Restore',
        acceptProps: {
            severity: 'success',
            size: 'small',
        },
        accept: () => {
            router.post(`/categories/${category.id}/restore`);
        },
    });
}

function onRowClick(event: { data: Category }) {
    navigateToView(event.data);
}

function onPage(event: { page: number; rows: number }) {
    perPage.value = event.rows;
    const params: Record<string, string | number | boolean> = {
        page: event.page + 1,
    };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.showDeleted) params.show_deleted = true;
    if (filters.searchSubcategories) params.search_subcategories = true;
    if (event.rows !== 15) params.per_page = event.rows;
    router.get('/categories', params, { preserveState: true });
}
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <h1 class="heading-lg">Categories</h1>
                <PagePermissionsSplitButton
                    page="categories"
                    page-label="Category"
                    create-route="/categories/create"
                    :can-manage="canManage"
                />
            </div>

            <!-- Filter Section -->
            <div
                class="filter-section flex flex-col gap-3 sm:flex-row sm:items-center"
            >
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="filters.search"
                        :placeholder="
                            filters.searchSubcategories
                                ? 'Search categories and subcategories...'
                                : 'Search by name or code...'
                        "
                        size="small"
                        fluid
                    />
                </IconField>
                <div class="flex flex-wrap items-center gap-2">
                    <Select
                        v-model="filters.status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Status"
                        size="small"
                        class="w-full sm:w-36"
                    />
                    <label class="flex cursor-pointer items-center gap-2">
                        <ToggleSwitch v-model="filters.searchSubcategories" />
                        <span class="text-sm whitespace-nowrap"
                            >Include Subcategories</span
                        >
                    </label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <ToggleSwitch v-model="filters.showDeleted" />
                        <span class="text-sm whitespace-nowrap"
                            >Show Deleted</span
                        >
                    </label>
                    <Button
                        v-if="hasActiveFilters"
                        icon="pi pi-times"
                        severity="secondary"
                        text
                        size="small"
                        @click="clearFilters"
                        v-tooltip.top="'Clear filters'"
                    />
                </div>
            </div>

            <!-- Categories Table -->
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="categories.data"
                dataKey="id"
                :lazy="true"
                :paginator="true"
                :rows="perPage"
                :rows-per-page-options="[10, 15, 25, 50]"
                :total-records="categories.total"
                :first="(categories.current_page - 1) * perPage"
                @page="onPage"
                @row-click="onRowClick"
                striped-rows
                size="small"
                class="overflow-hidden rounded-lg border border-border [&_.p-datatable-tbody>tr]:cursor-pointer"
            >
                <template #empty>
                    <div class="p-4 text-center text-muted-foreground">
                        No categories found.
                    </div>
                </template>
                <Column expander class="w-12 !pr-0" />
                <Column
                    field="category_name"
                    header="Category Name"
                    class="!pl-4"
                >
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span
                                class="font-medium"
                                :class="{
                                    'text-muted-foreground line-through':
                                        isDeleted(data),
                                }"
                            >
                                {{ data.category_name }}
                            </span>
                            <Tag
                                v-if="isDeleted(data)"
                                value="Deleted"
                                severity="danger"
                                class="!text-xs"
                            />
                        </div>
                    </template>
                </Column>
                <Column field="category_code" header="Code" class="w-20">
                    <template #body="{ data }">
                        <Tag :value="data.category_code" severity="secondary" />
                    </template>
                </Column>
                <Column
                    field="subcategories_count"
                    header="Subcategories"
                    class="hidden w-32 md:table-cell"
                >
                    <template #body="{ data }">
                        <span class="text-muted-foreground">
                            {{ data.active_subcategories_count ?? 0 }} /
                            {{ data.subcategories_count ?? 0 }}
                        </span>
                    </template>
                </Column>
                <Column field="is_active" header="Status">
                    <template #body="{ data }">
                        <Tag
                            :value="data.is_active ? 'Active' : 'Inactive'"
                            :severity="data.is_active ? 'success' : 'danger'"
                        />
                    </template>
                </Column>
                <Column header="" class="w-24 !pr-4">
                    <template #body="{ data }">
                        <div
                            v-if="isDeleted(data)"
                            class="flex justify-end gap-1"
                        >
                            <Button
                                v-if="canManage"
                                icon="pi pi-history"
                                severity="success"
                                text
                                rounded
                                size="small"
                                @click.stop="confirmRestore(data)"
                                v-tooltip.top="'Restore'"
                            />
                        </div>
                        <div v-else class="flex justify-end gap-1">
                            <Button
                                v-if="canManage"
                                icon="pi pi-pencil"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click.stop="navigateToEdit(data)"
                            />
                            <Button
                                v-if="canManage"
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click.stop="confirmDelete(data)"
                            />
                        </div>
                    </template>
                </Column>
                <template #expansion="{ data }">
                    <div class="bg-muted/30 p-4">
                        <!-- Mobile-only info -->
                        <div class="mb-4 grid gap-3 text-sm md:hidden">
                            <div
                                class="flex justify-between border-b border-border pb-2"
                            >
                                <span class="text-muted-foreground"
                                    >Subcategories</span
                                >
                                <span
                                    >{{ data.active_subcategories_count ?? 0 }}
                                    active /
                                    {{ data.subcategories_count ?? 0 }}
                                    total</span
                                >
                            </div>
                            <div
                                v-if="data.description"
                                class="flex flex-col gap-1 border-b border-border pb-2"
                            >
                                <span class="text-muted-foreground"
                                    >Description</span
                                >
                                <span class="text-sm">{{
                                    data.description
                                }}</span>
                            </div>
                            <div
                                v-if="isDeleted(data) && canManage"
                                class="flex gap-2 pt-2"
                            >
                                <Button
                                    label="Restore"
                                    icon="pi pi-history"
                                    severity="success"
                                    size="small"
                                    @click="confirmRestore(data)"
                                    class="flex-1"
                                />
                            </div>
                            <div
                                v-else-if="!isDeleted(data) && canManage"
                                class="flex gap-2 pt-2"
                            >
                                <Button
                                    label="Edit"
                                    icon="pi pi-pencil"
                                    severity="secondary"
                                    size="small"
                                    @click="navigateToEdit(data)"
                                    class="flex-1"
                                />
                                <Button
                                    label="Delete"
                                    icon="pi pi-trash"
                                    severity="danger"
                                    size="small"
                                    @click="confirmDelete(data)"
                                    class="flex-1"
                                />
                            </div>
                        </div>

                        <!-- Subcategories table -->
                        <div>
                            <h4
                                class="mb-2 text-sm font-medium text-muted-foreground"
                            >
                                Subcategories
                            </h4>
                            <DataTable
                                :value="data.subcategories"
                                dataKey="id"
                                size="small"
                                class="overflow-hidden rounded-lg border border-border"
                            >
                                <template #empty>
                                    <div
                                        class="p-3 text-center text-sm text-muted-foreground"
                                    >
                                        No subcategories found.
                                    </div>
                                </template>
                                <Column
                                    field="subcategory_name"
                                    header="Name"
                                    class="!pl-3"
                                >
                                    <template #body="{ data: sub }">
                                        <div class="flex items-center gap-2">
                                            <span
                                                :class="{
                                                    'text-muted-foreground line-through':
                                                        isSubcategoryDeleted(
                                                            sub,
                                                        ),
                                                }"
                                            >
                                                {{ sub.subcategory_name }}
                                            </span>
                                            <Tag
                                                v-if="sub.is_default"
                                                value="Default"
                                                severity="info"
                                                class="!text-xs"
                                            />
                                            <Tag
                                                v-if="isSubcategoryDeleted(sub)"
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
                                    class="w-20"
                                >
                                    <template #body="{ data: sub }">
                                        <Tag
                                            :value="sub.subcategory_code"
                                            severity="secondary"
                                        />
                                    </template>
                                </Column>
                                <Column
                                    field="description"
                                    header="Description"
                                    class="hidden md:table-cell"
                                >
                                    <template #body="{ data: sub }">
                                        <span class="text-muted-foreground">{{
                                            sub.description ?? '-'
                                        }}</span>
                                    </template>
                                </Column>
                                <Column
                                    field="is_active"
                                    header="Status"
                                    class="w-24"
                                >
                                    <template #body="{ data: sub }">
                                        <Tag
                                            :value="
                                                sub.is_active
                                                    ? 'Active'
                                                    : 'Inactive'
                                            "
                                            :severity="
                                                sub.is_active
                                                    ? 'success'
                                                    : 'danger'
                                            "
                                        />
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>

        <ConfirmDialog />
    </AppLayout>
</template>
