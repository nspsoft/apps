<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    PencilSquareIcon,
    TrashIcon,
    EyeIcon,
    FunnelIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    XCircleIcon,
    PaperAirplaneIcon,
    InformationCircleIcon,
    BanknotesIcon,
    TruckIcon,
    CheckBadgeIcon,
    ShoppingCartIcon,
    ClockIcon,
    ArrowDownTrayIcon,
    PrinterIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    purchaseOrders: Object,
    stats: Object,
    suppliers: Array,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || '');
const selectedSupplier = ref(props.filters?.supplier || '');
const showFilters = ref(false);

const applyFilters = debounce(() => {
    router.get('/purchasing/orders', {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        supplier: selectedSupplier.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus, selectedSupplier], applyFilters);

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedSupplier.value = '';
};

const deletePO = (po) => {
    if (confirm(`Are you sure you want to delete "${po.po_number}"?`)) {
        router.delete(`/purchasing/orders/${po.id}`);
    }
};

const getStatusBadge = (status) => {
    if (!status) return 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        submitted: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        approved: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        ordered: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
        partial: 'bg-orange-500/20 text-orange-400 border-orange-500/30',
        received: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const getStatusLabel = (status) => {
    if (!status) return '-';
    const labels = {
        draft: 'Draft',
        submitted: 'Submitted',
        approved: 'Approved',
        ordered: 'Ordered',
        partial: 'Partial',
        received: 'Received',
        cancelled: 'Cancelled',
    };
    return labels[status] || status;
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
    <Head title="Purchase Orders" />
    
    <AppLayout title="Purchase Orders">
        <template v-if="purchaseOrders">
            <!-- Header Actions -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-6">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search PO number..."
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                        />
                    </div>
                    <button 
                        @click="showFilters = !showFilters"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors border border-transparent"
                        :class="{ 'ring-2 ring-blue-500/50 border-blue-500/50': showFilters }"
                    >
                        <FunnelIcon class="h-5 w-5" />
                        Filters
                    </button>

                    <!-- Mini Statistics Row (Refined) -->
                    <div v-if="stats" class="flex flex-wrap items-center gap-2">
                        <div class="glass-card px-3 py-2 rounded-xl border-l-4 border-l-blue-500 flex items-center gap-3 shadow-sm">
                            <ShoppingCartIcon class="w-4 h-4 text-blue-500 shrink-0" />
                            <div class="flex items-baseline gap-2">
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ordered</span>
                                <span class="text-sm font-black text-slate-900 dark:text-white">{{ formatNumber(stats.total_qty || 0) }}</span>
                            </div>
                        </div>
                        <div class="glass-card px-3 py-2 rounded-xl border-l-4 border-l-emerald-500 flex items-center gap-3 shadow-sm">
                            <CheckBadgeIcon class="w-4 h-4 text-emerald-500 shrink-0" />
                            <div class="flex items-baseline gap-2">
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Received</span>
                                <span class="text-sm font-black text-slate-900 dark:text-white">{{ formatNumber(stats.total_received || 0) }}</span>
                            </div>
                        </div>
                        <div class="glass-card px-3 py-2 rounded-xl border-l-4 border-l-red-500 flex items-center gap-3 shadow-sm">
                            <ArrowDownTrayIcon class="w-4 h-4 text-red-500 rotate-180 shrink-0" />
                            <div class="flex items-baseline gap-2">
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Return</span>
                                <span class="text-sm font-black text-slate-900 dark:text-white">{{ formatNumber(stats.total_returned || 0) }}</span>
                            </div>
                        </div>
                        <div class="glass-card px-3 py-2 rounded-xl border-l-4 border-l-amber-500 flex items-center gap-3 shadow-sm">
                            <ClockIcon class="w-4 h-4 text-amber-500 shrink-0" />
                            <div class="flex items-baseline gap-2">
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Balance</span>
                                <span class="text-sm font-black text-slate-900 dark:text-white">{{ formatNumber(stats.total_balance || 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <Link
                    href="/purchasing/orders/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all shrink-0"
                >
                    <PlusIcon class="h-5 w-5" />
                    Create PO
                </Link>
            </div>

            <!-- Filters Panel -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="showFilters" class="mb-6 rounded-2xl glass-card p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Status</label>
                            <select
                                v-model="selectedStatus"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            >
                                <option value="">All Status</option>
                                <option v-for="status in statuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Supplier</label>
                            <select
                                v-model="selectedSupplier"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            >
                                <option value="">All Suppliers</option>
                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                    {{ supplier.name }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button 
                                @click="clearFilters"
                                class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                            >
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Purchase Orders Table -->
            <div class="rounded-2xl glass-card overflow-hidden">
                <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                                <th class="px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">PO Number</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Supplier</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Order Date</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Items</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Qty</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Recv</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Return</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Balance</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr 
                                v-for="po in purchaseOrders.data" 
                                :key="po.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors"
                            >
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800 font-mono text-xs text-slate-500">
                                            PO
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ po.po_number }}</div>
                                            <div class="text-xs text-slate-500">{{ po.warehouse?.name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="text-sm text-slate-900 dark:text-white font-medium">{{ po.supplier?.name }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ po.supplier?.code }}</div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">{{ formatDate(po.order_date) }}</span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ po.items_count }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm font-medium text-slate-900 dark:text-white">
                                    {{ formatNumber(po.total_qty || 0) }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ formatNumber(po.total_received || 0) }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-red-400/80">
                                    {{ formatNumber(po.total_returned || 0) }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center">
                                    <span 
                                        class="text-sm font-bold"
                                        :class="(parseFloat(po.total_qty || 0) - (parseFloat(po.total_received || 0) - parseFloat(po.total_returned || 0))) > 0 ? 'text-amber-400' : 'text-emerald-500'"
                                    >
                                        {{ formatNumber(parseFloat(po.total_qty || 0) - (parseFloat(po.total_received || 0) - parseFloat(po.total_returned || 0))) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ formatCurrency(po.total) }}</span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                    <span 
                                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold capitalize"
                                        :class="getStatusBadge(po.status)"
                                    >
                                        {{ getStatusLabel(po.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="`/purchasing/orders/${po.id}`"
                                            class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                            title="View"
                                        >
                                            <EyeIcon class="h-4 w-4" />
                                        </Link>
                                        <Link
                                            v-if="po.status === 'draft'"
                                            :href="`/purchasing/orders/${po.id}/edit`"
                                            class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                            title="Edit"
                                        >
                                            <PencilSquareIcon class="h-4 w-4" />
                                        </Link>
                                        <button
                                            v-if="po.status === 'draft'"
                                            @click="deletePO(po)"
                                            class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                                            title="Delete"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="purchaseOrders.data && purchaseOrders.data.length === 0">
                                <td colspan="11" class="px-4 py-12 text-center text-slate-500 italic">No purchase orders found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="purchaseOrders.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4">
                    <Pagination :links="purchaseOrders.links" />
                </div>
            </div>

            <!-- Feature Guide -->
            <div class="mt-12">
                <div class="flex items-center gap-2 mb-4 px-1">
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Procurement Guide</span>
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                                <ShoppingCartIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Purchase Orders</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Official <strong>PO Documents</strong> sent to suppliers.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-400">
                                <ClockIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Lead Time</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Track <strong>Expected Arrival</strong> dates.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                                <ArrowDownTrayIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Fast Receiving</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Generate <strong>Goods Receipts (GRN)</strong> directly.
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                                <PrinterIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">PO Printout</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Generate pixel-perfect <strong>PDF Purchase Orders</strong>.
                        </p>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-slate-900 dark:text-white text-center py-20">
            Loading order data...
        </div>
    </AppLayout>
</template>



