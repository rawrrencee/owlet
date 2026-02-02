<script setup lang="ts">
import type { ProductImage } from '@/types';
import Button from 'primevue/button';
import Image from 'primevue/image';
import { useConfirm } from 'primevue/useconfirm';
import { ref, watch } from 'vue';

interface Props {
    productId: number;
    coverImageUrl: string | null;
    images: ProductImage[];
    editable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    editable: false,
});

const emit = defineEmits<{
    (e: 'update:coverImageUrl', url: string | null): void;
    (e: 'update:images', images: ProductImage[]): void;
}>();

const localCoverUrl = ref<string | null>(props.coverImageUrl);
const localImages = ref<ProductImage[]>([...props.images]);
const uploading = ref(false);
const error = ref<string | null>(null);
const draggedIndex = ref<number | null>(null);

const confirm = useConfirm();

// Track which image has its overlay open (for mobile tap-to-toggle)
const activeOverlayIndex = ref<number | null>(null);

function toggleOverlay(index: number) {
    if (activeOverlayIndex.value === index) {
        activeOverlayIndex.value = null;
    } else {
        activeOverlayIndex.value = index;
    }
}

watch(
    () => props.coverImageUrl,
    (newUrl) => {
        localCoverUrl.value = newUrl;
    },
);

watch(
    () => props.images,
    (newImages) => {
        localImages.value = [...newImages];
    },
    { deep: true },
);

function onFileSelect(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
        error.value = 'Please select an image file.';
        return;
    }

    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        error.value = 'Image must be less than 5MB.';
        return;
    }

    error.value = null;
    uploadImage(file);
}

function uploadImage(file: File) {
    uploading.value = true;

    const formData = new FormData();
    formData.append('image', file);

    fetch(`/products/${props.productId}/images`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN':
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') || '',
            Accept: 'application/json',
        },
    })
        .then((response) => {
            if (!response.ok) throw new Error('Upload failed');
            return response.json();
        })
        .then((data) => {
            localImages.value.push(data.image);
            emit('update:images', localImages.value);
        })
        .catch(() => {
            error.value = 'Failed to upload image.';
        })
        .finally(() => {
            uploading.value = false;
        });
}

function confirmDeleteSupplementary(imageId: number) {
    confirm.require({
        message: 'Are you sure you want to delete this image?',
        header: 'Delete Image',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: { severity: 'secondary', size: 'small' },
        acceptLabel: 'Delete',
        acceptProps: { severity: 'danger', size: 'small' },
        accept: () => deleteSupplementary(imageId),
    });
}

