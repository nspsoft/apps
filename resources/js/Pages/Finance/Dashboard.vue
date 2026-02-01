<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <Head title="Financial Command" />

    <AppLayout title="Financial Command" :render-header="false">
        <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 selection:bg-cyan-500/30">
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="perspective-grid absolute inset-0 opacity-20"></div>
            </div>

            <div class="relative z-10 p-6 flex items-center justify-center min-h-[500px]">
                <div class="text-center space-y-4">
                    <div class="inline-block p-4 rounded-full bg-cyan-500/10 border border-cyan-500/20 mb-4 animate-pulse">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-cyan-400">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-white to-indigo-400 uppercase tracking-widest">
                        SYSTEM UPGRADE
                    </h1>
                    <p class="text-slate-400 max-w-md mx-auto">
                        The Financial Center visual core is being re-calibrated. 
                        Please access General Ledger or Reports from the sidebar.
                    </p>
                    <div class="pt-6">
                        <span class="px-3 py-1 bg-cyan-500/10 text-cyan-400 text-xs font-mono rounded border border-cyan-500/20">
                            Build Status: STABLE
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(6, 182, 212, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(6, 182, 212, 0.1) 1px, transparent 1px);
    background-size: 40px 40px;
    transform: perspective(500px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 20s linear infinite;
    transform-origin: top;
}
@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 40px; }
}
</style>
