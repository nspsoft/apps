<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CurrencyDollarIcon,
    ShoppingCartIcon,
    UserGroupIcon,
    ArrowTrendingUpIcon,
    BoltIcon,
    RocketLaunchIcon,
    GlobeAsiaAustraliaIcon,
    SparklesIcon,
    CpuChipIcon,
    SignalIcon
} from '@heroicons/vue/24/outline';
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
    Filler
} from 'chart.js';
import { Line, Bar, Doughnut } from 'vue-chartjs';

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

// --- Mock Data ---
const kpis = [
    { label: 'REVENUE', value: '4,521,098', prefix: '¥', change: '+12.5%', icon: CurrencyDollarIcon, color: 'text-cyan-400', shadow: 'shadow-cyan-500/50', border: 'border-cyan-500/30' },
    { label: 'ORDERS', value: '3,245', prefix: '', change: '+8.1%', icon: ShoppingCartIcon, color: 'text-violet-400', shadow: 'shadow-violet-500/50', border: 'border-violet-500/30' },
    { label: 'CUSTOMERS', value: '9,876', prefix: '', change: '+5.2%', icon: UserGroupIcon, color: 'text-fuchsia-400', shadow: 'shadow-fuchsia-500/50', border: 'border-fuchsia-500/30' },
    { label: 'AVG VALUE', value: '35,200', prefix: '¥', change: '-2.4%', icon: ArrowTrendingUpIcon, color: 'text-emerald-400', shadow: 'shadow-emerald-500/50', border: 'border-emerald-500/30' },
];

const recentSales = [
    { id: 'TXN-1024', item: 'QUANTUM CHIP A1', customer: 'CYBER CORP', amount: '¥1,250', status: 'COMPLETED' },
    { id: 'TXN-1025', item: 'HYPER-DRIVE B2', customer: 'ASTRO LOGISTICS', amount: '¥3,400', status: 'PENDING' },
    { id: 'TXN-1026', item: 'NEURAL NET C3', customer: 'AI SYSTEMS', amount: '¥980', status: 'COMPLETED' },
    { id: 'TXN-1027', item: 'PLASMA COIL', customer: 'OMEGA INDUSTRIES', amount: '¥5,600', status: 'PROCESSING' },
    { id: 'TXN-1028', item: 'DARK MATTER CORE', customer: 'VOID ENERGY', amount: '¥12,000', status: 'COMPLETED' },
];

// --- Chart Configs (Neon Glow Style) ---
const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#22d3ee',
            bodyColor: '#e2e8f0',
            borderColor: '#22d3ee',
            borderWidth: 1,
            padding: 12,
            titleFont: { family: 'Courier New', weight: 'bold' },
            bodyFont: { family: 'Courier New' },
            displayColors: false,
            callbacks: {
                label: (ctx) => `Vaue: ${ctx.formattedValue}`
            }
        }
    },
    scales: {
        x: { 
            grid: { color: 'rgba(6, 182, 212, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Courier New', size: 10 } }
        },
        y: { 
            grid: { color: 'rgba(6, 182, 212, 0.1)', drawBorder: false },
            ticks: { color: '#64748b', font: { family: 'Courier New', size: 10 } }
        }
    },
    elements: {
        line: { tension: 0.4, borderWidth: 3 },
        point: { radius: 0, hitRadius: 20, hoverRadius: 6 }
    }
};

const revenueData = {
    labels: Array.from({length: 12}, (_, i) => `Nov ${i+18}`),
    datasets: [{
        label: 'Revenue',
        data: [20, 45, 30, 60, 55, 80, 70, 95, 85, 110, 100, 130],
        borderColor: '#22d3ee', // Cyan
        backgroundColor: (ctx) => {
            const canvas = ctx.chart.ctx;
            const gradient = canvas.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(34, 211, 238, 0.4)');
            gradient.addColorStop(1, 'rgba(34, 211, 238, 0.0)');
            return gradient;
        },
        fill: true,
        pointBackgroundColor: '#000',
        pointBorderColor: '#22d3ee',
        pointBorderWidth: 2,
    }]
};

