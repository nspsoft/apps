<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { 
    MagnifyingGlassIcon, 
    ArrowLeftIcon,
    CalendarDaysIcon,
    PrinterIcon
} from '@heroicons/vue/24/outline';
import { formatNumber } from '@/helpers';

const props = defineProps({
    dates: Array,
    matrix: Array,
    weeks: { type: Array, default: () => [] },
    filters: Object,
});

const search = ref(props.filters.search || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const mode = ref(props.filters.mode || 'daily');

const handleSearch = () => {
    router.get(route('sales.planning.schedule.comparison'), {
        search: search.value, 
        start_date: startDate.value,
        end_date: endDate.value,
        mode: mode.value,
    }, { preserveState: true, replace: true });
};

watch([search, startDate, endDate], () => {
    handleSearch();
});

const toggleMode = (newMode) => {
    mode.value = newMode;
    handleSearch();
};

const formatDateShort = (dateStr) => {
    // For weekly mode, show the week label
    if (mode.value === 'weekly') {
        const week = props.weeks.find(w => w.key === dateStr);
        return week ? week.label : dateStr;
    }
    const d = new Date(dateStr);
    const day = d.getDate().toString().padStart(2, '0');
    const month = d.toLocaleString('en-US', { month: 'short' });
    return `${day}-${month}`;
};

const formatColumnHeader = (dateStr) => {
    if (mode.value === 'weekly') {
        return dateStr; // W1, W2, etc.
    }
    return formatDateShort(dateStr);
};

const getBalanceClass = (val) => {
    if (val < 0) return 'text-red-600 dark:text-red-400 font-bold';
    if (val > 0) return 'text-blue-600 dark:text-blue-400 font-bold';
    return 'text-slate-400';
};

const isToday = (dateStr) => {
    if (mode.value === 'weekly') return false;
    const today = new Date().toISOString().split('T')[0];
    return dateStr === today;
};

const printOfficial = () => {
    const params = new URLSearchParams({
        start_date: startDate.value,
        end_date: endDate.value,
        search: search.value || '',
        mode: mode.value,
    });
    window.open(route('sales.planning.schedule.print') + '?' + params.toString(), '_blank');
};
</script>

<template>
    <Head title="Monitoring Schedule vs Actual" />

    <AppLayout title="Monitoring Schedule vs Actual">
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('sales.planning.schedule.index')" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors no-print">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                        {{ mode === 'weekly' ? 'Weekly' : 'Daily' }} Delivery Monitoring Matrix
                    </h2>
                </div>
                <button @click="printOfficial" class="flex items-center gap-2 bg-blue-900 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-bold transition-colors no-print shadow-md">
                    <PrinterIcon class="w-4 h-4" />
                    Print Official (PT JIDOKA)
                </button>
            </div>
        </template>

        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Filter Bar (Hidden on Print) -->
            <div class="glass-card rounded-2xl p-6 mb-6 no-print">
                <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
                    <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto">
                        <div class="relative w-full sm:w-64">
                            <input 
                                v-model="search"
                                type="text" 
                                placeholder="Search Customer / Product / PO..." 
                                class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                            >
                            <MagnifyingGlassIcon class="w-5 h-5 text-slate-400 absolute left-3 top-2.5" />
                        </div>
                        <div class="flex items-center gap-2">
                            <input 
                                v-model="startDate"
                                type="date" 
                                class="rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 py-2"
                            >
                            <span class="text-slate-400">to</span>
                            <input 
                                v-model="endDate"
                                type="date" 
                                class="rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 py-2"
                            >
                        </div>
                        <!-- Daily/Weekly Toggle -->
                        <div class="flex items-center bg-slate-200 dark:bg-slate-700 rounded-lg p-0.5">
                            <button @click="toggleMode('daily')" 
                                :class="mode === 'daily' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                                class="px-4 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider transition-all">
                                Daily
                            </button>
                            <button @click="toggleMode('weekly')" 
                                :class="mode === 'weekly' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                                class="px-4 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider transition-all">
                                Weekly
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div class="flex gap-4 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            <div class="flex items-center gap-1"><div class="w-2 h-2 bg-red-500 rounded-full"></div> Minus Balance</div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 bg-blue-500 rounded-full"></div> Surplus</div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 bg-slate-200 dark:bg-slate-700 rounded-full"></div> On-time</div>
                        </div>
                        <button @click="printOfficial" class="flex items-center gap-2 bg-blue-900 hover:bg-blue-800 text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-lg hover:shadow-xl">
                            <PrinterIcon class="w-4 h-4" />
                            Print Official
                        </button>
                    </div>
                </div>
            </div>

            <!-- Matrix Table -->
            <div class="glass-card rounded-2xl overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-700/50 matrix-container">
                <div class="overflow-x-auto overflow-y-auto max-h-[750px] relative">
                    <table class="w-full border-collapse text-[12px]">
                        <thead class="bg-slate-100 dark:bg-slate-800 sticky top-0 z-30 font-bold">
                            <tr>
                                <th class="p-3 border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-left sticky left-0 z-40 min-w-[200px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]" rowspan="2">
                                    <span class="text-slate-900 dark:text-slate-100 font-black uppercase tracking-wider">Customer & PO</span>
                                </th>
                                <th class="p-3 border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-left sticky left-[200px] z-40 min-w-[250px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]" rowspan="2">
                                    <span class="text-slate-900 dark:text-slate-100 font-black uppercase tracking-wider">Product Detail</span>
                                </th>
                                <th class="p-3 border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-center sticky left-[450px] z-40 min-w-[85px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]" rowspan="2">
                                    <span class="text-slate-900 dark:text-slate-100 font-black uppercase tracking-wider">DATA</span>
                                </th>
                                <th v-for="date in dates" :key="date" 
                                    class="p-2 border border-slate-300 dark:border-slate-700 text-center font-black whitespace-nowrap"
                                    :class="[
                                        isToday(date) ? 'bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 ring-2 ring-inset ring-indigo-500/20' : 'text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800',
                                        mode === 'weekly' ? 'min-w-[100px]' : 'min-w-[65px]'
                                    ]"
                                >
                                    <div>{{ formatColumnHeader(date) }}</div>
                                    <div v-if="mode === 'weekly'" class="text-[9px] font-medium text-slate-500 dark:text-slate-400 mt-0.5">{{ formatDateShort(date) }}</div>
                                </th>
                                <th class="p-3 border border-slate-300 dark:border-slate-700 text-center bg-slate-200 dark:bg-slate-950 sticky right-0 z-30 min-w-[85px] shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.1)]" rowspan="2">
                                    <span class="text-slate-900 dark:text-slate-100 font-black uppercase tracking-wider">TOTAL</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <template v-for="(customer, cIdx) in matrix" :key="cIdx">
                                <template v-for="(product, pIdx) in customer.products" :key="pIdx">
                                    <!-- ROW 1: SCHEDULE -->
                                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors border-t-2 border-slate-200 dark:border-slate-700">
                                        <td v-if="pIdx === 0" 
                                            class="p-4 border border-slate-200 dark:border-slate-800 font-black text-indigo-700 dark:text-indigo-400 bg-white dark:bg-slate-950 sticky left-0 z-10 align-top shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]"
                                            :rowspan="customer.products.length * 3"
                                        >
                                            <div class="line-clamp-3 text-sm leading-tight">{{ customer.customer_name }}</div>
                                            <div class="text-[10px] font-mono text-slate-500 dark:text-slate-400 mt-2 uppercase tracking-tighter bg-slate-100 dark:bg-slate-800/50 px-2 py-0.5 rounded-full inline-block">{{ customer.customer_code }}</div>
                                        </td>
                                        <td class="p-4 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 sticky left-[200px] z-10 align-top shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]" rowspan="3">
                                            <div class="font-black text-slate-900 dark:text-white leading-tight break-words">{{ product.product_name }}</div>
                                            <div class="flex justify-between items-center mt-2 group-hover:bg-slate-100 dark:group-hover:bg-slate-800 p-1 rounded transition-colors">
                                                <span class="text-[10px] font-mono text-slate-600 dark:text-slate-400 uppercase font-bold">{{ product.sku }}</span>
                                                <span class="text-[10px] font-black bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 px-1.5 rounded">{{ product.unit }}</span>
                                            </div>
                                            <div v-if="product.po_number" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mt-2 border-l-2 border-slate-200 dark:border-slate-700 pl-2">PO: {{ product.po_number }}</div>
                                        </td>
                                        <td class="p-2 border border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900 sticky left-[450px] z-10 font-black text-slate-600 dark:text-slate-400 uppercase text-[10px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                            SCHEDULE
                                        </td>
                                        <td v-for="date in dates" :key="date" class="p-2 border border-slate-200 dark:border-slate-800 text-right font-mono text-slate-700 dark:text-slate-300 font-medium">
                                            {{ product.daily[date]?.sch > 0 ? formatNumber(product.daily[date].sch) : '-' }}
                                        </td>
                                        <td class="p-2 border border-slate-200 dark:border-slate-800 text-right bg-slate-50 dark:bg-slate-900 font-mono font-black text-slate-900 dark:text-white sticky right-0 z-10 transition-colors shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                            {{ formatNumber(product.totals.sch) }}
                                        </td>
                                    </tr>
                                    <!-- ROW 2: DELIVERY -->
                                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="p-2 border border-slate-200 dark:border-slate-800 bg-slate-100/30 dark:bg-slate-800/20 sticky left-[450px] z-10 font-black text-blue-600 dark:text-blue-400 uppercase text-[10px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                            DELIVERY
                                        </td>
                                        <td v-for="date in dates" :key="date" class="p-2 border border-slate-200 dark:border-slate-800 text-right font-mono font-bold text-blue-700 dark:text-blue-300">
                                            {{ product.daily[date]?.act > 0 ? formatNumber(product.daily[date].act) : '-' }}
                                        </td>
                                        <td class="p-2 border border-slate-200 dark:border-slate-800 text-right bg-blue-50/40 dark:bg-blue-900/20 font-mono font-black text-blue-800 dark:text-blue-200 sticky right-0 z-10 transition-colors shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                            {{ formatNumber(product.totals.act) }}
                                        </td>
                                    </tr>
                                    <!-- ROW 3: BALANCE -->
                                    <tr class="group bg-slate-100/50 dark:bg-slate-800/40 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                        <td class="p-2 border border-slate-200 dark:border-slate-800 bg-slate-200/50 dark:bg-slate-700/50 sticky left-[450px] z-10 font-black text-slate-900 dark:text-white uppercase text-[10px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                            BALANCE
                                        </td>
                                        <td v-for="date in dates" :key="date" 
                                            class="p-2 border border-slate-200 dark:border-slate-800 text-right font-mono"
                                            :class="product.daily[date]?.bal < 0 ? 'text-red-700 dark:text-red-400 font-black bg-red-100/40 dark:bg-red-900/20' : product.daily[date]?.bal > 0 ? 'text-blue-700 dark:text-blue-400 font-black bg-blue-50/30' : 'text-slate-400'"
                                        >
                                            {{ product.daily[date]?.bal !== 0 ? formatNumber(product.daily[date].bal) : '-' }}
                                        </td>
                                        <td class="p-2 border border-slate-200 dark:border-slate-800 text-right font-mono font-black sticky right-0 z-10 transition-colors shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.1)]"
                                            :class="product.totals.bal < 0 ? 'bg-red-200 dark:bg-red-800/60 text-red-900 dark:text-red-100' : 'bg-slate-200 dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-base shadow-inner'"
                                        >
                                            {{ formatNumber(product.totals.bal) }}
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <tr v-if="matrix.length === 0">
                                <td :colspan="dates.length + 4" class="p-20 text-center text-slate-500 italic">
                                    No data found for the selected period.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.glass-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.dark .glass-card {
    background: rgba(15, 23, 42, 0.6);
    border-color: rgba(255, 255, 255, 0.05);
}

/* Custom scrollbar for matrix */
.matrix-container ::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}
.matrix-container ::-webkit-scrollbar-track {
    background: transparent;
}
.matrix-container ::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.3);
    border-radius: 5px;
}
.matrix-container ::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.5);
}

@media print {
    .no-print {
        display: none !important;
    }
    .p-4, .sm\:p-6, .lg\:p-8 {
        padding: 0 !important;
    }
    .glass-card {
        border: none !important;
        box-shadow: none !important;
        background: white !important;
    }
    .matrix-container {
        overflow: visible !important;
        max-height: none !important;
    }
    table {
        font-size: 8px !important;
    }
    .sticky {
        position: static !important;
    }
}
</style>
