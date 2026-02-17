<script setup lang="ts">
import FloatingClockPanel from '@/components/timecards/FloatingClockPanel.vue';
import IncompleteTimecardModal from '@/components/timecards/IncompleteTimecardModal.vue';
import { usePermissionGuard } from '@/composables/usePermissionGuard';
import { useFlashToast } from '@/composables/useToast';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';
import Toast from 'primevue/toast';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
    hideFloatingPanel?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    hideFloatingPanel: false,
});

// Watch for flash messages and show toasts
useFlashToast();

// Guard routes based on permissions
usePermissionGuard();
</script>

<template>
    <Toast position="top-right" class="!max-w-[calc(100vw-2rem)]" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
    <!-- Floating clock panel for time tracking -->
    <FloatingClockPanel v-if="!props.hideFloatingPanel" />
    <!-- Modal for resolving incomplete timecards -->
    <IncompleteTimecardModal />
</template>
