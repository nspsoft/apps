<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    TrophyIcon,
    SparklesIcon,
    BuildingOffice2Icon,
    UserCircleIcon,
    CalendarIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ArrowTrendingUpIcon,
    AdjustmentsHorizontalIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    zones: {
        type: Array,
        default: () => [],
    },
    top3: {
        type: Array,
        default: () => [],
    },
    deptRankings: {
        type: Array,
        default: () => [],
    },
    buildings: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const buildingFilter = ref(props.filters.building_id || '');
const departmentFilter = ref(props.filters.department_id || '');

const applyFilters = () => {
    router.get(route('audit-5s.leaderboard.index'), {
        building_id: buildingFilter.value || undefined,
        department_id: departmentFilter.value || undefined,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    buildingFilter.value = '';
    departmentFilter.value = '';
    router.get(route('audit-5s.leaderboard.index'));
};

const getGradeBadge = (grade) => {
    switch (grade) {
        case 'A': return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
        case 'B': return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800';
        case 'C': return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
        case 'D': return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800';
        default: return 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
    }
};
</script>

<template>
    <AppLayout title="5S Leaderboard & Workplace Rankings">
        <Head title="5S Leaderboard & Rankings" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800">
                            Gamification & Recognition
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            5S Leaderboard & Workplace Rankings
                        </h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Honoring top performing factory bays, encouraging continuous workplace excellence, and highlighting areas requiring support.
                    </p>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Monitored Zones</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ stats.total_zones }}</p>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20">
                    <p class="text-xs font-medium text-emerald-800 dark:text-emerald-300">Green Zones (≥90%)</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-emerald-700 dark:text-emerald-400">{{ stats.green_zones }}</p>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50/50 p-4 shadow-sm dark:border-blue-900/60 dark:bg-blue-950/20">
                    <p class="text-xs font-medium text-blue-800 dark:text-blue-300">Yellow / Solid (70-89%)</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-blue-700 dark:text-blue-400">{{ stats.yellow_zones }}</p>
                </div>
                <div class="rounded-lg border border-rose-200 bg-rose-50/50 p-4 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/20">
                    <p class="text-xs font-medium text-rose-800 dark:text-rose-300">Red Zones (<70%)</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-rose-700 dark:text-rose-400">{{ stats.red_zones }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Pending First Audit</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-slate-500 dark:text-slate-400">{{ stats.unaudited }}</p>
                </div>
            </div>

            <!-- Top 3 Podium Cards -->
            <div v-if="top3.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <!-- 2nd Place (Silver) -->
                <div v-if="top3[1]" class="order-2 md:order-1 rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 size-20 rounded-full bg-slate-100/80 dark:bg-slate-800/80 pointer-events-none"></div>
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                🥈 2ND PLACE
                            </span>
                            <span :class="['inline-flex size-7 items-center justify-center rounded-full text-xs font-black border', getGradeBadge(top3[1].last_grade)]">
                                {{ top3[1].last_grade }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            <div class="size-14 rounded-full overflow-hidden border-2 border-slate-300 bg-white shrink-0 shadow-sm">
                                <img
                                    v-if="top3[1].pic_photo_url"
                                    :src="top3[1].pic_photo_url"
                                    class="size-full object-cover"
                                />
                                <UserCircleIcon v-else class="size-full text-slate-400" />
                            </div>
                            <div>
                                <p class="text-xs font-mono font-bold text-slate-500">{{ top3[1].code }}</p>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                                    {{ top3[1].name }}
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">PIC: {{ top3[1].pic_name || 'PIC' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs text-slate-500">{{ top3[1].department?.name }}</span>
                        <span class="font-mono text-2xl font-black text-slate-800 dark:text-slate-200">
                            {{ top3[1].last_score }}%
                        </span>
                    </div>
                </div>

                <!-- 1st Place (Gold Champion) -->
                <div v-if="top3[0]" class="order-1 md:order-2 rounded-xl border-2 border-amber-400 bg-gradient-to-b from-amber-50/60 to-white p-6 shadow-md dark:border-amber-600 dark:from-amber-950/20 dark:to-slate-900 flex flex-col justify-between relative overflow-hidden -translate-y-1">
                    <div class="absolute -right-4 -top-4 size-24 rounded-full bg-amber-200/50 dark:bg-amber-900/40 pointer-events-none"></div>
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-400 px-3.5 py-1 text-xs font-black text-amber-950 shadow-sm">
                                👑 1ST CHAMPION
                            </span>
                            <span :class="['inline-flex size-8 items-center justify-center rounded-full text-sm font-black border-2', getGradeBadge(top3[0].last_grade)]">
                                {{ top3[0].last_grade }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-center gap-4">
                            <div class="size-16 rounded-full overflow-hidden border-2 border-amber-400 bg-white shrink-0 shadow-md ring-4 ring-amber-200 dark:ring-amber-900/60">
                                <img
                                    v-if="top3[0].pic_photo_url"
                                    :src="top3[0].pic_photo_url"
                                    class="size-full object-cover"
                                />
                                <UserCircleIcon v-else class="size-full text-slate-400" />
                            </div>
                            <div>
                                <p class="text-xs font-mono font-bold text-amber-700 dark:text-amber-400">{{ top3[0].code }}</p>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">
                                    {{ top3[0].name }}
                                </h3>
                                <p class="text-xs text-slate-600 dark:text-slate-300 font-medium mt-0.5">PIC: {{ top3[0].pic_name || 'PIC' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-amber-200 dark:border-amber-900/60 flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-800 dark:text-amber-300">{{ top3[0].department?.name }}</span>
                        <span class="font-mono text-3xl font-black text-amber-600 dark:text-amber-400">
                            {{ top3[0].last_score }}%
                        </span>
                    </div>
                </div>

                <!-- 3rd Place (Bronze) -->
                <div v-if="top3[2]" class="order-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 size-20 rounded-full bg-amber-100/40 dark:bg-amber-950/40 pointer-events-none"></div>
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100/70 px-3 py-1 text-xs font-black text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                🥉 3RD PLACE
                            </span>
                            <span :class="['inline-flex size-7 items-center justify-center rounded-full text-xs font-black border', getGradeBadge(top3[2].last_grade)]">
                                {{ top3[2].last_grade }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            <div class="size-14 rounded-full overflow-hidden border-2 border-amber-300/80 bg-white shrink-0 shadow-sm">
                                <img
                                    v-if="top3[2].pic_photo_url"
                                    :src="top3[2].pic_photo_url"
                                    class="size-full object-cover"
                                />
                                <UserCircleIcon v-else class="size-full text-slate-400" />
                            </div>
                            <div>
                                <p class="text-xs font-mono font-bold text-slate-500">{{ top3[2].code }}</p>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                                    {{ top3[2].name }}
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">PIC: {{ top3[2].pic_name || 'PIC' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs text-slate-500">{{ top3[2].department?.name }}</span>
                        <span class="font-mono text-2xl font-black text-amber-800 dark:text-amber-400">
                            {{ top3[2].last_score }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Main Layout: Table and Department Rankings -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Full Rankings Table (2 Cols) -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Filters bar -->
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex flex-wrap items-center gap-3">
                        <select
                            v-model="buildingFilter"
                            @change="applyFilters"
                            class="rounded-md border-slate-300 text-xs shadow-sm dark:bg-slate-800 dark:border-slate-700"
                        >
                            <option value="">All Buildings</option>
                            <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>

                        <select
                            v-model="departmentFilter"
                            @change="applyFilters"
                            class="rounded-md border-slate-300 text-xs shadow-sm dark:bg-slate-800 dark:border-slate-700"
                        >
                            <option value="">All Departments</option>
                            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>

                        <button
                            type="button"
                            @click="resetFilters"
                            class="text-xs font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400"
                        >
                            Reset
                        </button>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-800">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                                All Zone Performance Standings
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3 text-center">Rank</th>
                                        <th class="px-4 py-3 text-left">Zone & Facility</th>
                                        <th class="px-4 py-3 text-left">Area PIC</th>
                                        <th class="px-4 py-3 text-center">Score</th>
                                        <th class="px-4 py-3 text-center">Grade</th>
                                        <th class="px-4 py-3 text-right">Audit Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    <tr
                                        v-for="(zone, index) in zones"
                                        :key="zone.id"
                                        class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                                    >
                                        <td class="px-4 py-3 whitespace-nowrap text-center font-bold">
                                            <span v-if="index === 0" class="text-amber-500 font-black">#1 🥇</span>
                                            <span v-else-if="index === 1" class="text-slate-400 font-black">#2 🥈</span>
                                            <span v-else-if="index === 2" class="text-amber-700 font-black">#3 🥉</span>
                                            <span v-else class="text-slate-500 font-mono">#{{ index + 1 }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-slate-900 dark:text-white">{{ zone.name }}</p>
                                            <p class="text-xs text-slate-500 font-mono">{{ zone.code }} • {{ zone.building?.name }}</p>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <div class="size-6 rounded-full overflow-hidden border border-slate-200 bg-white shrink-0">
                                                    <img
                                                        v-if="zone.pic_photo_url"
                                                        :src="zone.pic_photo_url"
                                                        class="size-full object-cover"
                                                    />
                                                    <UserCircleIcon v-else class="size-full text-slate-400" />
                                                </div>
                                                <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                                    {{ zone.pic_name || 'Unassigned' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="font-mono font-bold text-slate-900 dark:text-white">
                                                {{ zone.last_score ? `${zone.last_score}%` : '-' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span
                                                v-if="zone.last_grade"
                                                :class="['inline-flex size-6 items-center justify-center rounded-full text-xs font-black border', getGradeBadge(zone.last_grade)]"
                                            >
                                                {{ zone.last_grade }}
                                            </span>
                                            <span v-else class="text-xs text-slate-400">-</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-xs text-slate-500">
                                            {{ zone.last_audit_date ? new Date(zone.last_audit_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) : 'Never' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Department Leaderboard Sidebar (1 Col) -->
                <div class="space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center gap-2 pb-3 border-b border-slate-200 dark:border-slate-800">
                            <ArrowTrendingUpIcon class="size-4 text-blue-600" />
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                                Department Rankings
                            </h3>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div
                                v-for="(dept, dIdx) in deptRankings"
                                :key="dept.department_id"
                                class="rounded-lg border border-slate-100 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-800/40 flex items-center justify-between"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-bold text-slate-400">#{{ dIdx + 1 }}</span>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ dept.department_name }}</p>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">{{ dept.total_zones }} zones monitored</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-lg font-black" :class="dept.avg_score >= 80 ? 'text-emerald-600' : 'text-slate-800 dark:text-white'">
                                        {{ dept.avg_score }}%
                                    </p>
                                </div>
                            </div>

                            <div v-if="deptRankings.length === 0" class="text-center py-6 text-slate-400 text-xs">
                                No department performance records yet.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
