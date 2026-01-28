<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { TruckIcon, WrenchScrewdriverIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: String
});
</script>

<template>
    <Head :title="title" />
    
    <AppLayout :title="title">
        <div class="rounded-2xl glass-card p-12 text-center">
            <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-500/10 text-slate-500 dark:text-slate-400 mx-auto mb-6">
                <TruckIcon class="h-10 w-10" />
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ title }}</h3>
            <p class="text-slate-500 dark:text-slate-400 max-w-md mx-auto mb-6 leading-relaxed">
                Logistics module has been <strong class="text-emerald-500">Activated</strong> successfully via Auto-Deployment! 🚀
            </p>
            <div class="inline-flex items-center gap-2 text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10 px-4 py-2 rounded-xl border border-emerald-200 dark:border-emerald-500/20">
                <CheckBadgeIcon class="h-4 w-4" />
                <span class="text-xs font-bold uppercase tracking-widest">Active & Verified</span>
            </div>
        </div>
    </AppLayout>
</template>



