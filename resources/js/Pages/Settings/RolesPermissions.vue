<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ShieldCheckIcon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    ExclamationTriangleIcon,
    XMarkIcon,
    ChevronRightIcon,
    ChevronDownIcon,
    ChevronRightIcon as ChevronRightSmallIcon
} from '@heroicons/vue/24/outline';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
    roles: Array,
    permissionStructure: Object, // Structured: { module: { name, menus: { menu: { name, actions: [] } }, actions: [] } }
    availableActions: Array
});

const showRoleModal = ref(false);
const showDeleteConfirm = ref(false);
const roleToEdit = ref(null);
const roleToDelete = ref(null);
const expandedModules = ref({});

const form = useForm({
    name: '',
    permissions: [],
});

const openCreateModal = () => {
    roleToEdit.value = null;
    form.clearErrors();
    form.reset();
    showRoleModal.value = true;
};

const openEditModal = (role) => {
    roleToEdit.value = role;
    form.clearErrors();
    form.name = role.name;
    form.permissions = role.permissions.map(p => p.name);
    showRoleModal.value = true;
};

const saveRole = () => {
    if (roleToEdit.value) {
        form.put(route('settings.roles.update', roleToEdit.value.id), {
            onSuccess: () => showRoleModal.value = false,
        });
    } else {
        form.post(route('settings.roles.store'), {
            onSuccess: () => showRoleModal.value = false,
        });
    }
};

const confirmDelete = (role) => {
    roleToDelete.value = role;
    showDeleteConfirm.value = true;
};

const deleteRole = () => {
    router.delete(route('settings.roles.destroy', roleToDelete.value.id), {
        onSuccess: () => showDeleteConfirm.value = false,
    });
};

const togglePermission = (permName) => {
    const index = form.permissions.indexOf(permName);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(permName);
    }
};

const hasPermission = (permName) => {
    return form.permissions.includes(permName);
};

const toggleModuleCategory = (moduleKey) => {
    expandedModules.value[moduleKey] = !expandedModules.value[moduleKey];
};

const toggleBulk = (moduleKey, menuKey = null) => {
    let permsToToggle = [];
    
    if (menuKey) {
        // Toggle specific menu actions
        permsToToggle = props.availableActions.map(a => `${moduleKey}.${menuKey}.${a}`);
    } else {
        // Toggle entire module (including its sub-menus and base actions)
        const baseActions = props.availableActions.map(a => `${moduleKey}.${a}`);
        const menuActions = [];
        
        Object.keys(props.permissionStructure[moduleKey].menus || {}).forEach(mk => {
            props.availableActions.forEach(a => menuActions.push(`${moduleKey}.${mk}.${a}`));
        });
        
        permsToToggle = [...baseActions, ...menuActions];
    }
    
    // Check if all are already selected
    const allSelected = permsToToggle.every(p => form.permissions.includes(p));
    
    if (allSelected) {
        // Remove all
        form.permissions = form.permissions.filter(p => !permsToToggle.includes(p));
    } else {
        // Add missing ones
        permsToToggle.forEach(p => {
            if (!form.permissions.includes(p)) form.permissions.push(p);
        });
    }
};
</script>

