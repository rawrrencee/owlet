<script setup lang="ts">
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { computed, ref, watch } from 'vue';

interface Subcategory {
    id: number;
    subcategory_name: string;
}

interface Category {
    id: number;
    category_name: string;
    subcategories: Subcategory[];
}

interface Brand {
    id: number;
    brand_name: string;
}

const props = defineProps<{
    visible: boolean;
    mode: 'category' | 'brand';
    categories: Category[];
    brands: Brand[];
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    'select-category': [payload: { categoryId: number; subcategoryId: number | null }];
    'select-brand': [payload: { brandId: number }];
}>();

const search = ref('');
const selectedCategory = ref<Category | null>(null);

// Reset state when dialog opens/closes or mode changes
watch(() => props.visible, (val) => {
    if (val) {
        search.value = '';
        selectedCategory.value = null;
    }
});

watch(() => props.mode, () => {
    search.value = '';
    selectedCategory.value = null;
});

const dialogHeader = computed(() => {
    if (props.mode === 'brand') return 'Browse Brands';
    if (selectedCategory.value) return selectedCategory.value.category_name;
    return 'Browse Categories';
});

const filteredCategories = computed(() => {
    if (!search.value) return props.categories;
    const q = search.value.toLowerCase();
    return props.categories.filter(c => c.category_name.toLowerCase().includes(q));
});

const filteredBrands = computed(() => {
    if (!search.value) return props.brands;
    const q = search.value.toLowerCase();
    return props.brands.filter(b => b.brand_name.toLowerCase().includes(q));
});

const filteredSubcategories = computed(() => {
    if (!selectedCategory.value) return [];
    if (!search.value) return selectedCategory.value.subcategories;
    const q = search.value.toLowerCase();
    return selectedCategory.value.subcategories.filter(s => s.subcategory_name.toLowerCase().includes(q));
});

function close() {
    emit('update:visible', false);
}

function onCategoryClick(cat: Category) {
    if (cat.subcategories.length === 0) {
        emit('select-category', { categoryId: cat.id, subcategoryId: null });
        close();
    } else {
        selectedCategory.value = cat;
        search.value = '';
    }
}

function onSelectAllCategory() {
    if (!selectedCategory.value) return;
    emit('select-category', { categoryId: selectedCategory.value.id, subcategoryId: null });
    close();
}

function onSubcategoryClick(sub: Subcategory) {
    if (!selectedCategory.value) return;
    emit('select-category', { categoryId: selectedCategory.value.id, subcategoryId: sub.id });
    close();
}

function onBrandClick(brand: Brand) {
    emit('select-brand', { brandId: brand.id });
    close();
}

function onBack() {
    selectedCategory.value = null;
    search.value = '';
}
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        :header="dialogHeader"
        modal
        :style="{ width: '400px' }"
        :content-style="{ padding: '0' }"
    >
        <template #header>
            <div class="flex items-center gap-2">
                <Button
                    v-if="mode === 'category' && selectedCategory"
                    icon="pi pi-arrow-left"
                    text
                    size="small"
                    @click="onBack"
                />
                <span class="font-semibold">{{ dialogHeader }}</span>
            </div>
        </template>

        <div class="p-3 border-b">
            <IconField>
                <InputIcon class="pi pi-search" />
                <InputText
                    v-model="search"
                    :placeholder="mode === 'brand' ? 'Search brands...' : (selectedCategory ? 'Search subcategories...' : 'Search categories...')"
                    size="small"
                    class="w-full"
                />
            </IconField>
        </div>

        <!-- Brand list -->
        <div v-if="mode === 'brand'" class="max-h-80 overflow-y-auto divide-y">
            <div
                v-for="brand in filteredBrands"
                :key="brand.id"
                class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors"
                @click="onBrandClick(brand)"
            >
                <span class="text-sm">{{ brand.brand_name }}</span>
            </div>
            <div v-if="filteredBrands.length === 0" class="p-4 text-center text-sm text-muted-color">
                No brands found.
            </div>
        </div>

        <!-- Category list (step 1) -->
        <div v-else-if="!selectedCategory" class="max-h-80 overflow-y-auto divide-y">
            <div
                v-for="cat in filteredCategories"
                :key="cat.id"
                class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors"
                @click="onCategoryClick(cat)"
            >
                <span class="text-sm flex-1">{{ cat.category_name }}</span>
                <i v-if="cat.subcategories.length > 0" class="pi pi-chevron-right text-xs text-muted-color" />
            </div>
            <div v-if="filteredCategories.length === 0" class="p-4 text-center text-sm text-muted-color">
                No categories found.
            </div>
        </div>

        <!-- Subcategory list (step 2) -->
        <div v-else class="max-h-80 overflow-y-auto divide-y">
            <div
                class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors"
                @click="onSelectAllCategory"
            >
                <span class="text-sm font-medium">All {{ selectedCategory.category_name }}</span>
            </div>
            <div
                v-for="sub in filteredSubcategories"
                :key="sub.id"
                class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors"
                @click="onSubcategoryClick(sub)"
            >
                <span class="text-sm">{{ sub.subcategory_name }}</span>
            </div>
            <div v-if="filteredSubcategories.length === 0 && search" class="p-4 text-center text-sm text-muted-color">
                No subcategories found.
            </div>
        </div>
    </Dialog>
</template>
