<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    WrenchScrewdriverIcon,
    CameraIcon,
    CheckCircleIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    ShieldExclamationIcon,
    ClockIcon,
    BuildingOffice2Icon,
    UserCircleIcon,
    MagnifyingGlassIcon,
    ArrowUpTrayIcon,
    CheckBadgeIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    findings: {
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

// Modals
const isRemedyModalOpen = ref(false);
const isVerifyModalOpen = ref(false);
const isImagePreviewModalOpen = ref(false);
const previewImageUrl = ref('');
const selectedFinding = ref(null);

// Forms
const remedyForm = useForm({
    after_photo: null,
    after_preview: null,
    remedy_notes: '',
});

const verifyForm = useForm({
    action: 'approve',
    verification_notes: '',
});

// Filter state
const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || 'all');
const riskFilter = ref(props.filters.risk_level || 'all');
const statusFilter = ref(props.filters.status || '');
const pillarFilter = ref(props.filters.pillar || '');
const buildingFilter = ref(props.filters.building_id || '');
const redTagFilter = ref(props.filters.is_red_tag || '');

const setCategoryTab = (cat) => {
    categoryFilter.value = cat;
    applyFilters();
};

const applyFilters = () => {
    router.get(route('safety-5s.corrective-actions.index'), {
        category: categoryFilter.value !== 'all' ? categoryFilter.value : undefined,
        risk_level: riskFilter.value !== 'all' ? riskFilter.value : undefined,
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        pillar: pillarFilter.value || undefined,
        building_id: buildingFilter.value || undefined,
        is_red_tag: redTagFilter.value || undefined,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    search.value = '';
    categoryFilter.value = 'all';
    riskFilter.value = 'all';
    statusFilter.value = '';
    pillarFilter.value = '';
    buildingFilter.value = '';
    redTagFilter.value = '';
    router.get(route('safety-5s.corrective-actions.index'));
};

// Remediation modal open
const openRemedyModal = (finding) => {
    selectedFinding.value = finding;
    remedyForm.reset();
    remedyForm.after_preview = null;
    isRemedyModalOpen.value = true;
};

const handleAfterPhoto = (event) => {
    const file = event.target.files[0];
    if (file) {
        remedyForm.after_photo = file;
        remedyForm.after_preview = URL.createObjectURL(file);
    }
};

const submitRemediation = () => {
    remedyForm.post(route('safety-5s.corrective-actions.remedy', selectedFinding.value.id), {
        forceFormData: true,
        onSuccess: () => {
            isRemedyModalOpen.value = false;
            remedyForm.reset();
        },
    });
};

// Verification modal open
const openVerifyModal = (finding) => {
    selectedFinding.value = finding;
    verifyForm.reset();
    verifyForm.action = 'approve';
    isVerifyModalOpen.value = true;
};

const submitVerification = () => {
    verifyForm.post(route('safety-5s.corrective-actions.verify', selectedFinding.value.id), {
        onSuccess: () => {
            isVerifyModalOpen.value = false;
            verifyForm.reset();
        },
    });
};

// Image zoom modal
const openImagePreview = (url) => {
    if (!url) return;
    previewImageUrl.value = url;
    isImagePreviewModalOpen.value = true;
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'Closed': return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
        case 'Submitted': return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800';
        case 'In Progress': return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
        default: return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800';
    }
};
</script>

<template>
    <AppLayout title="5S Corrective Actions (CAPA)">
        <Head title="5S Corrective Actions (CAPA)" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800">
                            CAPA Remediation
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            5S Corrective Actions & Remediation Board
                        </h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Track non-conformances, inspect Before & After photographic evidence, and approve closed-loop resolutions.
                    </p>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Findings</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ stats.total }}</p>
                </div>
                <div class="rounded-lg border border-rose-200 bg-rose-50/50 p-4 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/20">
                    <p class="text-xs font-medium text-rose-800 dark:text-rose-300">Open</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-rose-700 dark:text-rose-400">{{ stats.open }}</p>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-4 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/20">
                    <p class="text-xs font-medium text-amber-800 dark:text-amber-300">In Progress</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-amber-700 dark:text-amber-400">{{ stats.in_progress }}</p>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50/50 p-4 shadow-sm dark:border-blue-900/60 dark:bg-blue-950/20">
                    <p class="text-xs font-medium text-blue-800 dark:text-blue-300">Pending Verification</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-blue-700 dark:text-blue-400">{{ stats.submitted }}</p>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20">
                    <p class="text-xs font-medium text-emerald-800 dark:text-emerald-300">Verified & Closed</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-emerald-700 dark:text-emerald-400">{{ stats.closed }}</p>
                </div>
            </div>

            <!-- Category Tabs -->
            <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-1">
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-semibold rounded-md transition duration-150 flex items-center gap-2"
                    :class="categoryFilter === 'all' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                    @click="setCategoryTab('all')"
                >
                    <span>All Action Items</span>
                    <span class="px-1.5 py-0.2 rounded-full text-xs" :class="categoryFilter === 'all' ? 'bg-blue-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
                        {{ stats.total || 0 }}
                    </span>
                </button>
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-semibold rounded-md transition duration-150 flex items-center gap-2"
                    :class="categoryFilter === '5s' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                    @click="setCategoryTab('5s')"
                >
                    <span>5S Workplace Findings</span>
                    <span class="px-1.5 py-0.2 rounded-full text-xs" :class="categoryFilter === '5s' ? 'bg-blue-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
                        {{ stats.count_5s || 0 }}
                    </span>
                </button>
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-semibold rounded-md transition duration-150 flex items-center gap-2"
                    :class="categoryFilter === 'safety' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                    @click="setCategoryTab('safety')"
                >
                    <span>Safety & Hazards</span>
                    <span class="px-1.5 py-0.2 rounded-full text-xs" :class="categoryFilter === 'safety' ? 'bg-rose-700 text-white' : 'bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300'">
                        {{ stats.count_safety || 0 }}
                    </span>
                </button>
            </div>

            <!-- Filter Controls -->
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-6">
                    <div class="relative sm:col-span-2">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search description, remedy, zone..."
                            @keyup.enter="applyFilters"
                            class="w-full rounded-md border-slate-300 pl-9 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                        />
                    </div>

                    <div>
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                        >
                            <option value="">All Statuses</option>
                            <option value="Open">Open</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Submitted">Submitted (Pending Review)</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>

                    <div>
                        <select
                            v-model="riskFilter"
                            @change="applyFilters"
                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                        >
                            <option value="all">All Risk Levels</option>
                            <option value="Critical">Critical Risk</option>
                            <option value="High">High Risk</option>
                            <option value="Medium">Medium Risk</option>
                            <option value="Low">Low Risk</option>
                        </select>
                    </div>

                    <div>
                        <select
                            v-model="pillarFilter"
                            @change="applyFilters"
                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                        >
                            <option value="">All 5S Pillars</option>
                            <option value="Sort">1S - Sort</option>
                            <option value="Set In Order">2S - Set In Order</option>
                            <option value="Shine">3S - Shine</option>
                            <option value="Standardize">4S - Standardize</option>
                            <option value="Sustain">5S - Sustain</option>
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

            <!-- CAPA Cards List -->
            <div class="space-y-4">
                <div
                    v-for="finding in findings.data"
                    :key="finding.id"
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition hover:border-slate-300 dark:hover:border-slate-700"
                >
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <!-- Left Info -->
                        <div class="space-y-2 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="finding.category === 'safety'"
                                    class="rounded bg-rose-100 dark:bg-rose-950/60 px-2 py-0.5 text-xs font-bold text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800"
                                >
                                    Safety Hazard
                                </span>
                                <span
                                    v-if="finding.risk_level"
                                    class="rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider border"
                                    :class="finding.risk_level === 'Critical' ? 'bg-rose-50 text-rose-700 border-rose-300 dark:bg-rose-950/40 dark:text-rose-400' : 'bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/40 dark:text-amber-400'"
                                >
                                    {{ finding.risk_level }} Risk
                                </span>
                                <span
                                    v-if="finding.hazard_report"
                                    class="rounded bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-xs font-mono text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700"
                                >
                                    #{{ finding.hazard_report.ticket_number }}
                                </span>
                                <span
                                    v-if="finding.category !== 'safety'"
                                    class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-900/60 dark:text-blue-300"
                                >
                                    {{ finding.pillar }}
                                </span>
                                <span
                                    v-if="finding.is_red_tag"
                                    class="inline-flex items-center gap-1 rounded bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-800 dark:bg-rose-950/60 dark:text-rose-300"
                                >
                                    <ShieldExclamationIcon class="size-3" />
                                    Red Tag
                                </span>
                                <span class="text-xs font-mono text-slate-500">
                                    {{ finding.zone?.code }} • {{ finding.zone?.building?.name }}
                                </span>
                                <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold border', getStatusBadge(finding.status)]">
                                    {{ finding.status }}
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                {{ finding.description }}
                            </h3>

                            <p class="text-xs text-slate-500 flex items-center gap-3">
                                <span>Zone: <strong>{{ finding.zone?.name }}</strong></span>
                                <span>PIC: <strong>{{ finding.zone?.pic_name || 'Unassigned' }}</strong></span>
                                <span>Due: <strong>{{ finding.due_date ? new Date(finding.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }}</strong></span>
                            </p>

                            <!-- Remediation remarks if submitted -->
                            <div v-if="finding.remedy_notes" class="mt-2 rounded-lg bg-slate-50 p-3 text-xs text-slate-700 dark:bg-slate-800/40 dark:text-slate-300">
                                <p class="font-semibold text-slate-900 dark:text-white">Remediation Action:</p>
                                <p class="mt-0.5">{{ finding.remedy_notes }}</p>
                                <p class="text-[10px] text-slate-500 mt-1">
                                    Submitted by {{ finding.remediated_by?.name || 'PIC' }} on {{ new Date(finding.remediated_at).toLocaleString('id-ID') }}
                                </p>
                            </div>

                            <!-- Verification remarks if present -->
                            <div v-if="finding.verification_notes" class="rounded-lg bg-emerald-50/60 p-3 text-xs text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                                <p class="font-semibold">Auditor Review:</p>
                                <p class="mt-0.5">{{ finding.verification_notes }}</p>
                            </div>
                        </div>

                        <!-- Side-by-Side Photos -->
                        <div class="flex items-center gap-3 shrink-0">
                            <!-- Before Photo -->
                            <div class="space-y-1 text-center">
                                <span class="text-[10px] font-bold uppercase text-slate-500">Before</span>
                                <div
                                    class="size-24 rounded-lg overflow-hidden border border-slate-200 bg-slate-100 cursor-pointer hover:opacity-90 shadow-sm"
                                    @click="openImagePreview(finding.before_photo_url)"
                                >
                                    <img
                                        v-if="finding.before_photo_url"
                                        :src="finding.before_photo_url"
                                        alt="Before"
                                        class="size-full object-cover"
                                    />
                                    <div v-else class="flex size-full items-center justify-center text-[10px] text-slate-400">
                                        No Photo
                                    </div>
                                </div>
                            </div>

                            <!-- After Photo -->
                            <div class="space-y-1 text-center">
                                <span class="text-[10px] font-bold uppercase text-slate-500">After</span>
                                <div
                                    class="size-24 rounded-lg overflow-hidden border border-slate-200 bg-slate-100 cursor-pointer hover:opacity-90 shadow-sm"
                                    @click="openImagePreview(finding.after_photo_url)"
                                >
                                    <img
                                        v-if="finding.after_photo_url"
                                        :src="finding.after_photo_url"
                                        alt="After"
                                        class="size-full object-cover"
                                    />
                                    <div v-else class="flex size-full items-center justify-center text-[10px] text-slate-400 bg-slate-50 dark:bg-slate-800">
                                        Pending
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Bottom Actions -->
                    <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <Link
                            :href="route('audit-5s.audits.show', finding.session_id)"
                            class="text-xs font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200"
                        >
                            Session: {{ finding.session?.session_code }} →
                        </Link>

                        <div class="flex items-center gap-2">
                            <!-- PIC Action: Submit Remedy -->
                            <button
                                v-if="finding.status === 'Open' || finding.status === 'In Progress'"
                                type="button"
                                @click="openRemedyModal(finding)"
                                class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-500 transition"
                            >
                                <ArrowUpTrayIcon class="size-3.5" />
                                <span>Upload Remediation</span>
                            </button>

                            <!-- Auditor Action: Verify & Sign-Off -->
                            <button
                                v-if="finding.status === 'Submitted'"
                                type="button"
                                @click="openVerifyModal(finding)"
                                class="inline-flex items-center gap-1.5 rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500 transition"
                            >
                                <CheckBadgeIcon class="size-3.5" />
                                <span>Verify & Sign-off</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="findings.data.length === 0" class="rounded-xl border border-slate-200 bg-white p-12 text-center text-slate-500 dark:border-slate-800 dark:bg-slate-900">
                    <CheckCircleIcon class="mx-auto size-12 text-slate-400 mb-2" />
                    <p class="font-bold text-slate-700 dark:text-slate-300">All corrective actions resolved!</p>
                    <p class="text-xs mt-1">No open non-conformances matching the current filter criteria.</p>
                </div>
            </div>

            <!-- Modal: Submit Remediation (After Photo + Notes) -->
            <div
                v-if="isRemedyModalOpen"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm p-4 flex items-center justify-center"
                @click.self="isRemedyModalOpen = false"
            >
                <div class="w-full max-w-lg rounded-xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Submit 5S Remediation</h3>
                        <button type="button" @click="isRemedyModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitRemediation" class="mt-4 space-y-4">
                        <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600 dark:bg-slate-800">
                            <p class="font-semibold text-slate-900 dark:text-white">Finding:</p>
                            <p>{{ selectedFinding?.description }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">After Photo (Bukti Perbaikan) *</label>
                            <input
                                type="file"
                                accept="image/*"
                                required
                                @change="handleAfterPhoto"
                                class="mt-1 w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                        </div>

                        <div v-if="remedyForm.after_preview" class="size-32 rounded-lg overflow-hidden border border-slate-300 shadow-inner">
                            <img :src="remedyForm.after_preview" class="size-full object-cover" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Action Taken Remarks *</label>
                            <textarea
                                v-model="remedyForm.remedy_notes"
                                required
                                rows="3"
                                placeholder="Describe the cleaning, relocation, floor repainting, or disposal performed..."
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-800">
                            <button
                                type="button"
                                @click="isRemedyModalOpen = false"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="remedyForm.processing || !remedyForm.after_photo"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50"
                            >
                                Submit for Verification
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal: Verify Remediation (Approve / Reject) -->
            <div
                v-if="isVerifyModalOpen"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm p-4 flex items-center justify-center"
                @click.self="isVerifyModalOpen = false"
            >
                <div class="w-full max-w-lg rounded-xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Verify 5S Remediation</h3>
                        <button type="button" @click="isVerifyModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitVerification" class="mt-4 space-y-4">
                        <div class="grid grid-cols-2 gap-3 text-center">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-500">Before</span>
                                <div class="mt-1 h-28 rounded-lg overflow-hidden border border-slate-200">
                                    <img :src="selectedFinding?.before_photo_url" class="size-full object-cover" />
                                </div>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-500">After</span>
                                <div class="mt-1 h-28 rounded-lg overflow-hidden border border-slate-200">
                                    <img :src="selectedFinding?.after_photo_url" class="size-full object-cover" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Verification Decision *</label>
                            <div class="mt-2 grid grid-cols-2 gap-3">
                                <label
                                    :class="[
                                        'flex cursor-pointer items-center justify-center gap-2 rounded-lg border p-3 font-semibold text-sm transition',
                                        verifyForm.action === 'approve'
                                            ? 'border-emerald-600 bg-emerald-50 text-emerald-700 ring-2 ring-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300'
                                            : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-400'
                                    ]"
                                >
                                    <input type="radio" value="approve" v-model="verifyForm.action" class="sr-only" />
                                    <CheckCircleIcon class="size-4" />
                                    <span>Approve & Close</span>
                                </label>
                                <label
                                    :class="[
                                        'flex cursor-pointer items-center justify-center gap-2 rounded-lg border p-3 font-semibold text-sm transition',
                                        verifyForm.action === 'reject'
                                            ? 'border-rose-600 bg-rose-50 text-rose-700 ring-2 ring-rose-300 dark:bg-rose-950/40 dark:text-rose-300'
                                            : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-400'
                                    ]"
                                >
                                    <input type="radio" value="reject" v-model="verifyForm.action" class="sr-only" />
                                    <XMarkIcon class="size-4" />
                                    <span>Reject / Rework</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Auditor Notes</label>
                            <textarea
                                v-model="verifyForm.verification_notes"
                                rows="2"
                                placeholder="Notes on the remediation quality..."
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-800">
                            <button
                                type="button"
                                @click="isVerifyModalOpen = false"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="verifyForm.processing"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
                            >
                                Confirm Verification
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Image Zoom Modal -->
            <div
                v-if="isImagePreviewModalOpen"
                class="fixed inset-0 z-50 overflow-hidden bg-slate-950/80 backdrop-blur-sm p-4 flex items-center justify-center cursor-pointer"
                @click="isImagePreviewModalOpen = false"
            >
                <div class="max-w-3xl max-h-[85vh] overflow-hidden rounded-xl bg-black shadow-2xl relative">
                    <img :src="previewImageUrl" class="max-h-[85vh] w-auto object-contain" />
                    <button
                        type="button"
                        @click="isImagePreviewModalOpen = false"
                        class="absolute top-3 right-3 rounded-full bg-black/60 p-2 text-white hover:bg-black"
                    >
                        <XMarkIcon class="size-5" />
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
