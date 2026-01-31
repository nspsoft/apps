<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { 
    BanknotesIcon, 
    ScaleIcon, 
    ArrowTrendingUpIcon, 
    ChartBarIcon,
    PieChartIcon,
    WalletIcon
} from '@heroicons/vue/24/outline';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import { 
    Chart as ChartJS, 
    Title, Tooltip, Legend, 
    LineElement, LinearScale, PointElement, CategoryScale, 
    ArcElement, RadialLinearScale, Filler, BarElement
} from 'chart.js';

ChartJS.register(
    Title, Tooltip, Legend, 
    LineElement, LinearScale, PointElement, CategoryScale, 
    ArcElement, RadialLinearScale, Filler, BarElement
);

const props = defineProps({
    kpi: Object,
    structure: Object,
    cash_flow: Object,
    performance: Object,
    asset_composition: Array,
    expense_breakdown: Array
});

// --- Real-time Clock ---
const time = ref('');
const updateTime = () => {
    const now = new Date();
    time.value = now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
};
let timer;
onMounted(() => {
    updateTime();
    timer = setInterval(updateTime, 1000);
});
onUnmounted(() => clearInterval(timer));

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', { 
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0 
}).format(value);

// === CHART CONFIGURATION ===
const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(5, 5, 16, 0.95)',
            titleColor: '#22d3ee',
            bodyColor: '#e2e8f0',
            borderColor: '#22d3ee',
            borderWidth: 1,
            padding: 12,
            titleFont: { family: 'Space Mono', weight: 'bold' },
            bodyFont: { family: 'Space Mono' },
            displayColors: true,
            boxPadding: 4
        }
    },
    scales: {
        x: {
            grid: { color: 'rgba(255, 255, 255, 0.05)' },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        },
        y: {
            grid: { color: 'rgba(255, 255, 255, 0.05)' },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 }, 
            callback: (val) => new Intl.NumberFormat('en-US', { notation: "compact", maximumFractionDigits: 1 }).format(val) }
        }
    }
};

// 1. Performance Trend (Bar)
const performanceData = {
    labels: props.performance.months,
    datasets: [
        {
            label: 'Revenue',
            data: props.performance.revenue,
            backgroundColor: '#10b981',
            borderRadius: 4
        },
        {
            label: 'Expenses',
            data: props.performance.expenses,
            backgroundColor: '#ef4444',
            borderRadius: 4
        }
    ]
};

// 2. Cash Flow (Line)
const cashFlowData = {
    labels: props.cash_flow.dates,
    datasets: [
        {
            label: 'Net Flow',
            data: props.cash_flow.inflow.map((inflow, i) => inflow - props.cash_flow.outflow[i]),
            borderColor: '#22d3ee',
            backgroundColor: (ctx) => {
                const canvas = ctx.chart.ctx;
                const gradient = canvas.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(34, 211, 238, 0.2)');
                gradient.addColorStop(1, 'rgba(34, 211, 238, 0.0)');
                return gradient;
            },
            fill: true,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 4
        }
    ]
};

// 3. Asset Composition (Doughnut)
const assetData = {
    labels: props.asset_composition.map(a => a.name),
    datasets: [{
        data: props.asset_composition.map(a => a.total),
        backgroundColor: ['#10b981', '#3b82f6', '#f59e0b'],
        borderColor: '#050510',
        borderWidth: 2,
        hoverOffset: 10
    }]
};
const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '60%',
    plugins: { legend: { display: false } },
    scales: { x: { display: false }, y: { display: false } }
};

</script>

