<script setup lang="ts">
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { computed } from 'vue';

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
}>();

const dialogVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});

function handleSelectPage() {
    emit('select-page');
    dialogVisible.value = false;
}

function handleSelectAll() {
    emit('select-all');
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
