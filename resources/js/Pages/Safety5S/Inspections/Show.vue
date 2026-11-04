<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ShieldCheckIcon,
    ArrowLeftIcon,
    BuildingOffice2Icon,
    CalendarIcon,
    UserCircleIcon,
    CheckCircleIcon,
    XCircleIcon,
    MinusCircleIcon,
    ExclamationTriangleIcon,
    WrenchScrewdriverIcon,
    ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    inspection: {
        type: Object,
        required: true,
    },
});

const getRiskBadge = (risk) => {
    switch (risk) {
        case 'Safe':
            return 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800';
        case 'Caution':
            return 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800';
        case 'Danger':
            return 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800';
        default:
            return 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    }
};
</script>

<template>
    <AppLayout :title="`Inspection ${inspection.inspection_number}`">
        <Head :title="`Inspection ${inspection.inspection_number} - Safety & 5S`" />

        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('safety-5s.inspections.index')"
                        class="p-1.5 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition duration-150"
                    >
                        <ArrowLeftIcon class="size-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-semibold text-blue-600 dark:text-blue-400">
                                {{ inspection.inspection_number }}
                            </span>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border"
                                :class="getRiskBadge(inspection.risk_status)"
                            >
                                {{ inspection.risk_status }}
                            </span>
                        </div>
                        <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white mt-0.5">
                            {{ inspection.zone?.name }} Safety Inspection
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('safety-5s.inspections.create')"
                        class="px-3.5 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition duration-150"
                    >
                        New Inspection
                    </Link>
                </div>
            </div>

            <!-- Overview Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Building / Plant</span>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white mt-1">
                        {{ inspection.building?.name }} ({{ inspection.building?.code }})
                    </p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Factory Bay & Area PIC</span>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white mt-1">
                        {{ inspection.zone?.name }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        PIC: {{ inspection.zone?.pic_employee?.full_name || 'Assigned Zone PIC' }}
                    </p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Inspection Date</span>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white mt-1">
                        {{ inspection.inspection_date }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        By {{ inspection.inspector?.name }}
                    </p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-slate-200 dark:border-slate-800 shadow-sm text-center">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Overall Compliance</span>
                    <p
                        class="text-2xl font-bold mt-1 tabular-nums"
                        :class="inspection.overall_score >= 90 ? 'text-emerald-600 dark:text-emerald-400' : (inspection.overall_score >= 75 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400')"
                    >
                        {{ inspection.overall_score }}%
                    </p>
                </div>
            </div>

            <!-- Checkpoints Detail -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="py-3 px-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/50 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                        Safety Checkpoints Audit Details
                    </h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        {{ inspection.items?.length || 0 }} Items Evaluated
                    </span>
                </div>

                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    <div
                        v-for="item in inspection.items"
                        :key="item.id"
                        class="p-4 sm:p-5 flex flex-col md:flex-row md:items-start justify-between gap-4"
                        :class="item.status === 'Fail' ? 'bg-rose-50/20 dark:bg-rose-950/10' : ''"
                    >
                        <div class="space-y-1.5 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ item.category }}
                                </span>
                                <span
                                    v-if="item.status === 'Pass'"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300"
                                >
                                    <CheckCircleIcon class="size-3.5" />
                                    <span>Pass</span>
                                </span>
                                <span
                                    v-else-if="item.status === 'Fail'"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300"
                                >
                                    <XCircleIcon class="size-3.5" />
                                    <span>Fail (Non-Compliant)</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    <MinusCircleIcon class="size-3.5" />
                                    <span>N/A</span>
                                </span>
                            </div>

                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                {{ item.checkpoint_title }}
                            </h3>

                            <p v-if="item.remarks" class="text-xs text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/30 p-2.5 rounded border border-rose-200 dark:border-rose-900/50">
                                <strong>Finding Note:</strong> {{ item.remarks }}
                            </p>
                        </div>

                        <!-- Photo Evidence -->
                        <div v-if="item.photo_path" class="shrink-0">
                            <a :href="`/storage/${item.photo_path}`" target="_blank" class="block rounded border border-slate-200 dark:border-slate-700 overflow-hidden hover:opacity-90">
                                <img :src="`/storage/${item.photo_path}`" class="w-24 h-24 object-cover" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Linked CAPA Action Items -->
            <div v-if="inspection.findings && inspection.findings.length > 0" class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <WrenchScrewdriverIcon class="size-5 text-rose-600 dark:text-rose-400" />
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                            Generated Corrective Action Items (CAPA)
                        </h2>
                    </div>
                    <Link
                        :href="route('safety-5s.corrective-actions.index')"
                        class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline"
                    >
                        <span>Open CAPA Board</span>
                        <ArrowTopRightOnSquareIcon class="size-3.5" />
                    </Link>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="finding in inspection.findings"
                        :key="finding.id"
                        class="p-4 rounded-md border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300">
                                    {{ finding.risk_level || 'High' }} Risk
                                </span>
                                <span class="text-xs text-slate-500">
                                    Target Due: {{ finding.due_date || 'Immediate' }}
                                </span>
                            </div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white mt-1">
                                {{ finding.description }}
                            </p>
                        </div>

                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold"
                            :class="finding.status === 'Closed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : (finding.status === 'Submitted' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300')"
                        >
                            Status: {{ finding.status }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- General Notes -->
            <div v-if="inspection.notes" class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-slate-200 dark:border-slate-800 shadow-sm text-sm text-slate-700 dark:text-slate-300">
                <span class="font-semibold text-slate-900 dark:text-white block mb-1">Inspector Notes:</span>
                <p>{{ inspection.notes }}</p>
            </div>
        </div>
    </AppLayout>
</template>
