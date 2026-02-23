<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard, login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
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

const stats = [
    { value: '10+', label: 'Modules' },
    { value: 'Multi-store', label: 'Support' },
    { value: 'Real-time', label: 'Inventory' },
    { value: 'Granular', label: 'Permissions' },
];

const chartBars = [40, 60, 35, 80, 55, 90, 70];
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-stone-950 text-stone-100">
        <!-- Ambient background orbs -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="orb orb-1 absolute h-[600px] w-[600px] rounded-full bg-amber-600/15 blur-3xl" />
            <div class="orb orb-2 absolute h-[400px] w-[400px] rounded-full bg-orange-700/10 blur-3xl" />
        </div>

        <!-- Header — logo only, no nav buttons (hero handles CTAs) -->
        <header class="relative z-10 mx-auto flex max-w-6xl items-center px-6 py-6">
            <div class="flex items-center gap-2">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-md bg-stone-800 text-white"
                >
                    <AppLogoIcon class="size-5 fill-current" />
                </div>
                <span class="text-lg font-semibold tracking-tight">Owlet</span>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative z-10 mx-auto max-w-6xl px-6 pb-16 pt-8 sm:pt-16">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <!-- Left: Headline + CTA -->
                <div class="hero-text">
                    <div
                        class="mb-5 inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-400"
                    >
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-400"></span>
                        Point of Sale &amp; Management Platform
                    </div>

                    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                        Run your stores
                        <span
                            class="bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent"
                        >
                            smarter.
                        </span>
                    </h1>

                    <p class="mt-5 text-lg leading-relaxed text-stone-400">
                        A streamlined platform for retail operations, inventory
                        control, and workforce management — built for speed and
                        simplicity.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <template v-if="$page.props.auth.user">
                            <Link :href="dashboard().url">
                                <button
                                    class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-950 transition-all hover:bg-amber-400 active:scale-95"
                                >
                                    Go to Dashboard
                                    <ArrowRight class="h-4 w-4" />
                                </button>
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="login().url">
                                <button
                                    class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-950 transition-all hover:bg-amber-400 active:scale-95"
                                >
                                    Login as Staff
                                    <ArrowRight class="h-4 w-4" />
                                </button>
                            </Link>
                            <Link :href="login().url">
                                <button
                                    class="inline-flex items-center gap-2 rounded-lg border border-stone-700 bg-stone-800/50 px-5 py-2.5 text-sm font-semibold text-stone-200 transition-all hover:border-stone-500 hover:bg-stone-800 active:scale-95"
                                >
                                    Login as Customer
                                </button>
                            </Link>
                        </template>
                    </div>
                </div>

                <!-- Right: CSS dashboard preview art -->
                <div class="hidden lg:block">
                    <div class="dashboard-preview relative">
                        <div
                            class="rounded-2xl border border-white/10 bg-stone-900/90 p-6 shadow-2xl backdrop-blur-sm"
                        >
                            <!-- Window chrome -->
                            <div class="mb-5 flex items-center gap-1.5">
                                <div class="h-2.5 w-2.5 rounded-full bg-red-500/70"></div>
                                <div class="h-2.5 w-2.5 rounded-full bg-amber-500/70"></div>
                                <div class="h-2.5 w-2.5 rounded-full bg-green-500/70"></div>
                                <div class="ml-3 h-4 w-28 rounded-md bg-stone-800"></div>
                            </div>

                            <!-- Stat cards -->
                            <div class="mb-4 grid grid-cols-3 gap-2.5">
                                <div
                                    v-for="i in 3"
                                    :key="i"
                                    class="rounded-lg bg-stone-800/80 p-3"
                                >
                                    <div class="mb-2 h-2 w-10 rounded bg-stone-700"></div>
                                    <div
                                        class="h-5 rounded"
                                        :class="
                                            i === 1
                                                ? 'w-14 bg-amber-500/50'
                                                : i === 2
                                                  ? 'w-12 bg-green-500/30'
                                                  : 'w-16 bg-stone-700'
                                        "
                                    ></div>
                                </div>
                            </div>

                            <!-- Bar chart -->
                            <div class="mb-4 rounded-xl bg-stone-800/80 p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <div class="h-2 w-16 rounded bg-stone-600"></div>
                                    <div class="h-2 w-8 rounded bg-stone-700"></div>
                                </div>
                                <div class="flex items-end gap-1.5" style="height: 56px">
                                    <div
                                        v-for="(h, i) in chartBars"
                                        :key="i"
                                        :style="{ height: h + '%' }"
                                        class="flex-1 rounded-sm transition-all"
                                        :class="
                                            i === 5
                                                ? 'bg-amber-500/80'
                                                : 'bg-amber-500/25'
                                        "
                                    ></div>
                                </div>
                            </div>

                            <!-- List items -->
                            <div class="space-y-2">
                                <div
                                    v-for="j in 3"
                                    :key="j"
                                    class="flex items-center gap-3 rounded-lg bg-stone-800/60 px-3 py-2.5"
                                >
                                    <div class="h-6 w-6 rounded-full bg-amber-500/25"></div>
                                    <div class="flex-1 space-y-1.5">
                                        <div class="h-2 w-20 rounded bg-stone-600"></div>
                                        <div class="h-1.5 w-14 rounded bg-stone-700"></div>
                                    </div>
                                    <div
                                        class="h-4 w-10 rounded-full"
                                        :class="
                                            j === 1
                                                ? 'bg-green-500/30'
                                                : j === 2
                                                  ? 'bg-amber-500/30'
                                                  : 'bg-stone-700/50'
                                        "
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Pulsing live indicator -->
                        <div class="absolute -right-2 -top-2">
                            <span
                                class="absolute inline-flex h-5 w-5 animate-ping rounded-full bg-amber-400 opacity-40"
                            ></span>
                            <span
                                class="relative inline-flex h-5 w-5 items-center justify-center rounded-full bg-stone-950"
                            >
                                <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats strip -->
        <section class="relative z-10 border-y border-white/5 bg-white/[0.025]">
            <div class="mx-auto max-w-6xl px-6 py-8">
                <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                    <div v-for="stat in stats" :key="stat.label" class="text-center">
                        <div class="text-2xl font-bold text-amber-400">{{ stat.value }}</div>
                        <div class="mt-0.5 text-sm text-stone-500">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="relative z-10 mx-auto max-w-6xl px-6 py-16 sm:py-24">
            <div class="text-center">
                <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Everything you need to run your stores
                </h2>
                <p class="mx-auto mt-3 max-w-xl text-stone-400">
                    Owlet brings together sales, inventory, and people management
                    into a single, cohesive system.
                </p>
            </div>

            <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(feature, index) in features"
                    :key="feature.title"
                    class="feature-card group rounded-xl border border-white/10 bg-white/[0.04] p-6 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/30 hover:bg-white/[0.07]"
                    :style="{ animationDelay: index * 80 + 'ms' }"
                >
                    <div
                        class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10 text-amber-400 transition-colors group-hover:bg-amber-500/20"
                    >
                        <component :is="feature.icon" class="h-5 w-5" />
                    </div>
                    <h3 class="font-semibold">{{ feature.title }}</h3>
                    <p class="mt-1 text-sm leading-relaxed text-stone-400">
                        {{ feature.description }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="relative z-10 border-t border-white/5">
            <div
                class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 py-8 sm:flex-row"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-6 w-6 items-center justify-center rounded bg-stone-800 text-white"
                    >
                        <AppLogoIcon class="size-3.5 fill-current" />
                    </div>
                    <span class="text-sm text-stone-500">
                        &copy; {{ new Date().getFullYear() }} Owlet. All rights
                        reserved.
                    </span>
                </div>
                <Link
                    href="/apply"
                    class="text-sm text-stone-500 transition-colors hover:text-stone-300"
                >
                    Register staff access
                </Link>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Ambient background orbs */
.orb-1 {
    top: -200px;
    left: -100px;
    animation: pulse-glow 8s ease-in-out infinite;
}

.orb-2 {
    bottom: 20%;
    right: -150px;
    animation: pulse-glow 10s ease-in-out infinite reverse;
}

@keyframes pulse-glow {
    0%,
    100% {
        transform: scale(1);
        opacity: 0.6;
    }
    50% {
        transform: scale(1.25);
        opacity: 1;
    }
}

/* Hero text entrance */
.hero-text {
    animation: fadeInUp 0.7s ease-out both;
}

/* Dashboard preview: entrance + continuous float */
.dashboard-preview {
    animation:
        fadeInUp 0.7s ease-out 0.15s both,
        float 7s ease-in-out 0.85s infinite;
}

/* Feature cards staggered entrance */
.feature-card {
    animation: fadeInUp 0.6s ease-out both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(28px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-14px);
    }
}
</style>
