<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ShieldExclamationIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    CameraIcon,
    CheckCircleIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    BuildingOffice2Icon,
    UserCircleIcon,
    CheckBadgeIcon,
    ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    reports: {
        type: Object,
        default: () => ({ data: [] }),
    },
    buildings: {
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
const statusFilter = ref(props.filters.status || '');
const riskFilter = ref(props.filters.risk_level || '');
const typeFilter = ref(props.filters.hazard_type || '');

const applyFilters = () => {
    router.get(route('safety-5s.hazard-reports.index'), {
        search: search.value || undefined,
        building_id: buildingFilter.value || undefined,
        status: statusFilter.value || undefined,
        risk_level: riskFilter.value || undefined,
        hazard_type: typeFilter.value || undefined,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    search.value = '';
    buildingFilter.value = '';
    statusFilter.value = '';
    riskFilter.value = '';
    typeFilter.value = '';
    router.get(route('safety-5s.hazard-reports.index'));
};

// Action Taken Modal
const isResolveModalOpen = ref(false);
const selectedReport = ref(null);
const resolveForm = useForm({
    action_taken_remarks: '',
    after_photo: null,
    after_preview: null,
});

const openResolveModal = (report) => {
    selectedReport.value = report;
    resolveForm.reset();
    resolveForm.after_preview = null;
    isResolveModalOpen.value = true;
};

const handleAfterPhoto = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    resolveForm.after_photo = file;
    resolveForm.after_preview = URL.createObjectURL(file);
};

const submitResolve = () => {
    if (!selectedReport.value) return;
    resolveForm.post(route('safety-5s.hazard-reports.resolve', selectedReport.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isResolveModalOpen.value = false;
        },
    });
};

// Verification Modal
const isVerifyModalOpen = ref(false);
const verifyForm = useForm({
    action: 'approve',
    notes: '',
});

const openVerifyModal = (report) => {
    selectedReport.value = report;
    verifyForm.reset();
    isVerifyModalOpen.value = true;
};

const submitVerify = () => {
    if (!selectedReport.value) return;
    verifyForm.post(route('safety-5s.hazard-reports.verify', selectedReport.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isVerifyModalOpen.value = false;
        },
    });
};

// Photo Preview Modal
const isImagePreviewModalOpen = ref(false);
const previewImageUrl = ref('');
const openImagePreview = (url) => {
    previewImageUrl.value = url;
    isImagePreviewModalOpen.value = true;
};

const getRiskBadge = (risk) => {
    switch (risk) {
        case 'Critical':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 border-rose-300';
        case 'High':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border-amber-300';
        case 'Medium':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300 border-yellow-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 border-blue-200';
    }
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'Closed':
            return 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800';
        case 'Action_Taken':
            return 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800';
        default:
            return 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800';
    }
};
</script>

