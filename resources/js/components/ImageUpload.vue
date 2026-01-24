<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import ConfirmDialog from 'primevue/confirmdialog';
import Image from 'primevue/image';
import { useConfirm } from 'primevue/useconfirm';
import { ref, watch } from 'vue';

interface Props {
    /** Current image URL (if exists) */
    imageUrl: string | null;
    /** URL to POST the image to */
    uploadUrl: string;
    /** URL to DELETE the image from */
    deleteUrl: string;
    /** Field name for the file input */
    fieldName?: string;
    /** Label for the upload section */
    label?: string;
    /** Placeholder icon class when no image */
    placeholderIcon?: string;
    /** Alt text for the image */
    alt?: string;
    /** Max file size in MB */
    maxSizeMB?: number;
    /** Accepted file types */
    accept?: string;
    /** Whether image is circular (for profile pictures) */
    circular?: boolean;
    /** Size of the preview in pixels */
    previewSize?: number;
}

const props = withDefaults(defineProps<Props>(), {
    fieldName: 'image',
    label: 'Image',
    placeholderIcon: 'pi pi-image',
    alt: 'Image',
    maxSizeMB: 5,
    accept: 'image/*',
    circular: false,
    previewSize: 96,
});

const emit = defineEmits<{
    (e: 'uploaded', url: string): void;
    (e: 'deleted'): void;
    (e: 'error', message: string): void;
}>();

// Local state
const currentImageUrl = ref<string | null>(props.imageUrl);
const selectedFile = ref<File | null>(null);
const previewUrl = ref<string | null>(null);
const uploading = ref(false);
const error = ref<string | null>(null);

const confirm = useConfirm();

// Watch for external imageUrl changes
watch(() => props.imageUrl, (newUrl) => {
    currentImageUrl.value = newUrl;
});

function onFileSelect(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
        error.value = 'Please select an image file.';
        emit('error', error.value);
        return;
    }

    // Validate file size
    const maxBytes = props.maxSizeMB * 1024 * 1024;
    if (file.size > maxBytes) {
        error.value = `Image must be less than ${props.maxSizeMB}MB.`;
        emit('error', error.value);
        return;
    }

    error.value = null;
    selectedFile.value = file;

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
        previewUrl.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
}

function upload() {
    if (!selectedFile.value) return;

    uploading.value = true;
    error.value = null;

    const formData = new FormData();
    formData.append(props.fieldName, selectedFile.value);

    router.post(props.uploadUrl, formData, {
        preserveScroll: true,
        onSuccess: () => {
            // Add cache-busting timestamp to the URL
            currentImageUrl.value = `${props.uploadUrl}?t=${Date.now()}`;
            selectedFile.value = null;
            previewUrl.value = null;
            emit('uploaded', currentImageUrl.value);
        },
        onError: (errors) => {
            error.value = errors[props.fieldName] || 'Failed to upload image.';
            emit('error', error.value);
        },
        onFinish: () => {
            uploading.value = false;
        },
    });
}

function confirmDelete() {
    confirm.require({
        message: `Are you sure you want to remove this ${props.label.toLowerCase()}?`,
        header: `Remove ${props.label}`,
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        rejectProps: {
            severity: 'secondary',
            size: 'small',
        },
        acceptLabel: 'Remove',
        acceptProps: {
            severity: 'danger',
            size: 'small',
        },
        accept: deleteImage,
    });
}

function deleteImage() {
    uploading.value = true;

    router.delete(props.deleteUrl, {
        preserveScroll: true,
        onSuccess: () => {
            currentImageUrl.value = null;
            selectedFile.value = null;
            previewUrl.value = null;
            emit('deleted');
        },
        onFinish: () => {
            uploading.value = false;
        },
    });
}

function cancelSelection() {
    selectedFile.value = null;
    previewUrl.value = null;
    error.value = null;
}

const displayUrl = () => previewUrl.value || currentImageUrl.value;
const hasImage = () => displayUrl() !== null;
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
        <div class="flex flex-col items-center gap-2">
            <div
                :class="[
                    'relative overflow-hidden bg-surface-200 dark:bg-surface-700',
                    circular ? 'rounded-full' : 'rounded-lg',
                ]"
                :style="{ width: `${previewSize}px`, height: `${previewSize}px` }"
            >
                <Image
                    v-if="hasImage()"
                    :src="displayUrl() || ''"
                    :alt="alt"
                    :image-class="`object-cover cursor-pointer`"
                    :image-style="{ width: `${previewSize}px`, height: `${previewSize}px` }"
                    :pt="{
                        root: { class: circular ? 'rounded-full overflow-hidden' : 'rounded-lg overflow-hidden' },
                        previewMask: { class: circular ? 'rounded-full' : 'rounded-lg' },
                    }"
                    preview
                />
                <div v-else class="flex h-full w-full items-center justify-center">
                    <i :class="[placeholderIcon, 'text-3xl text-surface-400']"></i>
                </div>
            </div>
        </div>
        <div class="flex flex-1 flex-col gap-2">
            <label class="font-medium">{{ label }}</label>
            <div class="flex flex-wrap items-center gap-2">
                <label class="cursor-pointer">
                    <input
                        type="file"
                        :accept="accept"
                        class="hidden"
                        @change="onFileSelect"
                        :disabled="uploading"
                    />
                    <Button
                        as="span"
                        :label="currentImageUrl ? 'Change' : 'Upload'"
                        icon="pi pi-upload"
                        size="small"
                        severity="secondary"
                        :disabled="uploading"
                    />
                </label>
                <Button
                    v-if="selectedFile"
                    label="Save"
                    icon="pi pi-check"
                    size="small"
                    :loading="uploading"
                    @click="upload"
                />
                <Button
                    v-if="selectedFile"
                    label="Cancel"
                    icon="pi pi-times"
                    size="small"
                    severity="secondary"
                    text
                    :disabled="uploading"
                    @click="cancelSelection"
                />
                <Button
                    v-if="currentImageUrl && !selectedFile"
                    label="Remove"
                    icon="pi pi-trash"
                    size="small"
                    severity="danger"
                    text
                    :disabled="uploading"
                    @click="confirmDelete"
                />
            </div>
            <small class="text-muted-foreground">
                Accepts JPG, PNG, GIF. Max {{ maxSizeMB }}MB.
            </small>
            <small v-if="error" class="text-red-500">
                {{ error }}
            </small>
        </div>
    </div>
    <ConfirmDialog />
</template>
