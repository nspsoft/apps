<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    HomeIcon,
    ShoppingCartIcon,
    ArrowRightOnRectangleIcon,
    Bars3Icon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

defineProps({
    title: String,
});

const sidebarOpen = ref(false);

const navigation = [
    { name: 'Dashboard', href: '/portal/dashboard', icon: HomeIcon },
    { name: 'Purchase Orders', href: '/portal/purchase-orders', icon: ShoppingCartIcon },
];

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
        <Head :title="title" />

        <!-- Mobile Sidebar -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-50 lg:hidden">
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" @click="sidebarOpen = false"></div>
            <div class="fixed inset-y-0 left-0 w-64 bg-white dark:bg-slate-800 shadow-xl p-4">
                <div class="flex items-center justify-between mb-8">
                    <span class="text-xl font-bold text-slate-900 dark:text-white">Vendor Portal</span>
                    <button @click="sidebarOpen = false">
                        <XMarkIcon class="h-6 w-6 text-slate-500" />
                    </button>
                </div>
                <nav class="space-y-2">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors"
                        :class="$page.url.startsWith(item.href) ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                    >
                        <component :is="item.icon" class="h-5 w-5" />
                        {{ item.name }}
                    </Link>
                </nav>
            </div>
        </div>

        <!-- Desktop Sidebar -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-center h-16 border-b border-slate-200 dark:border-slate-700">
                <span class="text-xl font-bold bg-gradient-to-r from-indigo-500 to-purple-500 bg-clip-text text-transparent">Vendor Portal</span>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors"
                    :class="$page.url.startsWith(item.href) ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                >
                    <component :is="item.icon" class="h-5 w-5" />
                    {{ item.name }}
                </Link>
            </nav>
            <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                <button
                    @click="logout"
                    class="flex w-full items-center gap-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                >
                    <ArrowRightOnRectangleIcon class="h-5 w-5" />
                    Logout
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:pl-64">
            <!-- Header -->
            <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 text-slate-500">
                    <Bars3Icon class="h-6 w-6" />
                </button>
                <div class="flex-1"></div>
                <div class="flex items-center gap-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-slate-500">{{ $page.props.auth.user.email }}</p>
                    </div>
                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold border border-indigo-200">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="py-10">
                <div class="px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
