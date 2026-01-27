<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PrinterIcon, ArrowLeftIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { formatNumber } from '@/helpers';

const props = defineProps({
    movements: Array,
    products: Array,
    warehouses: Array,
    filters: Object,
});

const selectedProduct = ref(props.filters.product_id || '');
const selectedWarehouse = ref(props.filters.warehouse_id || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const applyFilters = debounce(() => {
    router.get('/reports/stock-card', {
        product_id: selectedProduct.value || undefined,
        warehouse_id: selectedWarehouse.value || undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([selectedProduct, selectedWarehouse, startDate, endDate], applyFilters);

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};


const getTypeLabel = (type) => {
    return type.replace('_', ' ').toUpperCase();
};
</script>

<template>
    <Head title="Stock Card Report" />
    
    <AppLayout title="Reports">
        <div class="max-w-7xl mx-auto">
            <!-- No Print Elements -->
            <div class="print:hidden mb-6">
                <Link href="/reports" class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white mb-4">
                    <ArrowLeftIcon class="h-4 w-4" />
                    Back
                </Link>
                
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                    <div class="lg:col-span-2">
                        <label class="block text-xs text-slate-500 mb-1">Product</label>
                        <select
                            v-model="selectedProduct"
                            class="block w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2 px-3 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="">Select Product...</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">
                                {{ p.name }} ({{ p.sku }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Warehouse</label>
                        <select
                            v-model="selectedWarehouse"
                            class="block w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2 px-3 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="">All Warehouses</option>
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                {{ w.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Date Range</label>
                        <div class="flex gap-2">
                            <input v-model="startDate" type="date" class="w-full rounded-lg bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white border-0 py-2 px-2" />
                            <input v-model="endDate" type="date" class="w-full rounded-lg bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white border-0 py-2 px-2" />
                        </div>
                    </div>
                    <div class="flex items-end">
                        <button
                            onclick="window.print()"
                            class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white hover:bg-emerald-500 transition-colors"
                        >
                            <PrinterIcon class="h-4 w-4" />
                            Print
                        </button>
                    </div>
                </div>
            </div>

            <!-- Report Sheet -->
            <div class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-8 min-h-screen rounded-sm shadow-xl print:shadow-none print:w-full">
                <!-- Header -->
                <div class="border-b border-slate-200 dark:border-slate-800 pb-6 mb-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-2xl font-bold uppercase tracking-wide">Stock Card</h1>
                            <div class="mt-2 text-sm">
                                <p><span class="text-slate-500 dark:text-slate-400 w-20 inline-block">Product:</span> 
                                    <span class="font-bold text-slate-900 dark:text-white">{{ products.find(p => p.id == selectedProduct)?.name || 'Select Product' }}</span>
                                </p>
                                <p><span class="text-slate-500 dark:text-slate-400 w-20 inline-block">SKU:</span> 
                                    <span class="font-mono text-slate-900 dark:text-white">{{ products.find(p => p.id == selectedProduct)?.sku || '-' }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="text-right text-sm">
                            <p class="font-semibold text-slate-900 dark:text-white">ERP Manufacturing</p>
                            <p class="text-slate-500 dark:text-slate-400">Warehouse: {{ warehouses.find(w => w.id == selectedWarehouse)?.name || 'All Warehouses' }}</p>
                            <p class="text-slate-500 dark:text-slate-400">Period: {{ startDate || 'Start' }} - {{ endDate || 'End' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div v-if="selectedProduct">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-slate-200 dark:border-slate-800">
                                <th class="py-2 text-left font-bold w-32">Date</th>
                                <th class="py-2 text-left font-bold">Transaction Type</th>
                                <th class="py-2 text-left font-bold">Ref/Notes</th>
                                <th class="py-2 text-right font-bold w-24">In</th>
                                <th class="py-2 text-right font-bold w-24">Out</th>
                                <th class="py-2 text-right font-bold w-24">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <!-- Empty State for Opening Balance, maybe add later -->
                            <tr v-for="m in movements" :key="m.id" class="break-inside-avoid hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="py-2 text-slate-600 dark:text-slate-400">{{ formatDate(m.created_at) }}</td>
                                <td class="py-2 font-medium text-xs">{{ getTypeLabel(m.type) }}</td>
                                <td class="py-2 text-slate-500 text-xs">{{ m.notes }}</td>
                                <td class="py-2 text-right font-mono text-emerald-600">
                                    {{ m.qty > 0 ? formatNumber(m.qty) : '-' }}
                                </td>
                                <td class="py-2 text-right font-mono text-red-600">
                                    {{ m.qty < 0 ? formatNumber(Math.abs(m.qty)) : '-' }}
                                </td>
                                <td class="py-2 text-right font-bold font-mono">
                                    {{ formatNumber(m.balance_after) }}
                                </td>
                            </tr>
                            <tr v-if="movements.length === 0" class="text-center py-8">
                                <td colspan="6" class="py-8 text-slate-500 dark:text-slate-400 italic">No movements found for this period.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-24 text-slate-500 dark:text-slate-400">
                    Select a product to view the stock card.
                </div>

                <!-- Footer -->
                <div class="mt-12 pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-between text-xs text-slate-500 dark:text-slate-400">
                    <p>Printed from ERP System</p>
                    <p>Page 1 of 1</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    :deep(nav), :deep(.print\:hidden) {
        display: none !important;
    }
    .bg-white, .dark\:bg-slate-900 {
        background-color: white !important;
        color: black !important;
        box-shadow: none !important;
    }
    .text-slate-900, .dark\:text-white, .text-slate-500, .dark\:text-slate-400 {
        color: black !important;
    }
}
</style>


