<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import SplitButton from 'primevue/splitbutton';
import { computed, ref } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import PagePermissionsDialog from './PagePermissionsDialog.vue';

interface Props {
    page: string;
    pageLabel: string;
    createRoute: string;
    createLabel?: string;
    canManage: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    createLabel: undefined,
});

const { isAdmin } = usePermissions();
const dialogVisible = ref(false);

const buttonLabel = computed(
    () => props.createLabel ?? `Create ${props.pageLabel}`,
);

const menuItems = computed(() => [
    {
        label: 'Manage Permissions',
        icon: 'pi pi-users',
        command: () => {
            dialogVisible.value = true;
        },
    },
    {
        label: 'View Users Page',
        icon: 'pi pi-external-link',
        command: () => {
            router.get('/users', { tab: 'employees', role: 'staff' });
        },
    },
]);

function handleCreate() {
    router.get(props.createRoute);
}
</script>

<template>
    <!-- Admin: SplitButton with Create as main action -->
    <SplitButton
        v-if="isAdmin"
        :label="buttonLabel"
        icon="pi pi-plus"
        size="small"
        :model="menuItems"
        :disabled="!canManage"
        @click="handleCreate"
        v-tooltip.top="
            !canManage ? 'You do not have permission to create' : undefined
        "
    />
    <!-- Non-admin: Regular Button -->
    <Button
        v-else
        :label="buttonLabel"
        icon="pi pi-plus"
        size="small"
        :disabled="!canManage"
        @click="handleCreate"
        v-tooltip.top="
            !canManage ? 'You do not have permission to create' : undefined
        "
    />
    <PagePermissionsDialog
        v-model:visible="dialogVisible"
        :page="page"
        :page-label="pageLabel"
    />
</template>