const funnelData = {
    labels: ['LEADS', 'OPPORTUNITIES', 'PROPOSALS', 'CLOSED WON'],
    datasets: [{
        data: [1258, 624, 403, 183],
        backgroundColor: [
            'rgba(34, 211, 238, 0.8)', // Cyan
            'rgba(168, 85, 247, 0.8)', // Violet
            'rgba(232, 121, 249, 0.8)', // Fuchsia
            'rgba(16, 185, 129, 0.8)', // Emerald
        ],
        barThickness: 20,
        borderRadius: 4,
    }]
};

const productData = {
    labels: ['QUANTUM CHIP', 'HYPER DRIVE', 'NEURAL NET', 'ION THRUSTER'],
    datasets: [{
        data: [40, 25, 20, 15],
        backgroundColor: [
            '#22d3ee',
            '#8b5cf6',
            '#d946ef',
            '#10b981'
        ],
        borderWidth: 0,
        hoverOffset: 10
    }]
};

</script>

<template>
    <Head title="Sales Command" />

    <AppLayout title="Sales Command" :render-header="false">
        <!-- Sci-Fi Container -->
        <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 selection:bg-cyan-500/30">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <!-- Deep Space Gradient -->
                <div class="absolute inset-0 bg-gradient-to-b from-indigo-950/40 to-[#050510]"></div>
                
                <!-- Cyber Grid Animation -->
                <div class="perspective-grid absolute inset-0 opacity-20"></div>
                
                <!-- Nebula Clouds -->
                <div class="absolute top-[-10%] left-[20%] w-[600px] h-[600px] bg-cyan-600/10 blur-[150px] rounded-full animate-float"></div>
                <div class="absolute bottom-[-10%] right-[10%] w-[500px] h-[500px] bg-violet-600/10 blur-[150px] rounded-full animate-float-delayed"></div>
                
                <!-- Particle Dust -->
                <div class="stars"></div>
            </div>

            <!-- HUD Overlay -->
            <div class="relative z-10 p-6 space-y-8">
                
                <!-- Top Bar -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-cyan-500/20 pb-4 backdrop-blur-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-white/5 border border-white/10 rounded text-slate-400 tracking-[0.2em]">SYS.VER.4.0</span>
                            <span class="flex items-center gap-1.5 px-2 py-0.5 text-[10px] bg-emerald-500/10 border border-emerald-500/20 rounded text-emerald-400 tracking-wider animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> ONLINE
                            </span>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-white to-cyan-400 tracking-widest uppercase glow-text">
                            COMMAND CENTER
                        </h1>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden md:block">
                            <p class="text-[10px] text-cyan-500/70 tracking-[0.2em] mb-1">SYSTEM TIME</p>
                            <p class="text-2xl font-bold font-mono text-white glow-text">{{ time }}</p>
                        </div>
                        <div class="h-10 w-px bg-cyan-500/20 rotate-12 hidden md:block"></div>
                        <button class="group relative px-6 py-2 bg-cyan-500/5 hover:bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 uppercase text-xs font-bold tracking-widest transition-all hover:shadow-[0_0_20px_rgba(34,211,238,0.3)] clip-path-slant">
                            <span class="relative z-10 flex items-center gap-2">
                                <BoltIcon class="h-4 w-4" /> Initialize
                            </span>
                            <div class="absolute inset-0 bg-cyan-400/10 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-500"></div>
                        </button>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div 
                        v-for="(kpi, index) in kpis" 
                        :key="index"
                        class="hud-card group"
                        :class="`delay-${index}`"
                    >
                        <div class="hud-content p-6 relative z-10 bg-[#0a0a16]/60 backdrop-blur-xl border border-white/5 flex flex-col h-full rounded-xl overflow-hidden">
                            <!-- HUD Corners -->
                            <div class="absolute top-0 left-0 w-2 h-2 border-t-2 border-l-2 border-cyan-500/50"></div>
                            <div class="absolute top-0 right-0 w-2 h-2 border-t-2 border-r-2 border-cyan-500/50"></div>
                            <div class="absolute bottom-0 left-0 w-2 h-2 border-b-2 border-l-2 border-cyan-500/50"></div>
                            <div class="absolute bottom-0 right-0 w-2 h-2 border-b-2 border-r-2 border-cyan-500/50"></div>
                            
                            <!-- Content -->
                            <div class="flex justify-between items-start mb-4">
                                <p class="text-xs text-slate-400 tracking-[0.2em] uppercase font-bold">{{ kpi.label }}</p>
                                <component :is="kpi.icon" :class="`h-6 w-6 ${kpi.color} drop-shadow-[0_0_5px_rgba(34,211,238,0.5)]`" />
                            </div>
                            
                            <div class="mt-auto">
                                <h3 class="text-3xl font-black text-white mb-2 tracking-tight flex items-baseline gap-1">
                                    <span class="text-base text-slate-500 font-normal">{{ kpi.prefix }}</span>
                                    <span class="glow-text">{{ kpi.value }}</span>
                                </h3>
                                <div class="flex items-center gap-2">
                                    <div :class="`h-1 flex-1 rounded-full bg-slate-800 overflow-hidden`">
                                        <div :class="`h-full ${kpi.color.replace('text-', 'bg-')} w-[70%]`"></div>
                                    </div>
                                    <span :class="`text-[10px] font-bold ${kpi.change.startsWith('+') ? 'text-emerald-400' : 'text-rose-400'}`">{{ kpi.change }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Dashboard Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- Revenue Chart (Large) -->
                    <div class="lg:col-span-8 hud-panel min-h-[400px]">
                        <div class="panel-header flex items-center justify-between p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-300 tracking-widest uppercase">
                                <SignalIcon class="h-4 w-4" /> Revenue Trend (Last 30 Days)
                            </h3>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 text-[10px] bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded font-bold animate-pulse">● LIVE</span>
                                <span class="text-xs text-slate-500">TOTAL: ¥12.1M</span>
                            </div>
                        </div>
                        <div class="panel-body p-4 h-[350px] relative">
                             <!-- Grid overlay -->
                            <div class="absolute inset-4 border border-cyan-500/5 z-0 pointer-events-none grid grid-cols-6 grid-rows-4"></div>
                            <Line :data="revenueData" :options="commonOptions" />
                        </div>
                    </div>

                    <!-- Top Products (Donut) -->
                    <div class="lg:col-span-4 hud-panel flex flex-col">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-violet-300 tracking-widest uppercase">
                                <CpuChipIcon class="h-4 w-4" /> Top Products
                            </h3>
                        </div>
                        <div class="panel-body p-6 flex-1 flex items-center justify-center relative">
                            <div class="w-64 h-64 relative">
                                <Doughnut 
                                    :data="productData" 
                                    :options="{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        cutout: '75%',
                                        plugins: { legend: { display: false } },
                                        elements: { arc: { borderWidth: 0 } }
                                    }" 
                                />
                                <!-- Center Stats -->
                                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                    <span class="text-[10px] text-slate-500 tracking-widest">TOTAL SALES</span>
                                    <span class="text-2xl font-black text-white glow-text">¥8.5M</span>
                                </div>
                                
                                <!-- Decorative Orbital Rings -->
                                <div class="absolute inset-[-10px] border border-cyan-500/20 rounded-full animate-spin-slow border-dashed"></div>
                                <div class="absolute inset-[-20px] border border-violet-500/10 rounded-full animate-reverse-spin"></div>
                            </div>
                            
                            <!-- Floating Labels -->
                             <div class="absolute top-10 left-0 text-[10px] text-cyan-400 font-bold">QUANTUM CHIP<br>A1</div>
                             <div class="absolute top-10 right-0 text-[10px] text-violet-400 font-bold text-right">HYPER-DRIVE<br>B2</div>
                        </div>
                    </div>

                    <!-- Sales Funnel (Bar) -->
                    <div class="lg:col-span-5 hud-panel">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                             <h3 class="flex items-center gap-2 text-sm font-bold text-fuchsia-300 tracking-widest uppercase">
                                <ArrowTrendingUpIcon class="h-4 w-4" /> Sales Funnel
                            </h3>
                        </div>
                        <div class="panel-body p-6">
                            <div class="space-y-4">
                                <div v-for="(label, i) in funnelData.labels" :key="label" class="group">
                                    <div class="flex justify-between text-[10px] mb-1 font-bold">
                                        <span class="text-slate-400">{{ label }}</span>
                                        <span class="text-white">{{ funnelData.datasets[0].data[i] }}</span>
                                    </div>
                                    <div class="h-8 bg-slate-900/50 rounded border border-white/5 relative overflow-hidden">
                                        <div 
                                            class="h-full bg-gradient-to-r from-cyan-500/40 to-violet-500/40 border-r border-white/20 transition-all duration-1000 ease-out group-hover:brightness-125"
                                            :style="{ width: `${(funnelData.datasets[0].data[i] / 1500) * 100}%` }"
                                        ></div>
                                        <!-- Scanline -->
                                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="lg:col-span-7 hud-panel overflow-hidden flex flex-col">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5 flex justify-between items-center">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-300 tracking-widest uppercase">
                                <RocketLaunchIcon class="h-4 w-4" /> Recent Transactions
                            </h3>
                            <div class="flex items-center gap-2 bg-slate-900/50 rounded px-2 py-1 border border-white/10">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <input type="text" placeholder="Scanning..." class="bg-transparent border-none text-[10px] text-white focus:ring-0 p-0 w-24">
                            </div>
                        </div>
                        <div class="panel-body p-2 overflow-y-auto max-h-[300px] flex-1">
                            <table class="w-full text-left border-collapse">
                                <tbody class="divide-y divide-white/5">
                                    <tr 
                                        v-for="txn in recentSales" 
                                        :key="txn.id"
                                        class="group hover:bg-white/5 transition-colors"
                                    >
                                        <td class="p-3 text-[10px] font-mono text-cyan-500/70 border-l-2 border-transparent group-hover:border-cyan-500 transition-colors">
                                            <CpuChipIcon class="h-4 w-4 inline mr-2" />{{ txn.id }}
                                        </td>
                                        <td class="p-3 text-xs font-bold text-white uppercase">{{ txn.item }}</td>
                                        <td class="p-3 text-xs text-slate-400 hidden sm:table-cell">{{ txn.customer }}</td>
                                        <td class="p-3 text-xs font-mono text-right text-emerald-400 shadow-emerald-500/20">{{ txn.amount }}</td>
                                        <td class="p-3 text-right">
                                            <span 
                                                class="px-2 py-0.5 text-[10px] font-bold border rounded uppercase"
                                                :class="{
                                                    'bg-emerald-500/10 text-emerald-400 border-emerald-500/30': txn.status === 'COMPLETED',
                                                    'bg-amber-500/10 text-amber-400 border-amber-500/30': txn.status === 'PENDING',
                                                    'bg-cyan-500/10 text-cyan-400 border-cyan-500/30': txn.status === 'PROCESSING'
                                                }"
                                            >
                                                {{ txn.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
            
            <!-- Bottom Decoration -->
            <div class="fixed bottom-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-cyan-500 to-transparent opacity-50"></div>
        </div>
    </AppLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Jersey+10&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap');

.font-mono {
    font-family: 'Space Mono', monospace;
}

/* Background Effects */
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

@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(-20px, 20px); }
}

@keyframes float-delayed {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(20px, -20px); }
}

/* Card Styling */
.hud-card {
    position: relative;
    transition: all 0.3s ease;
}

.hud-card:hover {
    transform: translateY(-5px);
    filter: drop-shadow(0 0 10px rgba(6, 182, 212, 0.2));
}

.hud-panel {
    background: rgba(10, 10, 22, 0.6);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
    overflow: hidden;
}

/* Text Effects */
.glow-text {
    text-shadow: 0 0 10px currentColor;
}

/* Animations */
.animate-spin-slow {
    animation: spin 10s linear infinite;
}

.animate-reverse-spin {
    animation: spin 15s linear infinite reverse;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.clip-path-slant {
    clip-path: polygon(10% 0, 100% 0, 100% 100%, 0% 100%);
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 4px;
}
::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.2);
}
::-webkit-scrollbar-thumb {
    background: rgba(6, 182, 212, 0.3);
    border-radius: 2px;
}
</style>
