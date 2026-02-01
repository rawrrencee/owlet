<script setup lang="ts">
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { computed } from 'vue';

interface Props {
    visible: boolean;
    pageCount: number;
    totalCount: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    'deselect-page': [];
    'deselect-all': [];
}>();

const dialogVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});

function handleDeselectPage() {
    emit('deselect-page');
    dialogVisible.value = false;
}

function handleDeselectAll() {
    emit('deselect-all');
    dialogVisible.value = false;
}
</script>

<template>
    <Dialog
        v-model:visible="dialogVisible"
        modal
        :draggable="false"
        header="Deselect Products"
        class="w-full max-w-sm"
        :pt="{
            content: { class: 'p-0' },
        }"
    >
        <div class="flex flex-col gap-3 p-4">
            <p class="text-sm text-muted-foreground">
                Choose which products to deselect:
            </p>

            <Button
                :label="`Deselect all on this page (${pageCount} products)`"
                severity="secondary"
                size="small"
                class="w-full justify-start"
                @click="handleDeselectPage"
            />

            <Button
                :label="`Deselect all (${totalCount} products)`"
                severity="danger"
                size="small"
                class="w-full justify-start"
                @click="handleDeselectAll"
            />
        </div>
    </Dialog>
</template>
