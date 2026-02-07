<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ChartPieIcon, 
    CalculatorIcon, 
    ArrowPathIcon,
    ArrowUpCircleIcon,
    BeakerIcon
} from '@heroicons/vue/24/outline';

defineProps({
    title: String
});

const costElements = [
    { name: 'Raw Materials', value: 'Rp 450.000.000', percentage: 65, color: 'bg-blue-500' },
    { name: 'Direct Labor', value: 'Rp 120.000.000', percentage: 18, color: 'bg-purple-500' },
    { name: 'Factory Overhead', value: 'Rp 85.000.000', percentage: 12, color: 'bg-cyan-500' },
    { name: 'Other Costs', value: 'Rp 35.000.000', percentage: 5, color: 'bg-slate-400' },
];
</script>

<template>
    <Head :title="title" />
    
    <AppLayout :title="title">
        <div class="space-y-8">
            <!-- Alert Banner -->
            <div class="bg-blue-600 rounded-3xl p-6 text-white overflow-hidden relative shadow-2xl shadow-blue-500/20">
                <div class="relative z-10 flex flex-col md:flex-row items-center gap-6">
                    <div class="h-16 w-16 bg-white/20 backdrop-blur-xl rounded-2xl flex items-center justify-center shrink-0">
                        <BeakerIcon class="h-8 w-8 text-white" />
                    </div>
                    <div>
                        <h3 class="text-xl font-black tracking-tight uppercase">Blueprinting: Advanced Costing</h3>
                        <p class="text-blue-100 max-w-2xl text-sm leading-relaxed">
                            Implementing real-time **Actual Costing** engine. This module calculates COGS (HPP) by aggregating Raw Material (FIFO/Weighted), Direct Labor, and allocated Overhead for every Work Order.
                        </p>
                    </div>
                </div>
                <!-- Abstract Design Elements -->
                <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute right-40 -bottom-20 w-40 h-40 bg-blue-400/20 rounded-full blur-2xl"></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Cost Breakdown -->
                <div class="lg:col-span-2 glass-card p-8 rounded-[2rem]">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">Production Cost Breakdown</h3>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Status: Calculation Running</p>
                        </div>
                        <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl">
                            <ChartPieIcon class="h-6 w-6 text-slate-400" />
                        </div>
                    </div>

                    <div class="space-y-8">
                        <div v-for="cost in costElements" :key="cost.name">
                            <div class="flex justify-between items-end mb-2">
                                <div>
                                    <span class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ cost.name }}</span>
                                    <div class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ cost.percentage }}% OF TOTAL COGS</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-black text-slate-900 dark:text-white font-mono">{{ cost.value }}</div>
                                </div>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-3 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-1000 delay-300" :class="cost.color" :style="{ width: `${cost.percentage}%` }"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-12 pt-8 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="text-slate-400 text-xs font-bold uppercase tracking-widest">Total Estimated COGS</div>
                        <div class="text-3xl font-black text-slate-900 dark:text-white glow-text">Rp 690.400.000</div>
                    </div>
                </div>

                <!-- Right: Analysis Controls -->
                <div class="space-y-6">
                    <div class="glass-card p-6 rounded-3xl border-l-4 border-blue-500">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="p-3 bg-blue-500/10 text-blue-500 rounded-2xl">
                                <CalculatorIcon class="h-6 w-6" />
                            </div>
                            <h4 class="font-black text-slate-900 dark:text-white uppercase tracking-tighter">Cost Analytics</h4>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed mb-6">
                            Analyzing variance between **Standard Cost** and **Actual Cost**. Detecting efficiency leaks.
                        </p>
                        <button class="w-full py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:scale-105 transition-all shadow-xl shadow-slate-900/20 dark:shadow-white/10">
                            Run Variance Check
                        </button>
                    </div>

                    <div class="glass-card p-6 rounded-3xl border-l-4 border-purple-500">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="p-3 bg-purple-500/10 text-purple-500 rounded-2xl">
                                <ArrowUpCircleIcon class="h-6 w-6" />
                            </div>
                            <h4 class="font-black text-slate-900 dark:text-white uppercase tracking-tighter">Profitability</h4>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed mb-6">
                            Live margin detection based on current selling price and actual HPP.
                        </p>
                        <div class="p-4 bg-emerald-500/10 rounded-2xl border border-emerald-500/20 text-center">
                            <div class="text-xs font-bold text-emerald-500 uppercase mb-1">Current Net Margin</div>
                            <div class="text-2xl font-black text-emerald-500">22.4%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.glass-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
}

:deep(.dark) .glass-card {
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.glow-text {
    text-shadow: 0 0 15px rgba(59, 130, 246, 0.3);
}
</style>
