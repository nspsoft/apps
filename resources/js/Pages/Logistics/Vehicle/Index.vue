<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    TruckIcon, 
    PlusIcon, 
    MagnifyingGlassIcon,
    PencilSquareIcon,
    TrashIcon,
    ChevronUpDownIcon,
    FunnelIcon,
    TagIcon
} from '@heroicons/vue/24/outline';
import { CheckBadgeIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    vehicles: Object,
    filters: Object,
    vehicleStatuses: Array
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

const showModal = ref(false);
const editingVehicle = ref(null);

const form = useForm({
    license_plate: '',
    vehicle_type: '',
    brand: '',
    capacity_weight: 0,
    capacity_volume: 0,
    driver_name: '',
    status: 'available',
    notes: '',
    is_active: true
});

const openCreateModal = () => {
    editingVehicle.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (vehicle) => {
    editingVehicle.value = vehicle;
    form.clearErrors();
    form.license_plate = vehicle.license_plate;
    form.vehicle_type = vehicle.vehicle_type;
    form.brand = vehicle.brand;
    form.capacity_weight = vehicle.capacity_weight;
    form.capacity_volume = vehicle.capacity_volume;
    form.driver_name = vehicle.driver_name;
    form.status = vehicle.status;
    form.notes = vehicle.notes;
    form.is_active = vehicle.is_active;
    showModal.value = true;
};

const submit = () => {
    if (editingVehicle.value) {
        form.put(route('logistics.fleet.update', editingVehicle.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('logistics.fleet.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const deleteVehicle = (id) => {
    if (confirm('Are you sure you want to delete this vehicle?')) {
        form.delete(route('logistics.fleet.destroy', id));
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'available': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
        case 'busy': return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400';
        case 'maintenance': return 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400';
        default: return 'bg-slate-100 text-slate-700 dark:bg-slate-500/10 dark:text-slate-400';
    }
};
</script>

<template>
    <Head title="Vehicle Fleet" />

    <AppLayout title="Vehicle Fleet">
        <div class="sm:flex sm:items-center sm:justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Vehicle Fleet</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 font-medium uppercase tracking-widest">Master Data Armada</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <button
                    @click="openCreateModal"
                    class="group relative inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-500/20 transition-all hover:bg-blue-500 hover:shadow-blue-500/40 active:scale-95"
                >
                    <PlusIcon class="h-5 w-5" />
                    Tambah Kendaraan
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="md:col-span-2 relative group">
                <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari No Polisi, Driver, atau Tipe..."
                    class="w-full bg-white dark:bg-slate-900 border-0 ring-1 ring-slate-200 dark:ring-slate-800 rounded-xl pl-12 pr-4 py-3 text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 transition-all shadow-sm"
                />
            </div>
            <div class="relative group">
                <FunnelIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                <select
                    v-model="statusFilter"
                    class="w-full bg-white dark:bg-slate-900 border-0 ring-1 ring-slate-200 dark:ring-slate-800 rounded-xl pl-12 pr-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all shadow-sm appearance-none"
                >
                    <option value="">Semua Status</option>
                    <option v-for="s in vehicleStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>
        </div>

        <!-- Vehicles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div 
                v-for="vehicle in vehicles.data" 
                :key="vehicle.id"
                class="group relative bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transition-all hover:shadow-xl hover:border-blue-500/50"
            >
                <!-- Card Header -->
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="h-12 w-12 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500">
                            <TruckIcon class="h-7 w-7" />
                        </div>
                        <span 
                            class="px-3 py-1 text-xs font-bold rounded-full uppercase tracking-widest leading-none flex items-center gap-1.5"
                            :class="getStatusColor(vehicle.status)"
                        >
                            {{ vehicle.status }}
                        </span>
                    </div>
                    
                    <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight leading-tight mb-1">{{ vehicle.license_plate }}</h3>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ vehicle.brand }} - {{ vehicle.vehicle_type }}</p>

                    <div class="mt-6 space-y-3">
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                <PlusIcon class="w-4 h-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Driver Name</p>
                                <p class="text-slate-900 dark:text-white font-medium truncate">{{ vehicle.driver_name || 'No Pilot' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                <TagIcon class="w-4 h-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Capacity</p>
                                <p class="text-slate-900 dark:text-white font-medium truncate">{{ vehicle.capacity_weight }} Kg / {{ vehicle.capacity_volume }} m³</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="flex items-center border-t border-slate-100 dark:border-slate-800 divide-x divide-slate-100 dark:divide-slate-800">
                    <button 
                        @click="openEditModal(vehicle)"
                        class="flex-1 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-blue-500 transition-all flex items-center justify-center gap-2"
                    >
                        <PencilSquareIcon class="w-4 h-4" />
                        Edit
                    </button>
                    <button 
                        @click="deleteVehicle(vehicle.id)"
                        class="flex-1 py-3 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-500 transition-all flex items-center justify-center gap-2"
                    >
                        <TrashIcon class="w-4 h-4" />
                        Hapus
                    </button>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="vehicles.data.length > 0" class="mt-8 flex justify-center">
             <!-- Simplified Pagination as example -->
        </div>

        <!-- Zero State -->
        <div v-if="vehicles.data.length === 0" class="mt-12 text-center p-12 bg-white dark:bg-slate-900 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700">
            <TruckIcon class="mx-auto h-12 w-12 text-slate-400" />
            <h3 class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">Tidak ada kendaraan</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Mulai dengan menambahkan kendaraan baru ke armada Bapak.</p>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div @click="closeModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                
                <div class="relative w-full max-w-2xl rounded-3xl bg-white dark:bg-slate-900 shadow-2xl overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ editingVehicle ? 'Edit Kendaraan' : 'Tambah Kendaraan Baru' }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest font-bold">Informasi Detail Armada</p>
                        </div>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                            <PlusIcon class="h-6 w-6 rotate-45" />
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">No Polisi (License Plate)</label>
                                <input v-model="form.license_plate" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-blue-500 transition-all font-bold uppercase" placeholder="B 1234 ABC" required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Tipe Kendaraan</label>
                                <select v-model="form.vehicle_type" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-blue-500 transition-all">
                                    <option value="">Pilih Tipe</option>
                                    <option value="Truck">Truck</option>
                                    <option value="Van">Van</option>
                                    <option value="Box">Box</option>
                                    <option value="Motorcycle">Motorcycle</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Brand / Merk</label>
                                <input v-model="form.brand" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-blue-500 transition-all font-medium" placeholder="Mitsubishi Fuso" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Driver Name</label>
                                <input v-model="form.driver_name" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-blue-500 transition-all font-medium" placeholder="Nama Supir" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Capacity Weight (Kg)</label>
                                <input v-model="form.capacity_weight" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-blue-500 transition-all font-medium" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Status Awal</label>
                                <select v-model="form.status" class="w-full bg-slate-50 dark:bg-slate-800 border-0 ring-1 ring-slate-200 dark:ring-slate-700 rounded-xl px-4 py-3 shadow-inner focus:ring-2 focus:ring-blue-500 transition-all">
                                    <option v-for="s in vehicleStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-8 flex items-center justify-end gap-3">
                            <button 
                                type="button" 
                                @click="closeModal"
                                class="px-6 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit"
                                :disabled="form.processing"
                                class="px-10 py-2.5 text-sm font-black text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-lg shadow-blue-500/20 active:scale-95 transition-all disabled:opacity-50"
                            >
                                {{ editingVehicle ? 'Simpan Perubahan' : 'Tambah Kendaraan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.glass-card {
    background: rgba(255, 255, 255, 0.02);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.05);
}
</style>
