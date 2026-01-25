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
const collapsedKeys = ref<Record<string, boolean>>({});

// OrgChart node structure for PrimeVue
interface OrgChartNode {
    key: string;
    type: string;
    data: SubordinateInfo;
    children: OrgChartNode[];
}

// Convert subordinates to OrgChart nodes
function convertToOrgChartNodes(subordinates: SubordinateInfo[]): OrgChartNode[] {
    return subordinates.map((sub) => ({
        key: String(sub.id),
        type: 'subordinate',
        data: sub,
        children: sub.subordinates ? convertToOrgChartNodes(sub.subordinates) : [],
    }));
}

// Filter org chart nodes based on search
function filterNodes(nodes: OrgChartNode[], query: string): OrgChartNode[] {
    if (!query.trim()) return nodes;

    const lowerQuery = query.toLowerCase();

    return nodes
        .map((node) => {
            const matchesSearch = node.data.name.toLowerCase().includes(lowerQuery);

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

// Convert and filter in a single computed to ensure reactivity
const filteredOrgChartNodes = computed(() => {
    const nodes = convertToOrgChartNodes(props.subordinates);
    return filterNodes(nodes, searchQuery.value);
});

// Keep original nodes for collapse functionality
const orgChartNodes = computed(() => convertToOrgChartNodes(props.subordinates));

// Check if any subordinate has nested subordinates (to show/hide expand buttons)
function hasNestedSubordinates(subs: SubordinateInfo[]): boolean {
    return subs.some((s) => s.subordinates && s.subordinates.length > 0);
}

const showExpandCollapseButtons = computed(() => hasNestedSubordinates(props.subordinates));

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
    collectKeys(orgChartNodes.value);
    collapsedKeys.value = keys;
}

function navigateToSubordinate(employeeId: number) {
    router.visit(`/my-team/${employeeId}`);
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
                        placeholder="Search by name..."
                        size="small"
                        fluid
                    />
                </IconField>
                <div v-if="showExpandCollapseButtons" class="flex items-center gap-2">
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
            <div v-if="filteredOrgChartNodes.length > 0" class="flex flex-col gap-6 overflow-x-auto">
                <Card v-for="rootNode in filteredOrgChartNodes" :key="rootNode.key" class="min-w-max">
                    <template #content>
                        <OrganizationChart
                            :key="`${rootNode.key}-${searchQuery}`"
                            :value="rootNode"
                            v-model:collapsedKeys="collapsedKeys"
                            collapsible
                        >
                            <template #subordinate="{ node }">
                                <div
                                    class="flex cursor-pointer flex-col items-center gap-2 p-3 transition-colors hover:bg-surface-100 dark:hover:bg-surface-700"
                                    @click="navigateToSubordinate(node.data.id)"
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
                                        <div v-if="node.data.employee_number" class="text-sm text-muted-foreground">
                                            {{ node.data.employee_number }}
                                        </div>
                                        <div v-if="node.data.email" class="text-xs text-muted-foreground">
                                            {{ node.data.email }}
                                        </div>
                                    </div>

                                    <!-- Companies/Stores Tags -->
                                    <div class="flex flex-wrap justify-center gap-1">
                                        <template v-if="canViewSection('companies') && node.data.companies?.length">
                                            <Tag
                                                v-for="company in node.data.companies.slice(0, 2)"
                                                :key="company.id"
                                                :value="company.name"
                                                severity="info"
                                                class="!text-xs"
                                            />
                                            <Tag
                                                v-if="node.data.companies.length > 2"
                                                :value="`+${node.data.companies.length - 2}`"
                                                severity="info"
                                                class="!text-xs"
                                            />
                                        </template>
                                        <template v-if="canViewSection('stores') && node.data.stores?.length">
                                            <Tag
                                                v-for="store in node.data.stores.slice(0, 2)"
                                                :key="store.id"
                                                :value="store.name"
                                                severity="secondary"
                                                class="!text-xs"
                                            />
                                            <Tag
                                                v-if="node.data.stores.length > 2"
                                                :value="`+${node.data.stores.length - 2}`"
                                                severity="secondary"
                                                class="!text-xs"
                                            />
                                        </template>
                                    </div>

                                    <!-- Subordinate Count -->
                                    <Tag
                                        v-if="node.data.subordinates?.length"
                                        :value="`${node.data.subordinates.length} report${node.data.subordinates.length === 1 ? '' : 's'}`"
                                        severity="success"
                                        class="!text-xs"
                                    />
                                </div>
                            </template>
                            <!-- Default template fallback for any nodes without type -->
                            <template #default="{ node }">
                                <div
                                    class="flex cursor-pointer flex-col items-center gap-2 p-3 transition-colors hover:bg-surface-100 dark:hover:bg-surface-700"
                                    @click="navigateToSubordinate(node.data?.id)"
                                >
                                    <Avatar
                                        v-if="node.data?.profile_picture_url"
                                        :image="node.data.profile_picture_url"
                                        shape="circle"
                                        size="large"
                                    />
                                    <Avatar
                                        v-else
                                        :label="node.data?.name ? getInitials(node.data.name) : '?'"
                                        shape="circle"
                                        size="large"
                                        class="bg-primary/10 text-primary"
                                    />
                                    <div class="text-center">
                                        <div class="font-semibold">{{ node.data?.name ?? node.label ?? 'Unknown' }}</div>
                                    </div>
                                </div>
                            </template>
                        </OrganizationChart>
                    </template>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-1 flex-col items-center justify-center gap-4 rounded-lg border border-dashed border-sidebar-border/70 p-8 dark:border-sidebar-border">
                <i class="pi pi-users text-4xl text-muted-foreground"></i>
                <div class="text-center">
                    <h3 class="font-medium">No team members found</h3>
                    <p class="text-sm text-muted-foreground">
                        {{ searchQuery ? 'No team members match your search.' : 'You don\'t have any subordinates assigned.' }}
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
