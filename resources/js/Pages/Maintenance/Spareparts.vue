<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { 
    CubeIcon, 
    MagnifyingGlassIcon,
    PlusIcon,
    PencilIcon,
    ExclamationCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    spareparts: Array,
    stats: Object
});

const showModal = ref(false);
const isEdit = ref(false);
const search = ref('');

const form = useForm({
    id: null,
    name: '',
    part_number: '',
    location: '',
    stock: 0,
    min_stock: 5,
});

const editAdjustment = ref({
    adjustment: 0
});

const openCreate = () => {
    isEdit.value = false;
    form.reset();
    showModal.value = true;
};

const openEdit = (part) => {
    isEdit.value = true;
    form.id = part.id;
    form.name = part.name;
    form.part_number = part.part_number;
    form.location = part.location;
    form.stock = part.stock;
    form.min_stock = part.min_stock;
    editAdjustment.value.adjustment = 0;
    showModal.value = true;
};

const submit = () => {
    if (isEdit.value) {
        // If adjustment is present, send only that
        if (editAdjustment.value.adjustment !== 0) {
            useForm({ adjustment: editAdjustment.value.adjustment })
                .put(route('maintenance.spareparts.update', form.id), {
                    onSuccess: () => showModal.value = false
                });
        } else {
             form.put(route('maintenance.spareparts.update', form.id), {
                onSuccess: () => showModal.value = false
            });
        }
    } else {
        form.post(route('maintenance.spareparts.store'), {
            onSuccess: () => showModal.value = false
        });
    }
};

const filteredParts = () => {
    if (!search.value) return props.spareparts;
    const q = search.value.toLowerCase();
    return props.spareparts.filter(p => 
        p.name.toLowerCase().includes(q) || 
        p.part_number.toLowerCase().includes(q) ||
        p.location.toLowerCase().includes(q)
    );
};
</script>

