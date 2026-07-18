<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    TrophyIcon,
    UserGroupIcon,
    ClockIcon,
    ArrowTrendingUpIcon,
    StarIcon,
    ExclamationTriangleIcon,
    PrinterIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
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

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    suppliers: Array,
    stats: Object,
    gradeDistribution: Object,
    spendBySupplier: Array,
    onTimeTrend: Array,
    period: Number,
});

const selectedPeriod = ref(props.period);

const changePeriod = () => {
    router.get('/purchasing/supplier-scorecard', { months: selectedPeriod.value }, {
        preserveState: true,
        replace: true
    });
};

const printScorecard = () => {
    window.print();
};

// --- Clock ---
const time = ref('');
let timer;
onMounted(() => {
    const tick = () => { time.value = new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' }); };
    tick(); timer = setInterval(tick, 1000);
});
onUnmounted(() => clearInterval(timer));

// --- Chart shared ---
const chartOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#94a3b8', font: { family: 'Space Mono', size: 10 } } },
        tooltip: {
            backgroundColor: 'rgba(5,5,16,0.9)', titleColor: '#10b981', bodyColor: '#e2e8f0',
            borderColor: '#10b981', borderWidth: 1, padding: 12,
            titleFont: { family: 'Space Mono', weight: 'bold' }, bodyFont: { family: 'Space Mono' }, displayColors: false,
        },
    },
    scales: {
        x: { grid: { color: 'rgba(16,185,129,0.1)', drawBorder: false }, ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } } },
        y: { grid: { color: 'rgba(16,185,129,0.1)', drawBorder: false }, ticks: { color: '#64748b', font: { family: 'Space Mono', size: 10 } } },
    },
};

// On-time trend line
const onTimeTrendData = computed(() => ({
    labels: props.onTimeTrend.map(t => t.month),
    datasets: [{
        label: 'On-Time %',
        data: props.onTimeTrend.map(t => t.rate),
        borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.2)',
        borderWidth: 2, tension: 0.4, fill: true, pointRadius: 4, pointBackgroundColor: '#10b981',
    }],
}));

// Spend bar
const spendData = computed(() => ({
    labels: props.spendBySupplier.map(s => s.name),
    datasets: [{
        label: 'Total Spend',
        data: props.spendBySupplier.map(s => s.spend),
        backgroundColor: [
            'rgba(16,185,129,0.6)', 'rgba(245,158,11,0.6)', 'rgba(6,182,212,0.6)',
            'rgba(168,85,247,0.6)', 'rgba(239,68,68,0.6)', 'rgba(59,130,246,0.6)',
            'rgba(236,72,153,0.6)', 'rgba(234,179,8,0.6)', 'rgba(14,165,233,0.6)',
            'rgba(249,115,22,0.6)',
        ],
        borderWidth: 0, borderRadius: 4,
    }],
}));

// Grade Doughnut
const gradeColors = { A: '#10b981', B: '#06b6d4', C: '#f59e0b', D: '#f97316', F: '#ef4444' };
const gradeData = computed(() => ({
    labels: Object.keys(props.gradeDistribution),
    datasets: [{
        data: Object.values(props.gradeDistribution),
        backgroundColor: Object.keys(props.gradeDistribution).map(g => gradeColors[g] || '#64748b'),
        borderWidth: 0,
    }],
}));

// --- Helpers ---
const gradeStyle = (grade) => ({
    A: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40',
    B: 'bg-cyan-500/20 text-cyan-400 border-cyan-500/40',
    C: 'bg-amber-500/20 text-amber-400 border-amber-500/40',
    D: 'bg-orange-500/20 text-orange-400 border-orange-500/40',
    F: 'bg-rose-500/20 text-rose-400 border-rose-500/40',
}[grade] || 'bg-slate-500/20 text-slate-400');

const scoreColor = (score) => {
    if (score >= 90) return 'text-emerald-400';
    if (score >= 80) return 'text-cyan-400';
    if (score >= 70) return 'text-amber-400';
    if (score >= 60) return 'text-orange-400';
    return 'text-rose-400';
};

const scoreBarWidth = (score) => Math.min(100, Math.max(0, score)) + '%';
const scoreBarColor = (score) => {
    if (score >= 90) return 'bg-emerald-500';
    if (score >= 80) return 'bg-cyan-500';
    if (score >= 70) return 'bg-amber-500';
    if (score >= 60) return 'bg-orange-500';
    return 'bg-rose-500';
};
</script>

