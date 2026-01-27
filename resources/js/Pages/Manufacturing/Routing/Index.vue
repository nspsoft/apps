<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatCurrency } from '@/helpers';
import { 
    MagnifyingGlassIcon, 
    ArrowRightIcon, 
    ClockIcon, 
    BanknotesIcon, 
    CpuChipIcon,
    Bars3Icon,
    ChevronRightIcon,
    ListBulletIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    routings: Object,
    filters: Object,
});

const search = ref(props.filters.search);

watch(search, debounce((value) => {
    router.get(route('manufacturing.routing.index'), { search: value }, { preserveState: true, replace: true });
}, 300));


const getRoutingCosts = (operations) => {
    const labor = operations.reduce((acc, op) => acc + parseFloat(op.labor_cost || 0), 0);
    const machine = operations.reduce((acc, op) => acc + parseFloat(op.machine_cost || 0), 0);
    return { labor, machine, total: labor + machine };
};
</script>

<template>
    <Head title="Production Routing" />
    
    <AppLayout title="Production Routing">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header & Search -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Production Routing</h2>
                    <p class="text-sm text-slate-500 mt-1 uppercase tracking-widest font-bold font-mono">Process Steps & Cost Center</p>
                </div>
                
                <div class="relative w-full md:w-96">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <MagnifyingGlassIcon class="h-5 w-5 text-slate-500" />
                    </div>
                    <input 
                        v-model="search"
                        type="text"
                        placeholder="Search BOM or Product..."
                        class="block w-full rounded-2xl border-0 bg-white dark:bg-slate-950 py-3.5 pl-11 pr-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50 transition-all shadow-lg"
                    />
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="glass-card rounded-3xl p-6 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                        <ListBulletIcon class="h-16 w-16 text-slate-900 dark:text-white" />
                    </div>
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Total Active Routings</div>
                    <div class="text-3xl font-bold text-slate-900 dark:text-white font-mono">{{ routings.total }}</div>
                </div>
                <div class="bg-indigo-600/5 border border-indigo-500/20 rounded-3xl p-6 shadow-sm relative overflow-hidden group">
                     <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                        <ClockIcon class="h-16 w-16 text-indigo-400" />
                    </div>
                    <div class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest mb-1 font-mono">Process Mapping</div>
                    <div class="text-xs text-indigo-200 mt-1 italic">Monitor lead times and production steps efficiency.</div>
                </div>
            </div>

            <!-- List -->
            <div class="space-y-4">
                <div v-for="routing in routings.data" :key="routing.id" class="group glass-card rounded-[2rem] p-6 hover:border-blue-500/30 transition-all shadow-sm hover:shadow-blue-500/5">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-8">
                        <!-- BOM Identity -->
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-50 dark:bg-slate-800 text-[10px] font-bold text-slate-500 dark:text-slate-400 font-mono border border-slate-200 dark:border-slate-700">
                                    {{ routing.code }}
                                </span>
                                <span v-if="routing.version" class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">v{{ routing.version }}</span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-blue-400 transition-colors">{{ routing.product?.name }}</h3>
                            <p class="text-xs text-slate-500 font-medium italic mt-0.5">{{ routing.name }}</p>
                        </div>

                        <!-- Routing Metadata -->
                        <div class="flex flex-wrap items-center gap-8 lg:px-8 lg:border-l lg:border-r lg:border-slate-200 dark:border-slate-800/50">
                            <div class="flex flex-col items-center">
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 flex items-center gap-1">
                                    <Bars3Icon class="h-3 w-3" />
                                    Steps
                                </div>
                                <div class="text-xl font-bold text-slate-900 dark:text-white font-mono">
                                    {{ routing.operations_count }}
                                </div>
                            </div>

                            <div class="flex flex-col items-center">
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 flex items-center gap-1">
                                    <ClockIcon class="h-3 w-3" />
                                    Lead Time
                                </div>
                                <div class="text-xl font-bold text-slate-900 dark:text-white font-mono">
                                    {{ routing.lead_time_days }}<span class="text-[10px] ml-0.5 text-slate-500 dark:text-slate-400">d</span>
                                </div>
                            </div>
                        </div>

                        <!-- Cost Summary -->
                        <div class="flex-grow max-w-xs space-y-2">
                            <div class="flex justify-between items-center text-[10px] font-bold">
                                <span class="text-slate-500 uppercase tracking-wider">Labor Cost</span>
                                <span class="text-emerald-400 font-mono">{{ formatCurrency(getRoutingCosts(routing.operations).labor) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] font-bold">
                                <span class="text-slate-500 uppercase tracking-wider">Machine Cost</span>
                                <span class="text-blue-400 font-mono">{{ formatCurrency(getRoutingCosts(routing.operations).machine) }}</span>
                            </div>
                            <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex justify-between items-end">
                                <div class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Total Ops Cost</div>
                                <div class="text-base font-bold text-slate-900 dark:text-white font-mono">{{ formatCurrency(getRoutingCosts(routing.operations).total) }}</div>
                            </div>
                        </div>

                        <!-- Action -->
                        <div class="flex items-center gap-2">
                            <Link 
                                :href="route('manufacturing.boms.show', routing.id)"
                                class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-700 transition-all border border-transparent hover:border-slate-600"
                            >
                                <ArrowRightIcon class="h-5 w-5" />
                            </Link>
                        </div>
                    </div>

                    <!-- Process Visualizer (Quick Preview) -->
                    <div v-if="routing.operations?.length" class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800/50 flex items-center gap-3 overflow-x-auto pb-2 custom-scrollbar">
                        <div v-for="(op, idx) in routing.operations" :key="op.id" class="flex-shrink-0 flex items-center gap-3">
                            <div class="px-4 py-2 rounded-xl glass-card flex flex-col min-w-[140px]">
                                <span class="text-[9px] font-black text-slate-600 uppercase tracking-tighter mb-0.5">S{{ idx + 1 }}</span>
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 truncate">{{ op.name }}</span>
                            </div>
                            <ChevronRightIcon v-if="idx < routing.operations.length - 1" class="h-4 w-4 text-slate-700" />
                        </div>
                    </div>
                </div>

                <div v-if="!routings.data.length" class="py-24 text-center glass-card border-dashed rounded-[3rem]">
                    <ListBulletIcon class="h-16 w-16 text-slate-800 mx-auto mb-4" />
                    <h4 class="text-lg font-bold text-slate-500 dark:text-slate-400">No routings found</h4>
                    <p class="text-sm text-slate-500 max-w-xs mx-auto mt-2 italic">Try adjusting your search criteria or define routing steps in the BOM editor.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-8 flex justify-center">
                <nav class="flex gap-1">
                    <Link
                        v-for="(link, i) in routings.links"
                        :key="i"
                        :href="link.url || '#'"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all"
                        :class="[
                            link.active ? 'bg-blue-600 text-slate-900 dark:text-white shadow-lg shadow-blue-500/20' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-white dark:text-white',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    height: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #334155;
}
</style>



