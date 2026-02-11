<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch } from 'vue';
import { 
    MagnifyingGlassIcon, 
    ArrowUpTrayIcon, 
    CalendarDaysIcon,
    XMarkIcon,
    ChevronUpIcon,
    ChevronDownIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    schedules: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const date = ref(props.filters.date || '');
const sortField = ref(props.filters.sort || 'delivery_date');
const sortDirection = ref(props.filters.direction || 'asc');
const importModalOpen = ref(false);
const fileInput = ref(null);

const form = useForm({
    file: null,
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
    router.get(route('planning.schedule.index'), {
        search: search.value, 
        date: date.value,
        sort: sortField.value,
        direction: sortDirection.value,
    }, { preserveState: true, replace: true });
};

watch([search, date], () => {
    handleSearch();
});

const openImportModal = () => {
    console.log('Opening Schedule Import Modal');
    importModalOpen.value = true;
};

const closeImportModal = () => {
    console.log('Closing Schedule Import Modal');
    importModalOpen.value = false;
    form.reset();
};

const submitImport = () => {
    if (fileInput.value.files[0]) {
        form.file = fileInput.value.files[0];
        form.post(route('planning.schedule.import'), {
            onSuccess: () => closeImportModal(),
        });
    }
};

const isDelayed = (schedule) => {
    // Simple check: if date < today and qty > 0 (assuming not tracked against actual yet in this view)
    // In real implementation, need to compare with actual delivery
    return new Date(schedule.delivery_date) < new Date().setHours(0,0,0,0);
};

const isUpcoming = (schedule) => {
    const today = new Date();
    const nextWeek = new Date();
    nextWeek.setDate(today.getDate() + 7);
    const deliveryDate = new Date(schedule.delivery_date);
    return deliveryDate >= today && deliveryDate <= nextWeek;
};
</script>

<template>
    <Head title="Delivery Schedule" />

    <AppLayout title="Delivery Schedule">
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                Delivery Schedule
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <div class="flex gap-4 w-full md:w-auto">
                            <div class="relative w-full md:w-64">
                                <input 
                                    v-model="search"
                                    type="text" 
                                    placeholder="Search Customer / PO / Product..." 
                                    class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                >
                                <MagnifyingGlassIcon class="w-5 h-5 text-slate-400 absolute left-3 top-2.5" />
                            </div>
                            <input 
                                v-model="date"
                                type="date" 
                                class="rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        
                        <button 
                            @click="openImportModal"
                            class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg transition-colors"
                        >
                            <ArrowUpTrayIcon class="w-5 h-5" />
                            Import Excel
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                            <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-300">
                                <tr>
                                    <th @click="sort('delivery_date')" class="px-6 py-3 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                        <div class="flex items-center gap-1">
                                            Delivery Date
                                            <span v-if="sortField === 'delivery_date'" class="text-blue-600 dark:text-blue-400">
                                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                <ChevronDownIcon v-else class="h-3 w-3" />
                                            </span>
                                        </div>
                                    </th>
                                    <th @click="sort('customer_name')" class="px-6 py-3 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                        <div class="flex items-center gap-1">
                                            Customer
                                            <span v-if="sortField === 'customer_name'" class="text-blue-600 dark:text-blue-400">
                                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                <ChevronDownIcon v-else class="h-3 w-3" />
                                            </span>
                                        </div>
                                    </th>
                                    <th @click="sort('po_number')" class="px-6 py-3 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                        <div class="flex items-center gap-1">
                                            PO Number
                                            <span v-if="sortField === 'po_number'" class="text-blue-600 dark:text-blue-400">
                                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                <ChevronDownIcon v-else class="h-3 w-3" />
                                            </span>
                                        </div>
                                    </th>
                                    <th @click="sort('product_name')" class="px-6 py-3 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                        <div class="flex items-center gap-1">
                                            Product
                                            <span v-if="sortField === 'product_name'" class="text-blue-600 dark:text-blue-400">
                                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                <ChevronDownIcon v-else class="h-3 w-3" />
                                            </span>
                                        </div>
                                    </th>
                                    <th @click="sort('qty_scheduled')" class="px-6 py-3 text-right cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                        <div class="flex items-center justify-end gap-1">
                                            Qty Scheduled
                                            <span v-if="sortField === 'qty_scheduled'" class="text-blue-600 dark:text-blue-400">
                                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                                <ChevronDownIcon v-else class="h-3 w-3" />
                                            </span>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <tr v-for="schedule in schedules.data" :key="schedule.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <td class="px-6 py-2">
                                        <div class="flex items-center gap-2">
                                            <CalendarDaysIcon class="w-4 h-4 text-slate-400" />
                                            {{ new Date(schedule.delivery_date).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-2 font-medium text-slate-900 dark:text-white">{{ schedule.customer?.name }}</td>
                                    <td class="px-6 py-2 font-mono text-xs">{{ schedule.po_number || '-' }}</td>
                                    <td class="px-6 py-2">
                                        <div class="font-medium text-slate-900 dark:text-white">{{ schedule.product?.name }}</div>
                                        <div class="text-xs text-slate-500">{{ schedule.product?.sku }}</div>
                                    </td>
                                    <td class="px-6 py-2 text-right font-mono">{{ Number(schedule.qty_scheduled).toLocaleString('id-ID') }} {{ schedule.product?.unit?.code }}</td>
                                    <td class="px-6 py-2">
                                        <span v-if="isDelayed(schedule)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800/20 dark:text-red-400">
                                            Delayed
                                        </span>
                                        <span v-else-if="isUpcoming(schedule)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-400">
                                            Upcoming
                                        </span>
                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300">
                                            Scheduled
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="schedules.data.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                        No schedule data found. Import Excel to get started.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        <Pagination :links="schedules.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <div v-if="importModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeImportModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-4 pb-4 border-b border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg leading-6 font-bold text-slate-900 dark:text-white" id="modal-title">
                                Import Delivery Schedule
                            </h3>
                            <button @click="closeImportModal" class="text-slate-400 hover:text-slate-500 transition-colors">
                                <XMarkIcon class="w-6 h-6" />
                            </button>
                        </div>
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <div class="mt-2">
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                                        Upload an Excel file (.xlsx, .xls) with columns: 
                                        <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">customer_code</code>, 
                                        <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">product_sku</code>, 
                                        <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">delivery_date</code>, 
                                        <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">qty</code>, 
                                        <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">po_number</code>.
                                    </p>
                                    <input 
                                        ref="fileInput"
                                        type="file" 
                                        class="block w-full text-sm text-slate-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-full file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-emerald-50 file:text-emerald-700
                                            hover:file:bg-emerald-100
                                            dark:file:bg-emerald-900/30 dark:file:text-emerald-400
                                        "
                                        accept=".xlsx, .xls, .csv"
                                    />
                                    <div v-if="form.errors.file" class="text-red-500 text-xs mt-2">{{ form.errors.file }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            @click="submitImport" 
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:ml-3 sm:w-auto sm:text-sm"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Importing...' : 'Import' }}
                        </button>
                        <button 
                            @click="closeImportModal" 
                            type="button" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 dark:border-slate-600 shadow-sm px-4 py-2 bg-white dark:bg-slate-800 text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
