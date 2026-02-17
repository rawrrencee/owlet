<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type OrgChartNode } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Tree from 'primevue/tree';
import { computed, ref } from 'vue';

interface Props {
    orgChart: OrgChartNode[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Organisation Chart' },
];

const searchQuery = ref('');
const expandedKeys = ref<Record<string, boolean>>({});

// Filter org chart nodes based on search (bubble-up: parent shows if child matches)
function filterNodes(nodes: OrgChartNode[], query: string): OrgChartNode[] {
    if (!query.trim()) return nodes;

    const lowerQuery = query.toLowerCase();

    return nodes
        .map((node) => {
            const matchesSearch =
                node.data.name.toLowerCase().includes(lowerQuery) ||
                (node.data.designation?.toLowerCase().includes(lowerQuery) ??
                    false) ||
                (node.data.company?.toLowerCase().includes(lowerQuery) ??
                    false);

            const filteredChildren = filterNodes(node.children, query);

            if (matchesSearch || filteredChildren.length > 0) {
                return {
                    ...node,
                    children: filteredChildren,
                };
            }
            return null;
        })
        .filter((node): node is OrgChartNode => node !== null);
}

const filteredOrgChart = computed(() =>
    filterNodes(props.orgChart, searchQuery.value),
);

function navigateToEmployee(employeeId: number) {
    router.visit(`/users/${employeeId}/edit`);
}

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function getTierColor(tier: number): string {
    const colors: Record<number, string> = {
        1: 'secondary',
        2: 'info',
        3: 'warn',
        4: 'success',
        5: 'danger',
    };
    return colors[tier] || 'secondary';
}

function expandAll() {
    const keys: Record<string, boolean> = {};
    function collectKeys(nodes: OrgChartNode[]) {
        for (const node of nodes) {
            if (node.children.length > 0) {
                keys[node.key] = true;
                collectKeys(node.children);
            }
        }
    }
    collectKeys(props.orgChart);
    expandedKeys.value = keys;
}

function collapseAll() {
    expandedKeys.value = {};
}
</script>

<template>
    <Head title="Organisation Chart" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <h1 class="heading-lg">Organisation Chart</h1>
                <Button
                    label="Edit Chart"
                    icon="pi pi-pencil"
                    size="small"
                    @click="router.visit('/organisation-chart/edit')"
                />
            </div>

            <!-- Filter Section -->
            <div
                class="filter-section flex flex-col gap-3 sm:flex-row sm:items-center"
            >
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="searchQuery"
                        placeholder="Search by name, designation, or company..."
                        size="small"
                        fluid
                    />
                </IconField>
                <div class="flex items-center gap-2">
                    <Button
                        label="Expand All"
                        icon="pi pi-plus"
                        severity="secondary"
                        size="small"
                        text
                        @click="expandAll"
                    />
                    <Button
                        label="Collapse All"
                        icon="pi pi-minus"
                        severity="secondary"
                        size="small"
                        text
                        @click="collapseAll"
                    />
                </div>
            </div>

            <!-- Organisation Tree -->
            <Tree
                v-if="filteredOrgChart.length > 0"
                :value="filteredOrgChart"
                v-model:expandedKeys="expandedKeys"
                class="w-full"
            >
                <template #employee="{ node }">
                    <div
                        class="flex cursor-pointer flex-wrap items-center gap-2 py-0.5"
                        @click="navigateToEmployee(node.data.id)"
                    >
                        <Avatar
                            v-if="node.data.profile_picture_url"
                            :image="node.data.profile_picture_url"
                            shape="circle"
                            size="normal"
                        />
                        <Avatar
                            v-else
                            :label="getInitials(node.data.name)"
                            shape="circle"
                            size="normal"
                            class="bg-primary/10 text-primary"
                        />
                        <span class="font-semibold">{{ node.data.name }}</span>
                        <span
                            v-if="node.data.designation"
                            class="text-muted-color text-sm"
                        >
                            · {{ node.data.designation }}
                        </span>
                        <span
                            v-if="node.data.company"
                            class="text-muted-color text-sm"
                        >
                            · {{ node.data.company }}
                        </span>
                        <Tag
                            :value="`Tier ${node.data.tier}`"
                            :severity="getTierColor(node.data.tier)"
                            class="!text-xs"
                        />
                    </div>
                </template>
            </Tree>

            <!-- Empty State -->
            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center gap-4 rounded-lg border border-dashed border-border p-8"
            >
                <i class="pi pi-sitemap text-4xl text-muted-foreground"></i>
                <div class="text-center">
                    <h3 class="font-medium">No employees in hierarchy</h3>
                    <p class="text-sm text-muted-foreground">
                        {{
                            searchQuery
                                ? 'No employees match your search.'
                                : 'Create hierarchy relationships by editing employees.'
                        }}
                    </p>
                </div>
                <Button
                    v-if="searchQuery"
                    label="Clear Search"
                    severity="secondary"
                    size="small"
                    @click="searchQuery = ''"
                />
            </div>
        </div>
    </AppLayout>
</template>