<template>
    <Head title="Financial Command" />

    <AppLayout title="Financial Command" :render-header="false">
        <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 selection:bg-cyan-500/30">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="perspective-grid absolute inset-0 opacity-20"></div>
                <div class="perspective-grid absolute inset-0 opacity-20"></div>
            </div>

            <div class="relative z-10 p-4 md:p-6 space-y-6 max-w-7xl mx-auto">
                
                <!-- HEADER -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-4 border-b border-white/10">
                    <div>
                         <div class="flex items-center gap-2 mb-2">
                             <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-emerald-500/10 border border-emerald-500/20 rounded text-emerald-400 tracking-wider">
                                <span class="animate-ping absolute inline-flex h-1 w-1 rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-400"></span>
                                ONLINE
                            </span>
                            <span class="px-2 py-0.5 text-[10px] bg-white/5 border border-white/10 rounded text-slate-400 tracking-[0.2em]">FIN.DASH.V2</span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-white to-emerald-400 tracking-widest uppercase glow-text">
                            FINANCIAL COMMAND
                        </h1>
                    </div>
                    <div class="text-right hidden md:block">
                        <p class="text-[10px] text-slate-500 tracking-[0.3em] uppercase mb-1">SYSTEM TIME</p>
                        <p class="text-xl font-bold font-mono text-cyan-500 glow-text">{{ time }}</p>
                    </div>
                </div>

                <!-- KPI GRID (4 Items) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Net Profit -->
                    <div class="hud-panel p-5 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-all transform group-hover:scale-110">
                            <BanknotesIcon class="h-12 w-12 text-emerald-400" />
                        </div>
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-2">NET PROFIT</p>
                        <h3 class="text-2xl font-black text-white group-hover:text-emerald-300 transition-colors">
                            {{ formatCurrency(props.kpi.net_profit) }}
                        </h3>
                        <div class="mt-3 flex items-center gap-2">
                            <span class="text-emerald-400 text-xs font-bold bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">
                                {{ props.kpi.profit_margin }}% MARGIN
                            </span>
                        </div>
                        <div class="absolute bottom-0 left-0 h-0.5 w-full bg-slate-800">
                            <div class="h-full bg-emerald-500 w-[65%] shadow-[0_0_10px_#10b981]"></div>
                        </div>
                    </div>

                    <!-- Revenue -->
                    <div class="hud-panel p-5 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-all">
                            <ArrowTrendingUpIcon class="h-12 w-12 text-cyan-400" />
                        </div>
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-2">REVENUE</p>
                        <h3 class="text-2xl font-black text-white group-hover:text-cyan-300 transition-colors">
                            {{ formatCurrency(props.kpi.revenue) }}
                        </h3>
                         <div class="mt-3 text-xs text-slate-500">
                            Current Period Performance
                        </div>
                        <div class="absolute bottom-0 left-0 h-0.5 w-full bg-slate-800">
                            <div class="h-full bg-cyan-500 w-[80%] shadow-[0_0_10px_#22d3ee]"></div>
                        </div>
                    </div>

                    <!-- Liabilities -->
                    <div class="hud-panel p-5 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-all">
                            <ScaleIcon class="h-12 w-12 text-rose-400" />
                        </div>
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-2">LIABILITIES</p>
                        <h3 class="text-2xl font-black text-white group-hover:text-rose-300 transition-colors">
                            {{ formatCurrency(props.kpi.total_liabilities) }}
                        </h3>
                        <div class="mt-3 text-xs text-rose-400/80">
                            Ratio: {{ props.kpi.current_ratio }} (Safe > 1.0)
                        </div>
                         <div class="absolute bottom-0 left-0 h-0.5 w-full bg-slate-800">
                            <div class="h-full bg-rose-500 w-[40%] shadow-[0_0_10px_#f43f5e]"></div>
                        </div>
                    </div>

                    <!-- Total Assets -->
                    <div class="hud-panel p-5 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-all">
                            <WalletIcon class="h-12 w-12 text-amber-400" />
                        </div>
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-2">TOTAL ASSETS</p>
                        <h3 class="text-2xl font-black text-white group-hover:text-amber-300 transition-colors">
                            {{ formatCurrency(props.kpi.total_assets) }}
                        </h3>
                         <div class="mt-3 text-xs text-slate-500">
                            Equity: {{ formatCurrency(props.kpi.total_equity) }}
                        </div>
                         <div class="absolute bottom-0 left-0 h-0.5 w-full bg-slate-800">
                            <div class="h-full bg-amber-500 w-[90%] shadow-[0_0_10px_#f59e0b]"></div>
                        </div>
                    </div>
                </div>

                <!-- MAIN CHARTS (2 Cols) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 h-[400px]">
                    <!-- Performance Chart -->
                    <div class="hud-panel flex flex-col h-full">
                        <div class="p-4 border-b border-white/5 flex justify-between items-center bg-white/5">
                            <h3 class="text-sm font-bold text-cyan-300 tracking-widest uppercase flex items-center gap-2">
                                <ChartBarIcon class="h-4 w-4" /> Performance Trend (6 Mo)
                            </h3>
                        </div>
                        <div class="p-4 flex-1">
                            <Bar :data="performanceData" :options="commonOptions" />
                        </div>
                    </div>

                    <!-- Cash Flow Chart -->
                    <div class="hud-panel flex flex-col h-full">
                         <div class="p-4 border-b border-white/5 flex justify-between items-center bg-white/5">
                            <h3 class="text-sm font-bold text-emerald-300 tracking-widest uppercase flex items-center gap-2">
                                <ArrowTrendingUpIcon class="h-4 w-4" /> Net Cash Flow
                            </h3>
                        </div>
                        <div class="p-4 flex-1">
                            <Line :data="cashFlowData" :options="commonOptions" />
                        </div>
                    </div>
                </div>

                <!-- BOTTOM PANELS (3 Cols) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-[300px]">
                    
                    <!-- Asset Composition -->
                    <div class="hud-panel flex flex-col h-full">
                        <div class="p-4 border-b border-white/5 bg-white/5">
                            <h3 class="text-xs font-bold text-slate-400 tracking-widest uppercase">Asset Composition</h3>
                        </div>
                        <div class="p-6 flex-1 relative flex items-center justify-center">
                            <div class="w-full h-full max-h-[180px]">
                                <Doughnut :data="assetData" :options="doughnutOptions" />
                            </div>
                            <!-- Center Label -->
                             <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div class="text-center">
                                    <p class="text-[10px] text-slate-500 uppercase tracking-widest">TOTAL</p>
                                    <p class="text-sm font-bold text-white">ASSETS</p>
                                </div>
                            </div>
                        </div>
                         <!-- Legend -->
                        <div class="px-4 pb-4 flex justify-center gap-4 text-[10px]">
                            <div v-for="(item, i) in props.asset_composition" :key="i" class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full" :style="{ background: assetData.datasets[0].backgroundColor[i] }"></span>
                                <span class="text-slate-400">{{ item.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Expense Breakdown List -->
                    <div class="hud-panel flex flex-col h-full md:col-span-2">
                        <div class="p-4 border-b border-white/5 bg-white/5 flex justify-between">
                            <h3 class="text-xs font-bold text-slate-400 tracking-widest uppercase">Top Operating Expenses</h3>
                            <PieChartIcon class="h-4 w-4 text-slate-600" />
                        </div>
                        <div class="p-4 flex-1 overflow-y-auto custom-scrollbar">
                           <div class="space-y-3">
                                <div v-for="(exp, i) in props.expense_breakdown" :key="i" class="flex items-center justify-between group">
                                    <div class="flex items-center gap-3">
                                        <div class="text-xs font-mono text-slate-500 w-6">0{{ i + 1 }}</div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-200 group-hover:text-rose-400 transition-colors">{{ exp.category }}</p>
                                            <div class="h-1 w-24 bg-slate-800 rounded-full mt-1 overflow-hidden">
                                                <div class="h-full bg-rose-500/50" :style="{ width: `${(exp.total / props.kpi.expenses) * 100}%` }"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-mono font-bold text-white">{{ formatCurrency(exp.total) }}</p>
                                        <p class="text-[10px] text-slate-500">{{ Math.round((exp.total / props.kpi.expenses) * 100) }}% of Opex</p>
                                    </div>
                                </div>
                           </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}

.hud-panel {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
}

.glow-text {
    text-shadow: 0 0 20px rgba(34, 211, 238, 0.3);
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.1; }
    50% { opacity: 0.2; }
}
.animate-pulse-slow {
    animation: pulse-slow 8s infinite ease-in-out;
}

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
