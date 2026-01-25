<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import OrganizationChart from 'primevue/organizationchart';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type OrgChartNode } from '@/types';

interface Props {
    orgChart: OrgChartNode[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Organisation Chart' },
];

const searchQuery = ref('');
const collapsedKeys = ref<Record<string, boolean>>({});

// Filter org chart nodes based on search
function filterNodes(nodes: OrgChartNode[], query: string): OrgChartNode[] {
    if (!query.trim()) return nodes;

    const lowerQuery = query.toLowerCase();

    return nodes
        .map((node) => {
            const matchesSearch =
                node.data.name.toLowerCase().includes(lowerQuery) ||
                (node.data.designation?.toLowerCase().includes(lowerQuery) ?? false) ||
                (node.data.company?.toLowerCase().includes(lowerQuery) ?? false);

            const filteredChildren = filterNodes(node.children, query);

            // Include node if it matches or has matching children
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

const filteredOrgChart = computed(() => filterNodes(props.orgChart, searchQuery.value));

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
    collapsedKeys.value = {};
}

function collapseAll() {
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
    collapsedKeys.value = keys;
}
</script>

<template>
    <Head title="Organisation Chart" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold">Organisation Chart</h1>
                <Button
                    label="Edit Chart"
                    icon="pi pi-pencil"
                    size="small"
                    @click="router.visit('/organisation-chart/edit')"
                />
            </div>

            <!-- Filter Section -->
            <div class="flex flex-col gap-3 rounded-lg border border-sidebar-border/70 bg-surface-50 p-3 dark:border-sidebar-border dark:bg-surface-900 sm:flex-row sm:items-center">
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

            <!-- Organisation Chart -->
            <div v-if="filteredOrgChart.length > 0" class="flex flex-col gap-6 overflow-x-auto">
                <Card v-for="rootNode in filteredOrgChart" :key="rootNode.key" class="min-w-max">
                    <template #content>
                        <OrganizationChart
                            :key="`${rootNode.key}-${searchQuery}`"
                            :value="rootNode"
                            v-model:collapsedKeys="collapsedKeys"
                            collapsible
                        >
                            <template #employee="{ node }">
                                <div
                                    class="flex cursor-pointer flex-col items-center gap-2 p-3 transition-colors hover:bg-surface-100 dark:hover:bg-surface-700"
                                    @click="navigateToEmployee(node.data.id)"
                                >
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
                                    <div class="text-center">
                                        <div class="font-semibold">{{ node.data.name }}</div>
                                        <div v-if="node.data.designation" class="text-sm text-muted-foreground">
                                            {{ node.data.designation }}
                                        </div>
                                        <div v-if="node.data.company" class="text-xs text-muted-foreground">
                                            {{ node.data.company }}
                                        </div>
                                    </div>
                                    <Tag
                                        :value="`Tier ${node.data.tier}`"
                                        :severity="getTierColor(node.data.tier)"
                                        class="!text-xs"
                                    />
                                </div>
                            </template>
                        </OrganizationChart>
                    </template>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-1 flex-col items-center justify-center gap-4 rounded-lg border border-dashed border-sidebar-border/70 p-8 dark:border-sidebar-border">
                <i class="pi pi-sitemap text-4xl text-muted-foreground"></i>
                <div class="text-center">
                    <h3 class="font-medium">No employees in hierarchy</h3>
                    <p class="text-sm text-muted-foreground">
                        {{ searchQuery ? 'No employees match your search.' : 'Create hierarchy relationships by editing employees.' }}
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
