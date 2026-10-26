<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    BuildingOffice2Icon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    MagnifyingGlassIcon,
    ExclamationTriangleIcon,
    XMarkIcon,
    BriefcaseIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
    departments: Object,
    positions: Object,
    allDepartments: Array,
    filters: Object,
});

const activeTab = ref(props.filters?.tab || 'departments');
const searchQuery = ref(props.filters?.search || '');
const selectedDeptFilter = ref(props.filters?.department_id || '');

const switchTab = (tab) => {
    activeTab.value = tab;
    searchQuery.value = '';
    selectedDeptFilter.value = '';
    router.get(route('settings.departments'), { tab }, { preserveState: true, replace: true });
};

const doSearch = () => {
    router.get(route('settings.departments'), { 
        tab: activeTab.value,
        search: searchQuery.value,
        department_id: selectedDeptFilter.value
    }, { preserveState: true, replace: true });
};

// ==========================================
// DEPARTMENT LOGIC
// ==========================================
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteConfirm = ref(false);
const deptToEdit = ref(null);
const deptToDelete = ref(null);

const form = useForm({
    code: '',
    name: '',
    description: '',
    is_active: true,
});

const openCreateModal = () => {
    form.clearErrors();
    form.reset();
    form.is_active = true;
    showCreateModal.value = true;
};

const createDept = () => {
    form.post(route('settings.departments.store'), {
        onSuccess: () => { showCreateModal.value = false; },
    });
};

const openEditModal = (dept) => {
    deptToEdit.value = dept;
    form.clearErrors();
    form.code = dept.code || '';
    form.name = dept.name;
    form.description = dept.description || '';
    form.is_active = !!dept.is_active;
    showEditModal.value = true;
};

const updateDept = () => {
    form.put(route('settings.departments.update', deptToEdit.value.id), {
        onSuccess: () => { showEditModal.value = false; },
    });
};

const confirmDelete = (dept) => {
    deptToDelete.value = dept;
    showDeleteConfirm.value = true;
};

const deleteDept = () => {
    router.delete(route('settings.departments.destroy', deptToDelete.value.id), {
        onSuccess: () => { showDeleteConfirm.value = false; },
    });
};

// ==========================================
// POSITION LOGIC
// ==========================================
const showCreatePosModal = ref(false);
const showEditPosModal = ref(false);
const showDeletePosConfirm = ref(false);
const posToEdit = ref(null);
const posToDelete = ref(null);

const posForm = useForm({
    name: '',
    department_id: '',
    salary_range_min: '',
    salary_range_max: '',
    is_active: true,
});

const openCreatePosModal = () => {
    posForm.clearErrors();
    posForm.reset();
    posForm.department_id = selectedDeptFilter.value || (props.allDepartments?.[0]?.id || '');
    posForm.is_active = true;
    showCreatePosModal.value = true;
};

const createPos = () => {
    posForm.post(route('settings.positions.store'), {
        onSuccess: () => { showCreatePosModal.value = false; },
    });
};

const openEditPosModal = (pos) => {
    posToEdit.value = pos;
    posForm.clearErrors();
    posForm.name = pos.name;
    posForm.department_id = pos.department_id;
    posForm.salary_range_min = pos.salary_range_min || '';
    posForm.salary_range_max = pos.salary_range_max || '';
    posForm.is_active = !!pos.is_active;
    showEditPosModal.value = true;
};

const updatePos = () => {
    posForm.put(route('settings.positions.update', posToEdit.value.id), {
        onSuccess: () => { showEditPosModal.value = false; },
    });
};

const confirmDeletePos = (pos) => {
    posToDelete.value = pos;
    showDeletePosConfirm.value = true;
};

const deletePos = () => {
    router.delete(route('settings.positions.destroy', posToDelete.value.id), {
        onSuccess: () => { showDeletePosConfirm.value = false; },
    });
};
</script>

