<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
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
import {
    PresentationChartBarIcon,
    ShieldCheckIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    MapPinIcon,
    CalendarIcon,
    ArrowTrendingUpIcon,
} from '@heroicons/vue/24/outline';

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
    kpis: {
        type: Object,
        default: () => ({}),
    },
    dailyTrend: {
        type: Array,
        default: () => [],
    },
    topMissed: {
        type: Array,
        default: () => [],
    },
    statusDistribution: {
        type: Object,
        default: () => ({ labels: [], data: [] }),
    },
    incidentsByZone: {
        type: Array,
        default: () => [],
    },
    period: {
        type: Number,
        default: 14,
    },
});

const changePeriod = (days) => {
    router.get('/security-patrol/analytics', { period: days }, { preserveState: true });
};

// 1. Line Chart Data: Daily Compliance
const complianceChartData = computed(() => {
    return {
        labels: props.dailyTrend.map((d) => d.date),
        datasets: [
            {
                label: 'Tingkat Kepatuhan (%)',
                data: props.dailyTrend.map((d) => d.compliance_rate),
                borderColor: '#10b981', // emerald-500
                backgroundColor: 'rgba(16, 185, 129, 0.12)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6,
            },
        ],
    };
});

const complianceChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: {
            min: 0,
            max: 100,
            ticks: {
                callback: (value) => `${value}%`,
            },
            grid: { color: 'rgba(148, 163, 184, 0.1)' },
        },
        x: {
            grid: { display: false },
        },
    },
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (context) => `Kepatuhan: ${context.parsed.y}%`,
            },
        },
    },
};

// 2. Bar Chart Data: Top Missed Points
const missedChartData = computed(() => {
    return {
        labels: props.topMissed.map((m) => m.code),
        datasets: [
            {
                label: 'Kali Terlewat',
                data: props.topMissed.map((m) => m.missed_count),
                backgroundColor: '#f43f5e', // rose-500
                borderRadius: 4,
            },
        ],
    };
});

const missedChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: {
            beginAtZero: true,
            ticks: { stepSize: 1 },
            grid: { color: 'rgba(148, 163, 184, 0.1)' },
        },
        x: {
            grid: { display: false },
        },
    },
    plugins: {
        legend: { display: false },
    },
};

// 3. Doughnut Chart Data: Status Distribution
const statusChartData = computed(() => {
    return {
        labels: props.statusDistribution.labels || [],
        datasets: [
            {
                data: props.statusDistribution.data || [],
                backgroundColor: ['#10b981', '#f59e0b', '#3b82f6'],
                borderWidth: 2,
                borderColor: '#ffffff',
            },
        ],
    };
});

const statusChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: { boxWidth: 12, font: { size: 11 } },
        },
    },
    cutout: '68%',
};
</script>

