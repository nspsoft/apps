<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    PlusIcon, 
    PencilIcon, 
    TrashIcon, 
    XMarkIcon,
    CubeIcon,
    CpuChipIcon,
    TagIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    machines: Array,
});

const showModal = ref(false);
const editingMachine = ref(null);

const form = useForm({
    name: '',
    code: '',
    is_active: true,
});

const openCreateModal = () => {
    editingMachine.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (machine) => {
    editingMachine.value = machine;
    form.name = machine.name;
    form.code = machine.code || '';
    form.is_active = !!machine.is_active;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (editingMachine.value) {
        form.put(route('manufacturing.machines.update', editingMachine.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('manufacturing.machines.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteMachine = (machine) => {
    if (confirm(`Are you sure you want to delete machine "${machine.name}"?`)) {
        form.delete(route('manufacturing.machines.destroy', machine.id));
    }
};
</script>

<template>
    <Head title="Manajemen Mesin" />
    
    <AppLayout title="Manajemen Mesin">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Daftar Mesin / Line</h2>
                    <p class="text-sm text-slate-500 mt-1 uppercase tracking-widest font-bold">Machine Configuration</p>
                </div>
                <button 
                    @click="openCreateModal"
                    class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-5 py-3 text-sm font-bold text-slate-900 dark:text-white shadow-lg shadow-emerald-500/20 hover:from-emerald-500 transition-all active:scale-95"
                >
                    <PlusIcon class="h-5 w-5" />
                    Tambah Mesin Baru
                </button>
            </div>

            <!-- Table Card -->
            <div class="glass-card rounded-3xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-left">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Informasi Mesin</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Kode</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Status</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="machine in machines" :key="machine.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-emerald-400">
                                            <CpuChipIcon class="h-6 w-6" />
                                        </div>
                                        <div class="text-slate-900 dark:text-white font-bold text-lg">{{ machine.name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300 font-mono">
                                        <TagIcon class="h-4 w-4 text-slate-500" />
                                        {{ machine.code || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span 
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                        :class="machine.is_active ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border border-slate-500/30'"
                                    >
                                        {{ machine.is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button 
                                            @click="openEditModal(machine)"
                                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-blue-400 hover:bg-slate-700 hover:text-blue-300 transition-all active:scale-90"
                                            title="Edit"
                                        >
                                            <PencilIcon class="h-4 w-4" />
                                        </button>
                                        <button 
                                            @click="deleteMachine(machine)"
                                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-red-400 hover:bg-slate-700 hover:text-red-300 transition-all active:scale-90"
                                            title="Delete"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="machines.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500 italic">Belum ada mesin terdaftar</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-white dark:bg-slate-950/80 backdrop-blur-sm">
                    <div class="bg-white dark:bg-slate-950 w-full max-w-lg rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden">
                        <!-- Modal Header -->
                        <div class="relative p-6 border-b border-slate-200 dark:border-slate-800">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ editingMachine ? 'Edit Mesin' : 'Tambah Mesin Baru' }}</h3>
                            <button @click="closeModal" class="absolute right-6 top-6 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">
                                <XMarkIcon class="h-6 w-6" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <form @submit.prevent="submit" class="p-6 space-y-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Nama Mesin / Line</label>
                                <input 
                                    v-model="form.name"
                                    type="text"
                                    placeholder="e.g. Line 01 - Extrusion"
                                    class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-3.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50 transition-all"
                                    :class="{'border-red-500/50 focus:ring-red-500/50': form.errors.name}"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-red-400 text-xs mt-1.5 ml-1 font-medium">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Kode Mesin (Opsional)</label>
                                <input 
                                    v-model="form.code"
                                    type="text"
                                    placeholder="e.g. EX-01"
                                    class="w-full rounded-2xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-3.5 px-4 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-emerald-500/50 transition-all"
                                />
                            </div>

                            <div class="flex items-center gap-3 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800">
                                <input 
                                    v-model="form.is_active"
                                    type="checkbox"
                                    id="is_active"
                                    class="h-5 w-5 rounded-md border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-emerald-600 focus:ring-emerald-500/50"
                                />
                                <label for="is_active" class="text-sm font-bold text-slate-600 dark:text-slate-300">Setel sebagai Mesin Aktif</label>
                            </div>

                            <!-- Modal Actions -->
                            <div class="flex gap-3 pt-4">
                                <button 
                                    type="button"
                                    @click="closeModal"
                                    class="flex-1 py-4 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-700 active:scale-95 transition-all"
                                >
                                    Batal
                                </button>
                                <button 
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex-[2] py-4 rounded-2xl bg-emerald-600 text-slate-900 dark:text-white font-bold shadow-lg shadow-emerald-500/20 active:scale-95 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
                                >
                                    <span v-if="form.processing" class="animate-spin h-5 w-5 border-2 border-white/30 border-t-white rounded-full"></span>
                                    {{ editingMachine ? 'Simpan Perubahan' : 'Tambah Mesin' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>



