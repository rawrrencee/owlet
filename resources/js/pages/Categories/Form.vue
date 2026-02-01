<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { clearSkipPageInHistory, skipCurrentPageInHistory } from '@/composables/useSmartBack';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref } from 'vue';
import BackButton from '@/components/BackButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Category, type Subcategory } from '@/types';

interface Props {
    category: Category | null;
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.category);
const pageTitle = computed(() => (isEditing.value ? 'Edit Category' : 'Create Category'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Categories', href: '/categories' },
    { title: isEditing.value ? 'Edit' : 'Create' },
];

const form = useForm({
    category_name: props.category?.category_name ?? '',
    category_code: props.category?.category_code ?? '',
    description: props.category?.description ?? '',
    is_active: props.category?.is_active ?? true,
});

function submit() {
    if (isEditing.value) {
        skipCurrentPageInHistory();
        form.put(`/categories/${props.category!.id}`, {
            onSuccess: () => {
                router.visit(`/categories/${props.category!.id}`);
            },
            onError: () => {
                clearSkipPageInHistory();
            },
        });
    } else {
        form.post('/categories');
    }
}

function cancel() {
    if (isEditing.value) {
        router.visit(`/categories/${props.category!.id}`);
    } else {
        router.visit('/categories');
    }
}

// Subcategory management (only for edit mode)
const confirm = useConfirm();
const showSubcategoryDialog = ref(false);
const editingSubcategory = ref<Subcategory | null>(null);
const expandedRows = ref({});

const subcategoryForm = useForm({
    subcategory_name: '',
    subcategory_code: '',
    description: '',
    is_active: true,
});

function isSubcategoryDeleted(subcategory: Subcategory): boolean {
    return subcategory.is_deleted === true;
}

function openAddSubcategoryDialog() {
    editingSubcategory.value = null;
    subcategoryForm.reset();
    subcategoryForm.clearErrors();
    subcategoryForm.is_active = true;
    showSubcategoryDialog.value = true;
}

function openEditSubcategoryDialog(subcategory: Subcategory) {
    editingSubcategory.value = subcategory;
    subcategoryForm.subcategory_name = subcategory.subcategory_name;
    subcategoryForm.subcategory_code = subcategory.subcategory_code;
    subcategoryForm.description = subcategory.description ?? '';
    subcategoryForm.is_active = subcategory.is_active;
    subcategoryForm.clearErrors();
    showSubcategoryDialog.value = true;
}

function submitSubcategory() {
    if (editingSubcategory.value) {
        subcategoryForm.put(`/categories/${props.category!.id}/subcategories/${editingSubcategory.value.id}`, {
            onSuccess: () => {
                showSubcategoryDialog.value = false;
            },
            preserveScroll: true,
        });
    } else {
        subcategoryForm.post(`/categories/${props.category!.id}/subcategories`, {
            onSuccess: () => {
                showSubcategoryDialog.value = false;
            },
            preserveScroll: true,
        });
    }
}

function confirmDeleteSubcategory(subcategory: Subcategory) {
    confirm.require({
        message: `Are you sure you want to delete "${subcategory.subcategory_name}"?`,
        header: 'Delete Subcategory',
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
            router.delete(`/categories/${props.category!.id}/subcategories/${subcategory.id}`, {
                preserveScroll: true,
            });
        },
    });
}

