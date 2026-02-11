<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch } from 'vue';
import { 
    MagnifyingGlassIcon, 
    ArrowUpTrayIcon,
    XMarkIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber } from '@/helpers';

const props = defineProps({
    forecasts: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const month = ref(props.filters.month || '');
const sortField = ref(props.filters.sort || 'period');
const sortDirection = ref(props.filters.direction || 'desc');
const importModalOpen = ref(false);
const fileInput = ref(null);

const form = useForm({
    file: null,
    sales_name: '',
});

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    handleSearch();
};

const handleSearch = () => {
    router.get(route('sales.planning.forecast.index'), { 
        search: search.value, 
        month: month.value,
        sort: sortField.value,
        direction: sortDirection.value,
    }, { preserveState: true, replace: true });
};

watch([search, month], () => {
    handleSearch();
});

const openImportModal = () => {
    importModalOpen.value = true;
};

const closeImportModal = () => {
    importModalOpen.value = false;
    form.reset();
};

const submitImport = () => {
    if (form.file) {
        form.post(route('sales.planning.forecast.import'), {
            onSuccess: () => closeImportModal(),
        });
    }
};

const getAccuracyColor = (accuracy) => {
    if (accuracy >= 90) return '#10b981'; // emerald-500
    if (accuracy >= 75) return '#3b82f6'; // blue-500
    if (accuracy >= 50) return '#f59e0b'; // amber-500
    return '#ef4444'; // red-500
};

const getAccuracyClass = (accuracy) => {
    if (accuracy >= 90) return 'text-emerald-600 dark:text-emerald-400';
    if (accuracy >= 75) return 'text-blue-600 dark:text-blue-400';
    if (accuracy >= 50) return 'text-amber-600 dark:text-amber-400';
    return 'text-red-600 dark:text-red-400';
};

const formatMonth = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('id-ID', { year: 'numeric', month: 'long' });
};

const formatDateShort = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
};
</script>

