<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Finance Dashboard" />
    <AppLayout title="Finance Dashboard">
        <div class="min-h-screen bg-[#050510] flex items-center justify-center">
             <div class="text-center space-y-4">
                <div class="inline-block p-4 rounded-full bg-cyan-500/10 mb-4 animate-pulse">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-cyan-500">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.636-3.636l-6.233 4.75M16.5 7.5V11m6-3.5C18.333 1.667 17 1 12 1s-7.5 7.5-6 12.5" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-white tracking-widest uppercase glow-text">
                    SYSTEM UPGRADE
                </h1>
                <p class="text-slate-400 font-mono text-sm max-w-md mx-auto">
                    The Financial Command Center is currently undergoing a critical architecture upgrade.
                    Modules are being re-calibrated for the specific chart configuration.
                </p>
                <div class="pt-8">
                     <span class="px-3 py-1 text-[10px] bg-white/5 border border-white/10 rounded text-slate-500 tracking-wider">
                        STATUS: MAINTENANCE_MODE
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.glow-text {
    text-shadow: 0 0 20px rgba(6, 182, 212, 0.5);
}
</style>
