<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ShieldCheckIcon,
    ShieldExclamationIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    BuildingOffice2Icon,
    CalendarIcon,
    EyeIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    inspections: {
        type: Object,
        default: () => ({ data: [] }),
    },
    buildings: {
        type: Array,
        default: () => [],
    },
    zones: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search || '');
const buildingFilter = ref(props.filters.building_id || '');
const riskFilter = ref(props.filters.risk_status || '');

const applyFilters = () => {
    router.get(route('safety-5s.inspections.index'), {
        search: search.value || undefined,
        building_id: buildingFilter.value || undefined,
        risk_status: riskFilter.value || undefined,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    search.value = '';
    buildingFilter.value = '';
    riskFilter.value = '';
    router.get(route('safety-5s.inspections.index'));
};

const getRiskBadge = (risk) => {
    switch (risk) {
        case 'Safe':
            return {
                bg: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
                icon: CheckCircleIcon,
                label: 'Safe (Compliant)',
            };
        case 'Caution':
            return {
                bg: 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
                icon: ExclamationTriangleIcon,
                label: 'Caution',
            };
        case 'Danger':
            return {
                bg: 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800',
                icon: XCircleIcon,
                label: 'Danger / Non-Compliant',
            };
        default:
            return {
                bg: 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                icon: ShieldCheckIcon,
                label: risk || 'Unknown',
            };
    }
};
</script>

<template>
    <AppLayout title="Safety Inspections">
        <Head title="Safety Inspections - Safety & 5S" />

        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                            Operational Safety
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">5-Domain K3 Audit</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
                        Safety Inspections
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        Workplace safety checklist audits across factory bays. Non-compliant items automatically flow into Unified CAPA.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('safety-5s.inspections.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500 rounded-md shadow-sm transition duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                    >
                        <PlusIcon class="size-4" />
                        <span>New Inspection</span>
                    </Link>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Inspections</span>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                        {{ stats.total_inspections || 0 }}
                    </p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Avg Compliance</span>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                        {{ stats.avg_score || 100 }}%
                    </p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-emerald-200 dark:border-emerald-900/50 shadow-sm bg-emerald-50/20 dark:bg-emerald-950/10">
                    <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Safe Areas</span>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                        {{ stats.safe_count || 0 }}
                    </p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-amber-200 dark:border-amber-900/50 shadow-sm bg-amber-50/20 dark:bg-amber-950/10">
                    <span class="text-xs font-medium text-amber-700 dark:text-amber-400">Caution Areas</span>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                        {{ stats.caution_count || 0 }}
                    </p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-rose-200 dark:border-rose-900/50 shadow-sm bg-rose-50/20 dark:bg-rose-950/10">
                    <span class="text-xs font-medium text-rose-700 dark:text-rose-400">Danger / Hazards</span>
                    <p class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">
                        {{ stats.danger_count || 0 }}
                    </p>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row items-center gap-3">
                <div class="relative w-full md:w-72">
                    <MagnifyingGlassIcon class="size-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search inspection # or zone..."
                        class="w-full pl-9 pr-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white placeholder-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="w-full md:w-56">
                    <select
                        v-model="buildingFilter"
                        class="w-full px-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">All Buildings</option>
                        <option v-for="b in buildings" :key="b.id" :value="b.id">
                            {{ b.name }} ({{ b.code }})
                        </option>
                    </select>
                </div>

                <div class="w-full md:w-44">
                    <select
                        v-model="riskFilter"
                        class="w-full px-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">All Statuses</option>
                        <option value="Safe">Safe (≥90%)</option>
                        <option value="Caution">Caution (75-89%)</option>
                        <option value="Danger">Danger (&lt;75%)</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 ml-auto w-full md:w-auto">
                    <button
                        type="button"
                        class="px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-md transition duration-150"
                        @click="resetFilters"
                    >
                        Reset
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition duration-150"
                        @click="applyFilters"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- Inspections Table -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/50 text-slate-600 dark:text-slate-300 font-semibold">
                                <th class="py-3 px-4">Inspection #</th>
                                <th class="py-3 px-4">Building & Zone</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Inspector</th>
                                <th class="py-3 px-4 text-center">Score</th>
                                <th class="py-3 px-4 text-center">Risk Status</th>
                                <th class="py-3 px-4 text-center">Findings</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr
                                v-for="item in inspections.data"
                                :key="item.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition duration-150"
                            >
                                <td class="py-3 px-4 font-mono font-medium text-slate-900 dark:text-white">
                                    {{ item.inspection_number }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-900 dark:text-white">
                                        {{ item.zone?.name || 'All Zones' }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ item.building?.name }} ({{ item.zone?.code }})
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <CalendarIcon class="size-3.5 text-slate-400" />
                                        <span>{{ item.inspection_date }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300">
                                    {{ item.inspector?.name || 'Auditor' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold tabular-nums"
                                        :class="item.overall_score >= 90 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : (item.overall_score >= 75 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300')"
                                    >
                                        {{ item.overall_score }}%
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                        :class="getRiskBadge(item.risk_status).bg"
                                    >
                                        <component :is="getRiskBadge(item.risk_status).icon" class="size-3.5" />
                                        {{ item.risk_status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        v-if="item.findings_count > 0"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300"
                                    >
                                        {{ item.findings_count }} CAPA
                                    </span>
                                    <span v-else class="text-xs text-slate-400">0</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <Link
                                        :href="route('safety-5s.inspections.show', item.id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded transition duration-150"
                                    >
                                        <EyeIcon class="size-3.5" />
                                        <span>Details</span>
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="!inspections.data || inspections.data.length === 0">
                                <td colspan="8" class="py-12 text-center">
                                    <ShieldCheckIcon class="size-10 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">No Safety Inspections Found</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        Click "New Inspection" to start conducting workplace safety compliance checks.
                                    </p>
                                    <div class="mt-4">
                                        <Link
                                            :href="route('safety-5s.inspections.create')"
                                            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition duration-150"
                                        >
                                            <PlusIcon class="size-3.5" />
                                            <span>Start First Inspection</span>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="inspections.links && inspections.links.length > 3"
                    class="py-3 px-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500"
                >
                    <span>Showing {{ inspections.from || 0 }} to {{ inspections.to || 0 }} of {{ inspections.total }} inspections</span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, idx) in inspections.links"
                            :key="idx"
                            :href="link.url || '#'"
                            :class="[
                                'px-2.5 py-1 rounded text-xs transition duration-150',
                                link.active
                                    ? 'bg-blue-600 text-white font-medium'
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
