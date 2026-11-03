<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CheckCircleIcon,
    PlusIcon,
    EyeIcon,
    TrashIcon,
    CalendarIcon,
    BuildingOffice2Icon,
    UserIcon,
    ExclamationTriangleIcon,
    SparklesIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    sessions: {
        type: Object,
        default: () => ({ data: [] }),
    },
    buildings: {
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

const search = ref(props.filters.search || '');
const buildingFilter = ref(props.filters.building_id || '');
const gradeFilter = ref(props.filters.grade || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const applyFilters = () => {
    router.get(route('audit-5s.audits.index'), {
        search: search.value || undefined,
        building_id: buildingFilter.value || undefined,
        grade: gradeFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    search.value = '';
    buildingFilter.value = '';
    gradeFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    router.get(route('audit-5s.audits.index'));
};

const deleteSession = (session) => {
    if (!confirm(`Are you sure you want to delete audit session "${session.session_code}"?`)) return;

    router.delete(route('audit-5s.audits.destroy', session.id), {
        preserveScroll: true,
    });
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
    <AppLayout title="5S Audit Execution & History">
        <Head title="5S Audit Execution & History" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800">
                            Execution & Logs
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            5S Audit Execution & History
                        </h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Complete repository of executed 5S workplace audits, pillar evaluations, and scoring records.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('audit-5s.audits.create')"
                        class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition"
                    >
                        <SparklesIcon class="size-4" />
                        <span>Conduct New Audit</span>
                    </Link>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Audits</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ stats.total_audits }}</p>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20">
                    <p class="text-xs font-medium text-emerald-800 dark:text-emerald-300">Grade A (Excellent)</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-emerald-700 dark:text-emerald-400">{{ stats.grade_a_count }}</p>
                </div>
                <div class="rounded-lg border border-rose-200 bg-rose-50/50 p-4 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/20">
                    <p class="text-xs font-medium text-rose-800 dark:text-rose-300">Grade D (Red Alert)</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-rose-700 dark:text-rose-400">{{ stats.grade_d_count }}</p>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50/50 p-4 shadow-sm dark:border-blue-900/60 dark:bg-blue-950/20">
                    <p class="text-xs font-medium text-blue-800 dark:text-blue-300">Average Plant Score</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-blue-700 dark:text-blue-400">{{ stats.average_score }}%</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-5">
                    <div class="relative sm:col-span-2">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search code, zone, auditor..."
                            @keyup.enter="applyFilters"
                            class="w-full rounded-md border-slate-300 pl-9 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                        />
                    </div>

                    <div>
                        <select
                            v-model="buildingFilter"
                            @change="applyFilters"
                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                        >
                            <option value="">All Buildings</option>
                            <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>

                    <div>
                        <select
                            v-model="gradeFilter"
                            @change="applyFilters"
                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                        >
                            <option value="">All Grades</option>
                            <option value="A">Grade A (≥90%)</option>
                            <option value="B">Grade B (80-89%)</option>
                            <option value="C">Grade C (70-79%)</option>
                            <option value="D">Grade D (<70%)</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="applyFilters"
                            class="flex-1 rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 transition"
                        >
                            Filter
                        </button>
                        <button
                            type="button"
                            @click="resetFilters"
                            class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 transition"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-left">Session Code</th>
                                <th class="px-4 py-3 text-left">Audit Date</th>
                                <th class="px-4 py-3 text-left">Zone & Facility</th>
                                <th class="px-4 py-3 text-left">Auditor</th>
                                <th class="px-4 py-3 text-center">5S Scores (1S-5S)</th>
                                <th class="px-4 py-3 text-center">Total Score</th>
                                <th class="px-4 py-3 text-center">Grade</th>
                                <th class="px-4 py-3 text-center">Findings</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr
                                v-for="s in sessions.data"
                                :key="s.id"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
                            >
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <Link :href="route('audit-5s.audits.show', s.id)" class="font-mono font-bold text-blue-600 hover:underline dark:text-blue-400">
                                        {{ s.session_code }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-slate-700 dark:text-slate-300">
                                    {{ new Date(s.audit_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ s.zone?.name }}</p>
                                    <p class="text-xs text-slate-500 font-mono">{{ s.zone?.code }} • {{ s.zone?.building?.name }}</p>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-slate-700 dark:text-slate-300">
                                    <span class="inline-flex items-center gap-1.5">
                                        <UserIcon class="size-3.5 text-slate-400" />
                                        {{ s.auditor?.name || 'Unknown' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-center text-xs font-mono text-slate-600 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1">
                                        <span title="1S Sort">{{ s.score_sort }}%</span> /
                                        <span title="2S Set In Order">{{ s.score_set_in_order }}%</span> /
                                        <span title="3S Shine">{{ s.score_shine }}%</span> /
                                        <span title="4S Standardize">{{ s.score_standardize }}%</span> /
                                        <span title="5S Sustain">{{ s.score_sustain }}%</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <span class="font-mono font-bold text-base text-slate-900 dark:text-white">
                                        {{ s.total_score }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <span :class="['inline-flex items-center justify-center size-7 rounded-full text-xs font-black border', getGradeBadge(s.grade)]">
                                        {{ s.grade }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <span
                                        v-if="s.findings_count > 0"
                                        class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800"
                                    >
                                        <ExclamationTriangleIcon class="size-3" />
                                        {{ s.findings_count }} Findings
                                    </span>
                                    <span v-else class="text-xs text-slate-400">0</span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-right space-x-1.5">
                                    <Link
                                        :href="route('audit-5s.audits.show', s.id)"
                                        class="inline-flex items-center gap-1 rounded-md bg-white border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300"
                                    >
                                        <EyeIcon class="size-3.5" />
                                        <span>Details</span>
                                    </Link>
                                    <button
                                        type="button"
                                        @click="deleteSession(s)"
                                        class="rounded p-1 text-rose-500 hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-slate-800"
                                        title="Delete Audit Session"
                                    >
                                        <TrashIcon class="size-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="sessions.data.length === 0">
                                <td colspan="9" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
                                    <CheckCircleIcon class="mx-auto size-10 text-slate-400 mb-2" />
                                    <p class="font-semibold text-slate-700 dark:text-slate-300">No audit records found</p>
                                    <p class="text-xs mt-1">Conduct a new audit from the button above or schedule planned rounds.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