<template>
    <Head title="Departments & Positions" />
    
    <AppLayout title="Departments & Positions">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-500">
                        <BriefcaseIcon v-if="activeTab === 'positions'" class="h-7 w-7" />
                        <BuildingOffice2Icon v-else class="h-7 w-7" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Departments & Positions</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Manage organizational structure, company departments, and job titles</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button 
                        v-if="activeTab === 'departments'"
                        @click="openCreateModal"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 hover:bg-blue-500 transition-all duration-200"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Add Department
                    </button>
                    <button 
                        v-else
                        @click="openCreatePosModal"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-900/20 hover:bg-indigo-500 transition-all duration-200"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Add Position
                    </button>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800">
                <button 
                    @click="switchTab('departments')"
                    class="py-3 px-4 text-sm font-bold border-b-2 transition flex items-center gap-2"
                    :class="activeTab === 'departments' 
                        ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                        : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                >
                    <BuildingOffice2Icon class="w-4 h-4" />
                    <span>Departments</span>
                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                        {{ departments?.total || departments?.data?.length || 0 }}
                    </span>
                </button>
                <button 
                    @click="switchTab('positions')"
                    class="py-3 px-4 text-sm font-bold border-b-2 transition flex items-center gap-2"
                    :class="activeTab === 'positions' 
                        ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                        : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                >
                    <BriefcaseIcon class="w-4 h-4" />
                    <span>Positions (Jabatan)</span>
                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                        {{ positions?.total || positions?.data?.length || 0 }}
                    </span>
                </button>
            </div>

            <!-- TAB 1: DEPARTMENTS -->
            <div v-if="activeTab === 'departments'" class="rounded-2xl glass-card overflow-hidden shadow-xl">
                <!-- Search -->
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50">
                    <div class="relative max-w-sm w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon class="h-5 w-5 text-slate-500" />
                        </div>
                        <input 
                            v-model="searchQuery"
                            @keyup.enter="doSearch"
                            type="text" 
                            placeholder="Search departments..." 
                            class="block w-full pl-10 pr-3 py-2 glass-card rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-500 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all"
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden md:table-cell">Description</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-center">Employees</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <tr v-for="dept in departments.data" :key="dept.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <span class="text-xs font-mono font-bold text-blue-400 bg-blue-500/10 px-2 py-1 rounded-lg">{{ dept.code || '-' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ dept.name }}</div>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <div class="text-sm text-slate-500 dark:text-slate-400 max-w-xs truncate">{{ dept.description || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-mono font-bold text-slate-600 dark:text-slate-300">
                                        <UsersIcon class="w-3.5 h-3.5 text-slate-400" />
                                        {{ dept.employees_count || 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="dept.is_active" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-xs font-bold border border-emerald-500/20">Active</span>
                                    <span v-else class="inline-flex items-center px-2.5 py-1 rounded-lg bg-red-500/10 text-red-400 text-xs font-bold border border-red-500/20">Inactive</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button 
                                            type="button"
                                            @click="openEditModal(dept)"
                                            class="p-2 text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-blue-400/10 rounded-xl transition-all border border-transparent hover:border-blue-500/30"
                                            title="Edit"
                                        >
                                            <PencilSquareIcon class="h-5 w-5" />
                                        </button>
                                        <button 
                                            type="button"
                                            @click="confirmDelete(dept)"
                                            class="p-2 text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-red-400/10 rounded-xl transition-all border border-transparent hover:border-red-500/30"
                                            title="Delete"
                                        >
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="departments.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">
                                    No departments found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: POSITIONS -->
            <div v-else class="rounded-2xl glass-card overflow-hidden shadow-xl">
                <!-- Search & Filters -->
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <div class="relative max-w-sm w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon class="h-5 w-5 text-slate-500" />
                        </div>
                        <input 
                            v-model="searchQuery"
                            @keyup.enter="doSearch"
                            type="text" 
                            placeholder="Search position or department..." 
                            class="block w-full pl-10 pr-3 py-2 glass-card rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all"
                        />
                    </div>
                    <div class="w-full sm:w-64">
                        <select 
                            v-model="selectedDeptFilter"
                            @change="doSearch"
                            class="block w-full py-2 px-3 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500"
                        >
                            <option value="">-- All Departments --</option>
                            <option v-for="d in allDepartments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Table Positions -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Position Title</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-center">Assigned Employees</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <tr v-for="pos in (positions?.data || [])" :key="pos.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ pos.name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-500/10 text-indigo-400 text-xs font-semibold border border-indigo-500/20">
                                        {{ pos.department?.name || 'Unassigned' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-mono font-bold text-slate-600 dark:text-slate-300">
                                        <UsersIcon class="w-3.5 h-3.5 text-slate-400" />
                                        {{ pos.employees_count || 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="pos.is_active" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 text-xs font-bold border border-emerald-500/20">Active</span>
                                    <span v-else class="inline-flex items-center px-2.5 py-1 rounded-lg bg-red-500/10 text-red-400 text-xs font-bold border border-red-500/20">Inactive</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button 
                                            type="button"
                                            @click="openEditPosModal(pos)"
                                            class="p-2 text-slate-500 dark:text-slate-400 hover:text-indigo-400 hover:bg-indigo-400/10 rounded-xl transition-all border border-transparent hover:border-indigo-500/30"
                                            title="Edit Position"
                                        >
                                            <PencilSquareIcon class="h-5 w-5" />
                                        </button>
                                        <button 
                                            type="button"
                                            @click="confirmDeletePos(pos)"
                                            class="p-2 text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-red-400/10 rounded-xl transition-all border border-transparent hover:border-red-500/30"
                                            title="Delete Position"
                                        >
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!positions?.data || positions.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">
                                    No positions found. Click "Add Position" above to create one.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- MODALS: DEPARTMENTS -->
        <!-- ============================================== -->
        <!-- Create Dept Modal -->
        <TransitionRoot as="template" :show="showCreateModal">
            <Dialog as="div" class="relative z-[99]" @close="showCreateModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                <form @submit.prevent="createDept">
                                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                                        <DialogTitle as="h3" class="text-lg font-bold text-slate-900 dark:text-white">Add New Department</DialogTitle>
                                        <button @click="showCreateModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-6 w-6" />
                                        </button>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Code</label>
                                            <input v-model="form.code" type="text" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all" placeholder="e.g. PRD, LOG, IT" />
                                            <p v-if="form.errors.code" class="mt-1.5 text-xs text-red-500">{{ form.errors.code }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Name <span class="text-red-400">*</span></label>
                                            <input v-model="form.name" type="text" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all" placeholder="Department name" />
                                            <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-500">{{ form.errors.name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Description</label>
                                            <textarea v-model="form.description" rows="2" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all" placeholder="Optional description"></textarea>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <input v-model="form.is_active" type="checkbox" id="create_is_active" class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500" />
                                            <label for="create_is_active" class="text-sm text-slate-500 dark:text-slate-400">Active</label>
                                        </div>
                                    </div>
                                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                                        <button @click="showCreateModal = false" type="button" class="px-4 py-2 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="form.processing"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                        >
                                            Save Department
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Edit Dept Modal -->
        <TransitionRoot as="template" :show="showEditModal">
            <Dialog as="div" class="relative z-[99]" @close="showEditModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                <form @submit.prevent="updateDept">
                                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                                        <DialogTitle as="h3" class="text-lg font-bold text-slate-900 dark:text-white">Edit Department</DialogTitle>
                                        <button @click="showEditModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-6 w-6" />
                                        </button>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Code</label>
                                            <input v-model="form.code" type="text" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all" />
                                            <p v-if="form.errors.code" class="mt-1.5 text-xs text-red-500">{{ form.errors.code }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Name <span class="text-red-400">*</span></label>
                                            <input v-model="form.name" type="text" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all" />
                                            <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-500">{{ form.errors.name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Description</label>
                                            <textarea v-model="form.description" rows="2" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"></textarea>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <input v-model="form.is_active" type="checkbox" id="edit_is_active" class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500" />
                                            <label for="edit_is_active" class="text-sm text-slate-500 dark:text-slate-400">Active</label>
                                        </div>
                                    </div>
                                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                                        <button @click="showEditModal = false" type="button" class="px-4 py-2 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="form.processing"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                        >
                                            Update Department
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Delete Dept Confirmation -->
        <TransitionRoot as="template" :show="showDeleteConfirm">
            <Dialog as="div" class="relative z-[99]" @close="showDeleteConfirm = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                                <div class="p-6">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500 mb-6">
                                        <ExclamationTriangleIcon class="h-10 w-10" />
                                    </div>
                                    <div class="text-center">
                                        <DialogTitle as="h3" class="text-xl font-bold text-slate-900 dark:text-white mb-2">Delete Department</DialogTitle>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">
                                            Are you sure you want to delete <span class="text-slate-900 dark:text-white font-bold">{{ deptToDelete?.name }}</span>? This action cannot be undone.
                                        </p>
                                    </div>
                                    <div class="mt-8 flex gap-3">
                                        <button 
                                            @click="showDeleteConfirm = false"
                                            class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all"
                                        >
                                            No, Keep it
                                        </button>
                                        <button 
                                            @click="deleteDept"
                                            class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 text-sm font-semibold text-white shadow-lg shadow-red-900/20 hover:bg-red-500 transition-all"
                                        >
                                            Yes, Delete
                                        </button>
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- ============================================== -->
        <!-- MODALS: POSITIONS -->
        <!-- ============================================== -->
        <!-- Create Position Modal -->
        <TransitionRoot as="template" :show="showCreatePosModal">
            <Dialog as="div" class="relative z-[99]" @close="showCreatePosModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                <form @submit.prevent="createPos">
                                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                                        <DialogTitle as="h3" class="text-lg font-bold text-slate-900 dark:text-white">Add New Position</DialogTitle>
                                        <button @click="showCreatePosModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-6 w-6" />
                                        </button>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Department <span class="text-red-400">*</span></label>
                                            <select 
                                                v-model="posForm.department_id" 
                                                class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all"
                                            >
                                                <option value="">-- Select Department --</option>
                                                <option v-for="d in allDepartments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                            </select>
                                            <p v-if="posForm.errors.department_id" class="mt-1.5 text-xs text-red-500">{{ posForm.errors.department_id }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Position Name <span class="text-red-400">*</span></label>
                                            <input v-model="posForm.name" type="text" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all" placeholder="e.g. Operator, Staff HR, Welder" />
                                            <p v-if="posForm.errors.name" class="mt-1.5 text-xs text-red-500">{{ posForm.errors.name }}</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Salary Min (Optional)</label>
                                                <input v-model="posForm.salary_range_min" type="number" class="block w-full px-4 py-2 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all" placeholder="0" />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Salary Max (Optional)</label>
                                                <input v-model="posForm.salary_range_max" type="number" class="block w-full px-4 py-2 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all" placeholder="0" />
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <input v-model="posForm.is_active" type="checkbox" id="create_pos_active" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                            <label for="create_pos_active" class="text-sm text-slate-500 dark:text-slate-400">Active</label>
                                        </div>
                                    </div>
                                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                                        <button @click="showCreatePosModal = false" type="button" class="px-4 py-2 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="posForm.processing"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-900/20 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                        >
                                            Save Position
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Edit Position Modal -->
        <TransitionRoot as="template" :show="showEditPosModal">
            <Dialog as="div" class="relative z-[99]" @close="showEditPosModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                <form @submit.prevent="updatePos">
                                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                                        <DialogTitle as="h3" class="text-lg font-bold text-slate-900 dark:text-white">Edit Position</DialogTitle>
                                        <button @click="showEditPosModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-6 w-6" />
                                        </button>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Department <span class="text-red-400">*</span></label>
                                            <select 
                                                v-model="posForm.department_id" 
                                                class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all"
                                            >
                                                <option value="">-- Select Department --</option>
                                                <option v-for="d in allDepartments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                            </select>
                                            <p v-if="posForm.errors.department_id" class="mt-1.5 text-xs text-red-500">{{ posForm.errors.department_id }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-1.5">Position Name <span class="text-red-400">*</span></label>
                                            <input v-model="posForm.name" type="text" class="block w-full px-4 py-2.5 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all" />
                                            <p v-if="posForm.errors.name" class="mt-1.5 text-xs text-red-500">{{ posForm.errors.name }}</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Salary Min (Optional)</label>
                                                <input v-model="posForm.salary_range_min" type="number" class="block w-full px-4 py-2 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all" />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Salary Max (Optional)</label>
                                                <input v-model="posForm.salary_range_max" type="number" class="block w-full px-4 py-2 glass-card rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 transition-all" />
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <input v-model="posForm.is_active" type="checkbox" id="edit_pos_active" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" />
                                            <label for="edit_pos_active" class="text-sm text-slate-500 dark:text-slate-400">Active</label>
                                        </div>
                                    </div>
                                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                                        <button @click="showEditPosModal = false" type="button" class="px-4 py-2 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="posForm.processing"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-900/20 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                        >
                                            Update Position
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Delete Position Confirmation -->
        <TransitionRoot as="template" :show="showDeletePosConfirm">
            <Dialog as="div" class="relative z-[99]" @close="showDeletePosConfirm = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                                <div class="p-6">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500 mb-6">
                                        <ExclamationTriangleIcon class="h-10 w-10" />
                                    </div>
                                    <div class="text-center">
                                        <DialogTitle as="h3" class="text-xl font-bold text-slate-900 dark:text-white mb-2">Delete Position</DialogTitle>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">
                                            Are you sure you want to delete <span class="text-slate-900 dark:text-white font-bold">{{ posToDelete?.name }}</span>? This action cannot be undone.
                                        </p>
                                    </div>
                                    <div class="mt-8 flex gap-3">
                                        <button 
                                            @click="showDeletePosConfirm = false"
                                            class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all"
                                        >
                                            No, Keep it
                                        </button>
                                        <button 
                                            @click="deletePos"
                                            class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 text-sm font-semibold text-white shadow-lg shadow-red-900/20 hover:bg-red-500 transition-all"
                                        >
                                            Yes, Delete
                                        </button>
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>