<template>
    <AppLayout :render-header="false">
        <Head title="Supplier Scorecard" />

        <div class="min-h-screen bg-[#050510] text-white font-mono relative overflow-hidden">
            <div class="fixed inset-0 pointer-events-none z-0 no-print">
                <div class="absolute inset-0 perspective-grid opacity-30"></div>
                <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-[200px] animate-float"></div>
                <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-cyan-500/10 rounded-full blur-[200px] animate-float-delayed"></div>
            </div>

            <div class="relative z-10 p-4 lg:p-6 max-w-[1600px] mx-auto space-y-6">
                <!-- Print Only Header (PT Jidoka Official Format) -->
                <div class="hidden print-only mb-6 border-b-2 border-slate-900 pb-4">
                    <table class="w-full mb-4">
                        <tr>
                            <td width="60%" style="vertical-align: top; text-align: left; border: none !important; padding: 0 !important;">
                                <div style="margin-bottom: 10px; display: flex; align-items: center; gap: 15px;">
                                    <img src="/images/jri-official-logo.png" alt="logo" style="height: 55px; float: left; margin-right: 15px;">
                                    <div>
                                        <div style="color: #E21E26; font-weight: 900; font-style: italic; font-size: 24pt; letter-spacing: -1px; margin: 0; line-height: 1; font-family: Arial, sans-serif;">jidoka</div>
                                        <div style="color: #003680; font-weight: 800; font-size: 11pt; margin: -2px 0 5px 0; font-family: Arial, sans-serif;">PT. JIDOKA RESULT INDONESIA</div>
                                    </div>
                                    <div style="clear: both;"></div>
                                </div>
                                <div style="font-size: 9pt; line-height: 1.3; font-family: Arial, sans-serif; color: #333;">
                                    Kawasan Industri JABABEKA I<br>
                                    Jl. Jababeka II Blok C No. 19 L<br>
                                    Pasirgombong, Cikarang Utara, Bekasi 17530 Jawa Barat<br>
                                    Telp : +62 21 89383915, Fax. : +62 21 89383915<br>
                                    e_mail : purchasing@jidoka.co.id
                                </div>
                            </td>
                            <td width="40%" style="vertical-align: top; text-align: right; padding-top: 10px; border: none !important; padding: 0 !important;">
                                <div style="color: #000080; font-size: 18pt; font-weight: 900; font-style: italic; font-family: Arial, sans-serif; margin-bottom: 10px;">SUPPLIER SCORECARD</div>
                                <table style="float: right; font-size: 9pt; border-collapse: collapse; font-family: Arial, sans-serif;">
                                    <tr>
                                        <td style="font-weight: bold; padding: 2px 5px; text-align: left; border: none !important;">Cetak Tanggal</td>
                                        <td style="padding: 2px 5px; border: none !important;">:</td>
                                        <td style="padding: 2px 5px; text-align: left; border: none !important;">{{ new Date().toLocaleDateString('id-ID') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: bold; padding: 2px 5px; text-align: left; border: none !important;">Periode</td>
                                        <td style="padding: 2px 5px; border: none !important;">:</td>
                                        <td style="padding: 2px 5px; text-align: left; border: none !important;">{{ period }} Bulan Terakhir</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- KPI Summary Table (Print Only) -->
                <div class="hidden print-only mb-6">
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-family: Arial, sans-serif;">
                        <tr style="background: #f1f5f9; border: 1px solid #cbd5e1;">
                            <th style="padding: 8px 12px; text-align: left; font-size: 9pt; border: 1px solid #cbd5e1; font-weight: bold; color: #334155;">RINGKASAN OPERASIONAL</th>
                            <th style="padding: 8px 12px; text-align: right; font-size: 9pt; border: 1px solid #cbd5e1; font-weight: bold; color: #334155;">NILAI / HASIL</th>
                        </tr>
                        <tr style="border: 1px solid #cbd5e1;">
                            <td style="padding: 8px 12px; font-size: 9pt; border: 1px solid #cbd5e1; color: #0f172a;">Active Suppliers</td>
                            <td style="padding: 8px 12px; font-size: 9pt; text-align: right; font-weight: bold; border: 1px solid #cbd5e1; color: #0f172a;">{{ stats.total_suppliers }}</td>
                        </tr>
                        <tr style="border: 1px solid #cbd5e1;">
                            <td style="padding: 8px 12px; font-size: 9pt; border: 1px solid #cbd5e1; color: #0f172a;">Avg On-Time Delivery Rate</td>
                            <td style="padding: 8px 12px; font-size: 9pt; text-align: right; font-weight: bold; border: 1px solid #cbd5e1; color: #0f172a;">{{ stats.avg_on_time }}%</td>
                        </tr>
                        <tr style="border: 1px solid #cbd5e1;">
                            <td style="padding: 8px 12px; font-size: 9pt; border: 1px solid #cbd5e1; color: #0f172a;">Avg Score</td>
                            <td style="padding: 8px 12px; font-size: 9pt; text-align: right; font-weight: bold; border: 1px solid #cbd5e1; color: #0f172a;">{{ stats.avg_score }}</td>
                        </tr>
                        <tr style="border: 1px solid #cbd5e1;">
                            <td style="padding: 8px 12px; font-size: 9pt; border: 1px solid #cbd5e1; color: #0f172a;">Top Supplier</td>
                            <td style="padding: 8px 12px; font-size: 9pt; text-align: right; font-weight: bold; border: 1px solid #cbd5e1; color: #0f172a;">{{ stats.top_supplier }} (Score: {{ stats.top_score }})</td>
                        </tr>
                    </table>
                </div>

                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/5 pb-4 no-print">
                    <div>
                        <h1 class="text-2xl font-black tracking-wider text-emerald-400 uppercase flex items-center gap-3">
                            <TrophyIcon class="h-7 w-7" />
                            Supplier Scorecard
                        </h1>
                        <p class="text-xs text-slate-500 tracking-[0.3em] uppercase mt-1">VENDOR PERFORMANCE ANALYTICS</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Dropdown Period -->
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-400 font-mono tracking-wider uppercase">Period:</span>
                            <select
                                v-model="selectedPeriod"
                                @change="changePeriod"
                                class="bg-[#0a0a16] border border-white/10 text-white rounded-xl px-3 py-1.5 text-xs font-mono focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                            >
                                <option :value="1">1 Month</option>
                                <option :value="3">3 Months</option>
                                <option :value="6">6 Months</option>
                                <option :value="12">12 Months</option>
                                <option :value="24">24 Months</option>
                            </select>
                        </div>

                        <!-- Print Button -->
                        <button
                            @click="printScorecard"
                            class="flex items-center gap-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20 px-4 py-1.5 text-xs font-bold text-emerald-400 hover:bg-emerald-500 hover:text-black transition-colors uppercase tracking-wider font-mono"
                        >
                            <PrinterIcon class="h-4 w-4" />
                            Print Resume
                        </button>
                        
                        <div class="text-right hidden xl:block">
                            <p class="text-2xl font-bold font-mono text-white/20 tracking-wider">{{ time }}</p>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 kpi-container-print no-print">
                    <div class="hud-card bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <UserGroupIcon class="h-4 w-4 text-emerald-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Active Suppliers</span>
                        </div>
                        <p class="text-3xl font-black text-emerald-400 glow-text">{{ stats.total_suppliers }}</p>
                    </div>
                    <div class="hud-card bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <ClockIcon class="h-4 w-4 text-cyan-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Avg On-Time</span>
                        </div>
                        <p class="text-3xl font-black" :class="stats.avg_on_time >= 80 ? 'text-cyan-400' : 'text-amber-400'">
                            {{ stats.avg_on_time }}%
                        </p>
                    </div>
                    <div class="hud-card bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <StarIcon class="h-4 w-4 text-amber-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Avg Score</span>
                        </div>
                        <p class="text-3xl font-black" :class="scoreColor(stats.avg_score)">{{ stats.avg_score }}</p>
                    </div>
                    <div class="hud-card bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-4 lg:col-span-2 kpi-span-2-print">
                        <div class="flex items-center gap-2 mb-2">
                            <TrophyIcon class="h-4 w-4 text-yellow-400" />
                            <span class="text-[10px] text-slate-500 tracking-[0.15em] uppercase">Top Supplier</span>
                        </div>
                        <p class="text-lg font-black text-yellow-400 truncate">{{ stats.top_supplier }}</p>
                        <p class="text-[10px] text-slate-500 mt-0.5">Score: {{ stats.top_score }}</p>
                    </div>
                </div>

                <!-- Scorecard Table -->
                <div class="hud-panel">
                    <div class="panel-header p-4 border-b border-white/5 bg-emerald-500/5 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-400 tracking-widest uppercase">
                            <StarIcon class="h-4 w-4 no-print" />
                            Supplier Rankings
                            <span class="ml-2 bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full text-[10px] no-print">{{ suppliers.length }}</span>
                        </h3>
                    </div>
                    <div class="panel-body p-0 overflow-auto max-h-[30vh]">
                        <table class="w-full text-left border-collapse print-table">
                            <thead class="sticky top-0 z-10">
                                <tr class="text-[10px] text-slate-500 font-bold uppercase tracking-wider border-b border-white/10 bg-[#0a0a16]">
                                    <th class="p-3 w-8">#</th>
                                    <th class="p-3">Supplier</th>
                                    <th class="p-3 text-center">Score</th>
                                    <th class="p-3 text-center">Grade</th>
                                    <th class="p-3 text-center">On-Time %</th>
                                    <th class="p-3 text-center">Return %</th>
                                    <th class="p-3 text-center">Avg Days</th>
                                    <th class="p-3 text-right">POs</th>
                                    <th class="p-3 text-right">Spend</th>
                                    <th class="p-3 text-center no-print">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr
                                    v-for="(s, idx) in suppliers"
                                    :key="s.id"
                                    class="hover:bg-white/5 transition-colors group"
                                >
                                    <td class="p-3 text-xs font-mono border-l-2 border-transparent group-hover:border-emerald-500 transition-colors"
                                        :class="idx < 3 ? 'text-yellow-400 font-black' : 'text-slate-600'">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="p-3">
                                        <p class="text-xs font-bold text-white truncate max-w-[200px]">{{ s.name }}</p>
                                        <p class="text-[10px] text-slate-600 font-mono">{{ s.code }}</p>
                                    </td>
                                    <td class="p-3">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="text-sm font-black" :class="scoreColor(s.overall_score)">{{ s.overall_score }}</span>
                                            <div class="w-16 bg-white/5 rounded-full h-1.5 overflow-hidden">
                                                <div class="h-full rounded-full transition-all" :class="scoreBarColor(s.overall_score)" :style="{ width: scoreBarWidth(s.overall_score) }"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg border text-sm font-black" :class="gradeStyle(s.grade)">
                                            {{ s.grade }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="text-xs font-mono" :class="s.on_time_rate !== null ? (s.on_time_rate >= 80 ? 'text-emerald-400' : s.on_time_rate >= 60 ? 'text-amber-400' : 'text-rose-400') : 'text-slate-600'">
                                            {{ s.on_time_rate !== null ? s.on_time_rate + '%' : '—' }}
                                        </span>
                                        <p class="text-[9px] text-slate-600">{{ s.on_time_count }}✓ / {{ s.late_count }}✗</p>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="text-xs font-mono" :class="s.return_rate <= 2 ? 'text-emerald-400' : s.return_rate <= 5 ? 'text-amber-400' : 'text-rose-400'">
                                            {{ s.return_rate }}%
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="text-xs font-mono" :class="s.avg_fulfillment !== null ? (s.avg_fulfillment <= 7 ? 'text-emerald-400' : s.avg_fulfillment <= 14 ? 'text-amber-400' : 'text-rose-400') : 'text-slate-600'">
                                            {{ s.avg_fulfillment !== null ? s.avg_fulfillment + 'd' : '—' }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right text-xs text-slate-400 font-mono">{{ s.total_pos }}</td>
                                    <td class="p-3 text-right text-xs text-slate-400 font-mono">{{ formatCurrency(s.total_spend) }}</td>
                                    <td class="p-3 text-center no-print">
                                        <Link
                                            :href="route('purchasing.suppliers.show', s.id)"
                                            class="px-3 py-1 text-[10px] bg-emerald-500/20 text-emerald-400 rounded-lg hover:bg-emerald-500 hover:text-black transition-colors uppercase tracking-wider font-bold"
                                        >Detail</Link>
                                    </td>
                                </tr>
                                <tr v-if="suppliers.length === 0">
                                    <td colspan="10" class="p-8 text-center text-slate-500 text-xs uppercase tracking-wider">No supplier data available</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 no-print">
                    <!-- On-Time Trend -->
                    <div class="hud-panel">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-300 tracking-widest uppercase">
                                <ArrowTrendingUpIcon class="h-4 w-4" /> On-Time Trend
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[280px]">
                            <Line :data="onTimeTrendData" :options="{ ...chartOpts, plugins: { legend: { display: false } }, scales: { ...chartOpts.scales, y: { ...chartOpts.scales.y, min: 0, max: 100 } } }" />
                        </div>
                    </div>

                    <!-- Spend Distribution -->
                    <div class="hud-panel">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-amber-300 tracking-widest uppercase">
                                <StarIcon class="h-4 w-4" /> Top Spend
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[280px]">
                            <Bar :data="spendData" :options="{ ...chartOpts, indexAxis: 'y', plugins: { legend: { display: false } } }" />
                        </div>
                    </div>

                    <!-- Grade Distribution -->
                    <div class="hud-panel">
                        <div class="panel-header p-4 border-b border-white/5 bg-white/5">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-300 tracking-widest uppercase">
                                <TrophyIcon class="h-4 w-4" /> Grade Distribution
                            </h3>
                        </div>
                        <div class="panel-body p-4 h-[280px] flex items-center justify-center">
                            <div class="relative w-56 h-56" v-if="Object.keys(gradeDistribution).length > 0">
                                <Doughnut :data="gradeData" :options="{ responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', font: { family: 'Space Mono', size: 10 }, padding: 16 } } } }" />
                            </div>
                            <p v-else class="text-slate-500 text-xs uppercase tracking-wider">No grade data</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap');

.font-mono { font-family: 'Space Mono', monospace; }

.perspective-grid {
    background-image:
        linear-gradient(to right, rgba(16, 185, 129, 0.08) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(16, 185, 129, 0.08) 1px, transparent 1px);
    background-size: 40px 40px;
    transform: perspective(500px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 20s linear infinite;
    transform-origin: top;
}

@keyframes grid-move { 0% { background-position: 0 0; } 100% { background-position: 0 40px; } }
@keyframes float { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(-20px, 20px); } }
@keyframes float-delayed { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(20px, -20px); } }

.animate-float { animation: float 15s ease-in-out infinite; }
.animate-float-delayed { animation: float-delayed 18s ease-in-out infinite; }

.hud-card { transition: all 0.3s ease; }
.hud-card:hover { transform: translateY(-5px); filter: drop-shadow(0 0 10px rgba(16, 185, 129, 0.2)); }

.hud-panel {
    background: rgba(10, 10, 22, 0.6);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
    overflow: hidden;
}

.glow-text { text-shadow: 0 0 10px currentColor; }

@media print {
    .no-print {
        display: none !important;
    }
    .print-only {
        display: block !important;
    }
    /* Reset styles for printing */
    body, #app, .min-h-screen, .bg-\[\#050510\] {
        background: #ffffff !important;
        color: #000000 !important;
        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    }
    
    /* Override AppLayout sidebar padding to use full width */
    .lg\:pl-64, .lg\:pl-20 {
        padding-left: 0 !important;
    }

    .relative, .min-h-screen, .max-w-\[1600px\], .space-y-6 {
        background: transparent !important;
        color: #000000 !important;
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        backdrop-filter: none !important;
    }

    /* KPI Grid for Print */
    .kpi-container-print {
        display: grid !important;
        grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
        gap: 16px !important;
    }
    
    .kpi-span-2-print {
        grid-column: span 2 / span 2 !important;
    }
    
    .hud-card {
        background: transparent !important;
        border: 1px solid #cbd5e1 !important;
        color: #0f172a !important;
        box-shadow: none !important;
    }

    .hud-card span, .hud-card p, .hud-card h3 {
        color: #0f172a !important;
    }

    .glow-text {
        text-shadow: none !important;
    }

    /* Make rankings table scrollable panel fully visible on print */
    .panel-body {
        max-height: none !important;
        overflow: visible !important;
    }

    /* Make rankings table print beautifully */
    .hud-panel {
        background: transparent !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: none !important;
        backdrop-filter: none !important;
        border-radius: 8px !important;
        overflow: visible !important;
        max-height: none !important;
    }
    
    .panel-header {
        background: #f8fafc !important;
        border-bottom: 1px solid #cbd5e1 !important;
        color: #0f172a !important;
    }
    
    .panel-header h3 {
        color: #0f172a !important;
    }
    
    table {
        border-collapse: collapse !important;
        width: 100% !important;
    }
    
    thead tr {
        background-color: #f1f5f9 !important;
        border-bottom: 2px solid #cbd5e1 !important;
    }
    
    th {
        color: #334155 !important;
        font-weight: bold !important;
    }
    
    tbody tr {
        border-bottom: 1px solid #e2e8f0 !important;
    }
    
    td, th {
        padding: 8px 12px !important;
        color: #0f172a !important;
        background: transparent !important;
    }
    
    /* Score indicator colors for printing */
    .text-emerald-400 { color: #059669 !important; }
    .text-cyan-400 { color: #0891b2 !important; }
    .text-amber-400 { color: #d97706 !important; }
    .text-orange-400 { color: #ea580c !important; }
    .text-rose-400 { color: #dc2626 !important; }
    .text-slate-600 { color: #475569 !important; }
    
    /* Custom grades badge style for print */
    .grade-badge-print {
        border: 1px solid #cbd5e1 !important;
        border-radius: 4px !important;
        padding: 2px 6px !important;
        background: transparent !important;
        color: #0f172a !important;
    }
}
</style>

<style>
@media print {
    /* Globally override AppLayout paddings and margins for full width printing */
    body, #app, .min-h-screen, .bg-\[\#050510\] {
        background: #ffffff !important;
        color: #000000 !important;
        font-family: Arial, sans-serif !important;
    }

    .lg\:pl-64, .lg\:pl-20, .pl-64, .pl-20 {
        padding-left: 0 !important;
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    .px-4, .sm\:px-6, .lg\:px-8 {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .pt-8, .pb-24, .lg\:pb-8 {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

    /* Hide sidebar, top navbar, PWA banners, and other default UI globally */
    aside, header, nav, .print-hidden, .no-print, [role="navigation"], [role="banner"] {
        display: none !important;
    }

    /* Reset width of layout containers to 100% */
    .max-w-\[1600px\], .relative.z-10 {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
    }

    /* Make rankings table scrollable panel fully visible and compact on print */
    .panel-body {
        max-height: none !important;
        overflow: visible !important;
    }
    
    .hud-panel {
        background: transparent !important;
        border: 1px solid #000000 !important;
        box-shadow: none !important;
        backdrop-filter: none !important;
        border-radius: 4px !important;
        margin-top: 10px !important;
    }
    
    .panel-header {
        border-bottom: 1px solid #000000 !important;
        padding: 8px 10px !important;
        background: #f8fafc !important;
    }
    
    .panel-header h3 {
        font-size: 10pt !important;
        font-weight: bold !important;
        color: #000000 !important;
    }

    /* Compact Print Table Styles */
    table.print-table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    
    table.print-table th, 
    table.print-table td {
        border: 1px solid #000000 !important;
        padding: 4px 6px !important; /* Compact padding */
        color: #000000 !important;
        background: transparent !important;
        vertical-align: middle !important;
    }

    table.print-table th {
        background-color: #f1f5f9 !important;
        font-size: 8pt !important;
        font-weight: bold !important;
        text-transform: uppercase !important;
    }
    
    table.print-table td,
    table.print-table td p,
    table.print-table td span,
    table.print-table td div {
        font-size: 7.5pt !important; /* Clean compact size */
        color: #000000 !important;
        line-height: 1.2 !important;
        margin: 0 !important;
    }

    /* Grade badge compact styling for print */
    table.print-table .grade-badge-print {
        border: 1px solid #000000 !important;
        border-radius: 4px !important;
        padding: 1px 4px !important;
        font-size: 7.5pt !important;
        width: 20px !important;
        height: 20px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: transparent !important;
        color: #0f172a !important;
    }

    /* Override colors to print-friendly dark colors */
    .text-emerald-400, .text-emerald-500 { color: #047857 !important; font-weight: bold !important; }
    .text-cyan-400, .text-cyan-500 { color: #0369a1 !important; font-weight: bold !important; }
    .text-amber-400, .text-amber-500 { color: #b45309 !important; font-weight: bold !important; }
    .text-orange-400, .text-orange-500 { color: #c2410c !important; font-weight: bold !important; }
    .text-rose-400, .text-rose-500 { color: #b91c1c !important; font-weight: bold !important; }
    .text-slate-600 { color: #334155 !important; }
    .text-yellow-400 { color: #a16207 !important; font-weight: bold !important; }
    
    .glow-text {
        text-shadow: none !important;
    }
}
</style>
