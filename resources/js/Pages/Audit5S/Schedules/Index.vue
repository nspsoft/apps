<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CalendarDaysIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    XMarkIcon,
    FunnelIcon,
    CheckCircleIcon,
    ClockIcon,
    PlayIcon,
    BuildingOffice2Icon,
    UserIcon,
    ArrowPathIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    schedules: {
        type: Object,
        default: () => ({ data: [] }),
    },
    buildings: {
        type: Array,
        default: () => [],
    },
    auditors: {
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
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedSchedule = ref(null);

// Search & Filter State
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const buildingFilter = ref(props.filters.building_id || '');

const applyFilters = () => {
    router.get(route('audit-5s.schedules.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        building_id: buildingFilter.value || undefined,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    search.value = '';
    statusFilter.value = '';
    buildingFilter.value = '';
    router.get(route('audit-5s.schedules.index'));
};

// Form
const form = useForm({
    id: null,
    title: '',
    frequency: 'monthly',
    scheduled_date: new Date().toISOString().split('T')[0],
    auditor_id: '',
    building_id: '',
    status: 'Scheduled',
    notes: '',
});

const openCreateModal = () => {
    form.reset();
    form.frequency = 'monthly';
    form.scheduled_date = new Date().toISOString().split('T')[0];
    if (props.buildings.length > 0) form.building_id = props.buildings[0].id;
    isCreateModalOpen.value = true;
};

const openEditModal = (schedule) => {
    selectedSchedule.value = schedule;
    form.id = schedule.id;
    form.title = schedule.title;
    form.frequency = schedule.frequency;
    form.scheduled_date = schedule.scheduled_date;
    form.auditor_id = schedule.auditor_id;
    form.building_id = schedule.building_id;
    form.status = schedule.status;
    form.notes = schedule.notes;
    isEditModalOpen.value = true;
};

const submitCreate = () => {
    form.post(route('audit-5s.schedules.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            form.reset();
        },
    });
};

const submitEdit = () => {
    form.put(route('audit-5s.schedules.update', form.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
            form.reset();
        },
    });
};

const deleteSchedule = (schedule) => {
    if (!confirm(`Are you sure you want to delete schedule "${schedule.title}"?`)) return;

    router.delete(route('audit-5s.schedules.destroy', schedule.id), {
        preserveScroll: true,
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'Completed': return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
        case 'In Progress': return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800';
        case 'Cancelled': return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800';
        default: return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
    }
};
</script>

<template>
    <AppLayout title="5S Audit Schedules">
        <Head title="5S Audit Schedules" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800">
                            Planning & Compliance
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            5S Audit Schedules
                        </h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Plan, track recurring audit cycles, assign qualified auditors, and monitor execution progress.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition"
                    >
                        <PlusIcon class="size-4" />
                        <span>Schedule New Audit</span>
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Schedules</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ stats.total }}</p>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-4 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/20">
                    <p class="text-xs font-medium text-amber-800 dark:text-amber-300">Upcoming / Scheduled</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-amber-700 dark:text-amber-400">{{ stats.scheduled }}</p>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50/50 p-4 shadow-sm dark:border-blue-900/60 dark:bg-blue-950/20">
                    <p class="text-xs font-medium text-blue-800 dark:text-blue-300">In Progress</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-blue-700 dark:text-blue-400">{{ stats.in_progress }}</p>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20">
                    <p class="text-xs font-medium text-emerald-800 dark:text-emerald-300">Completed Cycles</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-emerald-700 dark:text-emerald-400">{{ stats.completed }}</p>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                    <div class="relative">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search title, auditor, notes..."
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
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                        >
                            <option value="">All Statuses</option>
                            <option value="Scheduled">Scheduled</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
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
                                <th class="px-4 py-3 text-left">Schedule Title</th>
                                <th class="px-4 py-3 text-left">Target Building</th>
                                <th class="px-4 py-3 text-left">Frequency</th>
                                <th class="px-4 py-3 text-left">Scheduled Date</th>
                                <th class="px-4 py-3 text-left">Assigned Auditor</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr
                                v-for="sch in schedules.data"
                                :key="sch.id"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
                            >
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ sch.title }}</p>
                                    <p v-if="sch.notes" class="text-xs text-slate-500 truncate max-w-xs">{{ sch.notes }}</p>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-slate-700 dark:text-slate-300">
                                    <span class="inline-flex items-center gap-1.5">
                                        <BuildingOffice2Icon class="size-4 text-slate-400" />
                                        {{ sch.building?.name || '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap capitalize font-medium text-slate-700 dark:text-slate-300">
                                    {{ sch.frequency.replace('_', ' ') }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono text-slate-700 dark:text-slate-300">
                                    {{ new Date(sch.scheduled_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-slate-700 dark:text-slate-300">
                                    <span class="inline-flex items-center gap-1.5">
                                        <UserIcon class="size-3.5 text-slate-400" />
                                        {{ sch.auditor?.name || 'Unassigned' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <span
                                        :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border', getStatusBadge(sch.status)]"
                                    >
                                        {{ sch.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-right space-x-1.5">
                                    <!-- Execute button -->
                                    <Link
                                        :href="route('audit-5s.audits.create', { schedule_id: sch.id })"
                                        class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800"
                                        title="Conduct Audit Now"
                                    >
                                        <PlayIcon class="size-3.5" />
                                        <span>Execute</span>
                                    </Link>

                                    <!-- Edit -->
                                    <button
                                        type="button"
                                        @click="openEditModal(sch)"
                                        class="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800"
                                        title="Edit Schedule"
                                    >
                                        <PencilSquareIcon class="size-4" />
                                    </button>

                                    <!-- Delete -->
                                    <button
                                        type="button"
                                        @click="deleteSchedule(sch)"
                                        class="rounded p-1 text-rose-500 hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-slate-800"
                                        title="Delete Schedule"
                                    >
                                        <TrashIcon class="size-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="schedules.data.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
                                    <CalendarDaysIcon class="mx-auto size-10 text-slate-400 mb-2" />
                                    <p class="font-semibold text-slate-700 dark:text-slate-300">No audit schedules found</p>
                                    <p class="text-xs mt-1">Click "Schedule New Audit" above to establish recurring or one-time 5S audit rounds.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal: Create Schedule -->
            <div
                v-if="isCreateModalOpen"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm p-4 flex items-center justify-center"
                @click.self="isCreateModalOpen = false"
            >
                <div class="w-full max-w-lg rounded-xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Create 5S Audit Schedule</h3>
                        <button type="button" @click="isCreateModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Schedule Title *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="e.g. November 2026 Regular Plant 1 5S Audit"
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Target Building *</label>
                                <select
                                    v-model="form.building_id"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Frequency *</label>
                                <select
                                    v-model="form.frequency"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option value="one_time">One Time</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Scheduled Date *</label>
                                <input
                                    v-model="form.scheduled_date"
                                    type="date"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Assigned Auditor *</label>
                                <select
                                    v-model="form.auditor_id"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option value="">-- Select Auditor --</option>
                                    <option v-for="u in auditors" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Notes / Objectives</label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Special focus on chemical storage, floor markings..."
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-800">
                            <button
                                type="button"
                                @click="isCreateModalOpen = false"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50"
                            >
                                Create Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal: Edit Schedule -->
            <div
                v-if="isEditModalOpen"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm p-4 flex items-center justify-center"
                @click.self="isEditModalOpen = false"
            >
                <div class="w-full max-w-lg rounded-xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Edit 5S Audit Schedule</h3>
                        <button type="button" @click="isEditModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitEdit" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Schedule Title *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Target Building *</label>
                                <select
                                    v-model="form.building_id"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Status *</label>
                                <select
                                    v-model="form.status"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Scheduled Date *</label>
                                <input
                                    v-model="form.scheduled_date"
                                    type="date"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Assigned Auditor *</label>
                                <select
                                    v-model="form.auditor_id"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option v-for="u in auditors" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Notes</label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-800">
                            <button
                                type="button"
                                @click="isEditModalOpen = false"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50"
                            >
                                Update Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
