<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    ArrowPathIcon,
    ArrowPathRoundedSquareIcon,
    ArchiveBoxIcon,
    ScaleIcon,
    ShieldCheckIcon,
    ReceiptRefundIcon,
    CubeIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
import debounce from 'lodash/debounce';

const props = defineProps({
    returns: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

const applyFilters = debounce(() => {
    router.get('/sales/returns', {
        search: search.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(search, applyFilters);

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        confirmed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};


const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Sales Returns" />
    
    <AppLayout title="Sales Returns">
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
                :href="route('sales.returns.create')"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
            >
                <PlusIcon class="h-5 w-5" />
                Create Return
            </Link>
        </div>

        <!-- Returns Table -->
        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                            <th class="px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Return Number</th>
                            <th class="px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Customer</th>
                            <th class="px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
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
                                        SRT
                                    </div>
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ ret.number }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-900 dark:text-white">
                                {{ ret.customer?.name }}
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
                                    {{ ret.status?.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <Link
                                    :href="route('sales.returns.show', ret.id)"
                                    class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                >
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="returns.data.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center text-slate-500 italic">No sales returns found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div v-if="returns.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ returns.from }} to {{ returns.to }} of {{ returns.total }} returns
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in returns.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                        :class="link.active 
                            ? 'bg-blue-600 text-slate-900 dark:text-white' 
                            : link.url 
                                ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white' 
                                : 'text-white cursor-not-allowed'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Feature Guide -->
        <div class="mt-12">
            <div class="flex items-center gap-2 mb-4 px-1">
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Customer Returns Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-red-500/10 text-red-400">
                            <ArrowPathIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Return Flow</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Process <strong>Customer Returns</strong> for damaged or incorrect goods. Returns can be linked back to the original Delivery Order for full traceability.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <ReceiptRefundIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Credit Memos</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Approved returns generate <strong>Credit Memos</strong> that customers can use to offset their outstanding invoices or future purchases.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-400">
                            <CubeIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Stock Reversal</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Mark items as <strong>Restockable</strong> to automatically increase inventory levels when the returned items are received back at the warehouse.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Quality Inspection</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Log <strong>Inspection Notes</strong> for each returned item to document defects and determine if the goods can be resold or must be scrapped.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



