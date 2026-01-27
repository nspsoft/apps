<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { GlobeAltIcon, WrenchScrewdriverIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: String
});
</script>

<template>
    <Head :title="title" />
    
    <AppLayout :title="title">
        <div class="rounded-2xl glass-card p-12 text-center">
            <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-cyan-500/10 text-cyan-400 mx-auto mb-6">
                <GlobeAltIcon class="h-10 w-10" />
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ title }}</h3>
            <p class="text-slate-500 dark:text-slate-400 max-w-md mx-auto mb-6 leading-relaxed">
                This External Portal is currently in the <strong>Blueprinting Phase</strong>. It will allow your suppliers and customers to interact directly with your ERP system through a secure access point.
            </p>
            <div class="inline-flex items-center gap-2 text-slate-500 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700">
                <WrenchScrewdriverIcon class="h-4 w-4" />
                <span class="text-xs font-bold uppercase tracking-widest">Advanced Capability</span>
            </div>
        </div>
    </AppLayout>
</template>



