<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
    MagnifyingGlassIcon, 
    TruckIcon, 
    ClipboardDocumentCheckIcon, 
    DocumentCheckIcon, 
    BanknotesIcon, 
    CheckBadgeIcon, 
    ExclamationCircleIcon 
} from '@heroicons/vue/24/outline';
import { debounce } from 'lodash';

const props = defineProps({
    chartData: Object,
    chartPercentages: Object,
    searchResult: Object,
    queryParams: Object,
});

const search = ref(props.queryParams?.search || '');

// Search Function
const performSearch = () => {
    // Using hardcoded path to avoid Ziggy/Route issues
    const url = '/sales/po-tracking';
    console.log('Searching for:', search.value);
    router.get(url, { search: search.value }, { preserveState: true, preserveScroll: true });
};

// Debounced Search Watcher (Auto-search)
watch(search, debounce((value) => {
    performSearch();
}, 500));
</script>

<template>
    <AppLayout title="PO Tracking">
        <div class="py-12 bg-slate-50 dark:bg-slate-950 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Page Header -->
                <div class="mb-10 text-center">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-bold uppercase tracking-wider mb-4 border border-blue-200 dark:border-blue-800">
                        <MagnifyingGlassIcon class="w-4 h-4" />
                        Live Tracking
                    </div>
                    <h1 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tight mb-4">
                        Track Customer PO
                    </h1>
                    <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                        Pantau posisi order pelanggan secara real-time dari hulu ke hilir.
                    </p>
                </div>

                <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
                    
                    <!-- Search Bar -->
                    <form @submit.prevent="performSearch" class="relative max-w-2xl mx-auto">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                            <MagnifyingGlassIcon class="h-6 w-6 text-slate-400" />
                        </div>
                        <input 
                            v-model="search"
                            type="text" 
                            class="block w-full pl-12 pr-24 py-4 bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 rounded-full text-lg shadow-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder:text-slate-400 text-slate-900 dark:text-white relative z-0"
                            placeholder="Enter Customer PO Number or SO Number..."
                            autofocus
                        >
                        <div class="absolute inset-y-0 right-2 flex items-center z-10 transition-transform active:scale-95">
                            <button 
                                type="submit"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-full transition-colors shadow-lg cursor-pointer"
                            >
                                Cari
                            </button>
                        </div>
                    </form>

                    <!-- Top Stats: Donut Chart -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[32px] p-8 shadow-xl">
                        
                        <!-- VIEW 1: GLOBAL STATS (Default) -->
                        <div v-if="!searchResult" class="contents">
                            <div class="md:col-span-5 flex justify-center">
                               <!-- CSS Conic Gradient Donut -->
                               <div class="relative w-64 h-64 rounded-full flex items-center justify-center transition-all duration-1000"
                                    :style="`background: conic-gradient(
                                        #3b82f6 0% ${chartPercentages.processing}%, 
                                        #10b981 ${chartPercentages.processing}% ${parseFloat(chartPercentages.processing) + parseFloat(chartPercentages.completed)}%, 
                                        #ef4444 ${parseFloat(chartPercentages.processing) + parseFloat(chartPercentages.completed)}% ${parseFloat(chartPercentages.processing) + parseFloat(chartPercentages.completed) + parseFloat(chartPercentages.cancelled)}%, 
                                        #cbd5e1 ${parseFloat(chartPercentages.processing) + parseFloat(chartPercentages.completed) + parseFloat(chartPercentages.cancelled)}% 100%
                                    )`">
                                    <div class="w-48 h-48 bg-white dark:bg-slate-900 rounded-full flex flex-col items-center justify-center shadow-inner">
                                        <span class="text-4xl font-extrabold text-slate-800 dark:text-white">{{ chartData.processing + chartData.completed }}</span>
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Active Orders</span>
                                    </div>
                               </div>
                            </div>

                            <div class="md:col-span-7">
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6">Order Status Distribution</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-2xl border border-blue-100 dark:border-blue-900/20">
                                        <h4 class="text-blue-600 font-bold text-lg">{{ chartPercentages.processing }}%</h4>
                                        <p class="text-xs text-slate-500 font-bold uppercase">Processing</p>
                                        <p class="text-[10px] text-slate-400 mt-1">{{ chartData.processing }} Orders</p>
                                    </div>
                                    <div class="p-4 bg-emerald-50 dark:bg-emerald-900/10 rounded-2xl border border-emerald-100 dark:border-emerald-900/20">
                                        <h4 class="text-emerald-600 font-bold text-lg">{{ chartPercentages.completed }}%</h4>
                                        <p class="text-xs text-slate-500 font-bold uppercase">Completed</p>
                                        <p class="text-[10px] text-slate-400 mt-1">{{ chartData.completed }} Orders</p>
                                    </div>
                                    <div class="p-4 bg-red-50 dark:bg-red-900/10 rounded-2xl border border-red-100 dark:border-red-900/20">
                                        <h4 class="text-red-500 font-bold text-lg">{{ chartPercentages.cancelled }}%</h4>
                                        <p class="text-xs text-slate-500 font-bold uppercase">Cancelled</p>
                                        <p class="text-[10px] text-slate-400 mt-1">{{ chartData.cancelled }} Orders</p>
                                    </div>
                                    <div class="p-4 bg-slate-100 dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
                                        <h4 class="text-slate-600 dark:text-slate-300 font-bold text-lg">{{ chartPercentages.draft }}%</h4>
                                        <p class="text-xs text-slate-500 font-bold uppercase">Draft</p>
                                        <p class="text-[10px] text-slate-400 mt-1">{{ chartData.draft }} Orders</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- VIEW 2: SINGLE ORDER PROGRESS (When Searching) -->
                        <div v-else class="contents animate-in fade-in zoom-in duration-500">
                             <div class="md:col-span-5 flex justify-center">
                               <!-- Single Order Progress Donut -->
                               <div class="relative w-64 h-64 rounded-full flex items-center justify-center transition-all duration-1000"
                                    :style="`background: conic-gradient(
                                        #10b981 0% ${searchResult.delivery_progress}%, 
                                        #e2e8f0 ${searchResult.delivery_progress}% 100%
                                    )`">
                                    <!-- Inner Circle -->
                                    <div class="w-48 h-48 bg-white dark:bg-slate-900 rounded-full flex flex-col items-center justify-center shadow-inner relative overflow-hidden">
                                        <!-- Wave/Water Effect -->
                                        <div class="absolute bottom-0 left-0 right-0 bg-emerald-500/10 transition-all duration-1000" :style="`height: ${searchResult.delivery_progress}%`"></div>
                                        
                                        <span class="text-5xl font-extrabold text-slate-800 dark:text-white relative z-10">{{ searchResult.delivery_progress }}%</span>
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1 relative z-10">Delivery Done</span>
                                    </div>
                               </div>
                            </div>

                            <div class="md:col-span-7">
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6">Order Fulfillment Status</h3>
                                <div class="space-y-6">
                                    <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-700">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-sm font-bold text-slate-500">Sales Order</span>
                                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ searchResult.so_number }}</span>
                                        </div>
                                        <div class="flex justify-between items-center mb-4">
                                            <span class="text-sm font-bold text-slate-500">Customer PO</span>
                                            <span class="text-sm font-bold text-blue-600">{{ searchResult.po_number }}</span>
                                        </div>
                                        
                                        <!-- Progress Bar -->
                                        <div class="w-full h-4 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-emerald-500 transition-all duration-1000 ease-out" :style="`width: ${searchResult.delivery_progress}%`"></div>
                                        </div>
                                        <div class="flex justify-between mt-2 text-xs font-bold text-slate-400">
                                            <span>0%</span>
                                            <span>100% (Completed)</span>
                                        </div>
                                    </div>

                                    <div class="flex gap-4">
                                        <div class="flex-1 p-4 bg-blue-50 dark:bg-blue-900/10 rounded-2xl border border-blue-100 dark:border-blue-900/20 text-center">
                                            <p class="text-blue-600 font-bold text-lg">{{ searchResult.overall_lead_time }}</p>
                                            <p class="text-[10px] text-slate-400 uppercase mt-1">Lead Time</p>
                                        </div>
                                        <div class="flex-1 p-4 bg-emerald-50 dark:bg-emerald-900/10 rounded-2xl border border-emerald-100 dark:border-emerald-900/20 text-center">
                                             <p v-if="searchResult.delivery_progress >= 100" class="text-emerald-600 font-bold text-lg">Completed</p>
                                             <p v-else class="text-amber-500 font-bold text-lg">In Progess</p>
                                             <p class="text-[10px] text-slate-400 uppercase mt-1">Status</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Pipeline Timeline (Only if Searched) -->
                    <div v-if="searchResult" class="bg-slate-900 rounded-[32px] p-8 md:p-12 shadow-2xl border border-slate-800 relative overflow-hidden">
                        
                        <!-- Header -->
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 gap-6 relative z-10">
                            <div>
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Customer PO Number</p>
                                <h2 class="text-3xl font-black text-blue-500">{{ searchResult.po_number }}</h2>
                                <p class="text-sm text-slate-400 mt-1">SO Ref: <span class="text-white font-mono">{{ searchResult.so_number }}</span></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Customer</p>
                                <h3 class="text-xl font-bold text-white">{{ searchResult.customer }}</h3>
                                <div class="inline-flex items-center gap-2 mt-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold">
                                    <span>Total Lead Time: {{ searchResult.overall_lead_time }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stepper -->
                        <div class="relative">
                            <!-- Horizontal Line -->
                            <div class="absolute top-[30px] left-0 w-full h-0.5 bg-slate-800 z-0"></div>

                            <div class="grid grid-cols-1 md:grid-cols-5 gap-8 relative z-10">
                                <div v-for="(step, index) in searchResult.timeline" :key="index" class="group flex flex-col items-center text-center">
                                    
                                    <!-- Icon Circle -->
                                    <div class="w-[60px] h-[60px] rounded-full flex items-center justify-center border-4 transition-all duration-300 bg-slate-900 mb-4 relative"
                                        :class="[
                                            step.status === 'Completed' || step.status === 'Paid' || step.status.includes('Partial') ? 'border-emerald-500 text-emerald-400 shadow-[0_0_20px_rgba(16,185,129,0.3)]' :
                                            step.status === 'Pending' ? 'border-slate-700 text-slate-600' : 'border-blue-500 text-blue-500'
                                        ]">
                                        
                                        <!-- Animated Pulse for Completed -->
                                        <div v-if="step.status === 'Completed' || step.status === 'Paid'" class="absolute inset-0 rounded-full border border-emerald-500 animate-ping opacity-20"></div>

                                        <ClipboardDocumentCheckIcon v-if="index === 0" class="w-8 h-8" />
                                        <DocumentCheckIcon v-if="index === 1" class="w-8 h-8" />
                                        <TruckIcon v-if="index === 2" class="w-8 h-8" />
                                        <BanknotesIcon v-if="index === 3" class="w-8 h-8" />
                                        <CheckBadgeIcon v-if="index === 4" class="w-8 h-8" />
                                    </div>

                                    <!-- Text Content -->
                                    <h4 class="text-sm font-bold text-white mb-1 group-hover:text-blue-400 transition-colors">{{ step.step }}</h4>
                                    
                                    <!-- Status with Date pill -->
                                    <div class="flex flex-col gap-1 items-center">
                                        <span class="text-[10px] px-2 py-0.5 rounded border"
                                            :class="[
                                                step.date !== '-' ? 'bg-slate-800 border-slate-700 text-slate-400' : 'bg-transparent border-transparent text-transparent'
                                            ]">
                                            {{ step.date }}
                                        </span>
                                        
                                        <span class="text-[10px] font-mono text-slate-500 flex items-center gap-1">
                                            <svg v-if="step.lead_time !== '-'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            {{ step.lead_time }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <!-- Empty State -->
                    <div v-else class="text-center py-20 opacity-50">
                        <ExclamationCircleIcon class="w-16 h-16 mx-auto text-slate-600 mb-4" />
                        <h3 class="text-xl font-bold text-slate-500">Waiting for Search Input</h3>
                        <p class="text-slate-400">Enter a PO Number to start tracking.</p>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
