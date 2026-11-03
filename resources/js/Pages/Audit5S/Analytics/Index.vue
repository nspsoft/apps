<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PresentationChartBarIcon,
    ArrowDownTrayIcon,
    CalendarIcon,
    BuildingOffice2Icon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ShieldExclamationIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    pillarAverages: {
        type: Object,
        default: () => ({
            'Sort': 0,
            'Set In Order': 0,
            'Shine': 0,
            'Standardize': 0,
            'Sustain': 0,
        }),
    },
    monthlyScores: {
        type: Array,
        default: () => [],
    },
    capaStats: {
        type: Object,
        default: () => ({}),
    },
    gradeCounts: {
        type: Object,
        default: () => ({ A: 0, B: 0, C: 0, D: 0 }),
    },
    buildings: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ year: 2026, building_id: null }),
    },
});

const selectedYear = ref(props.filters.year || new Date().getFullYear());
const selectedBuilding = ref(props.filters.building_id || '');

const applyFilters = () => {
    router.get(route('audit-5s.analytics.index'), {
        year: selectedYear.value,
        building_id: selectedBuilding.value || undefined,
    }, { preserveState: true, replace: true });
};

const exportExcel = () => {
    window.location.href = route('audit-5s.analytics.export-excel', {
        year: selectedYear.value,
        building_id: selectedBuilding.value || undefined,
    });
};

// SVG Radar Math
// 5 axes: angles = [ -90, -18, 54, 126, 198 ] degrees
const radarCenter = { x: 160, y: 150 };
const radarRadius = 110;

const pillars = [
    { key: 'Sort', label: '1S Sort (Ringkas)' },
    { key: 'Set In Order', label: '2S Set (Rapi)' },
    { key: 'Shine', label: '3S Shine (Resik)' },
    { key: 'Standardize', label: '4S Standard (Rawat)' },
    { key: 'Sustain', label: '5S Sustain (Rajin)' },
];

const getRadarPoint = (angleDeg, valuePercent) => {
    const angleRad = (angleDeg * Math.PI) / 180;
    const r = (valuePercent / 100) * radarRadius;
    return {
        x: radarCenter.x + r * Math.cos(angleRad),
        y: radarCenter.y + r * Math.sin(angleRad),
    };
};

const radarPolygonPoints = computed(() => {
    const angles = [-90, -18, 54, 126, 198];
    const points = pillars.map((p, idx) => {
        const val = props.pillarAverages[p.key] || 0;
        const pt = getRadarPoint(angles[idx], val);
        return `${pt.x},${pt.y}`;
    });
    return points.join(' ');
});

const getWebPoints = (levelPercent) => {
    const angles = [-90, -18, 54, 126, 198];
    const points = angles.map(a => {
        const pt = getRadarPoint(a, levelPercent);
        return `${pt.x},${pt.y}`;
    });
    return points.join(' ');
};

// Max monthly score for scaling
const maxMonthlyScore = 100;
</script>

