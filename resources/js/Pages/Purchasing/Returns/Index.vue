<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    DocumentTextIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    InformationCircleIcon,
    ReceiptRefundIcon,
    ShieldCheckIcon,
    NoSymbolIcon,
    ArrowUpTrayIcon,
    CubeIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    returns: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

const applyFilters = debounce(() => {
    router.get('/purchasing/returns', {
        search: search.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(search, applyFilters);

const getStatusBadge = (status) => {
    if (!status) return 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        confirmed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};


const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (e) {
        return date;
    }
};
</script>

<template>
    <Head title="Purchase Returns" />
    
    <AppLayout title="Purchase Returns">
        <template v-if="returns">
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div class="relative flex-1 sm:max-w-md">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search return number..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                
                <Link
                    href="/purchasing/returns/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    Create Return
                </Link>
            </div>

            <!-- Returns Table -->
            <div class="rounded-2xl glass-card overflow-hidden">
                <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                                <th class="px-4 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Return Number</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">PO Reference</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Supplier</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-4 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total</th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-4 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr 
                                v-for="ret in returns.data" 
                                :key="ret.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors"
                            >
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800 font-mono text-xs text-slate-500">
                                            PRT
                                        </div>
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ ret.number }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <Link v-if="ret.purchase_order_id" :href="`/purchasing/orders/${ret.purchase_order_id}`" class="text-sm text-blue-400 hover:underline">
                                        {{ ret.purchase_order?.po_number || 'N/A' }}
                                    </Link>
                                    <span v-else class="text-xs text-slate-500 italic">No Reference</span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                                    {{ ret.supplier?.name || 'Unknown' }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                    {{ formatDate(ret.return_date) }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right text-sm font-medium text-slate-900 dark:text-white">
                                    {{ formatCurrency(ret.total_amount) }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                    <span 
                                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                                        :class="getStatusBadge(ret.status)"
                                    >
                                        {{ ret.status?.toUpperCase() || 'DRAFT' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    <Link
                                        :href="`/purchasing/returns/${ret.id}`"
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                    >
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="returns.data && returns.data.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500 italic">No purchase returns found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="returns.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4">
                    <Pagination :links="returns.links" />
                </div>
            </div>

            <!-- Feature Guide -->
            <div class="mt-12">
                <div class="flex items-center gap-2 mb-4 px-1">
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Supplier Returns Guide</span>
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-red-500/10 text-red-400">
                                <ArrowUpTrayIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Outbound Returns</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Process <strong>Supplier Returns</strong> for defective or incorrect materials.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-400">
                                <CubeIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Stock Deduction</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Inventory levels are <strong>Automatically Reduced</strong> when a return is confirmed.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                                <BanknotesIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Debit Notes</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Generate <strong>Debit Notes</strong> to offset your Accounts Payable.
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                                <ShieldCheckIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Audit Compliance</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Maintain <strong>Full Documentation</strong> for all outbound shipments.
                        </p>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-slate-900 dark:text-white text-center py-20">
            Loading returns...
        </div>
    </AppLayout>
</template>



