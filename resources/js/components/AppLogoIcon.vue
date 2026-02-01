<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Minimalist owl icon -->
        <path
            fill="currentColor"
            d="M12 3C7.5 3 4 6 4 10c0 1.5.5 3 1.5 4.2-.3.8-.8 2-1.5 3.3 0 0 2.5-.5 4.5-1.5.5.3 1 .5 1.5.7V19c0 1.1.9 2 2 2s2-.9 2-2v-2.3c.5-.2 1-.4 1.5-.7 2 1 4.5 1.5 4.5 1.5-.7-1.3-1.2-2.5-1.5-3.3 1-1.2 1.5-2.7 1.5-4.2 0-4-3.5-7-8-7zm-3 8c-.8 0-1.5-.7-1.5-1.5S8.2 8 9 8s1.5.7 1.5 1.5S9.8 11 9 11zm6 0c-.8 0-1.5-.7-1.5-1.5S14.2 8 15 8s1.5.7 1.5 1.5-.7 1.5-1.5 1.5zm-3 3c-1.1 0-2-.4-2-.9s.9-.9 2-.9 2 .4 2 .9-.9.9-2 .9z"
        />
    </svg>
</template>
