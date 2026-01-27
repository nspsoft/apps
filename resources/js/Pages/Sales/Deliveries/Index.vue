<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    TruckIcon,
    EyeIcon,
    PrinterIcon,
    PlusIcon,
    XMarkIcon,
    InboxStackIcon,
    DocumentCheckIcon,
    ShieldCheckIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    deliveryOrders: Object,
    pendingSalesOrders: Array,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const showFilters = ref(false);
const showCreateModal = ref(false);
const selectedSoId = ref(null);

const deleteDelivery = (id) => {
    if (confirm('Yakin ingin menghapus Draft Surat Jalan ini? Tindakan ini tidak dapat dibatalkan.')) {
        router.delete(route('sales.deliveries.destroy', id), {
            preserveScroll: true
        });
    }
};

const soOptions = computed(() => props.pendingSalesOrders.map(so => ({
    id: so.id,
    label: `${so.so_number} - ${so.customer?.name}`
})));

const applyFilters = debounce(() => {
    router.get(route('sales.deliveries.index'), {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus], applyFilters);

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
};

const submitCreateDelivery = () => {
    if (!selectedSoId.value) return;
    
    router.post(route('sales.orders.create-delivery', selectedSoId.value), {}, {
        onSuccess: () => {
            showCreateModal.value = false;
            selectedSoId.value = null;
        }
    });
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        confirmed: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        shipped: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
        delivered: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Delivery Orders" />
    
    <AppLayout title="Delivery Orders">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search DO number..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                <button 
                    @click="showFilters = !showFilters"
                    class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                    :class="{ 'ring-2 ring-blue-500/50': showFilters }"
                >
                    <FunnelIcon class="h-5 w-5" />
                    Filters
                </button>
            </div>

            <button
                @click="showCreateModal = true"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
            >
                <PlusIcon class="h-5 w-5" />
                Create Delivery
            </button>
        </div>

        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                            <th class="px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">DO Number</th>
                            <th class="px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">SO Reference</th>
                            <th class="px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Customer</th>
                            <th class="px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Items</th>
                            <th class="px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr 
                            v-for="doOrder in deliveryOrders.data" 
                            :key="doOrder.id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors"
                        >
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800">
                                        <TruckIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ doOrder.do_number }}</div>
                                        <div class="text-xs text-slate-500">{{ doOrder.warehouse?.name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                <Link :href="route('sales.orders.show', doOrder.sales_order_id)" class="text-blue-400 hover:underline">
                                    {{ doOrder.sales_order?.so_number }}
                                </Link>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm text-slate-900 dark:text-white font-medium">{{ doOrder.shipping_name || doOrder.sales_order?.customer?.name }}</div>
                                <div v-if="doOrder.shipping_name && doOrder.shipping_name !== doOrder.sales_order?.customer?.name" class="text-[10px] text-slate-500 uppercase tracking-tighter">Member: {{ doOrder.sales_order?.customer?.name }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ formatDate(doOrder.delivery_date) }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300">
                                {{ doOrder.items_count }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span 
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                                    :class="getStatusBadge(doOrder.status)"
                                >
                                    {{ doOrder.status }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a :href="route('sales.deliveries.print', doOrder.id)" target="_blank" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                        <PrinterIcon class="h-4 w-4" />
                                    </a>
                                    <Link :href="route('sales.deliveries.show', doOrder.id)" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <button 
                                        v-if="doOrder.status === 'draft'"
                                        @click="deleteDelivery(doOrder.id)" 
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-red-500 hover:bg-red-500/10 transition-colors"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="deliveryOrders.data.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center">
                                <TruckIcon class="mx-auto h-12 w-12 text-slate-600" />
                                <h3 class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-300">No delivery orders found</h3>
                                <p class="mt-1 text-sm text-slate-500">Deliveries are created from Sales Orders.</p>
                                <div class="mt-6">
                                    <Link :href="route('sales.orders.index')" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white dark:text-white hover:bg-blue-500 transition-colors shadow-lg shadow-blue-500/20">
                                        Go to Sales Orders
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="deliveryOrders.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ deliveryOrders.from }} to {{ deliveryOrders.to }} of {{ deliveryOrders.total }} deliveries
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in deliveryOrders.links"
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
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Logistics & Delivery Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <TruckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Surat Jalan (DO)</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Every delivery generates a professional <strong>Surat Jalan</strong>. Use the Print icon to generate the document for the driver.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <InboxStackIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Inventory Sync</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Stock is only deducted from the warehouse once the DO is marked as <strong>Delivered</strong>, ensuring real-time inventory accuracy.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <DocumentCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Direct Invoicing</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Once a delivery is complete, you can generate a <strong>Sales Invoice</strong> directly from the DO to ensure billing matches what was shipped.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Proof of Delivery</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Maintain <strong>Accountability</strong> by tracking vehicle numbers and the personnel who prepared each shipment for delivery.
                    </p>
                </div>
            </div>
        </div>
        <!-- Create Delivery Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-white dark:bg-slate-950/80 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-2xl glass-card shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400">
                            <PlusIcon class="h-6 w-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Create New Delivery</h3>
                    </div>
                    <button @click="showCreateModal = false" class="rounded-lg p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white transition-colors">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>

                <div class="p-6">
                    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">Select a confirmed Sales Order to initiate a new Delivery Order (Surat Jalan).</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">Select Sales Order</label>
                            <SearchableSelect 
                                v-model="selectedSoId" 
                                :options="soOptions" 
                                placeholder="Search by SO Number or Customer..."
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30 px-6 py-4">
                    <button 
                        @click="showCreateModal = false"
                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="submitCreateDelivery"
                        :disabled="!selectedSoId"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white dark:text-white shadow-lg shadow-blue-500/20 hover:bg-blue-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <TruckIcon class="h-5 w-5" />
                        PROCEED CREATE DO
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



