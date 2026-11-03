<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    AdjustmentsHorizontalIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    XMarkIcon,
    CheckCircleIcon,
    BuildingOffice2Icon,
    ListBulletIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    checklists: {
        type: Array,
        default: () => [],
    },
    buildings: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('checklists');
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isBuildingModalOpen = ref(false);
const buildingModalMode = ref('create'); // 'create' or 'edit'

const form = useForm({
    id: null,
    pillar: 'Sort',
    title: '',
    guideline_notes: '',
    weight_points: 5,
    sort_order: 1,
    is_active: true,
});

const buildingForm = useForm({
    id: null,
    name: '',
    code: '',
    description: '',
    floor_plan_image: null,
});

const openAddBuildingModal = () => {
    buildingModalMode.value = 'create';
    buildingForm.reset();
    buildingForm.id = null;
    isBuildingModalOpen.value = true;
};

const openEditBuildingModal = (building) => {
    buildingModalMode.value = 'edit';
    buildingForm.reset();
    buildingForm.id = building.id;
    buildingForm.name = building.name;
    buildingForm.code = building.code;
    buildingForm.description = building.description || '';
    buildingForm.floor_plan_image = null;
    isBuildingModalOpen.value = true;
};

const submitBuilding = () => {
    if (buildingModalMode.value === 'create') {
        buildingForm.post(route('audit-5s.buildings.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isBuildingModalOpen.value = false;
                buildingForm.reset();
            },
        });
    } else {
        buildingForm.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('audit-5s.buildings.update', buildingForm.id), {
            preserveScroll: true,
            onSuccess: () => {
                isBuildingModalOpen.value = false;
                buildingForm.reset();
            },
        });
    }
};

const deleteBuilding = (building) => {
    if (!confirm(`Are you sure you want to delete building "${building.name}" and all its zones? This action cannot be undone.`)) return;

    router.delete(route('audit-5s.buildings.destroy', building.id), {
        preserveScroll: true,
    });
};

const openAddModal = (defaultPillar = 'Sort') => {
    form.reset();
    form.pillar = defaultPillar;
    form.weight_points = 5;
    form.sort_order = 1;
    form.is_active = true;
    isAddModalOpen.value = true;
};

const openEditModal = (item) => {
    form.id = item.id;
    form.pillar = item.pillar;
    form.title = item.title;
    form.guideline_notes = item.guideline_notes;
    form.weight_points = item.weight_points;
    form.sort_order = item.sort_order;
    form.is_active = item.is_active;
    isEditModalOpen.value = true;
};

const submitAdd = () => {
    form.post(route('audit-5s.settings.checklists.store'), {
        onSuccess: () => {
            isAddModalOpen.value = false;
            form.reset();
        },
    });
};

const submitEdit = () => {
    form.put(route('audit-5s.settings.checklists.update', form.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
            form.reset();
        },
    });
};