function deleteSupplementary(imageId: number) {
    fetch(`/products/${props.productId}/images/${imageId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN':
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') || '',
            Accept: 'application/json',
        },
    })
        .then((response) => {
            if (!response.ok) throw new Error('Delete failed');
            return response.json();
        })
        .then(() => {
            localImages.value = localImages.value.filter(
                (img) => img.id !== imageId,
            );
            emit('update:images', localImages.value);
        })
        .catch(() => {
            error.value = 'Failed to delete image.';
        });
}

function confirmPromoteToCover(imageId: number) {
    confirm.require({
        message:
            'Promote this image to cover? The current cover will become a supplementary image.',
        header: 'Promote to Cover',
        icon: 'pi pi-star',
        rejectLabel: 'Cancel',
        rejectProps: { severity: 'secondary', size: 'small' },
        acceptLabel: 'Promote',
        acceptProps: { severity: 'success', size: 'small' },
        accept: () => promoteToCover(imageId),
    });
}

function promoteToCover(imageId: number) {
    fetch(`/products/${props.productId}/images/${imageId}/promote`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN':
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') || '',
            Accept: 'application/json',
        },
    })
        .then((response) => {
            if (!response.ok) throw new Error('Promote failed');
            return response.json();
        })
        .then((data) => {
            // Update local state with response data
            localCoverUrl.value = data.cover_url;
            localImages.value = data.images;
            emit('update:coverImageUrl', data.cover_url);
            emit('update:images', data.images);
            activeOverlayIndex.value = null;
        })
        .catch(() => {
            error.value = 'Failed to promote image.';
        });
}

function onDragStart(event: DragEvent, index: number) {
    if (!props.editable) return;
    draggedIndex.value = index;

    // Set custom drag image to show only the image, not the container
    const target = event.currentTarget as HTMLElement;
    const img = target.querySelector('img');
    if (img && event.dataTransfer) {
        event.dataTransfer.setDragImage(img, 48, 48); // Center of 96x96 image
    }
}

function onDragOver(event: DragEvent) {
    event.preventDefault();
}

function onDrop(targetIndex: number) {
    if (draggedIndex.value === null || draggedIndex.value === targetIndex)
        return;

    const images = [...localImages.value];
    const [draggedItem] = images.splice(draggedIndex.value, 1);
    images.splice(targetIndex, 0, draggedItem);

    // Update sort orders
    images.forEach((img, idx) => {
        img.sort_order = idx;
    });

    localImages.value = images;
    draggedIndex.value = null;

    // Save the new order
    saveImageOrder(images.map((img) => img.id));
}

function saveImageOrder(imageIds: number[]) {
    fetch(`/products/${props.productId}/images/reorder`, {
        method: 'POST',
        body: JSON.stringify({ image_ids: imageIds }),
        headers: {
            'X-CSRF-TOKEN':
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') || '',
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
    })
        .then((response) => {
            if (!response.ok) throw new Error('Reorder failed');
            emit('update:images', localImages.value);
        })
        .catch(() => {
            error.value = 'Failed to reorder images.';
        });
}

function onDragEnd() {
    draggedIndex.value = null;
}

function moveImage(index: number, direction: 'up' | 'down') {
    const targetIndex = direction === 'up' ? index - 1 : index + 1;

    // Bounds check
    if (targetIndex < 0 || targetIndex >= localImages.value.length) return;

    const images = [...localImages.value];
    // Swap positions
    [images[index], images[targetIndex]] = [images[targetIndex], images[index]];

    // Update sort orders
    images.forEach((img, idx) => {
        img.sort_order = idx;
    });

    localImages.value = images;

    // Save the new order
    saveImageOrder(images.map((img) => img.id));
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap gap-4">
            <!-- Cover Image -->
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-muted-foreground"
                    >Cover</span
                >
                <div
                    class="relative h-24 w-24 overflow-hidden rounded-lg border-2 border-primary bg-muted"
                >
                    <Image
                        v-if="localCoverUrl"
                        :src="localCoverUrl"
                        alt="Cover image"
                        image-class="h-24 w-24 object-cover cursor-pointer"
                        :pt="{
                            root: { class: 'rounded-lg overflow-hidden' },
                            previewMask: { class: 'rounded-lg' },
                        }"
                        preview
                    />
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center"
                    >
                        <i class="pi pi-box text-3xl text-muted-foreground"></i>
                    </div>
                </div>
            </div>

            <!-- Supplementary Images -->
            <div
                v-for="(image, index) in localImages"
                :key="image.id"
                class="group flex flex-col gap-2"
                :draggable="editable"
                @dragstart="onDragStart($event, index)"
                @dragover="onDragOver"
                @drop="onDrop(index)"
                @dragend="onDragEnd"
            >
                <span
                    class="text-sm font-medium text-muted-foreground opacity-0"
                    >&nbsp;</span
                >
                <div
                    class="relative h-24 w-24 overflow-hidden rounded-lg border border-border bg-muted"
                    :class="{
                        'cursor-move': editable,
                        'border-dashed border-primary':
                            draggedIndex === index,
                    }"
                    @click="editable ? toggleOverlay(index) : undefined"
                >
                    <Image
                        :src="image.image_url"
                        :alt="image.image_filename"
                        image-class="h-24 w-24 object-cover cursor-pointer"
                        :pt="{
                            root: { class: 'rounded-lg overflow-hidden' },
                            previewMask: { class: 'rounded-lg' },
                        }"
                        :preview="!editable"
                    />
                    <!-- Action overlay -->
                    <div
                        v-if="editable"
                        class="image-action-overlay absolute inset-0 flex flex-col items-center justify-center bg-black/60 transition-opacity"
                        :class="{
                            'opacity-100': activeOverlayIndex === index,
                            'opacity-0 group-hover:opacity-100':
                                activeOverlayIndex !== index,
                        }"
                    >
                        <!-- Move buttons (top row) -->
                        <div
                            v-if="localImages.length > 1"
                            class="mb-1 flex gap-1"
                        >
                            <Button
                                icon="pi pi-chevron-left"
                                severity="secondary"
                                size="small"
                                rounded
                                text
                                class="!h-6 !w-6 !bg-white/20 hover:!bg-white/40"
                                :disabled="index === 0"
                                v-tooltip.top="'Move left'"
                                @click.stop="moveImage(index, 'up')"
                            />
                            <Button
                                icon="pi pi-chevron-right"
                                severity="secondary"
                                size="small"
                                rounded
                                text
                                class="!h-6 !w-6 !bg-white/20 hover:!bg-white/40"
                                :disabled="index === localImages.length - 1"
                                v-tooltip.top="'Move right'"
                                @click.stop="moveImage(index, 'down')"
                            />
                        </div>
                        <!-- Action buttons (bottom row) -->
                        <div class="flex gap-1">
                            <Button
                                icon="pi pi-star"
                                severity="success"
                                size="small"
                                rounded
                                text
                                class="!h-6 !w-6 !bg-white/20 hover:!bg-white/40"
                                v-tooltip.top="'Set as cover'"
                                @click.stop="confirmPromoteToCover(image.id)"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                size="small"
                                rounded
                                text
                                class="!h-6 !w-6 !bg-white/20 hover:!bg-white/40"
                                v-tooltip.top="'Delete'"
                                @click.stop="confirmDeleteSupplementary(image.id)"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Image Button -->
            <div v-if="editable" class="flex flex-col gap-2">
                <span
                    class="text-sm font-medium text-muted-foreground opacity-0"
                    >&nbsp;</span
                >
                <label class="cursor-pointer">
                    <input
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="onFileSelect"
                        :disabled="uploading"
                    />
                    <div
                        class="flex h-24 w-24 items-center justify-center rounded-lg border-2 border-dashed border-border bg-muted transition-colors hover:border-primary hover:bg-muted/80"
                    >
                        <i
                            v-if="!uploading"
                            class="pi pi-plus text-2xl text-muted-foreground"
                        ></i>
                        <i
                            v-else
                            class="pi pi-spin pi-spinner text-2xl text-muted-foreground"
                        ></i>
                    </div>
                </label>
            </div>
        </div>

        <small v-if="error" class="text-red-500">{{ error }}</small>
        <small v-if="editable" class="text-muted-foreground">
            Tap an image to show actions. Use arrows to reorder, star to set as
            cover.
        </small>
    </div>
</template>

<style scoped>
/* On touch devices (no hover capability), show overlay on tap only */
@media (hover: none) {
    .image-action-overlay {
        /* Override group-hover since it doesn't work on touch */
        opacity: 0 !important;
    }
    .image-action-overlay.opacity-100 {
        opacity: 1 !important;
    }
}
</style>
