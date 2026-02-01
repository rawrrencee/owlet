<script setup lang="ts">
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import VirtualScroller from 'primevue/virtualscroller';
import { computed } from 'vue';

interface SelectionProduct {
    id: number;
    product_name: string;
    product_number: string;
    brand_name?: string | null;
    image_url?: string | null;
}

interface Props {
    visible: boolean;
    products: SelectionProduct[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    'deselect': [productId: number];
    'clear-all': [];
    'preview': [productId: number];
}>();

const dialogVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});

function getInitials(product: SelectionProduct): string {
    const words = product.product_name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return product.product_name.substring(0, 2).toUpperCase();
}

function handleDeselect(productId: number) {
    emit('deselect', productId);
}

function handlePreview(productId: number) {
    emit('preview', productId);
}

function handleClearAll() {
    emit('clear-all');
    dialogVisible.value = false;
}

function handleClose() {
    dialogVisible.value = false;
}
</script>

<template>
    <Dialog
        v-model:visible="dialogVisible"
        modal
        :draggable="false"
        header="Selected Products"
        class="w-full max-w-md"
        :pt="{
            content: { class: 'p-0' },
        }"
    >
        <div class="flex flex-col">
            <!-- Products List -->
            <div v-if="products.length > 0" class="border-b border-border">
                <VirtualScroller
                    :items="products"
                    :itemSize="56"
                    class="h-[320px]"
                >
                    <template v-slot:item="{ item, options }">
                        <div
                            :class="[
                                'flex items-center gap-3 overflow-hidden px-4 py-2',
                                { 'bg-muted/50': options.odd },
                            ]"
                            style="height: 56px"
                        >
                            <!-- Clickable product info area -->
                            <div
                                class="flex min-w-0 flex-1 cursor-pointer items-center gap-3 overflow-hidden"
                                @click="handlePreview(item.id)"
                            >
                                <!-- Image/Avatar -->
                                <img
                                    v-if="item.image_url"
                                    :src="item.image_url"
                                    :alt="item.product_name"
                                    class="h-10 w-10 shrink-0 rounded object-cover"
                                />
                                <Avatar
                                    v-else
                                    :label="getInitials(item)"
                                    shape="square"
                                    class="!h-10 !w-10 shrink-0 rounded bg-primary/10 text-primary"
                                />

                                <!-- Product Info -->
                                <div class="min-w-0 flex-1 overflow-hidden">
                                    <div class="truncate text-sm font-medium">
                                        {{ item.product_name }}
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <span>{{ item.product_number }}</span>
                                        <span v-if="item.brand_name" class="truncate">
                                            {{ item.brand_name }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Deselect Button -->
                            <Button
                                icon="pi pi-times"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                @click="handleDeselect(item.id)"
                                v-tooltip.left="'Remove from selection'"
                            />
                        </div>
                    </template>
                </VirtualScroller>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center p-8 text-center">
                <i class="pi pi-inbox mb-3 text-4xl text-muted-foreground/50"></i>
                <p class="text-sm text-muted-foreground">No products selected</p>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between gap-2 p-4">
                <Button
                    v-if="products.length > 0"
                    label="Clear All"
                    severity="danger"
                    text
                    size="small"
                    @click="handleClearAll"
                />
                <span v-else></span>

                <div class="flex items-center gap-2">
                    <span v-if="products.length > 0" class="text-sm text-muted-foreground">
                        {{ products.length }} selected
                    </span>
                    <Button
                        label="Done"
                        size="small"
                        @click="handleClose"
                    />
                </div>
            </div>
        </div>
    </Dialog>
</template>