<template>
    <Head title="Spareparts Inventory" />

    <AppLayout title="Spareparts Inventory" :render-header="false">
        <div class="min-h-screen bg-[#050510] relative overflow-hidden font-mono text-cyan-50 selection:bg-amber-500/30">
            
            <!-- Dynamic Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-950/20 to-[#050510]"></div>
                 <div class="perspective-grid absolute inset-0 opacity-10"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6 max-w-7xl mx-auto">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-4">
                    <div>
                         <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] bg-amber-500/10 border border-amber-500/20 rounded text-amber-400 tracking-[0.2em] uppercase">
                                Inventory Control
                            </span>
                            <span class="px-2 py-0.5 text-[10px] bg-white/5 border border-white/10 rounded text-slate-400 tracking-[0.2em] uppercase">MAINT.PARTS.V1</span>
                        </div>
                        <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-white to-orange-400 tracking-widest uppercase glow-text">
                            SPAREPARTS INV.
                        </h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="relative group">
                            <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-4 w-4 text-slate-500 group-focus-within:text-amber-400" />
                            <input v-model="search" type="text" placeholder="Search Part No..." class="bg-white/5 border border-white/10 rounded-lg pl-9 pr-4 py-2 text-sm text-white focus:border-amber-500 outline-none w-64 transition-all">
                        </div>
                        <button 
                            @click="openCreate"
                            class="px-4 py-2 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-white font-bold rounded-lg shadow-[0_0_15px_rgba(245,158,11,0.3)] transition-all flex items-center gap-2 text-sm"
                        >
                            <PlusIcon class="h-4 w-4" /> ADD PART
                        </button>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="hud-panel p-4">
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest">Total Types</p>
                        <h3 class="text-2xl font-black text-white">{{ stats.total_items }}</h3>
                    </div>
                    <div class="hud-panel p-4 border-amber-500/30">
                        <p class="text-[10px] text-amber-400 uppercase tracking-widest">Low Stock Items</p>
                         <h3 class="text-2xl font-black text-amber-500">{{ stats.low_stock }}</h3>
                    </div>
                    <div class="hud-panel p-4">
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest">Total Value</p>
                        <h3 class="text-2xl font-black text-white">{{ stats.stock_value }}</h3>
                    </div>
                </div>

                <!-- Inventory Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="part in filteredParts()" :key="part.id" 
                        class="hud-panel p-4 relative group hover:border-amber-500/50 transition-all"
                    >
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded bg-white/5 flex items-center justify-center text-slate-400 group-hover:text-amber-400 transition-colors">
                                    <CubeIcon class="h-6 w-6" />
                                </div>
                                <div>
                                    <h4 class="font-bold text-white group-hover:text-amber-300 transition-colors">{{ part.name }}</h4>
                                    <p class="text-xs text-slate-500 font-mono">{{ part.part_number }}</p>
                                </div>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded border" :class="part.status === 'Optimal' ? 'border-emerald-500/30 text-emerald-400' : 'border-rose-500/30 text-rose-400'">
                                {{ part.status }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase">Location</p>
                                <p class="text-slate-300 font-mono">{{ part.location }}</p>
                            </div>
                             <div class="text-right">
                                <p class="text-[10px] text-slate-500 uppercase">Unit Cost</p>
                                <p class="text-slate-300 font-mono">{{ part.cost }}</p>
                            </div>
                        </div>

                        <div class="flex items-end justify-between border-t border-white/5 pt-4">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase">Current Stock</p>
                                <div class="flex items-baseline gap-1">
                                    <h3 class="text-2xl font-black font-mono" :class="part.stock <= part.min_stock ? 'text-rose-500' : 'text-emerald-400'">
                                        {{ part.stock }}
                                    </h3>
                                    <span class="text-xs text-slate-500">units</span>
                                </div>
                            </div>
                             <button 
                                @click="openEdit(part)"
                                class="p-2 text-slate-400 hover:text-white hover:bg-white/10 rounded transition-colors"
                            >
                                <PencilIcon class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-amber-500 to-transparent w-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>
                </div>

            </div>

             <!-- Modal -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                 <div class="bg-[#0f172a] border border-amber-500/30 p-6 rounded-xl w-full max-w-lg shadow-[0_0_50px_rgba(245,158,11,0.2)]">
                    <h3 class="text-lg font-bold text-amber-400 mb-4 uppercase tracking-wider">
                        {{ isEdit ? 'Update Inventory' : 'Add New Part' }}
                    </h3>
                    
                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Quick Stock Adjustment -->
                        <div v-if="isEdit" class="bg-white/5 p-4 rounded border border-white/10 mb-4">
                            <label class="block text-xs text-slate-400 mb-2">Quick Adjustment (+ Add, - Remove)</label>
                            <div class="flex gap-2">
                                <input v-model="editAdjustment.adjustment" type="number" class="flex-1 bg-black/40 border border-white/10 rounded p-2 text-white outline-none focus:border-amber-500 text-center font-bold">
                            </div>
                            <p class="text-[10px] text-slate-500 mt-2 text-center">Entering a value here will only update stock quantity.</p>
                        </div>

                        <!-- Full Form -->
                        <div :class="{'opacity-50 pointer-events-none': isEdit && editAdjustment.adjustment !== 0}">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Part Name</label>
                                    <input v-model="form.name" type="text" class="w-full bg-white/5 border border-white/10 rounded p-2 text-white outline-none focus:border-amber-500">
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Part Number</label>
                                    <input v-model="form.part_number" type="text" class="w-full bg-white/5 border border-white/10 rounded p-2 text-white outline-none focus:border-amber-500">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                     <label class="block text-xs text-slate-400 mb-1">Location</label>
                                    <input v-model="form.location" type="text" class="w-full bg-white/5 border border-white/10 rounded p-2 text-white outline-none focus:border-amber-500">
                                </div>
                                <div>
                                     <label class="block text-xs text-slate-400 mb-1">Min Stock Alert</label>
                                    <input v-model="form.min_stock" type="number" class="w-full bg-white/5 border border-white/10 rounded p-2 text-white outline-none focus:border-amber-500">
                                </div>
                            </div>
                             <div v-if="!isEdit">
                                <label class="block text-xs text-slate-400 mb-1">Initial Stock</label>
                                <input v-model="form.stock" type="number" class="w-full bg-white/5 border border-white/10 rounded p-2 text-white outline-none focus:border-amber-500">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-white/10">
                            <button type="button" @click="showModal = false" class="px-4 py-2 text-slate-400 hover:text-white">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-500 font-bold">SAVE CHANGES</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.hud-panel {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
}
.perspective-grid {
    background-image: 
        linear-gradient(to right, rgba(245, 158, 11, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(245, 158, 11, 0.05) 1px, transparent 1px);
    background-size: 40px 40px;
    transform: perspective(500px) rotateX(60deg) translateY(-100px) scale(2);
    animation: grid-move 20s linear infinite;
    transform-origin: top;
}
@keyframes grid-move {
    0% { background-position: 0 0; }
    100% { background-position: 0 40px; }
}
.glow-text {
    text-shadow: 0 0 10px rgba(245, 158, 11, 0.3);
}
</style>
