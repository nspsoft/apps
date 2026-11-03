<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    PrinterIcon,
    TrashIcon,
    BuildingOffice2Icon,
    UserCircleIcon,
    CalendarIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    CameraIcon,
    ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    session: {
        type: Object,
        required: true,
    },
});

const getGradeBadge = (grade) => {
    switch (grade) {
        case 'A': return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
        case 'B': return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800';
        case 'C': return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
        case 'D': return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800';
        default: return 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
    }
};

const deleteSession = () => {
    if (!confirm(`Are you sure you want to delete session ${props.session.session_code}?`)) return;
    router.delete(route('audit-5s.audits.destroy', props.session.id));
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <AppLayout title="Audit Session Report">
        <Head :title="`5S Audit - ${session.session_code}`" />

        <div class="space-y-6 max-w-5xl mx-auto print:max-w-full">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between print:hidden">
                <div>
                    <Link
                        :href="route('audit-5s.audits.index')"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 mb-2 transition"
                    >
                        <ArrowLeftIcon class="size-3.5" />
                        <span>Back to Audit History</span>
                    </Link>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800">
                            Audit Certificate & Report
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white font-mono">
                            {{ session.session_code }}
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="printReport"
                        class="inline-flex items-center gap-1.5 rounded-md bg-white border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 shadow-sm transition"
                    >
                        <PrinterIcon class="size-4" />
                        <span>Print Report</span>
                    </button>
                    <button
                        type="button"
                        @click="deleteSession"
                        class="inline-flex items-center gap-1.5 rounded-md bg-rose-50 border border-rose-200 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300 shadow-sm transition"
                    >
                        <TrashIcon class="size-4" />
                        <span>Delete</span>
                    </button>
                </div>
            </div>

            <!-- Score & Zone Highlight Card -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 items-center">
                    <!-- Left: Zone & PIC -->
                    <div class="sm:col-span-2 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-mono font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ session.zone?.code }}
                            </span>
                            <span class="text-sm font-medium text-slate-500">
                                {{ session.zone?.building?.name }} • {{ session.zone?.area_type }} Area
                            </span>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                            {{ session.zone?.name }}
                        </h2>

                        <!-- PIC Info -->
                        <div class="flex items-center gap-3 pt-2">
                            <div class="size-11 rounded-full overflow-hidden border border-slate-200 bg-white shrink-0">
                                <img
                                    v-if="session.zone?.pic_photo_url"
                                    :src="session.zone.pic_photo_url"
                                    :alt="session.zone?.pic_name"
                                    class="size-full object-cover"
                                />
                                <UserCircleIcon v-else class="size-full text-slate-400" />
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-500">Area PIC</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ session.zone?.pic_name || session.zone?.pic_employee?.full_name || 'Unassigned' }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ session.zone?.department?.name || '-' }} • NIK: {{ session.zone?.pic_employee?.nik || '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="text-xs text-slate-500 flex items-center gap-4 pt-1">
                            <span class="flex items-center gap-1">
                                <CalendarIcon class="size-3.5" />
                                Audit Date: {{ new Date(session.audit_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                            </span>
                            <span>Auditor: <strong>{{ session.auditor?.name || 'Administrator' }}</strong></span>
                        </div>
                    </div>

                    <!-- Right: Big Grade & Total Score -->
                    <div class="rounded-xl border p-5 text-center flex flex-col items-center justify-center shadow-sm" :class="getGradeBadge(session.grade)">
                        <p class="text-xs font-semibold uppercase tracking-wider">Overall Score</p>
                        <p class="mt-1 text-4xl font-black font-mono">
                            {{ session.total_score }}%
                        </p>
                        <div class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3 py-1 text-xs font-black uppercase dark:bg-black/40">
                            Grade {{ session.grade }} ({{ session.grade === 'A' ? 'Excellent' : session.grade === 'B' ? 'Good' : session.grade === 'C' ? 'Satisfactory' : 'Needs Action' }})
                        </div>
                    </div>
                </div>

                <!-- 5 Pillars Breakdown Progress Bar -->
                <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800 grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <div class="rounded-lg bg-slate-50 p-3 text-center dark:bg-slate-800/40">
                        <p class="text-xs font-bold text-slate-500">1S (Ringkas)</p>
                        <p class="mt-1 text-lg font-black font-mono text-slate-900 dark:text-white">{{ session.score_sort }}%</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3 text-center dark:bg-slate-800/40">
                        <p class="text-xs font-bold text-slate-500">2S (Rapi)</p>
                        <p class="mt-1 text-lg font-black font-mono text-slate-900 dark:text-white">{{ session.score_set_in_order }}%</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3 text-center dark:bg-slate-800/40">
                        <p class="text-xs font-bold text-slate-500">3S (Resik)</p>
                        <p class="mt-1 text-lg font-black font-mono text-slate-900 dark:text-white">{{ session.score_shine }}%</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3 text-center dark:bg-slate-800/40">
                        <p class="text-xs font-bold text-slate-500">4S (Rawat)</p>
                        <p class="mt-1 text-lg font-black font-mono text-slate-900 dark:text-white">{{ session.score_standardize }}%</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3 text-center dark:bg-slate-800/40 col-span-2 sm:col-span-1">
                        <p class="text-xs font-bold text-slate-500">5S (Rajin)</p>
                        <p class="mt-1 text-lg font-black font-mono text-slate-900 dark:text-white">{{ session.score_sustain }}%</p>
                    </div>
                </div>
            </div>

            <!-- Summary Notes -->
            <div v-if="session.summary_notes" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">
                    Auditor Summary Notes & Observations
                </h3>
                <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">
                    {{ session.summary_notes }}
                </p>
            </div>

            <!-- Findings & Action Items -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2">
                        <CameraIcon class="size-4 text-rose-600" />
                        Audit Findings & Corrective Action Items ({{ session.findings.length }})
                    </h3>
                    <Link
                        :href="route('audit-5s.corrective-actions.index', { zone_id: session.zone_id })"
                        class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400"
                    >
                        View in CAPA Board →
                    </Link>
                </div>

                <div v-if="session.findings.length === 0" class="text-center py-6 text-slate-400 text-xs">
                    No findings recorded. Zone is fully compliant with 5S standards.
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="finding in session.findings"
                        :key="finding.id"
                        class="rounded-lg border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-800/40 space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1 rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-900/60 dark:text-blue-300">
                                {{ finding.pillar }}
                            </span>
                            <span
                                v-if="finding.is_red_tag"
                                class="inline-flex items-center gap-1 rounded bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-800 dark:bg-rose-950/60 dark:text-rose-300"
                            >
                                <ShieldExclamationIcon class="size-3" />
                                Red Tag
                            </span>
                        </div>

                        <p class="text-sm font-semibold text-slate-900 dark:text-white">
                            {{ finding.description }}
                        </p>

                        <!-- Before & After Photos Side-by-Side -->
                        <div class="grid grid-cols-2 gap-2 pt-2">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold uppercase text-slate-500">Before Photo</span>
                                <div class="h-28 rounded overflow-hidden border border-slate-200 bg-white dark:border-slate-700">
                                    <img
                                        v-if="finding.before_photo_url"
                                        :src="finding.before_photo_url"
                                        alt="Before Remedy"
                                        class="size-full object-cover"
                                    />
                                    <div v-else class="flex size-full items-center justify-center text-xs text-slate-400">
                                        No Photo
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] font-bold uppercase text-slate-500">After Photo</span>
                                <div class="h-28 rounded overflow-hidden border border-slate-200 bg-white dark:border-slate-700">
                                    <img
                                        v-if="finding.after_photo_url"
                                        :src="finding.after_photo_url"
                                        alt="After Remedy"
                                        class="size-full object-cover"
                                    />
                                    <div v-else class="flex size-full items-center justify-center text-xs text-slate-400 bg-slate-100 dark:bg-slate-800">
                                        Pending Action
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Remarks -->
                        <div class="pt-2 border-t border-slate-200 dark:border-slate-800 text-xs flex justify-between items-center">
                            <span class="text-slate-500">Status: <strong>{{ finding.status }}</strong></span>
                            <span class="text-slate-500">Due: {{ finding.due_date ? new Date(finding.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) : '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Ratings Table -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                        Evaluation Item Breakdown
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-left">Pillar</th>
                                <th class="px-4 py-3 text-left">Audit Item & Standard Criteria</th>
                                <th class="px-4 py-3 text-center">Score (1-5)</th>
                                <th class="px-4 py-3 text-right">Weighted Normalized</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr
                                v-for="r in session.ratings"
                                :key="r.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                            >
                                <td class="px-4 py-3 whitespace-nowrap text-xs font-bold text-slate-500">
                                    {{ r.pillar }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ r.checklist?.title || 'Criteria' }}</p>
                                    <p v-if="r.notes" class="text-xs text-slate-500 italic mt-0.5">Note: {{ r.notes }}</p>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <span
                                        :class="[
                                            'inline-flex size-7 items-center justify-center rounded-md font-bold text-xs shadow-sm',
                                            r.score >= 4 ? 'bg-emerald-600 text-white' : r.score === 3 ? 'bg-amber-500 text-white' : 'bg-rose-600 text-white'
                                        ]"
                                    >
                                        {{ r.score }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-mono font-semibold text-slate-700 dark:text-slate-300">
                                    {{ Math.round((r.score / 5) * 100) }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