const deleteItem = (item) => {
    if (!confirm(`Are you sure you want to delete "${item.title}"?`)) return;
    router.delete(route('audit-5s.settings.checklists.destroy', item.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="5S Audit Criteria & Master Settings">
        <Head title="5S Criteria & Settings" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800">
                            Standards & Configuration
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            5S Audit Criteria & Settings
                        </h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Customize evaluation criteria, audit checklists per pillar, weighting, and facility buildings.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        v-if="activeTab === 'checklists'"
                        type="button"
                        @click="openAddModal('Sort')"
                        class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition"
                    >
                        <PlusIcon class="size-4" />
                        <span>Add Checklist Item</span>
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="openAddBuildingModal()"
                        class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition"
                    >
                        <PlusIcon class="size-4" />
                        <span>Add Building</span>
                    </button>
                </div>
            </div>

            <!-- Tab Buttons -->
            <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800">
                <button
                    type="button"
                    @click="activeTab = 'checklists'"
                    :class="[
                        'inline-flex items-center gap-2 border-b-2 py-3 px-1 text-sm font-semibold transition',
                        activeTab === 'checklists'
                            ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'
                    ]"
                >
                    <ListBulletIcon class="size-4" />
                    <span>5S Checklist Evaluation Standards ({{ checklists.length }})</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'buildings'"
                    :class="[
                        'inline-flex items-center gap-2 border-b-2 py-3 px-1 text-sm font-semibold transition',
                        activeTab === 'buildings'
                            ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'
                    ]"
                >
                    <BuildingOffice2Icon class="size-4" />
                    <span>Buildings & Facilities ({{ buildings.length }})</span>
                </button>
            </div>

            <!-- Tab 1: Checklists -->
            <div v-if="activeTab === 'checklists'" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-left">Pillar</th>
                                <th class="px-4 py-3 text-left">Evaluation Criteria</th>
                                <th class="px-4 py-3 text-left">Operational Guideline</th>
                                <th class="px-4 py-3 text-center">Weight</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr
                                v-for="c in checklists"
                                :key="c.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                            >
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-md bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-900/60 dark:text-blue-300">
                                        {{ c.pillar }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ c.title }}</p>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-500 max-w-sm">
                                    {{ c.guideline_notes || '-' }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-center font-mono font-semibold">
                                    {{ c.weight_points }} pts
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold border',
                                            c.is_active
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300'
                                                : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400'
                                        ]"
                                    >
                                        {{ c.is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-right space-x-1">
                                    <button
                                        type="button"
                                        @click="openEditModal(c)"
                                        class="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800"
                                        title="Edit Criteria"
                                    >
                                        <PencilSquareIcon class="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteItem(c)"
                                        class="rounded p-1 text-rose-500 hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-slate-800"
                                        title="Delete Criteria"
                                    >
                                        <TrashIcon class="size-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Buildings -->
            <div v-if="activeTab === 'buildings'" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50 dark:bg-slate-800/40 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Industrial Facilities & Plant Buildings</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Manage plant buildings, assembly workshops, and their architectural layout floor maps.</p>
                    </div>
                    <button
                        type="button"
                        @click="openAddBuildingModal()"
                        class="inline-flex items-center gap-1.5 self-start sm:self-auto rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-500 transition"
                    >
                        <PlusIcon class="size-3.5" />
                        <span>Add Building</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        v-for="b in buildings"
                        :key="b.id"
                        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 flex flex-col justify-between"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-mono font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ b.code }}
                                </span>
                                <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">
                                    {{ b.zones_count ?? 0 }} Zones
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                {{ b.name }}
                            </h3>
                            <p class="text-xs text-slate-500">{{ b.description || 'No description provided.' }}</p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <Link
                                :href="route('audit-5s.floor-map', { building_id: b.id })"
                                class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400 flex items-center gap-1"
                            >
                                <span>Open Floor Map →</span>
                            </Link>

                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="openEditBuildingModal(b)"
                                    class="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 transition"
                                    title="Edit Building Details"
                                >
                                    <PencilSquareIcon class="size-4" />
                                </button>
                                <button
                                    v-if="buildings.length > 1"
                                    type="button"
                                    @click="deleteBuilding(b)"
                                    class="rounded p-1 text-rose-500 hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-slate-800 transition"
                                    title="Delete Building"
                                >
                                    <TrashIcon class="size-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal: Add Checklist -->
            <div
                v-if="isAddModalOpen"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm p-4 flex items-center justify-center"
                @click.self="isAddModalOpen = false"
            >
                <div class="w-full max-w-lg rounded-xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Add 5S Checklist Item</h3>
                        <button type="button" @click="isAddModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitAdd" class="mt-4 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">5S Pillar *</label>
                                <select
                                    v-model="form.pillar"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option value="Sort">1S - Sort (Ringkas)</option>
                                    <option value="Set In Order">2S - Set In Order (Rapi)</option>
                                    <option value="Shine">3S - Shine (Resik)</option>
                                    <option value="Standardize">4S - Standardize (Rawat)</option>
                                    <option value="Sustain">5S - Sustain (Rajin)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Weight Points (1-10) *</label>
                                <input
                                    v-model.number="form.weight_points"
                                    type="number"
                                    min="1"
                                    max="10"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm dark:bg-slate-800 dark:border-slate-700"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Item Title *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="e.g. Identification & Red Tagging of Unneeded Materials"
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm dark:bg-slate-800 dark:border-slate-700"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Guideline Notes (Penjelasan Standar)</label>
                            <textarea
                                v-model="form.guideline_notes"
                                rows="3"
                                placeholder="Criteria for scoring: what to inspect, what is considered abnormal..."
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm dark:bg-slate-800 dark:border-slate-700"
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3 items-center">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Sort Order</label>
                                <input
                                    v-model.number="form.sort_order"
                                    type="number"
                                    min="0"
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm dark:bg-slate-800 dark:border-slate-700"
                                />
                            </div>
                            <div class="pt-5">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="form.is_active"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Active in Audits</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-800">
                            <button
                                type="button"
                                @click="isAddModalOpen = false"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50"
                            >
                                Save Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal: Edit Checklist -->
            <div
                v-if="isEditModalOpen"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm p-4 flex items-center justify-center"
                @click.self="isEditModalOpen = false"
            >
                <div class="w-full max-w-lg rounded-xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Edit 5S Checklist Item</h3>
                        <button type="button" @click="isEditModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitEdit" class="mt-4 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">5S Pillar *</label>
                                <select
                                    v-model="form.pillar"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option value="Sort">1S - Sort (Ringkas)</option>
                                    <option value="Set In Order">2S - Set In Order (Rapi)</option>
                                    <option value="Shine">3S - Shine (Resik)</option>
                                    <option value="Standardize">4S - Standardize (Rawat)</option>
                                    <option value="Sustain">5S - Sustain (Rajin)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Weight Points (1-10) *</label>
                                <input
                                    v-model.number="form.weight_points"
                                    type="number"
                                    min="1"
                                    max="10"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm dark:bg-slate-800 dark:border-slate-700"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Item Title *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm dark:bg-slate-800 dark:border-slate-700"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Guideline Notes</label>
                            <textarea
                                v-model="form.guideline_notes"
                                rows="3"
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm dark:bg-slate-800 dark:border-slate-700"
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3 items-center">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Sort Order</label>
                                <input
                                    v-model.number="form.sort_order"
                                    type="number"
                                    min="0"
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm dark:bg-slate-800 dark:border-slate-700"
                                />
                            </div>
                            <div class="pt-5">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="form.is_active"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Active in Audits</span>
                                </label>
                            </div>
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
                                Update Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal: Add / Edit Building -->
            <div
                v-if="isBuildingModalOpen"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm p-4 flex items-center justify-center"
                @click.self="isBuildingModalOpen = false"
            >
                <div class="w-full max-w-md rounded-xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ buildingModalMode === 'edit' ? 'Edit Factory Building / Facility' : 'Add Factory Building / Facility' }}
                        </h3>
                        <button type="button" @click="isBuildingModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitBuilding" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Building Code *</label>
                            <input
                                v-model="buildingForm.code"
                                type="text"
                                required
                                placeholder="e.g. BLD-01"
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Building / Plant Name *</label>
                            <input
                                v-model="buildingForm.name"
                                type="text"
                                required
                                placeholder="e.g. Jidoka Main Production Facility"
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Description</label>
                            <textarea
                                v-model="buildingForm.description"
                                rows="2"
                                placeholder="Notes about this facility..."
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Floor Plan Image (Optional)</label>
                            <input
                                type="file"
                                accept="image/*,.svg"
                                @change="buildingForm.floor_plan_image = $event.target.files[0]"
                                class="mt-1 w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <p class="mt-1 text-[11px] text-slate-500">Upload SVG or PNG architectural layout map.</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-800">
                            <button
                                type="button"
                                @click="isBuildingModalOpen = false"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="buildingForm.processing"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50"
                            >
                                {{ buildingModalMode === 'edit' ? 'Update Building' : 'Save Building' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
