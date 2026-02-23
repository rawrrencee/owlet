<script setup lang="ts">
import { dashboard, login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import {
    ShoppingBag,
    Users,
    Package,
    BarChart3,
    Percent,
    Store,
    ArrowRight,
} from 'lucide-vue-next';

const features = [
    {
        icon: ShoppingBag,
        title: 'POS & Transactions',
        description:
            'Fast, intuitive point of sale with multi-currency support, offer stacking, and real-time inventory updates.',
    },
    {
        icon: Users,
        title: 'Employee Management',
        description:
            'Timecards, leave requests, contracts, and organisational hierarchy all in one place.',
    },
    {
        icon: Package,
        title: 'Inventory & Stock',
        description:
            'Product catalog, delivery orders, purchase orders, stocktakes, and barcode scanning.',
    },
    {
        icon: BarChart3,
        title: 'Analytics',
        description:
            'Sales dashboards, employee performance, product trends, and exportable reports.',
    },
    {
        icon: Percent,
        title: 'Offers & Promotions',
        description:
            'Flexible discount engine with product, bundle, category, brand, and minimum-spend offers.',
    },
    {
        icon: Store,
        title: 'Multi-Store Support',
        description:
            'Manage multiple outlets with per-store permissions, currencies, and inventory tracking.',
    },
];
</script>

<template>
    <Head title="Welcome" />

    <div
        class="min-h-screen bg-stone-50 text-stone-900 dark:bg-stone-950 dark:text-stone-100"
    >
        <!-- Header -->
        <header
            class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-md bg-stone-800 text-sm font-bold text-white dark:bg-stone-200 dark:text-stone-900"
                >
                    O
                </div>
                <span class="text-lg font-semibold tracking-tight">Owlet</span>
            </div>
            <nav class="flex items-center gap-3">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard().url"
                >
                    <Button label="Go to Dashboard" size="small" />
                </Link>
                <template v-else>
                    <Link :href="login().url">
                        <Button
                            label="Login"
                            size="small"
                        />
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero -->
        <section class="mx-auto max-w-6xl px-6 py-16 sm:py-24">
            <div class="max-w-2xl">
                <h1
                    class="text-4xl font-bold tracking-tight sm:text-5xl"
                >
                    Modern Point of Sales &amp; Employee Management
                </h1>
                <p
                    class="mt-4 text-lg text-stone-600 dark:text-stone-400"
                >
                    A streamlined platform for retail operations, inventory
                    control, and workforce management — built for speed and
                    simplicity.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <template v-if="$page.props.auth.user">
                        <Link :href="dashboard().url">
                            <Button label="Go to Dashboard" size="small">
                                <template #icon>
                                    <ArrowRight class="mr-2 h-4 w-4" />
                                </template>
                            </Button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login().url">
                            <Button label="Login as Staff" size="small" />
                        </Link>
                        <Link :href="login().url">
                            <Button
                                label="Login as Customer"
                                size="small"
                                outlined
                            />
                        </Link>
                    </template>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section
            class="border-t border-stone-200 bg-white py-16 sm:py-24 dark:border-stone-800 dark:bg-stone-900"
        >
            <div class="mx-auto max-w-6xl px-6">
                <h2
                    class="text-center text-2xl font-semibold tracking-tight sm:text-3xl"
                >
                    Everything you need to run your stores
                </h2>
                <p
                    class="mx-auto mt-3 max-w-xl text-center text-stone-600 dark:text-stone-400"
                >
                    Owlet brings together sales, inventory, and people
                    management into a single, cohesive system.
                </p>
                <div
                    class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="rounded-lg border border-stone-200 bg-stone-50 p-6 dark:border-stone-700 dark:bg-stone-800"
                    >
                        <div
                            class="mb-3 flex h-10 w-10 items-center justify-center rounded-md bg-stone-200 dark:bg-stone-700"
                        >
                            <component
                                :is="feature.icon"
                                class="h-5 w-5 text-stone-700 dark:text-stone-300"
                            />
                        </div>
                        <h3 class="font-semibold">{{ feature.title }}</h3>
                        <p
                            class="mt-1 text-sm text-stone-600 dark:text-stone-400"
                        >
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer
            class="border-t border-stone-200 py-8 dark:border-stone-800"
        >
            <div
                class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 sm:flex-row"
            >
                <p class="text-sm text-stone-500 dark:text-stone-500">
                    &copy; {{ new Date().getFullYear() }} Owlet. All rights
                    reserved.
                </p>
                <Link
                    href="/apply"
                    class="text-sm text-stone-500 hover:text-stone-700 dark:text-stone-500 dark:hover:text-stone-300"
                >
                    Click here to register staff access
                </Link>
            </div>
        </footer>
    </div>
</template>
