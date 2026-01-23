<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
}

interface Props {
    meta: PaginationMeta;
}

defineProps<Props>();
</script>

<template>
    <nav
        v-if="meta.last_page > 1"
        class="flex items-center justify-between px-2"
    >
        <div class="text-sm text-muted-foreground">
            Showing {{ meta.from }} to {{ meta.to }} of {{ meta.total }} results
        </div>
        <div class="flex items-center gap-1">
            <Button
                v-if="meta.links[0]?.url"
                variant="outline"
                size="icon"
                as-child
            >
                <Link :href="meta.links[0].url" preserve-scroll>
                    <ChevronLeft class="h-4 w-4" />
                </Link>
            </Button>
            <Button v-else variant="outline" size="icon" disabled>
                <ChevronLeft class="h-4 w-4" />
            </Button>

            <template v-for="link in meta.links.slice(1, -1)" :key="link.label">
                <Button
                    v-if="link.url"
                    :variant="link.active ? 'default' : 'outline'"
                    size="icon"
                    as-child
                >
                    <Link :href="link.url" preserve-scroll v-html="link.label" />
                </Button>
                <Button v-else variant="outline" size="icon" disabled>
                    <span v-html="link.label" />
                </Button>
            </template>

            <Button
                v-if="meta.links[meta.links.length - 1]?.url"
                variant="outline"
                size="icon"
                as-child
            >
                <Link :href="meta.links[meta.links.length - 1].url!" preserve-scroll>
                    <ChevronRight class="h-4 w-4" />
                </Link>
            </Button>
            <Button v-else variant="outline" size="icon" disabled>
                <ChevronRight class="h-4 w-4" />
            </Button>
        </div>
    </nav>
</template>