<template>
    <Head title="Roles & Permissions" />
    
    <AppLayout title="Roles & Permissions">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-400">
                        <ShieldCheckIcon class="h-7 w-7" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Access Control</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Configure granular menu access and operational privileges</p>
                    </div>
                </div>
                <button 
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:text-white shadow-lg shadow-indigo-900/20 hover:bg-indigo-500 transition-all duration-200"
                >
                    <PlusIcon class="h-5 w-5" />
                    Create New Role
                </button>
            </div>

            <!-- Roles Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="role in roles" 
                    :key="role.id"
                    class="group relative rounded-2xl glass-card p-6 shadow-xl hover:border-indigo-500/50 transition-all duration-300"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-indigo-400 border border-slate-200 dark:border-slate-700">
                                <ShieldCheckIcon class="h-6 w-6" />
                            </div>
                            <h3 class="font-bold text-slate-900 dark:text-white">{{ role.name }}</h3>
                        </div>
                        <div class="flex gap-1" v-if="role.name !== 'Super Admin'">
                            <button 
                                type="button"
                                @click="openEditModal(role)" 
                                class="p-2 text-slate-500 dark:text-slate-400 hover:text-indigo-400 hover:bg-indigo-400/10 rounded-xl transition-all border border-transparent hover:border-indigo-500/30"
                                title="Edit Role"
                            >
                                <PencilSquareIcon class="h-5 w-5" />
                            </button>
                            <button 
                                type="button"
                                @click="confirmDelete(role)" 
                                class="p-2 text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-red-400/10 rounded-xl transition-all border border-transparent hover:border-red-500/30"
                                title="Delete Role"
                            >
                                <TrashIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Privileges Summary</div>
                        <div class="flex flex-wrap gap-2">
                            <span 
                                v-for="perm in role.permissions.slice(0, 3)" 
                                :key="perm.id"
                                class="inline-flex px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800 text-[10px] font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                            >
                                {{ perm.name }}
                            </span>
                            <span v-if="role.permissions.length > 3" class="text-[10px] text-slate-500 font-medium self-center">+{{ role.permissions.length - 3 }} more</span>
                            <span v-if="role.permissions.length === 0" class="text-xs italic text-slate-600">No permissions assigned</span>
                        </div>
                    </div>

                    <button 
                        @click="openEditModal(role)"
                        class="w-full mt-2 flex items-center justify-between px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-all group-hover:border-indigo-500/20 border border-transparent"
                    >
                        Configure Granular Access
                        <ChevronRightIcon class="h-3 w-3" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Role Form Modal (Includes Granular Permission Matrix) -->
        <TransitionRoot as="template" :show="showRoleModal">
            <Dialog as="div" class="relative z-[99]" @close="showRoleModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">
                                <form @submit.prevent="saveRole">
                                    <div class="px-8 py-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900">
                                        <DialogTitle as="h3" class="text-xl font-bold text-slate-900 dark:text-white">
                                            {{ roleToEdit ? 'Edit Role: ' + roleToEdit.name : 'Create New Role' }}
                                        </DialogTitle>
                                        <button @click="showRoleModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-7 w-7" />
                                        </button>
                                    </div>

                                    <div class="p-8 space-y-8 max-h-[70vh] overflow-y-auto custom-scrollbar">
                                        <!-- Role Name Section -->
                                        <div>
                                            <label class="block text-sm font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">Role Name</label>
                                            <input 
                                                v-model="form.name" 
                                                type="text" 
                                                class="block w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl text-lg font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all placeholder-slate-400 dark:placeholder-slate-600" 
                                                placeholder="e.g. Sales Coordinator" 
                                            />
                                            <p v-if="form.errors.name" class="mt-2 text-xs text-red-500 font-medium">{{ form.errors.name }}</p>
                                        </div>

                                        <!-- Granular Permission Matrix -->
                                        <div>
                                            <div class="flex items-center justify-between mb-4">
                                                <label class="block text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Granular Access Matrix</label>
                                                <div class="flex items-center gap-4">
                                                    <span class="text-xs text-slate-500 font-medium">{{ form.permissions.length }} active permissions</span>
                                                </div>
                                            </div>
                                            
                                            <div class="space-y-4">
                                                <div 
                                                    v-for="(moduleData, moduleKey) in permissionStructure" 
                                                    :key="moduleKey"
                                                    class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 overflow-hidden shadow-sm"
                                                >
                                                    <!-- Module Header -->
                                                    <div 
                                                    class="px-6 py-4 bg-slate-100/80 dark:bg-slate-900 flex items-center justify-between cursor-pointer border-b border-slate-200 dark:border-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                                        @click="toggleModuleCategory(moduleKey)"
                                                    >
                                                        <div class="flex items-center gap-3">
                                                            <component 
                                                                :is="expandedModules[moduleKey] ? ChevronDownIcon : ChevronRightSmallIcon" 
                                                                class="h-4 w-4 text-slate-500" 
                                                            />
                                                            <span class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ moduleData.name }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-4">
                                                            <button 
                                                                type="button" 
                                                                @click.stop="toggleBulk(moduleKey)"
                                                                class="text-[10px] font-bold text-indigo-500 hover:text-indigo-400 uppercase tracking-tighter"
                                                            >
                                                                Toggle All Module
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Sub-menus Matrix -->
                                                    <div v-show="expandedModules[moduleKey]" class="overflow-x-auto">
                                                        <table class="w-full text-left">
                                                            <thead>
                                                                <tr class="bg-slate-100 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800/30">
                                                                    <th class="px-8 py-3 text-[10px] font-bold text-slate-600 uppercase">Sub-menu / Action</th>
                                                                    <th v-for="action in availableActions" :key="action" class="px-4 py-3 text-[10px] font-bold text-slate-600 uppercase text-center">
                                                                        {{ action }}
                                                                    </th>
                                                                    <th class="px-4 py-3"></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/30">
                                                                <!-- Base Module Actions -->
                                                                <tr class="bg-indigo-500/5 border-b border-indigo-500/10">
                                                                    <td class="px-8 py-3">
                                                                        <span class="text-xs font-bold text-indigo-400 italic">General Module Access</span>
                                                                    </td>
                                                                    <td v-for="action in availableActions" :key="action" class="px-4 py-3 text-center">
                                                                        <button 
                                                                            type="button" 
                                                                            @click="togglePermission(`${moduleKey}.${action}`)"
                                                                            class="h-4 w-4 rounded border transition-all inline-flex items-center justify-center p-0"
                                                                            :class="hasPermission(`${moduleKey}.${action}`) 
                                                                                ? 'bg-indigo-600 border-indigo-600 shadow-[0_0_8px_rgba(79,70,229,0.4)]' 
                                                                                : 'bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'"
                                                                        >
                                                                            <svg v-if="hasPermission(`${moduleKey}.${action}`)" class="h-2.5 w-2.5 text-slate-900 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                                            </svg>
                                                                        </button>
                                                                    </td>
                                                                    <td class="px-4 py-3"></td>
                                                                </tr>

                                                                <!-- Sub-menus and Menus -->
                                                                <tr v-for="(menuData, menuKey) in moduleData.menus" :key="menuKey" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                                                    <td class="px-8 py-3 pl-12">
                                                                        <span class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ menuData.name }}</span>
                                                                    </td>
                                                                    <td v-for="action in availableActions" :key="action" class="px-4 py-3 text-center">
                                                                        <button 
                                                                            type="button" 
                                                                            @click="togglePermission(`${moduleKey}.${menuKey}.${action}`)"
                                                                            class="h-4 w-4 rounded border transition-all inline-flex items-center justify-center p-0"
                                                                            :class="hasPermission(`${moduleKey}.${menuKey}.${action}`) 
                                                                                ? 'bg-indigo-600 border-indigo-600 shadow-[0_0_8px_rgba(79,70,229,0.4)]' 
                                                                                : 'bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'"
                                                                        >
                                                                            <svg v-if="hasPermission(`${moduleKey}.${menuKey}.${action}`)" class="h-2.5 w-2.5 text-slate-900 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                                            </svg>
                                                                        </button>
                                                                    </td>
                                                                    <td class="px-4 py-3 text-right">
                                                                        <button 
                                                                            type="button" 
                                                                            @click="toggleBulk(moduleKey, menuKey)"
                                                                            class="text-[9px] font-bold text-slate-700 hover:text-indigo-400 uppercase transition-colors"
                                                                        >
                                                                            All
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-8 py-5 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-4">
                                        <button @click="showRoleModal = false" type="button" class="px-4 py-2 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="form.processing"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-2.5 text-sm font-bold text-slate-900 dark:text-white shadow-lg shadow-indigo-900/20 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                        >
                                            Commit Access Rights
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Delete Confirmation Modal -->
        <TransitionRoot as="template" :show="showDeleteConfirm">
            <Dialog as="div" class="relative z-[99]" @close="showDeleteConfirm = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/50 dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-2xl glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                                <div class="p-8 text-center">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500 mb-6 font-bold shadow-inner border border-red-500/20">
                                        <ExclamationTriangleIcon class="h-10 w-10" />
                                    </div>
                                    <DialogTitle as="h3" class="text-2xl font-black text-slate-900 dark:text-white mb-2 tracking-tight">Void Role?</DialogTitle>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 px-4 leading-relaxed">
                                        Deleting role <span class="text-slate-900 dark:text-white font-bold">{{ roleToDelete?.name }}</span> will revoke all granular sub-menu access for assigned users.
                                    </p>
                                    <div class="mt-8 flex gap-3">
                                        <button 
                                            @click="showDeleteConfirm = false"
                                            class="flex-1 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 text-sm font-bold text-slate-500 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-all"
                                        >
                                            Keep it
                                        </button>
                                        <button 
                                            @click="deleteRole"
                                            class="flex-1 px-4 py-3 rounded-xl bg-red-600 text-sm font-bold text-slate-900 dark:text-white shadow-lg shadow-red-900/20 hover:bg-red-500 transition-all"
                                        >
                                            Delete Permanently
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

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #475569;
}
</style>



