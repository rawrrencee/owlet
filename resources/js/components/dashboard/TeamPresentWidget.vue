<script setup lang="ts">
import type { TeamMember } from '@/types';
import Avatar from 'primevue/avatar';
import Card from 'primevue/card';
import { computed } from 'vue';

const props = defineProps<{
    data: TeamMember[];
}>();

const timedInCount = computed(
    () => props.data.filter((m) => m.is_timed_in).length,
);
</script>

<template>
    <Card class="h-full">
        <template #title>
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold">Team Present</span>
                <span class="text-sm text-muted-foreground">
                    {{ timedInCount }}/{{ data.length }}
                </span>
            </div>
        </template>
        <template #content>
            <div
                v-if="data.length === 0"
                class="py-4 text-center text-sm text-muted-foreground"
            >
                No team members.
            </div>
            <div v-else class="max-h-[200px] space-y-2 overflow-y-auto">
                <div
                    v-for="member in data"
                    :key="member.id"
                    class="flex items-center gap-2"
                >
                    <div class="relative size-8 flex-shrink-0">
                        <Avatar
                            v-if="member.profile_picture_url"
                            :image="member.profile_picture_url"
                            size="small"
                            shape="circle"
                            :pt="{ image: { class: 'object-cover' } }"
                        />
                        <Avatar
                            v-else
                            :label="member.name[0]?.toUpperCase()"
                            size="small"
                            shape="circle"
                        />
                        <span
                            class="absolute right-0 bottom-0 h-2.5 w-2.5 rounded-full border-2 border-white dark:border-stone-800"
                            :class="
                                member.is_timed_in
                                    ? 'bg-green-500'
                                    : 'bg-stone-300 dark:bg-stone-600'
                            "
                        ></span>
                    </div>
                    <span class="truncate text-sm">{{ member.name }}</span>
                </div>
            </div>
        </template>
    </Card>
</template>
