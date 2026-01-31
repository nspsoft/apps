<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    BoltIcon,
    CpuChipIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    ChartBarIcon,
    ClockIcon,
    CubeIcon,
    UserGroupIcon,
    BuildingOfficeIcon,
    ShoppingCartIcon,
    BanknotesIcon,
    TruckIcon
} from '@heroicons/vue/24/outline';
import { formatNumber } from '@/helpers';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { Bar, Doughnut, Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    stats: Object,
    recentPOs: Array,
    recentSOs: Array,
    activeWorkOrders: Array,
    lowStockProducts: Array,
    poStatusCounts: Object,
    soStatusCounts: Object,
    monthlySales: Array,
    monthlyPurchases: Array,
    stockByType: Object,
    stockMovements: Array,
    topCustomers: Array,
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

// --- Chart Options ---
const commonOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#94a3b8', font: { family: 'Space Mono' } } },
        tooltip: {
            backgroundColor: 'rgba(5, 5, 16, 0.9)',
            titleColor: '#22d3ee',
            bodyColor: '#e2e8f0',
            borderColor: '#22d3ee',
            borderWidth: 1,
            padding: 12,
            titleFont: { family: 'Space Mono', weight: 'bold' },
            bodyFont: { family: 'Space Mono' },
            displayColors: false,
        },
    },
    scales: {
        x: { 
            grid: { color: 'rgba(34, 211, 238, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        },
        y: { 
            grid: { color: 'rgba(34, 211, 238, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } }
        },
    },
}));

// -- Chart Data --

// 1. Sales vs Purchases Trend (Dual Line)
const trendChartData = computed(() => ({
    labels: props.monthlySales.map(d => d.month),
    datasets: [
        {
            label: 'Sales Volume',
            data: props.monthlySales.map(d => d.total),
            borderColor: '#22d3ee',
            backgroundColor: 'rgba(34, 211, 238, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
        },
        {
            label: 'Purchase Volume',
            data: props.monthlyPurchases.map(d => d.total),
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
        }
    ]
}));

// 2. Stock Items by Type (Doughnut)
const stockTypeChartData = computed(() => ({
    labels: Object.keys(props.stockByType),
    datasets: [{
        data: Object.values(props.stockByType),
        backgroundColor: ['#22d3ee', '#10b981', '#f59e0b', '#6366f1', '#ec4899'],
        borderWidth: 0,
        hoverOffset: 15,
        cutout: '70%',
    }]
}));

// 3. Top Customers (Horizontal Bar)
const topCustomersChartData = computed(() => ({
    labels: props.topCustomers.map(c => c.name),
    datasets: [{
        label: 'Revenue',
        data: props.topCustomers.map(c => c.total),
        backgroundColor: 'rgba(34, 211, 238, 0.6)',
        borderColor: '#22d3ee',
        borderWidth: 1,
        borderRadius: 4,
    }]
}));

const getStatusColor = (status) => {
    const s = status.toLowerCase();
    if (['completed', 'shipped', 'delivered', 'done', 'approved'].includes(s)) return 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20';
    if (['planned', 'confirmed', 'in_progress', 'pending', 'confirmed'].includes(s)) return 'text-cyan-400 bg-cyan-500/10 border-cyan-500/20';
    if (['draft', 'picking', 'packed'].includes(s)) return 'text-slate-400 bg-slate-500/10 border-slate-500/20';
    return 'text-rose-400 bg-rose-500/10 border-rose-500/20';
};
</script>

