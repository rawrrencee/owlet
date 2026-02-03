<script setup lang="ts">
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { computed, ref } from 'vue';

interface Props {
    visible: boolean;
    pageCount: number;
    totalCount: number;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const emit = defineEmits<{
    'update:visible': [value: boolean];
    'select-page': [];
    'select-all': [];
    cancel: [];
}>();

// Track if a selection action was taken
const selectionMade = ref(false);

const dialogVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});

function handleSelectPage() {
    selectionMade.value = true;
    emit('select-page');
    dialogVisible.value = false;
}

function handleSelectAll() {
    selectionMade.value = true;
    emit('select-all');
}

function handleDialogHide() {
    // If dialog is closed without making a selection, emit cancel
    if (!selectionMade.value) {
        emit('cancel');
    }
    // Reset for next open
    selectionMade.value = false;
}
</script>

<template>
    <Dialog
        v-model:visible="dialogVisible"
        modal
        :closable="!loading"
        :draggable="false"
        header="Select Products"
        class="w-full max-w-sm"
        :pt="{
            content: { class: 'p-0' },
        }"
        @hide="handleDialogHide"
    >
        <div class="flex flex-col gap-3 p-4">
            <p class="text-sm text-muted-foreground">
                Choose which products to select:
            </p>

            <Button
                :label="`Select all on this page (${pageCount} products)`"
                severity="secondary"
                size="small"
                class="w-full justify-start"
                :disabled="loading"
                @click="handleSelectPage"
            />

            <Button
                :label="`Select all ${totalCount} products matching filters`"
                size="small"
                class="w-full justify-start"
                :loading="loading"
                :disabled="loading"
                @click="handleSelectAll"
            />
        </div>
    </Dialog>
</template>
