<script setup lang="ts">
import type { Product } from '@/types';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Image from 'primevue/image';
import Tag from 'primevue/tag';
import { computed } from 'vue';

interface Props {
    visible: boolean;
    product: Product | null;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const emit = defineEmits<{
    (e: 'update:visible', value: boolean): void;
    (e: 'select', variant: Product): void;
    (e: 'close'): void;
}>();

const variants = computed(() => props.product?.variants ?? []);
const hasVariants = computed(() => variants.value.length > 0);

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function selectVariant(variant: Product) {
    emit('select', variant);
}

function close() {
    emit('update:visible', false);
    emit('close');
}
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        dismissable-mask
        :style="{ width: '40rem' }"
        :breakpoints="{ '960px': '75vw', '640px': '95vw' }"
        :draggable="false"
        header="Select Variant"
        @update:visible="emit('update:visible', $event)"
    >
        <!-- Loading state -->
        <div v-if="loading" class="flex items-center justify-center py-8">
            <i class="pi pi-spin pi-spinner text-2xl text-muted-foreground" />
        </div>

        <!-- No product -->
        <div
            v-else-if="!product"
            class="py-8 text-center text-muted-foreground"
        >
            No product selected
        </div>

        <!-- Product with variants -->
        <div v-else class="flex flex-col gap-4">
            <!-- Parent Product Header -->
            <div class="flex items-center gap-4">
                <Image
                    v-if="product.image_url"
                    :src="product.image_url"
                    :alt="product.product_name"
                    image-class="!h-16 !w-16 rounded-lg object-cover"
                    :pt="{
                        root: { class: 'rounded-lg overflow-hidden flex-shrink-0' },
                    }"
                />
                <Avatar
                    v-else
                    :label="getInitials(product.product_name)"
                    shape="square"
                    class="!h-16 !w-16 flex-shrink-0 rounded-lg bg-primary/10 text-xl text-primary"
                />
                <div class="min-w-0 flex-1">
                    <h3 class="font-medium">{{ product.product_name }}</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <Tag
                            :value="product.product_number"
                            severity="secondary"
                            class="!text-xs"
                        />
                        <span
                            v-if="product.brand_name"
                            class="text-sm text-muted-foreground"
                        >
                            {{ product.brand_name }}
                        </span>
                    </div>
                </div>
            </div>

            <Divider class="!my-2" />

            <!-- Variants List -->
            <div v-if="hasVariants">
                <h4 class="mb-3 text-sm font-medium text-muted-foreground">
                    Select a variant ({{ variants.length }})
                </h4>
                <div class="flex max-h-[300px] flex-col gap-2 overflow-y-auto">
                    <button
                        v-for="variant in variants"
                        :key="variant.id"
                        type="button"
                        class="flex items-center gap-3 rounded-lg border border-border p-3 text-left transition-colors hover:border-primary hover:bg-primary/5 focus:border-primary focus:outline-none"
                        @click="selectVariant(variant)"
                    >
                        <Image
                            v-if="variant.image_url"
                            :src="variant.image_url"
                            :alt="variant.product_name"
                            image-class="!h-12 !w-12 rounded object-cover"
                            :pt="{
                                root: {
                                    class: 'rounded overflow-hidden flex-shrink-0',
                                },
                            }"
                        />
                        <Avatar
                            v-else
                            :label="getInitials(variant.variant_name ?? variant.product_name)"
                            shape="square"
                            class="!h-12 !w-12 flex-shrink-0 rounded bg-primary/10 text-sm text-primary"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">
                                    {{ variant.variant_name ?? variant.product_name }}
                                </span>
                                <Tag
                                    v-if="!variant.is_active"
                                    value="Inactive"
                                    severity="danger"
                                    class="!text-xs"
                                />
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs text-muted-foreground">
                                    {{ variant.product_number }}
                                </span>
                                <span
                                    v-if="variant.barcode"
                                    class="text-xs text-muted-foreground"
                                >
                                    &middot; {{ variant.barcode }}
                                </span>
                            </div>
                        </div>
                        <i class="pi pi-chevron-right text-muted-foreground" />
                    </button>
                </div>
            </div>

            <!-- No variants message -->
            <div v-else class="py-4 text-center text-muted-foreground">
                <i class="pi pi-info-circle mb-2 text-2xl" />
                <p>This product has no variants.</p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end">
                <Button
                    label="Cancel"
                    severity="secondary"
                    size="small"
                    @click="close"
                />
            </div>
        </template>
    </Dialog>
</template>