<template>
    <Head title="Sales Forecast" />

    <AppLayout title="Sales Forecast">
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                Sales Forecast
            </h2>
        </template>

        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Main Container -->
            <div class="glass-card rounded-2xl overflow-hidden p-6">
                    <!-- Actions Row -->
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <div class="flex gap-4 w-full md:w-auto">
                            <div class="relative w-full md:w-64">
                                <input 
                                    v-model="search"
                                    type="text" 
                                    placeholder="Search Customer / Product..." 
                                    class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                >
                                <MagnifyingGlassIcon class="w-5 h-5 text-slate-400 absolute left-3 top-2.5" />
                            </div>
                            <input 
                                v-model="month"
                                type="month" 
                                class="rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        
                        <div class="flex gap-2">
                            <a 
                                :href="route('sales.planning.forecast.export', { search, month })"
                                class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Export Excel
                            </a>
                            <button 
                                @click="openImportModal"
                                class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg transition-colors"
                            >
                                <ArrowUpTrayIcon class="w-5 h-5" />
                                Import Excel
                            </button>
                        </div>
                    </div>

                    <!-- Table Wrapper -->
                    <div class="rounded-2xl glass-card overflow-hidden">
                        <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                                <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-300">
                                    <tr class="border-b border-slate-200 dark:border-slate-700">
                                        <th @click="sort('period')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">
                                            <div class="flex items-center gap-1">
                                                Period
                                                <span v-if="sortField === 'period'" class="text-blue-600 dark:text-blue-400">
                                                    <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                    <ChevronDownIcon v-else class="h-3 w-3" />
                                                </span>
                                            </div>
                                        </th>
                                        <th @click="sort('customer_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">
                                            <div class="flex items-center gap-1">
                                                Customer
                                                <span v-if="sortField === 'customer_name'" class="text-blue-600 dark:text-blue-400">
                                                    <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                    <ChevronDownIcon v-else class="h-3 w-3" />
                                                </span>
                                            </div>
                                        </th>
                                        <th @click="sort('product_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">
                                            <div class="flex items-center gap-1">
                                                Product
                                                <span v-if="sortField === 'product_name'" class="text-blue-600 dark:text-blue-400">
                                                    <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                    <ChevronDownIcon v-else class="h-3 w-3" />
                                                </span>
                                            </div>
                                        </th>
                                        <th @click="sort('qty_forecast')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-right cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">
                                            <div class="flex items-center justify-end gap-1">
                                                Qty Forecast
                                                <span v-if="sortField === 'qty_forecast'" class="text-blue-600 dark:text-blue-400">
                                                    <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                    <ChevronDownIcon v-else class="h-3 w-3" />
                                                </span>
                                            </div>
                                        </th>
                                        <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Qty PO (Actual)</th>
                                        <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Accuracy</th>
                                        <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Sales Name</th>
                                        <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Unit</th>
                                        <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="forecast in forecasts.data" :key="forecast.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400 uppercase font-medium">
                                            {{ formatMonth(forecast.period) }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ forecast.customer?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono tracking-tight">{{ forecast.customer?.code }}</div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ forecast.product?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono tracking-tight">{{ forecast.product?.sku }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-right font-mono text-sm text-slate-900 dark:text-white">
                                            {{ formatNumber(forecast.qty_forecast) }}
                                        </td>
                                        <td class="px-4 py-2 text-right font-mono text-sm">
                                            <span :class="{
                                                'text-blue-500 font-bold': forecast.qty_actual > 0,
                                                'text-slate-400': forecast.qty_actual === 0
                                            }">
                                                {{ formatNumber(forecast.qty_actual) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <div class="flex flex-col items-center">
                                                <span :class="getAccuracyClass(forecast.accuracy)" class="font-bold text-[10px] tracking-tighter">
                                                    {{ forecast.accuracy }}%
                                                </span>
                                                <div class="w-12 h-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mt-0.5">
                                                    <div 
                                                        class="h-full transition-all duration-500" 
                                                        :style="{ width: forecast.accuracy + '%', backgroundColor: getAccuracyColor(forecast.accuracy) }"
                                                    ></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white truncate max-w-[120px]" :title="forecast.sales_name">{{ forecast.sales_name || '-' }}</div>
                                            <div class="text-[9px] text-slate-500 uppercase flex items-center gap-1" v-if="forecast.created_by">
                                                <UserIcon class="w-2.5 h-2.5" />
                                                {{ forecast.created_by_user?.name || 'User' }} • {{ formatDateShort(forecast.created_at) }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-slate-500 font-mono">{{ forecast.product?.unit?.code }}</td>
                                        <td class="px-4 py-2 text-[11px] italic text-slate-500 dark:text-slate-400 line-clamp-1 truncate max-w-[150px]" :title="forecast.notes">{{ forecast.notes || '-' }}</td>
                                    </tr>
                                    <tr v-if="forecasts.data.length === 0">
                                        <td colspan="9" class="px-4 py-12 text-center text-slate-500 whitespace-nowrap">
                                            No forecast data found. Import Excel to get started.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination Wrapper -->
                    <div class="mt-6">
                        <Pagination :links="forecasts.links" />
                    </div>
                </div>
            </div>

        <!-- Import Modal -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="importModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md p-6 shadow-2xl border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                            Import Forecast Data
                        </h3>
                        <button @click="closeImportModal" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sales Name</label>
                        <input 
                            v-model="form.sales_name" 
                            type="text"
                            placeholder="Enter salesperson name"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-3">Excel/CSV File</label>
                        <div class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-8 hover:border-blue-500/50 transition-all relative group bg-slate-50/50 dark:bg-slate-900/50">
                            <input 
                                type="file" 
                                ref="fileInput"
                                @change="(e) => form.file = e.target.files[0]"
                                class="absolute inset-0 opacity-0 cursor-pointer z-20"
                                accept=".xlsx,.xls,.csv"
                            />
                            <div class="flex flex-col items-center transition-transform group-hover:scale-105 duration-300">
                                <div class="p-3 rounded-full bg-blue-50 dark:bg-blue-900/30 mb-3">
                                    <ArrowUpTrayIcon class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-300 font-semibold mb-1">
                                    {{ form.file ? form.file.name : 'Click or drag file to upload' }}
                                </p>
                                <p class="text-xs text-slate-500">Maximum size: 2MB</p>
                            </div>
                            
                            <div class="mt-4 z-30 relative py-1 px-3 rounded-lg bg-blue-50 dark:bg-blue-900/40 border border-blue-100 dark:border-blue-800/50">
                                <a 
                                    :href="route('sales.planning.forecast.template')"
                                    class="flex items-center gap-1.5 text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    Download Template
                                </a>
                            </div>
                        </div>
                        <div v-if="form.errors.file" class="text-red-500 text-xs mt-2 font-medium">{{ form.errors.file }}</div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button 
                            @click="submitImport"
                            :disabled="!form.file || form.processing"
                            class="flex-1 rounded-xl bg-blue-600 py-3 text-sm font-bold text-white hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-blue-500/20"
                        >
                            {{ form.processing ? 'Importing...' : 'Start Import' }}
                        </button>
                        <button 
                            @click="closeImportModal"
                            class="flex-1 rounded-xl bg-slate-100 dark:bg-slate-700 py-3 text-sm font-bold text-slate-600 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600 transition-all font-mono uppercase tracking-wider"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>