<template>
    <AppLayout title="Grafik & Analitik Patroli">
        <Head title="Grafik & Analitik - Security Patrol" />

        <div class="space-y-6">
            <!-- Header & Period Filter -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300">
                            Evaluasi Keamanan
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Grafik & Analitik Patroli
                        </h1>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Visualisasi tingkat kepatuhan patroli, titik pos rawan terlewat, dan statistik temuan keamanan.
                    </p>
                </div>

                <!-- Time Filter Pills -->
                <div class="inline-flex p-1 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm text-xs">
                    <button
                        @click="changePeriod(7)"
                        :class="[
                            'px-3 py-1.5 rounded-md font-semibold transition',
                            period === 7 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                        ]"
                    >
                        7 Hari Terakhir
                    </button>
                    <button
                        @click="changePeriod(14)"
                        :class="[
                            'px-3 py-1.5 rounded-md font-semibold transition',
                            period === 14 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                        ]"
                    >
                        14 Hari Terakhir
                    </button>
                    <button
                        @click="changePeriod(30)"
                        :class="[
                            'px-3 py-1.5 rounded-md font-semibold transition',
                            period === 30 ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                        ]"
                    >
                        30 Hari Terakhir
                    </button>
                </div>
            </div>

            <!-- KPI Metric Bar -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Tingkat Kepatuhan</span>
                        <ArrowTrendingUpIcon class="size-4 text-emerald-500" />
                    </div>
                    <p class="text-3xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400 mt-2 tabular-nums">
                        {{ kpis.compliance_rate || 0 }}%
                    </p>
                    <span class="text-xs text-slate-500 mt-1 block">Rasio sesi 100% lengkap</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Rata-rata Durasi</span>
                        <ClockIcon class="size-4 text-blue-500" />
                    </div>
                    <p class="text-3xl font-extrabold tracking-tight text-blue-600 dark:text-blue-400 mt-2 tabular-nums">
                        {{ kpis.avg_duration_minutes || 0 }} <span class="text-sm font-semibold text-slate-500">Menit</span>
                    </p>
                    <span class="text-xs text-slate-500 mt-1 block">Waktu keliling per putaran</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Titik Terlewat (Missed)</span>
                        <MapPinIcon class="size-4 text-rose-500" />
                    </div>
                    <p class="text-3xl font-extrabold tracking-tight text-rose-600 dark:text-rose-400 mt-2 tabular-nums">
                        {{ kpis.total_missed_points || 0 }}
                    </p>
                    <span class="text-xs text-slate-500 mt-1 block">Akumulasi titik diskip</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Total Temuan Insiden</span>
                        <ExclamationTriangleIcon class="size-4 text-amber-500" />
                    </div>
                    <p class="text-3xl font-extrabold tracking-tight text-amber-600 dark:text-amber-400 mt-2 tabular-nums">
                        {{ kpis.total_incidents || 0 }}
                    </p>
                    <span class="text-xs text-slate-500 mt-1 block">Diteruskan ke tiket GA</span>
                </div>
            </div>

            <!-- Charts Grid 1: Compliance Trend Line Chart & Status Doughnut -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Line Chart (2 cols) -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Tren Kepatuhan Patroli Harian (%)
                            </h3>
                            <p class="text-xs text-slate-500">Persentase kelengkapan pemeriksaan pos per hari.</p>
                        </div>
                    </div>
                    <div class="h-64 w-full">
                        <Line :data="complianceChartData" :options="complianceChartOptions" />
                    </div>
                </div>

                <!-- Doughnut Chart (1 col) -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-5 shadow-sm space-y-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                            Distribusi Status Patroli
                        </h3>
                        <p class="text-xs text-slate-500">Perbandingan hasil sesi patroli periode ini.</p>
                    </div>
                    <div class="h-64 w-full flex items-center justify-center">
                        <Doughnut :data="statusChartData" :options="statusChartOptions" />
                    </div>
                </div>
            </div>

            <!-- Charts Grid 2: Top Missed Points & Incidents by Zone -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Missed Points Bar Chart -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Pos Paling Sering Terlewat (Top Missed)
                            </h3>
                            <p class="text-xs text-slate-500">Titik checkpoint yang paling jarang dikunjungi satpam.</p>
                        </div>
                    </div>

                    <div v-if="topMissed.length === 0" class="text-center py-10 text-xs text-slate-400">
                        Tidak ada titik terlewat pada periode ini. Kepatuhan sempurna!
                    </div>
                    <div v-else class="space-y-4">
                        <div class="h-48 w-full">
                            <Bar :data="missedChartData" :options="missedChartOptions" />
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800 border-t border-slate-100 dark:border-slate-800 pt-2">
                            <div v-for="m in topMissed" :key="m.id" class="flex items-center justify-between py-1.5 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ m.code }}</span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ m.name }}</span>
                                </div>
                                <span class="font-bold text-rose-600 tabular-nums">{{ m.missed_count }}x diskip</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Incident Distribution by Area Zone -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-5 shadow-sm space-y-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                            Sebaran Temuan Insiden per Zona Area
                        </h3>
                        <p class="text-xs text-slate-500">Area pabrik yang paling sering ditemukan potensi bahaya/kerusakan.</p>
                    </div>

                    <div v-if="incidentsByZone.length === 0" class="text-center py-12 text-xs text-slate-400">
                        Tidak ada laporan insiden pada periode ini. Seluruh pos aman terkendali.
                    </div>
                    <div v-else class="space-y-3 pt-2">
                        <div
                            v-for="zone in incidentsByZone"
                            :key="zone.area_zone"
                            class="p-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex items-center justify-between text-xs"
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="size-2 rounded-full bg-amber-500"></span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ zone.area_zone || 'Area Umum' }}</span>
                            </div>
                            <span class="font-bold text-slate-800 dark:text-slate-200 tabular-nums">
                                {{ zone.count }} Laporan Insiden
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
