<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
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

// Flatten subordinates tree into a single list for grid display
function flattenSubordinates(subs: SubordinateInfo[], depth = 0): Array<SubordinateInfo & { depth: number }> {
    const result: Array<SubordinateInfo & { depth: number }> = [];
    for (const sub of subs) {
        result.push({ ...sub, depth });
        if (sub.subordinates && sub.subordinates.length > 0) {
            result.push(...flattenSubordinates(sub.subordinates, depth + 1));
        }
    }
    return result;
}

// Flatten and filter subordinates
const flattenedSubordinates = computed(() => {
    const flattened = flattenSubordinates(props.subordinates);

    if (!searchQuery.value.trim()) {
        return flattened;
    }

    const query = searchQuery.value.toLowerCase();
    return flattened.filter((sub) => {
        const nameMatch = sub.name.toLowerCase().includes(query);
        const employeeNumberMatch = sub.employee_number?.toLowerCase().includes(query);
        const emailMatch = sub.email?.toLowerCase().includes(query);
        return nameMatch || employeeNumberMatch || emailMatch;
    });
});

// Count total team members
const totalTeamMembers = computed(() => flattenSubordinates(props.subordinates).length);

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

function navigateToSubordinate(employeeId: number) {
    router.visit(`/my-team/${employeeId}`);
}

function getSubordinateCountLabel(count: number): string {
    return count === 1 ? '1 direct report' : `${count} direct reports`;
}
</script>

<template>
    <Head title="My Team" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="heading-lg">My Team</h1>
                    <Tag v-if="totalTeamMembers > 0" :value="`${totalTeamMembers} member${totalTeamMembers === 1 ? '' : 's'}`" severity="secondary" />
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <IconField class="w-full sm:max-w-md">
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="searchQuery"
                        placeholder="Search by name, ID, or email..."
                        size="small"
                        fluid
                    />
                </IconField>
            </div>

            <!-- Team Members Grid -->
            <div v-if="flattenedSubordinates.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div
                    v-for="member in flattenedSubordinates"
                    :key="member.id"
                    class="group relative cursor-pointer rounded-lg border border-border bg-card p-4 transition-all hover:border-primary/50 hover:shadow-md"
                    @click="navigateToSubordinate(member.id)"
                >
                    <!-- Card Header with Avatar and Name -->
                    <div class="flex items-start gap-3">
                        <Avatar
                            v-if="member.profile_picture_url"
                            :image="member.profile_picture_url"
                            shape="circle"
                            size="large"
                            class="shrink-0"
                        />
                        <Avatar
                            v-else
                            :label="getInitials(member.name)"
                            shape="circle"
                            size="large"
                            class="shrink-0 bg-primary/10 text-primary"
                        />
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate font-semibold text-foreground group-hover:text-primary">
                                {{ member.name }}
                            </h3>
                            <p v-if="member.employee_number" class="truncate text-sm text-muted-foreground">
                                #{{ member.employee_number }}
                            </p>
                            <p v-if="member.email" class="truncate text-xs text-muted-foreground">
                                {{ member.email }}
                            </p>
                        </div>
                    </div>

                    <!-- Companies Tags -->
                    <div v-if="canViewSection('companies') && member.companies?.length" class="mt-3 flex flex-wrap gap-1">
                        <Tag
                            v-for="company in member.companies.slice(0, 2)"
                            :key="company.id"
                            :value="company.name"
                            severity="info"
                            class="!text-xs"
                        />
                        <Tag
                            v-if="member.companies.length > 2"
                            :value="`+${member.companies.length - 2}`"
                            severity="info"
                            class="!text-xs"
                        />
                    </div>

                    <!-- Stores Tags -->
                    <div v-if="canViewSection('stores') && member.stores?.length" class="mt-2 flex flex-wrap gap-1">
                        <Tag
                            v-for="store in member.stores.slice(0, 2)"
                            :key="store.id"
                            :value="store.name"
                            severity="secondary"
                            class="!text-xs"
                        />
                        <Tag
                            v-if="member.stores.length > 2"
                            :value="`+${member.stores.length - 2}`"
                            severity="secondary"
                            class="!text-xs"
                        />
                    </div>

                    <!-- Footer with Subordinate Count -->
                    <div v-if="member.subordinates?.length" class="mt-3 flex items-center gap-1.5 border-t border-border pt-3 text-sm text-muted-foreground">
                        <i class="pi pi-users text-xs"></i>
                        <span>{{ getSubordinateCountLabel(member.subordinates.length) }}</span>
                    </div>

                    <!-- Depth Indicator (for nested subordinates) -->
                    <div
                        v-if="member.depth > 0"
                        class="absolute -left-px top-3 h-6 w-1 rounded-r bg-primary/30"
                        :style="{ opacity: Math.max(0.3, 1 - member.depth * 0.2) }"
                    />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-1 flex-col items-center justify-center gap-4 rounded-lg border border-dashed border-border p-8">
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
            <div v-if="visibleSections.length > 0 && flattenedSubordinates.length > 0" class="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                <span class="font-medium">Visible information:</span>
                <span v-for="section in visibleSections" :key="section">
                    {{ availableSections[section] }}
                </span>
            </div>
        </div>
    </AppLayout>
</template>