<template>
    <AppLayout title="5S Analytics & Executive Reports">
        <Head title="5S Analytics & Reports" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800">
                            Executive Intelligence
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            5S Analytics & Executive Reports
                        </h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Spider pillar evaluations, longitudinal compliance trends, and CAPA resolution metrics.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="exportExcel"
                        class="inline-flex items-center gap-1.5 rounded-md bg-white border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 shadow-sm transition"
                    >
                        <ArrowDownTrayIcon class="size-4" />
                        <span>Export CSV Report</span>
                    </button>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <CalendarIcon class="size-4 text-slate-400" />
                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Year:</span>
                    <select
                        v-model="selectedYear"
                        @change="applyFilters"
                        class="rounded-md border-slate-300 text-xs shadow-sm dark:bg-slate-800 dark:border-slate-700"
                    >
                        <option :value="2024">2024</option>
                        <option :value="2025">2025</option>
                        <option :value="2026">2026</option>
                        <option :value="2027">2027</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <BuildingOffice2Icon class="size-4 text-slate-400" />
                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Building:</span>
                    <select
                        v-model="selectedBuilding"
                        @change="applyFilters"
                        class="rounded-md border-slate-300 text-xs shadow-sm dark:bg-slate-800 dark:border-slate-700"
                    >
                        <option value="">All Buildings</option>
                        <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Grade & Closure Metrics -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20">
                    <p class="text-xs font-medium text-emerald-800 dark:text-emerald-300">Grade A Audits</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-emerald-700 dark:text-emerald-400">{{ gradeCounts.A }}</p>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50/50 p-4 shadow-sm dark:border-blue-900/60 dark:bg-blue-950/20">
                    <p class="text-xs font-medium text-blue-800 dark:text-blue-300">Grade B Audits</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-blue-700 dark:text-blue-400">{{ gradeCounts.B }}</p>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-4 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/20">
                    <p class="text-xs font-medium text-amber-800 dark:text-amber-300">Grade C Audits</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-amber-700 dark:text-amber-400">{{ gradeCounts.C }}</p>
                </div>
                <div class="rounded-lg border border-rose-200 bg-rose-50/50 p-4 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/20">
                    <p class="text-xs font-medium text-rose-800 dark:text-rose-300">Grade D (Alerts)</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-rose-700 dark:text-rose-400">{{ gradeCounts.D }}</p>
                </div>
            </div>

            <!-- Charts Row: Radar Spider Chart + Monthly Trend Bar -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 5S Spider / Radar Chart -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                                    5-Pillar Spider / Radar Evaluation
                                </h3>
                                <p class="text-xs text-slate-500">Plant-wide average normalized score per pillar</p>
                            </div>
                        </div>

                        <!-- SVG Spider Graph -->
                        <div class="flex items-center justify-center p-2">
                            <svg viewBox="0 0 320 300" class="w-full max-w-sm overflow-visible">
                                <!-- Concentric Background Webs (20%, 40%, 60%, 80%, 100%) -->
                                <polygon
                                    v-for="lvl in [20, 40, 60, 80, 100]"
                                    :key="lvl"
                                    :points="getWebPoints(lvl)"
                                    fill="none"
                                    stroke="currentColor"
                                    class="text-slate-200 dark:text-slate-800 stroke-[1]"
                                />

                                <!-- Axis Lines -->
                                <line
                                    v-for="(ang, i) in [-90, -18, 54, 126, 198]"
                                    :key="i"
                                    :x1="radarCenter.x"
                                    :y1="radarCenter.y"
                                    :x2="getRadarPoint(ang, 100).x"
                                    :y2="getRadarPoint(ang, 100).y"
                                    stroke="currentColor"
                                    class="text-slate-300 dark:text-slate-700 stroke-[1]"
                                />

                                <!-- Data Polygon -->
                                <polygon
                                    :points="radarPolygonPoints"
                                    class="fill-blue-500/30 stroke-blue-600 dark:stroke-blue-400 stroke-2"
                                />

                                <!-- Data Dots -->
                                <circle
                                    v-for="(ang, i) in [-90, -18, 54, 126, 198]"
                                    :key="`dot-${i}`"
                                    :cx="getRadarPoint(ang, pillarAverages[pillars[i].key] || 0).x"
                                    :cy="getRadarPoint(ang, pillarAverages[pillars[i].key] || 0).y"
                                    r="4"
                                    class="fill-blue-600 dark:fill-blue-400"
                                />

                                <!-- Labels -->
                                <text :x="radarCenter.x" y="24" text-anchor="middle" class="text-[10px] font-bold fill-slate-700 dark:fill-slate-300">
                                    1S: Sort ({{ pillarAverages['Sort'] }}%)
                                </text>
                                <text :x="getRadarPoint(-18, 118).x" :y="getRadarPoint(-18, 118).y" text-anchor="start" class="text-[10px] font-bold fill-slate-700 dark:fill-slate-300">
                                    2S: Set ({{ pillarAverages['Set In Order'] }}%)
                                </text>
                                <text :x="getRadarPoint(54, 122).x" :y="getRadarPoint(54, 122).y + 6" text-anchor="start" class="text-[10px] font-bold fill-slate-700 dark:fill-slate-300">
                                    3S: Shine ({{ pillarAverages['Shine'] }}%)
                                </text>
                                <text :x="getRadarPoint(126, 122).x" :y="getRadarPoint(126, 122).y + 6" text-anchor="end" class="text-[10px] font-bold fill-slate-700 dark:fill-slate-300">
                                    4S: Standard ({{ pillarAverages['Standardize'] }}%)
                                </text>
                                <text :x="getRadarPoint(198, 118).x" :y="getRadarPoint(198, 118).y" text-anchor="end" class="text-[10px] font-bold fill-slate-700 dark:fill-slate-300">
                                    5S: Sustain ({{ pillarAverages['Sustain'] }}%)
                                </text>
                            </svg>
                        </div>
                    </div>

                    <!-- Pillar Pill Values -->
                    <div class="grid grid-cols-5 gap-2 pt-4 border-t border-slate-100 dark:border-slate-800 text-center">
                        <div v-for="p in pillars" :key="p.key" class="rounded bg-slate-50 p-2 dark:bg-slate-800/40">
                            <p class="text-[10px] font-bold text-slate-500 truncate">{{ p.key }}</p>
                            <p class="text-xs font-black font-mono text-slate-900 dark:text-white">{{ pillarAverages[p.key] }}%</p>
                        </div>
                    </div>
                </div>

                <!-- Monthly 12-Month Performance Trend -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                                    Monthly 5S Score Progression ({{ selectedYear }})
                                </h3>
                                <p class="text-xs text-slate-500">Average overall score by calendar month</p>
                            </div>
                        </div>

                        <!-- Bar Chart -->
                        <div class="pt-6">
                            <div class="flex items-end justify-between gap-1.5 h-48 border-b border-slate-200 dark:border-slate-800 pb-2">
                                <div
                                    v-for="m in monthlyScores"
                                    :key="m.month"
                                    class="flex-1 flex flex-col items-center gap-1 group relative"
                                >
                                    <!-- Tooltip -->
                                    <div class="absolute bottom-full mb-1 hidden group-hover:flex flex-col items-center z-10 pointer-events-none">
                                        <div class="rounded bg-slate-900 px-2 py-1 text-[10px] text-white shadow font-mono">
                                            {{ m.avg_score ? `${m.avg_score}%` : 'No data' }}
                                        </div>
                                    </div>

                                    <!-- Bar -->
                                    <div
                                        class="w-full rounded-t transition-all duration-300"
                                        :style="{
                                            height: m.avg_score ? `${(m.avg_score / maxMonthlyScore) * 160}px` : '4px',
                                        }"
                                        :class="[
                                            m.avg_score
                                                ? m.avg_score >= 85
                                                    ? 'bg-emerald-500 hover:bg-emerald-400'
                                                    : m.avg_score >= 70
                                                    ? 'bg-blue-500 hover:bg-blue-400'
                                                    : 'bg-rose-500 hover:bg-rose-400'
                                                : 'bg-slate-100 dark:bg-slate-800'
                                        ]"
                                    ></div>
                                    <!-- Month Label -->
                                    <span class="text-[10px] font-medium text-slate-500">{{ m.month }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CAPA Closure & Red-Tag Summary -->
                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-3 gap-3 text-center">
                        <div class="rounded bg-slate-50 p-2 dark:bg-slate-800/40">
                            <p class="text-[10px] font-semibold text-slate-500">CAPA Resolution Rate</p>
                            <p class="text-base font-black text-emerald-600">{{ capaStats.closure_rate }}%</p>
                        </div>
                        <div class="rounded bg-slate-50 p-2 dark:bg-slate-800/40">
                            <p class="text-[10px] font-semibold text-slate-500">Open Actions</p>
                            <p class="text-base font-black text-amber-600">{{ capaStats.open }}</p>
                        </div>
                        <div class="rounded bg-slate-50 p-2 dark:bg-slate-800/40">
                            <p class="text-[10px] font-semibold text-slate-500">Red-Tag Items</p>
                            <p class="text-base font-black text-rose-600">{{ capaStats.red_tags }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
