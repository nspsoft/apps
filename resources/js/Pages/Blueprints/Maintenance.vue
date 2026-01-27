<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ShieldExclamationIcon, WrenchScrewdriverIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: String
});
</script>

<template>
    <Head :title="title" />
    
    <AppLayout :title="title">
        <div class="rounded-2xl glass-card p-12 text-center">
            <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-amber-500/10 text-amber-400 mx-auto mb-6">
                <ShieldExclamationIcon class="h-10 w-10" />
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ title }}</h3>
            <p class="text-slate-500 dark:text-slate-400 max-w-md mx-auto mb-6 leading-relaxed">
                This Maintenance module is currently in the <strong>Blueprinting Phase</strong>. It will be used to manage preventive maintenance schedules, track machine breakdowns, and monitor spareparts stock.
            </p>
            <div class="inline-flex items-center gap-2 text-slate-500 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700">
                <WrenchScrewdriverIcon class="h-4 w-4" />
                <span class="text-xs font-bold uppercase tracking-widest">Future Capability</span>
            </div>
        </div>
    </AppLayout>
</template>



