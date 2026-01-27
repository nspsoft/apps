<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PrinterIcon,
    ArrowLeftIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    data: Array,
    warehouses: Array,
    filters: Object,
    date: String,
});

const selectedWarehouse = ref(props.filters.warehouse_id || '');

const applyFilters = debounce(() => {
    router.get('/reports/inventory-balance', {
        warehouse_id: selectedWarehouse.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(selectedWarehouse, applyFilters);


const getTotalValue = () => {
    return props.data.reduce((sum, item) => sum + Number(item.value), 0);
};
</script>

<template>
    <Head title="Inventory Balance Report" />
    
    <AppLayout title="Reports">
        <div class="max-w-7xl mx-auto">
            <!-- No Print Elements -->
            <div class="print:hidden mb-6 flex items-center justify-between">
                <Link href="/reports" class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
                    <ArrowLeftIcon class="h-4 w-4" />
                    Back
                </Link>
                <div class="flex items-center gap-4">
                    <select
                        v-model="selectedWarehouse"
                        class="rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                    >
                        <option value="">All Warehouses</option>
                        <option v-for="w in props.warehouses" :key="w.id" :value="w.id">
                            {{ w.name }}
                        </option>
                    </select>
                    <button
                        onclick="window.print()"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        Print PDF
                    </button>
                </div>
            </div>

            <!-- Report Sheet -->
            <div class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-8 min-h-screen rounded-sm shadow-xl print:shadow-none print:w-full">
                <!-- Header -->
                <div class="border-b border-slate-200 dark:border-slate-800 pb-6 mb-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-2xl font-bold uppercase tracking-wide text-slate-900 dark:text-white">Inventory Balance</h1>
                            <p class="text-sm text-slate-500 mt-1 dark:text-slate-400">Generated on {{ date }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">ERP Manufacturing</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Warehouse: {{ warehouses.find(w => w.id == selectedWarehouse)?.name || 'All Warehouses' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-slate-200 dark:border-slate-800">
                            <th class="py-2 text-left font-bold">Code</th>
                            <th class="py-2 text-left font-bold">Product Name</th>
                            <th class="py-2 text-left font-bold">Category</th>
                            <th class="py-2 text-right font-bold">Qty</th>
                            <th class="py-2 text-left font-bold pl-2">Unit</th>
                            <th class="py-2 text-right font-bold">Value (IDR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <tr v-for="item in data" :key="item.code" class="break-inside-avoid">
                            <td class="py-2 font-mono text-xs">{{ item.code }}</td>
                            <td class="py-2 font-medium">{{ item.name }}</td>
                            <td class="py-2 text-slate-500">{{ item.category }}</td>
                            <td class="py-2 text-right">{{ formatNumber(item.qty) }}</td>
                            <td class="py-2 pl-2 text-xs text-slate-500">{{ item.unit }}</td>
                            <td class="py-2 text-right">{{ formatCurrency(item.value) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-200 dark:border-slate-800 font-bold bg-slate-50 dark:bg-slate-800/50">
                            <td colspan="5" class="py-3 text-right pr-4 text-slate-900 dark:text-white">TOTAL VALUATION</td>
                            <td class="py-3 text-right text-emerald-600 dark:text-emerald-400">{{ formatCurrency(getTotalValue()) }}</td>
                        </tr>
                    </tfoot>
                </table>

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
    table {
        border-color: #e2e8f0 !important;
    }
}
</style>


