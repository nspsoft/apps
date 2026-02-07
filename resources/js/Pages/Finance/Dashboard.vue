<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { 
    BanknotesIcon, 
    ArrowTrendingUpIcon, 
    ArrowTrendingDownIcon, 
    WalletIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline';

const stats = [
    { name: 'Total Balance', value: 'Rp 4.250.000.000', change: '+12.5%', icon: BanknotesIcon, color: 'text-emerald-400' },
    { name: 'Monthly Revenue', value: 'Rp 850.200.000', change: '+5.2%', icon: ArrowTrendingUpIcon, color: 'text-blue-400' },
    { name: 'Account Payable', value: 'Rp 320.400.000', change: '-2.1%', icon: ArrowTrendingDownIcon, color: 'text-red-400' },
    { name: 'Cash on Hand', value: 'Rp 1.120.000.000', change: '+8.0%', icon: WalletIcon, color: 'text-purple-400' },
];
</script>

<template>
    <Head title="Finance Dashboard" />
    <AppLayout title="Finance Dashboard">
        <div class="space-y-8">
            <!-- Header section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">Financial Command</h1>
                    <p class="text-slate-500 dark:text-slate-400">Real-time financial intelligence and monitoring.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <ArrowPathIcon class="h-4 w-4" />
                        Refresh Data
                    </button>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-500/30 hover:scale-105 transition-all">
                        + New Entry
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="stat in stats" :key="stat.name" class="glass-card p-6 rounded-3xl relative overflow-hidden group hover:scale-[1.02] transition-all duration-300">
                    <!-- Background Glow -->
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-current opacity-[0.03] rounded-full blur-2xl group-hover:opacity-[0.08] transition-opacity" :class="stat.color"></div>
                    
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                            <component :is="stat.icon" class="h-6 w-6" />
                        </div>
                        <span class="text-xs font-bold px-2 py-1 rounded-lg" :class="stat.change.startsWith('+') ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400'">
                            {{ stat.change }}
                        </span>
                    </div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">{{ stat.name }}</div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ stat.value }}</div>
                </div>
            </div>

            <!-- Main Dashboard Area -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Revenue Chart Simulation -->
                <div class="lg:col-span-2 glass-card p-8 rounded-[2rem]">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Revenue Analytics</h3>
                            <p class="text-sm text-slate-500">6-month performance trend</p>
                        </div>
                        <select class="bg-slate-50 dark:bg-slate-900 border-none rounded-xl text-xs font-bold text-slate-400 focus:ring-0">
                            <option>Last 6 Months</option>
                            <option>This Year</option>
                        </select>
                    </div>

                    <!-- Visual Simulation -->
                    <div class="h-64 flex items-end justify-between gap-4 px-4 pb-4">
                        <div v-for="i in 12" :key="i" class="flex-1 group relative">
                            <div 
                                class="w-full bg-gradient-to-t from-blue-600 to-cyan-400 rounded-t-lg transition-all duration-500 cursor-pointer hover:opacity-80"
                                :style="{ height: `${[40, 60, 45, 70, 85, 95, 65, 80, 55, 90, 75, 85][i-1]}%` }"
                            >
                                <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                    Rp {{ (Math.random() * 900 + 100).toFixed(1) }}M
                                </div>
                            </div>
                            <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                {{ ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOV', 'DES'][i-1] }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="glass-card p-8 rounded-[2rem] flex flex-col">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight mb-6">Cash Flow</h3>
                    <div class="space-y-6 flex-1">
                        <div v-for="i in 5" :key="i" class="flex items-center justify-between p-4 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors border border-transparent hover:border-slate-100 dark:hover:border-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-xl flex items-center justify-center font-bold" :class="i % 2 === 0 ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500'">
                                    {{ i % 2 === 0 ? '+' : '-' }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ i % 2 === 0 ? 'Sales Payment' : 'Vendor Utility' }}</div>
                                    <div class="text-[10px] text-slate-400 uppercase tracking-wider">MAR 07, 2026</div>
                                </div>
                            </div>
                            <div class="text-sm font-black" :class="i % 2 === 0 ? 'text-emerald-500' : 'text-slate-900 dark:text-white'">
                                Rp {{ (Math.random() * 50 + 5).toFixed(1) }}jt
                            </div>
                        </div>
                    </div>
                    <button class="mt-6 w-full py-3 rounded-2xl bg-slate-50 dark:bg-slate-900 text-slate-400 text-xs font-bold uppercase tracking-widest hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        View Full Ledger
                    </button>
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
</style>