<template>
    <AppLayout title="Hazard & Near-Miss Reports">
        <Head title="Hazard & Near-Miss Reports - Safety & 5S" />

        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300">
                            Active Prevention
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Zero Incident Culture</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
                        Hazard & Near-Miss Reports
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        Rapid factory bay hazard reporting (Unsafe Action, Unsafe Condition, Near Miss) auto-assigned to zone PICs.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('safety-5s.hazard-reports.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 rounded-md shadow-sm transition duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2"
                    >
                        <PlusIcon class="size-4" />
                        <span>Report Hazard / Near-Miss</span>
                    </Link>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Reported</span>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                        {{ stats.total || 0 }}
                    </p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-amber-200 dark:border-amber-900/50 shadow-sm bg-amber-50/20 dark:bg-amber-950/10">
                    <span class="text-xs font-medium text-amber-700 dark:text-amber-400">Open / Pending</span>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                        {{ stats.reported || 0 }}
                    </p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-blue-200 dark:border-blue-900/50 shadow-sm bg-blue-50/20 dark:bg-blue-950/10">
                    <span class="text-xs font-medium text-blue-700 dark:text-blue-400">Action Submitted</span>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                        {{ stats.action_taken || 0 }}
                    </p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-emerald-200 dark:border-emerald-900/50 shadow-sm bg-emerald-50/20 dark:bg-emerald-950/10">
                    <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Resolved & Closed</span>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                        {{ stats.closed || 0 }}
                    </p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-rose-200 dark:border-rose-900/50 shadow-sm bg-rose-50/20 dark:bg-rose-950/10">
                    <span class="text-xs font-medium text-rose-700 dark:text-rose-400">Critical / High Alerts</span>
                    <p class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">
                        {{ stats.critical_high || 0 }}
                    </p>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row items-center gap-3">
                <div class="relative w-full md:w-64">
                    <MagnifyingGlassIcon class="size-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search ticket, desc..."
                        class="w-full pl-9 pr-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white placeholder-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="w-full md:w-44">
                    <select
                        v-model="buildingFilter"
                        class="w-full px-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">All Buildings</option>
                        <option v-for="b in buildings" :key="b.id" :value="b.id">
                            {{ b.name }}
                        </option>
                    </select>
                </div>

                <div class="w-full md:w-40">
                    <select
                        v-model="riskFilter"
                        class="w-full px-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">All Risk Levels</option>
                        <option value="Critical">Critical</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>

                <div class="w-full md:w-44">
                    <select
                        v-model="typeFilter"
                        class="w-full px-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">All Types</option>
                        <option value="Unsafe_Condition">Unsafe Condition</option>
                        <option value="Unsafe_Action">Unsafe Action</option>
                        <option value="Near_Miss">Near Miss</option>
                        <option value="Environmental">Environmental</option>
                    </select>
                </div>

                <div class="w-full md:w-36">
                    <select
                        v-model="statusFilter"
                        class="w-full px-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">All Status</option>
                        <option value="Reported">Reported</option>
                        <option value="Action_Taken">Action Taken</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 ml-auto w-full md:w-auto">
                    <button
                        type="button"
                        class="px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 rounded-md transition duration-150"
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

            <!-- Reports Table -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/50 text-slate-600 dark:text-slate-300 font-semibold">
                                <th class="py-3 px-4">Ticket & Type</th>
                                <th class="py-3 px-4">Building & Zone</th>
                                <th class="py-3 px-4">Assigned Area PIC</th>
                                <th class="py-3 px-4">Description</th>
                                <th class="py-3 px-4 text-center">Photos</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr
                                v-for="report in reports.data"
                                :key="report.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition duration-150"
                            >
                                <td class="py-3 px-4 space-y-1">
                                    <div class="font-mono text-xs font-bold text-slate-900 dark:text-white">
                                        {{ report.ticket_number }}
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border"
                                            :class="getRiskBadge(report.risk_level)"
                                        >
                                            {{ report.risk_level }}
                                        </span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ report.hazard_type.replace('_', ' ') }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-900 dark:text-white">
                                        {{ report.zone?.name }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ report.building?.name }} ({{ report.zone?.code }})
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5">
                                        <UserCircleIcon class="size-4 text-blue-500" />
                                        <span class="text-xs font-medium text-slate-900 dark:text-white">
                                            {{ report.zone?.pic_employee?.full_name || 'Area PIC' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-3 px-4 max-w-xs">
                                    <p class="text-xs text-slate-800 dark:text-slate-200 line-clamp-2">
                                        {{ report.description }}
                                    </p>
                                    <span v-if="report.location_details" class="text-[11px] text-slate-400 block mt-0.5">
                                        Loc: {{ report.location_details }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            v-if="report.photo_before_path"
                                            type="button"
                                            class="p-1 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300 text-xs"
                                            title="View Before Photo"
                                            @click="openImagePreview(`/storage/${report.photo_before_path}`)"
                                        >
                                            <CameraIcon class="size-4 text-rose-500" />
                                        </button>
                                        <button
                                            v-if="report.photo_after_path"
                                            type="button"
                                            class="p-1 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300 text-xs"
                                            title="View Remediation Photo"
                                            @click="openImagePreview(`/storage/${report.photo_after_path}`)"
                                        >
                                            <CameraIcon class="size-4 text-emerald-500" />
                                        </button>
                                        <span v-if="!report.photo_before_path && !report.photo_after_path" class="text-xs text-slate-400">-</span>
                                    </div>
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                        :class="getStatusBadge(report.status)"
                                    >
                                        {{ report.status.replace('_', ' ') }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="report.status === 'Reported'"
                                            type="button"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded transition duration-150"
                                            @click="openResolveModal(report)"
                                        >
                                            <ArrowUpTrayIcon class="size-3.5" />
                                            <span>Take Action</span>
                                        </button>

                                        <button
                                            v-if="report.status === 'Action_Taken'"
                                            type="button"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded transition duration-150"
                                            @click="openVerifyModal(report)"
                                        >
                                            <CheckBadgeIcon class="size-3.5" />
                                            <span>Verify</span>
                                        </button>

                                        <span v-if="report.status === 'Closed'" class="text-xs text-emerald-600 dark:text-emerald-400 font-medium inline-flex items-center gap-1">
                                            <CheckCircleIcon class="size-4" />
                                            <span>Closed</span>
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!reports.data || reports.data.length === 0">
                                <td colspan="7" class="py-12 text-center">
                                    <ShieldExclamationIcon class="size-10 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">No Hazard Reports Found</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        Great job! All work areas are currently reported safe.
                                    </p>
                                    <div class="mt-4">
                                        <Link
                                            :href="route('safety-5s.hazard-reports.create')"
                                            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-md transition duration-150"
                                        >
                                            <PlusIcon class="size-3.5" />
                                            <span>Report First Hazard</span>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="reports.links && reports.links.length > 3"
                    class="py-3 px-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500"
                >
                    <span>Showing {{ reports.from || 0 }} to {{ reports.to || 0 }} of {{ reports.total }} reports</span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, idx) in reports.links"
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

        <!-- Take Action / Remediation Modal -->
        <div v-if="isResolveModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-lg max-w-lg w-full border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                            Submit Remediation: {{ selectedReport?.ticket_number }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Upload evidence of mitigation action taken by Area PIC.
                        </p>
                    </div>
                    <button type="button" @click="isResolveModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <XMarkIcon class="size-5" />
                    </button>
                </div>

                <form @submit.prevent="submitResolve" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Action Taken Description <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="resolveForm.action_taken_remarks"
                            rows="3"
                            required
                            placeholder="Detail what corrective actions were executed (e.g. oil puddle cleaned, cable re-routed into cable tray)..."
                            class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                            After-Action Photo Evidence <span class="text-rose-500">*</span>
                        </label>
                        <div v-if="!resolveForm.after_preview">
                            <label class="flex flex-col items-center justify-center p-4 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-md cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800">
                                <CameraIcon class="size-6 text-slate-400 mb-1" />
                                <span class="text-xs text-slate-600 dark:text-slate-300">Upload Photo Proof</span>
                                <input type="file" accept="image/*" required class="hidden" @change="handleAfterPhoto" />
                            </label>
                        </div>
                        <div v-else class="relative rounded overflow-hidden border border-slate-200 max-h-36">
                            <img :src="resolveForm.after_preview" class="w-full h-36 object-cover" />
                            <button
                                type="button"
                                class="absolute top-2 right-2 p-1 bg-rose-600 text-white rounded-full hover:bg-rose-700"
                                @click="resolveForm.after_photo = null; resolveForm.after_preview = null;"
                            >
                                <XMarkIcon class="size-4" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            class="px-3 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:text-slate-900"
                            @click="isResolveModalOpen = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="resolveForm.processing || !resolveForm.after_photo"
                            class="px-4 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-md shadow-sm"
                        >
                            {{ resolveForm.processing ? 'Submitting...' : 'Submit Action Taken' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Verification Modal -->
        <div v-if="isVerifyModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-lg max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                            Verify Hazard Resolution
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Ticket: {{ selectedReport?.ticket_number }}
                        </p>
                    </div>
                    <button type="button" @click="isVerifyModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <XMarkIcon class="size-5" />
                    </button>
                </div>

                <form @submit.prevent="submitVerify" class="space-y-4">
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" value="approve" v-model="verifyForm.action" class="text-emerald-600" />
                            <span class="text-xs font-medium text-slate-800 dark:text-slate-200">Approve & Close</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" value="reject" v-model="verifyForm.action" class="text-rose-600" />
                            <span class="text-xs font-medium text-slate-800 dark:text-slate-200">Reject & Request Re-action</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Supervisor / Auditor Verification Notes
                        </label>
                        <textarea
                            v-model="verifyForm.notes"
                            rows="2"
                            placeholder="Add verification feedback or instructions..."
                            class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            class="px-3 py-1.5 text-xs text-slate-600 dark:text-slate-400"
                            @click="isVerifyModalOpen = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="verifyForm.processing"
                            class="px-4 py-1.5 text-xs font-semibold text-white rounded-md shadow-sm"
                            :class="verifyForm.action === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
                        >
                            {{ verifyForm.processing ? 'Processing...' : (verifyForm.action === 'approve' ? 'Confirm & Close Ticket' : 'Reject Action') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Image Preview Modal -->
        <div v-if="isImagePreviewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm" @click.self="isImagePreviewModalOpen = false">
            <div class="max-w-3xl w-full bg-slate-950 rounded-lg p-2 border border-slate-800 relative">
                <button
                    type="button"
                    class="absolute top-3 right-3 p-1.5 bg-slate-800/80 hover:bg-slate-700 text-white rounded-full transition"
                    @click="isImagePreviewModalOpen = false"
                >
                    <XMarkIcon class="size-5" />
                </button>
                <img :src="previewImageUrl" class="w-full max-h-[80vh] object-contain rounded" />
            </div>
        </div>
    </AppLayout>
</template>