<template>
    <Head title="Command Core" />

    <AppLayout title="Dashboard" :render-header="false">
        <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 selection:bg-cyan-500/30">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-b from-cyan-950/20 to-[#050510]"></div>
                <div class="perspective-grid absolute inset-0 opacity-20"></div>
                <div class="absolute top-[-10%] left-[20%] w-[600px] h-[600px] bg-cyan-600/10 blur-[150px] rounded-full animate-float"></div>
                <div class="stars"></div>
            </div>

            <div class="relative z-10 p-6 space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-4 backdrop-blur-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-white/5 border border-white/10 rounded text-slate-400 tracking-[0.2em]">CORE.SYS.7.2.0</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-cyan-500/10 border border-cyan-500/20 rounded text-cyan-400 tracking-wider animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span> GLOBAL SYNC ACTIVE
                            </span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-white to-indigo-400 tracking-widest uppercase glow-text">
                            COMMAND CORE
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden md:block border-r border-white/10 pr-6">
                            <p class="text-[10px] text-cyan-500/70 tracking-[0.2em] mb-1">SYSTEM TIME</p>
                            <p class="text-2xl font-bold font-mono text-white glow-text">{{ time }}</p>
                        </div>
                        <div class="text-right hidden md:block">
                            <p class="text-[10px] text-cyan-500/70 tracking-[0.2em] mb-1">VERSION</p>
                            <p class="text-2xl font-bold font-mono text-white glow-text">PWA-V1.2</p>
                        </div>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <CubeIcon class="h-12 w-12 text-cyan-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">TOTAL PRODUCTS</p>
                                <h3 class="text-3xl font-black text-white glow-text tracking-tight">{{ stats.products }}</h3>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px]">
                                <span class="text-cyan-500/70">STOCK MANAGED</span>
                                <Link href="/inventory/products" class="text-cyan-400 hover:underline">VIEW ALL</Link>
                            </div>
                        </div>
                    </div>

                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <BuildingOfficeIcon class="h-12 w-12 text-emerald-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">SUPPLIERS</p>
                                <h3 class="text-3xl font-black text-white glow-text tracking-tight">{{ stats.suppliers }}</h3>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px]">
                                <span class="text-emerald-500/70">VERIFIED VENDORS</span>
                                <Link href="/purchasing/suppliers" class="text-emerald-400 hover:underline">ENTITY LIST</Link>
                            </div>
                        </div>
                    </div>

                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity">
                                <UserGroupIcon class="h-12 w-12 text-indigo-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">CUSTOMERS</p>
                                <h3 class="text-3xl font-black text-white glow-text tracking-tight">{{ stats.customers }}</h3>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px]">
                                <span class="text-indigo-500/70">ACTIVE CLIENTS</span>
                                <Link href="/sales/customers" class="text-indigo-400 hover:underline">CRM REGISTRY</Link>
                            </div>
                        </div>
                    </div>

                    <div class="hud-card group">
                        <div class="hud-content p-6 h-full relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 rounded-xl overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:opacity-40 transition-opacity animate-pulse">
                                <ExclamationTriangleIcon class="h-12 w-12 text-rose-400" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold mb-1">LOW STOCK ALERTS</p>
                                <h3 class="text-3xl font-black text-rose-500 glow-text tracking-tight">{{ stats.lowStock }}</h3>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px]">
                                <span class="text-rose-500/70">CRITICAL DEPLETION</span>
                                <Link href="/inventory/stocks" class="text-rose-400 hover:underline font-bold animate-pulse">REPLENISH</Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Analysis Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Revenue Pulse (Dual Trend) -->
                    <div class="lg:col-span-2 hud-panel flex flex-col h-[400px]">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-300 tracking-widest uppercase">
                                <BanknotesIcon class="h-4 w-4" /> REVENUE PULSE (SALES VS PURCHASES)
                            </h3>
                        </div>
                        <div class="panel-body p-6 flex-1">
                            <Line :data="trendChartData" :options="commonOptions" />
                        </div>
                    </div>

                    <!-- Allocation Hub (Stock by Type) -->
                    <div class="hud-panel flex flex-col h-[400px]">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-300 tracking-widest uppercase">
                                <CubeIcon class="h-4 w-4" /> RESOURCE ALLOCATION
                            </h3>
                        </div>
                        <div class="panel-body p-6 flex-1 flex flex-row items-center justify-center gap-8 relative">
                            <div class="w-1/2 h-[250px] relative">
                                <Doughnut 
                                    :data="stockTypeChartData" 
                                    :options="{ 
                                        ...commonOptions, 
                                        cutout: '75%', 
                                        scales: { x: { display: false }, y: { display: false } },
                                        plugins: { legend: { display: false } } 
                                    }" 
                                />
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center pointer-events-none w-full">
                                    <p class="text-[10px] text-slate-500 tracking-[0.2em] uppercase font-bold">TOTAL UNITS</p>
                                    <p class="text-3xl font-black text-white glow-text leading-tight">{{ formatNumber(Object.values(stockByType).reduce((a,b) => a+b, 0)) }}</p>
                                </div>
                            </div>
                            <div class="w-1/2 space-y-4">
                                <div v-for="(val, key, index) in stockByType" :key="key" class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2.5 h-2.5 rounded-sm" :style="{ backgroundColor: stockTypeChartData.datasets[0].backgroundColor[index] }"></div>
                                        <span class="text-[10px] font-mono text-slate-400 uppercase tracking-widest">{{ key.replace('_', ' ') }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-white">{{ formatNumber(val) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Operational Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Sales Transmissions -->
                    <div class="hud-panel h-[480px] flex flex-col">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-300 tracking-widest uppercase">
                                <ShoppingCartIcon class="h-4 w-4" /> RECENT SALES TRANSMISSIONS
                            </h3>
                            <Link href="/sales/orders" class="text-[10px] text-cyan-400 hover:underline uppercase tracking-widest font-bold">LOGS</Link>
                        </div>
                        <div class="panel-body panel-body-scroll p-0 pb-8 flex-1 overflow-y-auto overflow-x-hidden">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-white/10 bg-white/5">
                                        <th class="p-3">Reference</th>
                                        <th class="p-3">Entity</th>
                                        <th class="p-3">Volume</th>
                                        <th class="p-3">Phase</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <tr v-for="so in recentSOs" :key="so.id" class="hover:bg-cyan-500/5 transition-colors group">
                                        <td class="p-3 text-xs text-cyan-400 font-mono font-bold">{{ so.number }}</td>
                                        <td class="p-3 text-[10px] text-slate-300 uppercase truncate max-w-[150px]">{{ so.customer }}</td>
                                        <td class="p-3 text-xs font-bold text-white">{{ formatNumber(so.total) }}</td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest border" :class="getStatusColor(so.status)">
                                                {{ so.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Production Fabrication Progress -->
                    <div class="hud-panel h-[480px] flex flex-col">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-300 tracking-widest uppercase">
                                <CpuChipIcon class="h-4 w-4" /> FABRICATION PIPELINE
                            </h3>
                            <Link href="/manufacturing/work-orders" class="text-[10px] text-emerald-400 hover:underline uppercase tracking-widest font-bold">FACTORY</Link>
                        </div>
                        <div class="panel-body panel-body-scroll p-4 pb-12 space-y-4 flex-1 overflow-y-auto">
                            <div v-for="wo in activeWorkOrders" :key="wo.id" class="p-4 bg-white/5 border border-white/5 rounded-xl group hover:border-emerald-500/30 transition-all">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <p class="text-xs font-black text-white tracking-widest group-hover:text-emerald-400 transition-colors">{{ wo.number }}</p>
                                        <p class="text-[10px] text-slate-500 uppercase">{{ wo.product }}</p>
                                    </div>
                                    <span class="text-[8px] font-black uppercase tracking-tighter px-1.5 py-0.5 rounded border" :class="getStatusColor(wo.status)">
                                        {{ wo.status }}
                                    </span>
                                </div>
                                <div class="space-y-1.5">
                                    <div class="flex justify-between text-[10px] font-mono">
                                        <span class="text-slate-500 uppercase">SYNC_PROGRESS</span>
                                        <span class="text-emerald-400">{{ formatNumber(wo.qty_produced) }} / {{ formatNumber(wo.qty_planned) }} UNITS</span>
                                    </div>
                                    <div class="h-2 bg-slate-900 rounded-full border border-white/5 overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-emerald-600 to-emerald-400 shadow-[0_0_10px_rgba(16,185,129,0.3)] transition-all duration-1000" :style="{ width: `${wo.progress}%` }"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- Spacer for scroll comfort -->
                            <div class="h-10"></div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top Customer Influence -->
                    <div class="hud-panel flex flex-col h-[400px]">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-indigo-300 tracking-widest uppercase">
                                <UserGroupIcon class="h-4 w-4" /> CUSTOMER INFLUENCE MAP (YTD)
                            </h3>
                        </div>
                        <div class="panel-body p-6 flex-1">
                            <Bar :data="topCustomersChartData" :options="{ ...commonOptions, indexAxis: 'y' }" />
                        </div>
                    </div>

                    <!-- Critical Resource Anomalies -->
                    <div class="hud-panel h-[400px] flex flex-col overflow-hidden">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-rose-300 tracking-widest uppercase">
                                <ExclamationTriangleIcon class="h-4 w-4" /> CRITICAL RESOURCE ANOMALIES (LOW STOCK)
                            </h3>
                            <Link href="/inventory/stocks" class="text-[10px] text-rose-400 hover:underline uppercase tracking-widest font-bold">INVENTORY</Link>
                        </div>
                        <div class="panel-body panel-body-scroll p-0 pb-12 flex-1 overflow-y-auto">
                            <div class="grid grid-cols-1 divide-y divide-white/5">
                                <div v-for="item in lowStockProducts" :key="item.id" class="p-4 flex items-center justify-between hover:bg-rose-500/5 transition-colors group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-lg bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-500 group-hover:scale-110 transition-transform">
                                            <CubeIcon class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-black text-white group-hover:text-rose-400 transition-colors uppercase tracking-tight">{{ item.name }}</p>
                                            <p class="text-[10px] text-slate-500 font-mono">{{ item.sku }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-black text-rose-500">{{ item.stock }} / {{ item.min_stock }}</p>
                                        <p class="text-[8px] text-slate-600 uppercase tracking-tighter">{{ item.unit }} REMAINING</p>
                                    </div>
                                </div>
                                <div v-if="lowStockProducts.length === 0" class="p-12 text-center">
                                    <p class="text-xs text-emerald-500/50 uppercase tracking-[.2em]">ALL SYSTEMS OPTIMAL - NO ANOMALIES DETECTED</p>
                                </div>
                            </div>
                            <!-- Spacer for scroll comfort -->
                            <div class="h-10"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Jersey+10&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap');

.font-mono {
    font-family: 'Space Mono', monospace;
}

/* Custom HUD Scrollbar */
.panel-body-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
}

.panel-body-scroll::-webkit-scrollbar {
    width: 4px;
}

.panel-body-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.panel-body-scroll::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.panel-body-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Background Effects */
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(34, 211, 238, 0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(34, 211, 238, 0.1) 1px, transparent 1px);
    background-size: 40px 40px;
    transform: perspective(500px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 20s linear infinite;
    transform-origin: top;
}

@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 40px; }
}

@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(-20px, 20px); }
}

/* HUD Styling */
.hud-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hud-card:hover {
    transform: translateY(-5px) scale(1.02);
    filter: drop-shadow(0 0 15px rgba(34, 211, 238, 0.3));
}

.hud-panel {
    background: rgba(10, 10, 22, 0.8);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    box-shadow: 0 0 40px rgba(0, 0, 0, 0.8);
    transition: border-color 0.3s ease;
}
.hud-panel:hover {
    border-color: rgba(34, 211, 238, 0.3);
}

/* Text Effects */
.glow-text {
    text-shadow: 0 0 10px currentColor;
}

/* Custom Scrollbar for modern feel */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: #050510;
}
::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
    background: #334155;
}
</style>
