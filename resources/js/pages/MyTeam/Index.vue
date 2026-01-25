<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Tree from 'primevue/tree';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type AvailableSections, type BreadcrumbItem, type SubordinateInfo } from '@/types';

interface Props {
    subordinates: SubordinateInfo[];
    visibleSections: string[];
    availableSections: AvailableSections;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Team' },
];

const searchQuery = ref('');
const expandedKeys = ref<Record<string, boolean>>({});

// Convert subordinates to tree nodes for PrimeVue Tree component
interface TreeNode {
    key: string;
    label: string;
    data: SubordinateInfo;
    children?: TreeNode[];
}

function convertToTreeNodes(subordinates: SubordinateInfo[]): TreeNode[] {
    return subordinates.map((sub) => ({
        key: String(sub.id),
        label: sub.name,
        data: sub,
        children: sub.subordinates ? convertToTreeNodes(sub.subordinates) : undefined,
    }));
}

// Filter tree nodes based on search
function filterTreeNodes(nodes: TreeNode[], query: string): TreeNode[] {
    if (!query.trim()) return nodes;

    const lowerQuery = query.toLowerCase();

    return nodes
        .map((node) => {
            const matchesSearch =
                node.data.name.toLowerCase().includes(lowerQuery) ||
                (node.data.employee_number?.toLowerCase().includes(lowerQuery) ?? false) ||
                (node.data.email?.toLowerCase().includes(lowerQuery) ?? false);

            const filteredChildren = node.children ? filterTreeNodes(node.children, query) : [];

            if (matchesSearch || filteredChildren.length > 0) {
                return {
                    ...node,
                    children: filteredChildren.length > 0 ? filteredChildren : node.children,
                };
            }
            return null;
        })
        .filter((node): node is TreeNode => node !== null);
}

const treeNodes = computed(() => convertToTreeNodes(props.subordinates));
const filteredTreeNodes = computed(() => filterTreeNodes(treeNodes.value, searchQuery.value));

function getInitials(name: string): string {
    const words = name.split(' ');
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function canViewSection(section: string): boolean {
    return props.visibleSections.includes(section);
}

function expandAll() {
    const keys: Record<string, boolean> = {};
    function collectKeys(nodes: TreeNode[]) {
        for (const node of nodes) {
            keys[node.key] = true;
            if (node.children) {
                collectKeys(node.children);
            }
        }
    }
    collectKeys(treeNodes.value);
    expandedKeys.value = keys;
}

function collapseAll() {
    expandedKeys.value = {};
}
</script>

<template>
    <Head title="My Team" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold">My Team</h1>
            </div>

            <!-- Filter Section -->
            <div class="flex flex-col gap-3 rounded-lg border border-sidebar-border/70 bg-surface-50 p-3 dark:border-sidebar-border dark:bg-surface-900 sm:flex-row sm:items-center">
                <IconField class="flex-1">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="searchQuery"
                        placeholder="Search by name, employee number, or email..."
                        size="small"
                        fluid
                    />
                </IconField>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded px-3 py-1.5 text-sm text-muted-foreground hover:bg-surface-100 dark:hover:bg-surface-700"
                        @click="expandAll"
                    >
                        Expand All
                    </button>
                    <button
                        type="button"
                        class="rounded px-3 py-1.5 text-sm text-muted-foreground hover:bg-surface-100 dark:hover:bg-surface-700"
                        @click="collapseAll"
                    >
                        Collapse All
                    </button>
                </div>
            </div>

            <!-- Team Tree -->
            <Card v-if="filteredTreeNodes.length > 0">
                <template #content>
                    <Tree
                        :value="filteredTreeNodes"
                        v-model:expandedKeys="expandedKeys"
                        selectionMode="single"
                        class="w-full border-none"
                    >
                        <template #default="{ node }">
                            <div class="flex flex-1 flex-col gap-3 py-2 sm:flex-row sm:items-start sm:gap-4">
                                <!-- Avatar and Basic Info -->
                                <div class="flex items-center gap-3">
                                    <Avatar
                                        v-if="node.data.profile_picture_url"
                                        :image="node.data.profile_picture_url"
                                        shape="circle"
                                        size="large"
                                    />
                                    <Avatar
                                        v-else
                                        :label="getInitials(node.data.name)"
                                        shape="circle"
                                        size="large"
                                        class="bg-primary/10 text-primary"
                                    />
                                    <div class="flex flex-col">
                                        <span class="font-semibold">{{ node.data.name }}</span>
                                        <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                            <span v-if="node.data.employee_number">{{ node.data.employee_number }}</span>
                                            <span v-if="node.data.email">{{ node.data.email }}</span>
                                        </div>
                                        <span v-if="node.data.phone" class="text-sm text-muted-foreground">
                                            {{ node.data.phone }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Conditional Info Based on Visibility -->
                                <div class="ml-14 flex flex-col gap-2 sm:ml-0 sm:flex-1 sm:flex-row sm:items-center sm:justify-end sm:gap-4">
                                    <!-- Companies -->
                                    <div v-if="canViewSection('companies') && node.data.companies?.length" class="flex flex-wrap gap-1">
                                        <Tag
                                            v-for="company in node.data.companies"
                                            :key="company.id"
                                            :value="company.name"
                                            severity="info"
                                            class="!text-xs"
                                        />
                                    </div>

                                    <!-- Stores -->
                                    <div v-if="canViewSection('stores') && node.data.stores?.length" class="flex flex-wrap gap-1">
                                        <Tag
                                            v-for="store in node.data.stores"
                                            :key="store.id"
                                            :value="store.name"
                                            severity="secondary"
                                            class="!text-xs"
                                        />
                                    </div>

                                    <!-- Subordinate Count -->
                                    <Tag
                                        v-if="node.data.subordinates?.length"
                                        :value="`${node.data.subordinates.length} report${node.data.subordinates.length === 1 ? '' : 's'}`"
                                        severity="success"
                                        class="!text-xs"
                                    />
                                </div>
                            </div>
                        </template>
                    </Tree>
                </template>
            </Card>

            <!-- Empty State -->
            <div v-else class="flex flex-1 flex-col items-center justify-center gap-4 rounded-lg border border-dashed border-sidebar-border/70 p-8 dark:border-sidebar-border">
                <i class="pi pi-users text-4xl text-muted-foreground"></i>
                <div class="text-center">
                    <h3 class="font-medium">No team members found</h3>
                    <p class="text-sm text-muted-foreground">
                        {{ searchQuery ? 'No team members match your search.' : 'You don\'t have any subordinates assigned.' }}
                    </p>
                </div>
            </div>

            <!-- Legend -->
            <div v-if="visibleSections.length > 0" class="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                <span class="font-medium">Visible information:</span>
                <span v-for="section in visibleSections" :key="section">
                    {{ availableSections[section] }}
                </span>
            </div>
        </div>
    </AppLayout>
</template>
