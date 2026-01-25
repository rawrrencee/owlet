<script setup lang="ts">
import Button from 'primevue/button';
import Image from 'primevue/image';
import { ref, watch } from 'vue';

interface Props {
    /** Current image URL (if exists, for edit mode) */
    imageUrl?: string | null;
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
    imageUrl: null,
    label: 'Image',
    placeholderIcon: 'pi pi-image',
    alt: 'Image',
    maxSizeMB: 5,
    accept: 'image/*',
    circular: false,
    previewSize: 96,
});

const model = defineModel<File | null>({ default: null });

const emit = defineEmits<{
    (e: 'error', message: string): void;
}>();

// Local state
const previewUrl = ref<string | null>(null);
const error = ref<string | null>(null);

// Watch for external model changes (e.g., form reset)
watch(model, (newFile) => {
    if (!newFile && previewUrl.value) {
        previewUrl.value = null;
    }
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
    model.value = file;

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
        previewUrl.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);

    // Reset input so the same file can be selected again if needed
    input.value = '';
}

function clearSelection() {
    model.value = null;
    previewUrl.value = null;
    error.value = null;
}

const displayUrl = () => previewUrl.value || props.imageUrl;
const hasImage = () => displayUrl() !== null;
const hasNewSelection = () => previewUrl.value !== null;
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
                    />
                    <Button
                        as="span"
                        :label="hasImage() ? 'Change' : 'Select'"
                        icon="pi pi-upload"
                        size="small"
                        severity="secondary"
                    />
                </label>
                <Button
                    v-if="hasNewSelection()"
                    label="Clear"
                    icon="pi pi-times"
                    size="small"
                    severity="secondary"
                    text
                    @click="clearSelection"
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
</template>
