<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatCurrency } from '@/helpers';
import { 
    PlusIcon, 
    WrenchScrewdriverIcon, 
    ClockIcon, 
    ArrowLongRightIcon, 
    ChartBarIcon, 
    UserGroupIcon, 
    BoltIcon, 
    DocumentMagnifyingGlassIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    PauseIcon,
    PlayIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: Object,
    shift_data: Object,
    machine_statuses: Array,
    recent_entries: Array,
});


const getStatusColor = (status) => {
    const colors = {
        'Running': 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
        'Downtime': 'text-red-500 bg-red-500/10 border-red-500/20',
        'Idle': 'text-slate-500 bg-slate-500/10 border-slate-500/20',
    };
    return colors[status] || colors.Idle;
};

const getOEEColor = (val) => {
    if (val >= 85) return 'text-emerald-500';
    if (val >= 60) return 'text-amber-500';
    return 'text-red-500';
};
</script>

<template>
    <Head title="Production Intelligence" />
    
    <AppLayout title="Production Intelligence">
        <!-- Top Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="glass-card rounded-3xl p-6 relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 h-24 w-24 bg-blue-500/5 rounded-full blur-3xl group-hover:bg-blue-500/10 transition-all duration-700"></div>
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-500">
                        <ChartBarIcon class="h-6 w-6" />
                    </div>
                    <div :class="[stats.growth >= 0 ? 'text-emerald-500 bg-emerald-500/10' : 'text-red-500 bg-red-500/10', 'px-2.5 py-1 rounded-lg text-xs font-bold flex items-center gap-1']">
                        <ArrowUpIcon v-if="stats.growth >= 0" class="h-3 w-3" />
                        <ArrowDownIcon v-else class="h-3 w-3" />
                        {{ Math.abs(stats.growth) }}%
                    </div>
                </div>
                <div class="text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-1">{{ formatNumber(stats.today_qty) }}</div>
                <div class="text-xs font-bold text-slate-500 uppercase tracking-widest">Today's Output</div>
                <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800/50 flex items-center justify-between">
                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Yesterday</span>
                    <span class="text-xs font-mono text-slate-500 dark:text-slate-400 font-bold">{{ formatNumber(stats.yesterday_qty) }}</span>
                </div>
            </div>

            <div class="glass-card rounded-3xl p-6 relative overflow-hidden group" v-for="metric in [
                { label: 'Quality rate', value: stats.quality, icon: CheckCircleIcon, color: 'emerald' },
                { label: 'Performance', value: stats.performance, icon: BoltIcon, color: 'amber' },
                { label: 'Availability', value: stats.availability, icon: ClockIcon, color: 'blue' }
            ]" :key="metric.label">
                <div class="flex items-center justify-between mb-4">
                    <div :class="[`bg-${metric.color}-500/10 text-${metric.color}-500`, 'p-3 rounded-2xl']">
                        <component :is="metric.icon" class="h-6 w-6" />
                    </div>
                    <div class="text-[10px] uppercase font-black text-slate-600 tracking-tighter">Baseline 85%</div>
                </div>
                <div class="text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-1">{{ metric.value }}%</div>
                <div class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ metric.label }}</div>
                <div class="mt-4 h-1.5 w-full bg-slate-50 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div :class="[`bg-${metric.color}-500`, 'h-full transition-all duration-1000']" :style="{ width: `${metric.value}%` }"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Center OEE Gauge -->
            <div class="lg:col-span-1 glass-card rounded-3xl p-8 flex flex-col items-center justify-center text-center relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-b from-blue-500/5 to-transparent"></div>
                
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.3em] mb-8 relative z-10">Overall OEE Result</h3>
                
                <div class="relative w-48 h-48 flex items-center justify-center mb-8 relative z-10">
                    <svg class="transform -rotate-90 w-48 h-48">
                        <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="12" fill="transparent" class="text-slate-800" />
                        <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="12" fill="transparent" :stroke-dasharray="2 * Math.PI * 88" :stroke-dashoffset="2 * Math.PI * 88 * (1 - stats.oee / 100)" stroke-linecap="round" :class="[getOEEColor(stats.oee), 'transition-all duration-1000 ease-out']" />
                    </svg>
                    <div class="absolute flex flex-col items-center">
                        <span class="text-5xl font-black text-slate-900 dark:text-white tracking-tight">{{ stats.oee }}%</span>
                        <span class="text-[10px] font-black text-slate-500 uppercase mt-1">World Class: 85%</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 w-full relative z-10">
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800">
                        <div class="text-xs font-bold text-slate-500 mb-1">Status</div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white uppercase">{{ stats.oee >= 85 ? 'Excellent' : (stats.oee >= 60 ? 'Healthy' : 'Sub-Optimal') }}</div>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800">
                        <div class="text-xs font-bold text-slate-500 mb-1">Trend</div>
                        <div class="text-xs font-bold text-emerald-500 uppercase tracking-tighter">Improving 2.4%</div>
                    </div>
                </div>
            </div>

            <!-- Shift Productivity & Active Machines -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Shift Bars -->
                <div class="glass-card rounded-3xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                             <UserGroupIcon class="h-5 w-5 text-blue-500" />
                             Today Productivity by Shift
                        </h3>
                        <span class="text-[10px] text-slate-500 font-mono">{{ new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) }}</span>
                    </div>
                    <div class="space-y-6">
                        <div v-for="shift in ['Shift 1', 'Shift 2', 'Shift 3']" :key="shift" class="group">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ shift }}</span>
                                <span class="text-xs font-mono font-bold text-slate-900 dark:text-white">{{ formatNumber(shift_data[shift.slice(-1)] || 0) }} unit</span>
                            </div>
                            <div class="h-3 w-full bg-slate-50 dark:bg-slate-800 rounded-full overflow-hidden p-0.5">
                                <div class="h-full bg-gradient-to-r from-blue-600 to-blue-400 rounded-full transition-all duration-700" :style="{ width: `${Math.min(100, ((shift_data[shift.slice(-1)] || 0) / (stats.today_qty || 1)) * 100)}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Machines -->
                <div class="glass-card rounded-3xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                             <BoltIcon class="h-5 w-5 text-amber-500" />
                             Live Machine Status
                        </h3>
                        <Link href="/manufacturing/machines" class="text-[10px] font-black text-blue-500 hover:text-blue-400 uppercase tracking-widest">Master Data →</Link>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="machine in machine_statuses" :key="machine.id" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/20 border border-slate-200 dark:border-slate-800 flex items-center gap-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/40 transition-colors">
                            <div :class="[getStatusColor(machine.status), 'p-2 rounded-xl border flex-shrink-0']">
                                <PlayIcon v-if="machine.status === 'Running'" class="h-5 w-5" />
                                <PauseIcon v-else-if="machine.status === 'Downtime'" class="h-5 w-5" />
                                <ClockIcon v-else class="h-5 w-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ machine.name }}</div>
                                <div class="flex items-center gap-2 text-[10px] font-bold">
                                    <span :class="machine.status === 'Running' ? 'text-emerald-500' : (machine.status === 'Downtime' ? 'text-red-500' : 'text-slate-500')">
                                        {{ machine.status }}
                                    </span>
                                    <span class="text-slate-600">•</span>
                                    <span class="text-slate-500">Updated {{ machine.last_update }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-mono font-bold text-slate-900 dark:text-white">{{ formatNumber(machine.last_qty) }}</div>
                                <div class="text-[10px] text-slate-500 font-bold uppercase tracking-tighter">Last Qty</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Table -->
        <div class="glass-card rounded-3xl overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <DocumentMagnifyingGlassIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                    Latest Production Logs
                </h3>
                <Link href="/manufacturing/work-orders" class="text-[10px] font-black text-slate-500 hover:text-slate-900 dark:text-white uppercase tracking-widest">Historical Data →</Link>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/30">
                            <th class="p-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Time</th>
                            <th class="p-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Work Order</th>
                            <th class="p-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Product</th>
                            <th class="p-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Machine</th>
                            <th class="p-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Shift</th>
                            <th class="p-4 text-right text-[10px] font-black text-slate-500 uppercase tracking-widest">Qty Produced</th>
                            <th class="p-4 text-right text-[10px] font-black text-slate-500 uppercase tracking-widest">Reject</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                        <tr v-for="entry in recent_entries" :key="entry.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/20 transition-colors">
                            <td class="p-4 text-xs text-slate-500 dark:text-slate-400 font-mono">{{ new Date(entry.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</td>
                            <td class="p-4">
                                <Link :href="route('manufacturing.work-orders.show', entry.work_order_id)" class="text-xs font-bold text-blue-400 hover:text-blue-300">
                                    {{ entry.work_order?.wo_number }}
                                </Link>
                            </td>
                            <td class="p-4 text-xs font-bold text-slate-600 dark:text-slate-300">{{ entry.work_order?.product?.name }}</td>
                            <td class="p-4 text-xs text-slate-500 dark:text-slate-400">{{ entry.machine_line }}</td>
                            <td class="p-4">
                                <span class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">Shift {{ entry.shift }}</span>
                            </td>
                            <td class="p-4 text-right text-xs font-mono font-bold text-slate-900 dark:text-white">{{ formatNumber(entry.qty_produced) }}</td>
                            <td class="p-4 text-right text-xs font-mono font-bold text-red-400">{{ formatNumber(entry.qty_rejected) }}</td>
                        </tr>
                        <tr v-if="recent_entries.length === 0">
                            <td colspan="7" class="p-12 text-center text-slate-500 text-sm italic">No entries recorded for today yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>