function confirmRestoreSubcategory(subcategory: Subcategory) {
    confirm.require({
        message: `Are you sure you want to restore "${subcategory.subcategory_name}"?`,
        header: 'Restore Subcategory',
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
            router.post(`/categories/${props.category!.id}/subcategories/${subcategory.id}/restore`, {}, {
                preserveScroll: true,
            });
        },
    });
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-4">
                <BackButton fallback-url="/categories" />
                <h1 class="heading-lg">{{ pageTitle }}</h1>
            </div>

            <div class="mx-auto w-full max-w-4xl">
                <Card>
                    <template #content>
                        <form @submit.prevent="submit" class="flex flex-col gap-6">
                            <!-- Basic Information -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Basic Information</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-2">
                                        <label for="category_name" class="font-medium">Category Name *</label>
                                        <InputText
                                            id="category_name"
                                            v-model="form.category_name"
                                            :invalid="!!form.errors.category_name"
                                            placeholder="Electronics"
                                            size="small"
                                            fluid
                                        />
                                        <small v-if="form.errors.category_name" class="text-red-500">
                                            {{ form.errors.category_name }}
                                        </small>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label for="category_code" class="font-medium">Category Code *</label>
                                        <InputText
                                            id="category_code"
                                            v-model="form.category_code"
                                            :invalid="!!form.errors.category_code"
                                            placeholder="ELEC"
                                            maxlength="4"
                                            size="small"
                                            fluid
                                            class="uppercase"
                                        />
                                        <small class="text-muted-foreground">Max 4 characters, must be unique</small>
                                        <small v-if="form.errors.category_code" class="text-red-500">
                                            {{ form.errors.category_code }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <!-- Description -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Description</h3>
                                <div class="flex flex-col gap-2">
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        :invalid="!!form.errors.description"
                                        placeholder="Category description..."
                                        rows="3"
                                        fluid
                                    />
                                    <small v-if="form.errors.description" class="text-red-500">
                                        {{ form.errors.description }}
                                    </small>
                                </div>
                            </div>

                            <Divider />

                            <!-- Status -->
                            <div>
                                <h3 class="mb-4 text-lg font-medium">Status</h3>
                                <div class="flex items-center gap-3">
                                    <ToggleSwitch v-model="form.is_active" />
                                    <span :class="form.is_active ? 'text-green-600' : 'text-red-600'">
                                        {{ form.is_active ? 'Active' : 'Inactive' }}
                                    </span>
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
                                    :label="isEditing ? 'Save Changes' : 'Create Category'"
                                    size="small"
                                    :loading="form.processing"
                                />
                            </div>
                        </form>
                    </template>
                </Card>

                <!-- Subcategories Section (Edit mode only) -->
                <Card v-if="isEditing && category" class="mt-4">
                    <template #content>
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium">Subcategories</h3>
                                <Button
                                    label="Add Subcategory"
                                    icon="pi pi-plus"
                                    size="small"
                                    @click="openAddSubcategoryDialog"
                                />
                            </div>

                            <DataTable
                                v-model:expandedRows="expandedRows"
                                :value="category.subcategories"
                                dataKey="id"
                                striped-rows
                                size="small"
                                class="overflow-hidden rounded-lg border border-border"
                            >
                                <template #empty>
                                    <div class="p-4 text-center text-muted-foreground">
                                        No subcategories found.
                                    </div>
                                </template>
                                <Column expander class="w-10 !pr-0 md:hidden" />
                                <Column field="subcategory_name" header="Name" class="!pl-3 md:w-48">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-2">
                                            <span :class="{ 'text-muted-foreground line-through': isSubcategoryDeleted(data) }">
                                                {{ data.subcategory_name }}
                                            </span>
                                            <Tag v-if="data.is_default" value="Default" severity="info" class="!text-xs" />
                                            <Tag v-if="isSubcategoryDeleted(data)" value="Deleted" severity="danger" class="!text-xs" />
                                        </div>
                                    </template>
                                </Column>
                                <Column field="subcategory_code" header="Code" class="w-20 !pl-0">
                                    <template #body="{ data }">
                                        <Tag :value="data.subcategory_code" severity="secondary" />
                                    </template>
                                </Column>
                                <Column field="description" header="Description" class="hidden md:table-cell">
                                    <template #body="{ data }">
                                        <span class="text-muted-foreground">{{ data.description ?? '-' }}</span>
                                    </template>
                                </Column>
                                <Column field="is_active" header="Status" class="hidden w-24 md:table-cell">
                                    <template #body="{ data }">
                                        <Tag
                                            :value="data.is_active ? 'Active' : 'Inactive'"
                                            :severity="data.is_active ? 'success' : 'danger'"
                                        />
                                    </template>
                                </Column>
                                <Column header="" class="w-24 !pr-4">
                                    <template #body="{ data }">
                                        <div v-if="isSubcategoryDeleted(data)" class="flex justify-end gap-1">
                                            <Button
                                                icon="pi pi-history"
                                                severity="success"
                                                text
                                                rounded
                                                size="small"
                                                @click="confirmRestoreSubcategory(data)"
                                                v-tooltip.top="'Restore'"
                                            />
                                        </div>
                                        <div v-else class="flex justify-end gap-1">
                                            <Button
                                                icon="pi pi-pencil"
                                                severity="secondary"
                                                text
                                                rounded
                                                size="small"
                                                @click="openEditSubcategoryDialog(data)"
                                            />
                                            <Button
                                                v-if="!data.is_default"
                                                icon="pi pi-trash"
                                                severity="danger"
                                                text
                                                rounded
                                                size="small"
                                                @click="confirmDeleteSubcategory(data)"
                                            />
                                        </div>
                                    </template>
                                </Column>
                                <template #expansion="{ data }">
                                    <div class="grid gap-2 p-3 text-sm md:hidden">
                                        <div class="flex justify-between border-b border-border pb-2">
                                            <span class="text-muted-foreground">Status</span>
                                            <Tag
                                                :value="data.is_active ? 'Active' : 'Inactive'"
                                                :severity="data.is_active ? 'success' : 'danger'"
                                            />
                                        </div>
                                        <div v-if="data.description" class="flex flex-col gap-1">
                                            <span class="text-muted-foreground">Description</span>
                                            <span>{{ data.description }}</span>
                                        </div>
                                        <div v-else class="text-muted-foreground">No description</div>
                                    </div>
                                </template>
                            </DataTable>
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Subcategory Dialog -->
        <Dialog
            v-model:visible="showSubcategoryDialog"
            :header="editingSubcategory ? 'Edit Subcategory' : 'Add Subcategory'"
            :modal="true"
            :closable="true"
            class="w-full max-w-lg"
        >
            <form @submit.prevent="submitSubcategory" class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="subcategory_name" class="font-medium">Subcategory Name *</label>
                    <InputText
                        id="subcategory_name"
                        v-model="subcategoryForm.subcategory_name"
                        :invalid="!!subcategoryForm.errors.subcategory_name"
                        placeholder="Phones"
                        size="small"
                        fluid
                    />
                    <small v-if="subcategoryForm.errors.subcategory_name" class="text-red-500">
                        {{ subcategoryForm.errors.subcategory_name }}
                    </small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="subcategory_code" class="font-medium">Subcategory Code *</label>
                    <InputText
                        id="subcategory_code"
                        v-model="subcategoryForm.subcategory_code"
                        :invalid="!!subcategoryForm.errors.subcategory_code"
                        placeholder="PHON"
                        maxlength="4"
                        size="small"
                        fluid
                        class="uppercase"
                    />
                    <small class="text-muted-foreground">Max 4 characters, must be unique within category</small>
                    <small v-if="subcategoryForm.errors.subcategory_code" class="text-red-500">
                        {{ subcategoryForm.errors.subcategory_code }}
                    </small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="subcategory_description" class="font-medium">Description</label>
                    <Textarea
                        id="subcategory_description"
                        v-model="subcategoryForm.description"
                        :invalid="!!subcategoryForm.errors.description"
                        placeholder="Subcategory description..."
                        rows="2"
                        fluid
                    />
                    <small v-if="subcategoryForm.errors.description" class="text-red-500">
                        {{ subcategoryForm.errors.description }}
                    </small>
                </div>

                <div class="flex items-center gap-3">
                    <ToggleSwitch v-model="subcategoryForm.is_active" />
                    <span :class="subcategoryForm.is_active ? 'text-green-600' : 'text-red-600'">
                        {{ subcategoryForm.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="mt-2 flex justify-end gap-2">
                    <Button
                        type="button"
                        label="Cancel"
                        severity="secondary"
                        size="small"
                        @click="showSubcategoryDialog = false"
                        :disabled="subcategoryForm.processing"
                    />
                    <Button
                        type="submit"
                        :label="editingSubcategory ? 'Save Changes' : 'Add Subcategory'"
                        size="small"
                        :loading="subcategoryForm.processing"
                    />
                </div>
            </form>
        </Dialog>

        <ConfirmDialog />
    </AppLayout>
</template>
