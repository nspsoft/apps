<script setup>
import { ref, watch } from 'vue';
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
    CheckBadgeIcon,
    TruckIcon,
    CurrencyDollarIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import { ShoppingCartIcon, ClockIcon, ArrowDownTrayIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
import POImportModal from './POImportModal.vue';

// showImportModal removed - now redirects to full page

const props = defineProps({
    salesOrders: Object,
    stats: Object,
    customers: Array,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedCustomer = ref(props.filters.customer || '');
const showFilters = ref(false);

const applyFilters = debounce(() => {
    router.get('/sales/orders', {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        customer: selectedCustomer.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus, selectedCustomer], applyFilters);

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedCustomer.value = '';
};

const deleteSO = (so) => {
    if (confirm(`Are you sure you want to delete "${so.so_number}"?`)) {
        router.delete(`/sales/orders/${so.id}`);
    }
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        confirmed: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        processing: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        shipped: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
        delivered: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Draft',
        confirmed: 'Confirmed',
        processing: 'Processing',
        shipped: 'Shipped',
        delivered: 'Delivered',
        cancelled: 'Cancelled',
    };
    return labels[status] || status;
};

const getStatusDescription = (status) => {
    const descriptions = {
        draft: 'Pesanan baru dibuat, belum dikonfirmasi.',
        confirmed: 'Pesanan disetujui, siap diproses.',
        processing: 'Sedang disiapkan di gudang.',
        shipped: 'Barang dalam pengiriman.',
        delivered: 'Pesanan sudah sampai.',
        cancelled: 'Pesanan dibatalkan.',
    };
    return descriptions[status] || '';
};


const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Sales Orders" />
    
    <AppLayout title="Sales Orders">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search SO number..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                <button 
                    @click="showFilters = !showFilters"
                    class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors border border-transparent"
                    :class="{ 'ring-2 ring-blue-500/50 border-blue-500/50': showFilters }"
                >
                    <FunnelIcon class="h-5 w-5" />
                    Filters
                </button>

                <!-- Mini Statistics Row (Sales Orders) -->
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
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sent</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white">{{ formatNumber(stats.total_delivered || 0) }}</span>
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
            
            <div class="flex items-center gap-2 shrink-0">
                <Link
                    href="/sales/po-extractor"
                    class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all group"
                >
                    <SparklesIcon class="h-5 w-5 text-amber-500 group-hover:scale-110 transition-transform" />
                    Import PO (AI)
                </Link>
                <Link
                    href="/sales/orders/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    Create SO
                </Link>
            </div>
        </div>

        <!-- AI Import now redirects to full page at /sales/po-extractor -->

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
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Customer</label>
                        <select
                            v-model="selectedCustomer"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">All Customers</option>
                            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                {{ customer.name }}
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

        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">SO Number</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Customer</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">No PO</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Order Date</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Items</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Qty</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Sent</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Invoiced</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Return</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Balance</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Total</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Status</th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 px-4 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shadow-sm">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr 
                            v-for="so in salesOrders.data" 
                            :key="so.id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors"
                        >
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800">
                                        <DocumentTextIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ so.so_number }}</div>
                                        <div class="text-xs text-slate-500">{{ so.warehouse?.name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm text-slate-900 dark:text-white">{{ so.customer?.name }}</div>
                                <div class="text-xs text-slate-500">{{ so.customer?.code }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap max-w-[180px]">
                                <div class="truncate text-sm font-medium text-slate-600 dark:text-slate-300 font-mono" :title="so.customer_po_number">
                                    {{ so.customer_po_number || '-' }}
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ formatDate(so.order_date) }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300">
                                {{ so.items_count }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm font-medium text-slate-900 dark:text-white">
                                {{ formatNumber(so.total_qty_ordered || 0) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300">
                                {{ formatNumber(so.total_qty_delivered || 0) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-blue-500">
                                {{ formatNumber(so.total_qty_invoiced || 0) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-red-400">
                                {{ formatNumber(so.total_qty_returned || 0) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span 
                                    class="text-sm font-bold"
                                    :class="(parseFloat(so.total_qty_ordered) - (parseFloat(so.total_qty_delivered) - parseFloat(so.total_qty_returned || 0))) > 0 ? 'text-amber-400' : 'text-emerald-500'"
                                >
                                    {{ formatNumber(parseFloat(so.total_qty_ordered || 0) - (parseFloat(so.total_qty_delivered || 0) - parseFloat(so.total_qty_returned || 0))) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ formatCurrency(so.total) }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span 
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium cursor-help"
                                    :class="getStatusBadge(so.status)"
                                    :title="getStatusDescription(so.status)"
                                >
                                    {{ getStatusLabel(so.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="`/sales/orders/${so.id}`"
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                    >
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <Link
                                        v-if="so.status === 'draft'"
                                        :href="`/sales/orders/${so.id}/edit`"
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </Link>
                                    <button
                                        v-if="so.status === 'draft'"
                                        @click="deleteSO(so)"
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="salesOrders.data.length === 0">
                            <td colspan="11" class="px-4 py-12 text-center">
                                <DocumentTextIcon class="mx-auto h-12 w-12 text-slate-600" />
                                <h3 class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-300">No sales orders found</h3>
                                <p class="mt-1 text-sm text-slate-500">Create a new sales order to get started.</p>
                                <div class="mt-4">
                                    <Link
                                        href="/sales/orders/create"
                                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors"
                                    >
                                        <PlusIcon class="h-4 w-4" />
                                        Create SO
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="salesOrders.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ salesOrders.from }} to {{ salesOrders.to }} of {{ salesOrders.total }} orders
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in salesOrders.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                        :class="link.active 
                            ? 'bg-blue-600 text-white' 
                            : link.url 
                                ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' 
                                : 'text-slate-300 dark:text-slate-600 cursor-not-allowed'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Feature Guide -->
        <div class="mt-12">
            <div class="flex items-center gap-2 mb-4 px-1">
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Sales Operations Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <CheckBadgeIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Order Fulfillment</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Track <strong>Qty Balance</strong> to see what's pending. Orders move from Draft to Confirmed before they can be processed for shipping.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <TruckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Delivery Linking</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Generate <strong>Delivery Orders (DO)</strong> directly from confirmed SOs. One SO can have multiple partial deliveries if needed.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <CurrencyDollarIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Invoicing Flow</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Create <strong>Proforma or Final Invoices</strong> based on ordered or delivered quantities to ensure accurate billing for your customers.
                    </p>
                </div>
                
                <div class="glass-card rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Return Tracking</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Monitor <strong>Returned Items</strong> in the list view. The system automatically recalculates the balance to prevent over-delivery.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



